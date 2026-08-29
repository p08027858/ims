<?php

namespace App\Services;

/**
 * Shared by `/teacher/students/{internship_id}` and `/company/students/{internship_id}`.
 */
final class StudentTimelineService
{
    private SupabaseClient $client;

    public function __construct(?SupabaseClient $client = null)
    {
        $this->client = $client ?? new SupabaseClient();
    }

    public function belongsToTeacher(int $internshipId, int $teacherId): bool
    {
        $rows = $this->client->restGet('internships', 'id=eq.' . $internshipId . '&teacher_id=eq.' . $teacherId . '&select=id');
        return !empty($rows);
    }

    public function belongsToCompany(int $internshipId, int $companyId): bool
    {
        $rows = $this->client->restGet('internships', 'id=eq.' . $internshipId . '&company_id=eq.' . $companyId . '&select=id');
        return !empty($rows);
    }

    /** @return array{name:string,company:string}|null */
    public function getContext(int $internshipId): ?array
    {
        $rows = $this->client->restGet('internships', 'id=eq.' . $internshipId . '&select=student_id,company_id');
        if (!isset($rows[0])) {
            return null;
        }

        $stu = $this->client->restGet('students', 'id=eq.' . $rows[0]['student_id'] . '&select=first_name,last_name');
        $com = $this->client->restGet('companies', 'id=eq.' . $rows[0]['company_id'] . '&select=name');

        return [
            'name' => isset($stu[0]) ? trim((string) (($stu[0]['first_name'] ?? '') . ' ' . ($stu[0]['last_name'] ?? ''))) : '-',
            'company' => (string) ($com[0]['name'] ?? '-'),
        ];
    }

    /** @return array<int, array{date:string,items:array<int,array{icon:string,color:string,text:string}>}> */
    public function getTimeline(int $internshipId, string $tab): array
    {
        $buckets = [];

        if (in_array($tab, ['overview', 'attendance'], true)) {
            $rows = $this->client->restGet(
                'attendance',
                'internship_id=eq.' . $internshipId . '&order=created_at.desc&limit=30&select=created_at,status,check_in_at,check_out_at'
            );
            foreach ($rows as $attendance) {
                $workDate = substr((string) ($attendance['created_at'] ?? ''), 0, 10);
                if ($workDate === '') {
                    continue;
                }

                if (($attendance['status'] ?? '') === 'leave') {
                    $buckets[$workDate][] = ['icon' => 'event_available', 'color' => 'status-inactive', 'text' => 'ลา (อนุมัติแล้ว)'];
                    continue;
                }

                if (($attendance['check_in_at'] ?? null) !== null) {
                    $buckets[$workDate][] = [
                        'icon' => 'check_circle',
                        'color' => 'status-success',
                        'text' => 'เข้างาน ' . date('H:i', strtotime((string) $attendance['check_in_at'])),
                    ];
                }

                if (($attendance['check_out_at'] ?? null) !== null) {
                    $buckets[$workDate][] = [
                        'icon' => 'check_circle',
                        'color' => 'status-success',
                        'text' => 'ออกงาน ' . date('H:i', strtotime((string) $attendance['check_out_at'])),
                    ];
                }
            }
        }

        if (in_array($tab, ['overview', 'daily_logs'], true)) {
            $rows = $this->client->restGet('daily_logs', 'internship_id=eq.' . $internshipId . '&order=log_date.desc&limit=30&select=log_date,status');
            $labels = [
                'draft' => ['edit_note', 'status-warning', 'บันทึกร่างไว้'],
                'submitted' => ['check_circle', 'status-success', 'ส่งบันทึกงานแล้ว'],
                'reviewed' => ['task_alt', 'status-success', 'บันทึกงานได้รับการตรวจแล้ว'],
                'revision_requested' => ['warning', 'status-error', 'ผู้ตรวจขอให้แก้ไขบันทึก'],
            ];
            foreach ($rows as $log) {
                [$icon, $color, $text] = $labels[$log['status']] ?? ['description', 'on-surface-variant', (string) ($log['status'] ?? '-')];
                $buckets[$log['log_date']][] = ['icon' => $icon, 'color' => $color, 'text' => $text];
            }
        }

        if ($tab === 'overview') {
            foreach (array_keys($buckets) as $date) {
                $hasCheckin = false;
                $hasLog = false;
                foreach ($buckets[$date] as $item) {
                    if (str_starts_with((string) $item['text'], 'เข้างาน')) {
                        $hasCheckin = true;
                    }
                    if (in_array($item['text'], ['ส่งบันทึกงานแล้ว', 'บันทึกงานได้รับการตรวจแล้ว', 'บันทึกร่างไว้'], true)) {
                        $hasLog = true;
                    }
                }
                if ($hasCheckin && !$hasLog) {
                    $buckets[$date][] = ['icon' => 'warning', 'color' => 'status-warning', 'text' => 'ยังไม่บันทึกงาน'];
                }
            }
        }

        if (in_array($tab, ['overview', 'leave'], true)) {
            $rows = $this->client->restGet('leave_requests', 'internship_id=eq.' . $internshipId . '&order=start_date.desc&limit=30&select=start_date,end_date,leave_type,status');
            $typeLabels = ['sick' => 'ลาป่วย', 'personal' => 'ลากิจ', 'other' => 'อื่นๆ'];
            $statusLabels = ['pending' => 'รออนุมัติ', 'approved' => 'อนุมัติแล้ว', 'rejected' => 'ปฏิเสธ', 'cancelled' => 'ยกเลิกแล้ว'];
            foreach ($rows as $leave) {
                $color = $leave['status'] === 'approved' ? 'status-inactive' : ($leave['status'] === 'rejected' ? 'status-error' : 'status-warning');
                $text = ($typeLabels[$leave['leave_type']] ?? $leave['leave_type']) . ' (' . ($statusLabels[$leave['status']] ?? $leave['status']) . ')';
                $buckets[$leave['start_date']][] = ['icon' => 'event_available', 'color' => $color, 'text' => $text];
            }
        }

        if ($tab === 'evaluation') {
            $rows = $this->client->restGet('evaluations', 'internship_id=eq.' . $internshipId . '&order=evaluation_date.desc&select=evaluation_date,total_score,grade,status,week_number,evaluation_templates(evaluator_type)');
            foreach ($rows as $evaluation) {
                $type = $evaluation['evaluation_templates']['evaluator_type'] ?? '';
                $label = match ($type) {
                    'company_weekly' => 'ประเมินรายสัปดาห์ครั้งที่ ' . $evaluation['week_number'],
                    'company_final' => 'ประเมินปลายภาค (สถานประกอบการ)',
                    'teacher_final' => 'ประเมินปลายภาค (ครูนิเทศ)',
                    default => (string) $type,
                };
                $scoreText = $evaluation['status'] === 'submitted'
                    ? $label . ': ' . $evaluation['total_score'] . (!empty($evaluation['grade']) ? ' เกรด ' . $evaluation['grade'] : '')
                    : $label . ' (ร่าง)';
                $buckets[$evaluation['evaluation_date']][] = [
                    'icon' => 'grading',
                    'color' => $evaluation['status'] === 'submitted' ? 'status-success' : 'status-warning',
                    'text' => $scoreText,
                ];
            }
        }

        krsort($buckets);
        $out = [];
        foreach ($buckets as $date => $items) {
            $out[] = ['date' => date('j M', strtotime($date)), 'items' => $items];
        }

        return $out;
    }
}