<?php

namespace App\Controllers;

use App\Services\ApplicationService;
use App\Services\DailyLogService;
use App\Services\EvaluationService;
use App\Services\InternshipService;
use App\Services\ReportService;
use App\Services\SupabaseClient;
use App\Support\Session;

/**
 * Dashboard loaders for `/student/dashboard`, `/company/dashboard`, `/admin/dashboard` and
 * `/super-admin/dashboard`.
 *
 * Every section degrades independently: a failing query zeroes that stat instead of 500-ing the
 * whole dashboard. Attendance-derived cards now read from the unified `attendance` table so the
 * dashboard matches the same backend rules as check-in and check-out.
 */
final class DashboardController
{
    private SupabaseClient $client;

    public function __construct()
    {
        $this->client = new SupabaseClient();
    }

    public function studentData(array $params): array
    {
        $user = Session::user() ?? [];
        $data = [
            'student' => ['first_name' => (string) ($user['first_name'] ?? ''), 'company_name' => '', 'week_number' => null, 'total_weeks' => null],
            'stats' => ['hours_logged' => 0, 'hours_required' => 0, 'percent_complete' => 0, 'days_present' => 0, 'last_score' => 0, 'last_score_max' => 0],
            'hasCheckedInToday' => false,
            'journeyStage' => 'application',
            'streakDays' => 0,
            'pendingLog' => null,
            'recentAttendance' => [],
        ];

        try {
            $userId = (string) ($user['id'] ?? '');
            $students = $this->client->restGet('students', 'user_id=eq.' . $userId . '&select=id');
            if (!isset($students[0])) {
                return $data;
            }
            $studentId = (int) $students[0]['id'];

            $internships = $this->client->restGet(
                'internships',
                'student_id=eq.' . $studentId . '&deleted_at=is.null&order=id.desc&select=id,company_id,start_date,end_date,status,total_required_hours'
            );
            $active = null;
            foreach ($internships as $i) {
                if (($i['status'] ?? '') === 'active') {
                    $active = $i;
                    break;
                }
            }

            $data['journeyStage'] = $this->resolveJourneyStage($internships, $active !== null);
            if ($active === null) {
                return $data;
            }

            $internshipId = (int) $active['id'];
            $required = max(0, (int) ($active['total_required_hours'] ?? 0));
            [$weeksTotal, $weekNow] = $this->weekProgress($active['start_date'] ?? null, $active['end_date'] ?? null);

            $companies = $this->client->restGet('companies', 'id=eq.' . (int) ($active['company_id'] ?? 0) . '&select=name');
            $data['student'] = [
                'first_name' => (string) ($user['first_name'] ?? ''),
                'company_name' => (string) ($companies[0]['name'] ?? ''),
                'week_number' => $weekNow,
                'total_weeks' => $weeksTotal,
            ];
            $data['stats'] = $this->attendanceStats($internshipId, $required);

            try {
                $evaluations = new EvaluationService($this->client);
                $template = $evaluations->getTemplateByType('company_weekly');
                if ($template !== null) {
                    $data['stats']['last_score_max'] = (int) round((float) $template['max_score']);
                }
                foreach ($evaluations->getHistoryForInternship($internshipId, true) as $h) {
                    if (($h['template'] ?? '') === 'company_weekly') {
                        $data['stats']['last_score'] = (int) round((float) $h['total_score']);
                        break;
                    }
                }
            } catch (\Throwable) {
            }

            $todayRow = $this->todayAttendanceRow($internshipId);
            $data['hasCheckedInToday'] = $todayRow !== null
                && !empty($todayRow['check_in_time'])
                && empty($todayRow['check_out_time']);

            $logDates = [];
            foreach ((new DailyLogService($this->client))->listForStudent($internshipId) as $log) {
                $logDates[] = substr((string) $log['log_date'], 0, 10);
            }
            $data['streakDays'] = $this->consecutiveLogDays($logDates);
            $data['pendingLog'] = $this->missingCurrentWeekLog($logDates, $weekNow);
            $data['recentAttendance'] = $this->recentAttendance($internshipId);
        } catch (\Throwable) {
        }

        return $data;
    }

    private function resolveJourneyStage(array $internships, bool $hasActive): string
    {
        if ($hasActive) {
            return 'internship';
        }
        foreach ($internships as $i) {
            if (($i['status'] ?? '') === 'completed') {
                return 'evaluation';
            }
        }
        try {
            $userId = (string) (Session::user()['id'] ?? '');
            foreach ((new ApplicationService($this->client))->listForStudent($userId) as $app) {
                if (($app['status'] ?? '') === 'accepted') {
                    return 'approved';
                }
            }
        } catch (\Throwable) {
        }
        return 'application';
    }

    /** @return array{0:?int,1:?int} */
    private function weekProgress(?string $start, ?string $end): array
    {
        if ($start === null || $start === '' || $end === null || $end === '') {
            return [null, null];
        }
        $startTs = strtotime($start);
        $endTs = strtotime($end);
        if ($startTs === false || $endTs === false || $endTs <= $startTs) {
            return [null, null];
        }
        $total = max(1, (int) ceil(($endTs - $startTs) / 86400 / 7));
        $current = (int) floor((time() - $startTs) / 86400 / 7) + 1;
        return [$total, min($total, max(1, $current))];
    }

    /** @return array{hours_logged:float,hours_required:int,percent_complete:int,days_present:int,days_late:int,days_absent:int} */
    private function attendanceStats(int $internshipId, int $required): array
    {
        $stats = ['hours_logged' => 0, 'hours_required' => $required, 'percent_complete' => 0, 'days_present' => 0, 'days_late' => 0, 'days_absent' => 0];
        $rows = $this->client->restGet('attendance', 'internship_id=eq.' . $internshipId . '&select=check_in_at,check_out_at,total_hours,day_status');
        $hours = 0.0;
        foreach ($rows as $r) {
            $rowHours = isset($r['total_hours']) && $r['total_hours'] !== null ? (float) $r['total_hours'] : 0.0;
            if ($rowHours <= 0 && !empty($r['check_in_at']) && !empty($r['check_out_at'])) {
                $in = strtotime((string) $r['check_in_at']);
                $out = strtotime((string) $r['check_out_at']);
                if ($in !== false && $out !== false) {
                    $minutes = ($out - $in) / 60;
                    if ($minutes > 0 && $minutes < 1440) {
                        $rowHours = $minutes / 60;
                    }
                }
            }
            $hours += $rowHours;
            match ((string) ($r['day_status'] ?? '')) {
                'present' => $stats['days_present']++,
                'late' => $stats['days_late']++,
                'absent' => $stats['days_absent']++,
                default => null,
            };
        }
        $stats['hours_logged'] = round($hours, 1);
        $stats['percent_complete'] = $required > 0 ? (int) round(min(100.0, $hours / $required * 100)) : 0;
        return $stats;
    }

    private function todayAttendanceRow(int $internshipId): ?array
    {
        $rows = $this->client->restGet(
            'attendance',
            'internship_id=eq.' . $internshipId . '&work_date=eq.' . date('Y-m-d') . '&select=check_in_at,check_out_at&limit=1'
        );
        if (!isset($rows[0])) {
            return null;
        }

        return [
            'check_in_time' => $rows[0]['check_in_at'] ?? null,
            'check_out_time' => $rows[0]['check_out_at'] ?? null,
        ];
    }

    private function consecutiveLogDays(array $logDates): int
    {
        if (empty($logDates)) {
            return 0;
        }
        $set = array_fill_keys($logDates, true);
        $cursor = strtotime(date('Y-m-d'));
        if (!isset($set[date('Y-m-d', $cursor)])) {
            $cursor = strtotime('-1 day');
        }
        $streak = 0;
        while (isset($set[date('Y-m-d', $cursor)])) {
            $streak++;
            $cursor = strtotime('-1 day', $cursor);
        }
        return $streak;
    }

    private function missingCurrentWeekLog(array $logDates, ?int $weekNumber): ?array
    {
        if ($weekNumber === null) {
            return null;
        }
        $weekStart = strtotime('monday this week');
        $now = strtotime(date('Y-m-d'));
        foreach ($logDates as $d) {
            $ts = strtotime((string) $d);
            if ($ts !== false && $ts >= $weekStart && $ts <= $now) {
                return null;
            }
        }
        return ['week' => $weekNumber];
    }

    private function recentAttendance(int $internshipId): array
    {
        $rows = $this->client->restGet(
            'attendance',
            'internship_id=eq.' . $internshipId . '&order=work_date.desc&limit=3&select=work_date,check_in_at,check_out_at,day_status'
        );
        $out = [];
        foreach ($rows as $r) {
            $ts = strtotime((string) ($r['work_date'] ?? ''));
            $dayLabel = $ts !== false ? date('d/m', $ts) : '-';
            if (!empty($r['check_in_at'])) {
                $out[] = ['title' => 'Check-in', 'detail' => $dayLabel . ', ' . date('H:i', strtotime((string) $r['check_in_at'])) . ' hrs', 'ok' => true];
                if (!empty($r['check_out_at'])) {
                    $out[] = ['title' => 'Check-out', 'detail' => $dayLabel . ', ' . date('H:i', strtotime((string) $r['check_out_at'])) . ' hrs', 'ok' => true];
                }
            } elseif ((string) ($r['day_status'] ?? '') === 'absent') {
                $out[] = ['title' => 'Absent', 'detail' => $dayLabel, 'ok' => false];
            } else {
                $out[] = ['title' => 'No attendance', 'detail' => $dayLabel, 'ok' => false];
            }
        }
        return $out;
    }

    public function companyData(array $params): array
    {
        $data = [
            'company' => ['name' => ''],
            'stats' => ['checked_in_today' => 0, 'total_students' => 0, 'pending_evaluations' => 0, 'pending_leave' => 0],
            'students' => [],
        ];

        try {
            $userId = (string) (Session::user()['id'] ?? '');
            $supervisors = $this->client->restGet('company_supervisors', 'user_id=eq.' . $userId . '&select=company_id');
            $companyId = (int) ($supervisors[0]['company_id'] ?? 0);
            if ($companyId <= 0) {
                return $data;
            }

            $companies = $this->client->restGet('companies', 'id=eq.' . $companyId . '&select=name');
            $data['company']['name'] = (string) ($companies[0]['name'] ?? '');

            $advisees = (new InternshipService($this->client))->listAdviseesWithProgress('company_id', $companyId);
            $data['stats']['total_students'] = count($advisees);

            try {
                $data['stats']['pending_evaluations'] = count((new EvaluationService($this->client))->listEligibleForCompanyWeekly($companyId));
            } catch (\Throwable) {
            }

            $internshipIds = array_map(static fn (array $a) => (int) $a['internship_id'], $advisees);
            if (!empty($internshipIds)) {
                $leaves = $this->client->restGet('leave_requests', 'internship_id=in.(' . implode(',', $internshipIds) . ')&status=eq.pending&select=id');
                $data['stats']['pending_leave'] = count($leaves);
            }

            $today = date('Y-m-d');
            $deptNames = [];
            foreach ($advisees as $a) {
                $internshipId = (int) $a['internship_id'];
                $positionRows = $this->client->restGet('internships', 'id=eq.' . $internshipId . '&select=position_title');
                $position = (string) ($positionRows[0]['position_title'] ?? '');

                $university = '';
                $deptId = $a['department_id'] ?? null;
                if ($deptId !== null && $deptId !== '') {
                    $deptKey = (string) $deptId;
                    if (!array_key_exists($deptKey, $deptNames)) {
                        $depts = $this->client->restGet('departments', 'id=eq.' . (int) $deptId . '&select=name_th');
                        $deptNames[$deptKey] = (string) ($depts[0]['name_th'] ?? '');
                    }
                    $university = $deptNames[$deptKey];
                }

                $checkIn = '--:--';
                $status = 'absent';
                $att = $this->client->restGet('attendance', 'internship_id=eq.' . $internshipId . '&work_date=eq.' . $today . '&select=check_in_at,day_status&limit=1');
                if (isset($att[0]) && !empty($att[0]['check_in_at'])) {
                    $checkIn = date('H:i', strtotime((string) $att[0]['check_in_at']));
                    $rawStatus = (string) ($att[0]['day_status'] ?? '');
                    $status = in_array($rawStatus, ['present', 'late'], true) ? $rawStatus : 'present';
                }

                $data['students'][] = [
                    'internship_id' => $internshipId,
                    'name' => (string) $a['name'],
                    'position' => $position,
                    'university' => $university,
                    'check_in' => $checkIn,
                    'status' => $status,
                ];
                if ($status !== 'absent') {
                    $data['stats']['checked_in_today']++;
                }
            }
        } catch (\Throwable) {
        }

        return $data;
    }

    public function adminData(array $params): array
    {
        $data = [
            'faculty' => (string) (Session::user()['faculty_name'] ?? ''),
            'pending' => ['users' => 0, 'companies' => 0, 'matching' => 0],
            'batchSummary' => ['active' => 0, 'completed' => 0, 'terminated' => 0, 'withdrawn' => 0],
        ];

        try {
            $data['pending']['users'] = count($this->client->restGet('users', 'status=eq.pending&select=id'));
        } catch (\Throwable) {
        }
        try {
            $data['pending']['companies'] = count($this->client->restGet('companies', 'status=eq.pending&select=id'));
        } catch (\Throwable) {
        }
        try {
            $data['pending']['matching'] = count((new ApplicationService($this->client))->listAcceptedUnmatched());
        } catch (\Throwable) {
        }
        try {
            $summary = (new ReportService($this->client))->getSummary();
            $data['batchSummary'] = [
                'active' => (int) $summary['active'],
                'completed' => (int) $summary['completed'],
                'terminated' => (int) $summary['terminated'],
                'withdrawn' => (int) $summary['withdrawn'],
            ];
        } catch (\Throwable) {
        }

        return $data;
    }
}
