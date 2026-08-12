<?php

namespace App\Services;

/**
 * Shared by `/teacher/students/{internship_id}` and `/company/students/{internship_id}`
 * (SITEMAP.md §3/§4, UI_UX.md §4.2, Phase 11) — both roles see the exact same student detail
 * page, just scoped to their own advisees/students (teacher_id vs company_id). Feeds
 * teacher/student_timeline.php's existing tab+timeline shape, which had been pure mock since
 * Phase 0 (never actually wired despite the route existing since Phase 4).
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
            'name' => isset($stu[0]) ? trim($stu[0]['first_name'] . ' ' . $stu[0]['last_name']) : '-',
            'company' => $com[0]['name'] ?? '-',
        ];
    }

    /**
     * @return array<int, array{date:string,items:array<int,array{icon:string,color:string,text:string}>}>
     *   Newest date first, day-bucketed to match teacher/student_timeline.php's existing markup.
     */
    public function getTimeline(int $internshipId, string $tab): array
    {
        $buckets = []; // 'Y-m-d' => list of items

        if (in_array($tab, ['overview', 'attendance'], true)) {
            $rows = $this->client->restGet('attendance', 'internship_id=eq.' . $internshipId . '&order=work_date.desc&limit=30&select=*');
            foreach ($rows as $a) {
                if ($a['day_status'] === 'leave') {
                    $buckets[$a['work_date']][] = ['icon' => 'event_available', 'color' => 'status-inactive', 'text' => 'ลา (อนุมัติแล้ว)'];
                    continue;
                }
                if ($a['check_in_at'] !== null) {
                    $buckets[$a['work_date']][] = ['icon' => 'check_circle', 'color' => 'status-success', 'text' => 'เข้างาน ' . date('H:i', strtotime($a['check_in_at']))];
                }
                if ($a['check_out_at'] !== null) {
                    $buckets[$a['work_date']][] = ['icon' => 'check_circle', 'color' => 'status-success', 'text' => 'ออกงาน ' . date('H:i', strtotime($a['check_out_at']))];
                }
            }
        }

        if (in_array($tab, ['overview', 'daily_logs'], true)) {
            $rows = $this->client->restGet('daily_logs', 'internship_id=eq.' . $internshipId . '&order=log_date.desc&limit=30&select=log_date,status');
            $labels = ['draft' => ['edit_note', 'status-warning', 'บันทึกร่างไว้'], 'submitted' => ['check_circle', 'status-success', 'บันทึกงานแล้ว'], 'reviewed' => ['task_alt', 'status-success', 'บันทึกงานได้รับการตรวจแล้ว'], 'revision_requested' => ['warning', 'status-error', 'ผู้ตรวจขอให้แก้ไขบันทึก']];
            foreach ($rows as $l) {
                [$icon, $color, $text] = $labels[$l['status']] ?? ['description', 'on-surface-variant', $l['status']];
                $buckets[$l['log_date']][] = ['icon' => $icon, 'color' => $color, 'text' => $text];
            }
        }

        if ($tab === 'overview') {
            // Flag missing-log days too, matching the original wireframe's "⚠ ยังไม่บันทึกงาน" example.
            foreach (array_keys($buckets) as $date) {
                $hasCheckin = false;
                $hasLog = false;
                foreach ($buckets[$date] as $item) {
                    if (str_starts_with($item['text'], 'เข้างาน')) {
                        $hasCheckin = true;
                    }
                    if (in_array($item['text'], ['บันทึกงานแล้ว', 'บันทึกงานได้รับการตรวจแล้ว', 'บันทึกร่างไว้'], true)) {
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
            foreach ($rows as $lr) {
                $color = $lr['status'] === 'approved' ? 'status-inactive' : ($lr['status'] === 'rejected' ? 'status-error' : 'status-warning');
                $text = ($typeLabels[$lr['leave_type']] ?? $lr['leave_type']) . ' (' . ($statusLabels[$lr['status']] ?? $lr['status']) . ')';
                $buckets[$lr['start_date']][] = ['icon' => 'event_available', 'color' => $color, 'text' => $text];
            }
        }

        if ($tab === 'evaluation') {
            $rows = $this->client->restGet('evaluations', 'internship_id=eq.' . $internshipId . '&order=evaluation_date.desc&select=evaluation_date,total_score,grade,status,week_number,evaluation_templates(evaluator_type)');
            foreach ($rows as $e) {
                $type = $e['evaluation_templates']['evaluator_type'] ?? '';
                $label = match ($type) {
                    'company_weekly' => 'ประเมินรายสัปดาห์ที่ ' . $e['week_number'],
                    'company_final' => 'ประเมินปลายภาค (ผู้ประกอบการ)',
                    'teacher_final' => 'ประเมินปลายภาค (ครูนิเทศ)',
                    default => $type,
                };
                $scoreText = $e['status'] === 'submitted' ? $label . ': ' . $e['total_score'] . ($e['grade'] ? ' เกรด ' . $e['grade'] : '') : $label . ' (ร่าง)';
                $buckets[$e['evaluation_date']][] = ['icon' => 'grading', 'color' => $e['status'] === 'submitted' ? 'status-success' : 'status-warning', 'text' => $scoreText];
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
