<?php

namespace App\Services;

/** GET /admin/reports/summary (AI_AGENT_PHASES.md Phase 8 item 4). */
final class ReportService
{
    private SupabaseClient $client;

    public function __construct(?SupabaseClient $client = null)
    {
        $this->client = $client ?? new SupabaseClient();
    }

    /** @return array{total_students:int,active:int,completed:int,terminated:int,withdrawn:int,avg_attendance_hours:float,avg_final_score:float} */
    public function getSummary(?int $batchId = null): array
    {
        $filter = $batchId !== null ? 'batch_id=eq.' . $batchId . '&' : '';
        $internships = $this->client->restGet('internships', $filter . 'deleted_at=is.null&select=id,status');
        $counts = ['active' => 0, 'completed' => 0, 'terminated' => 0, 'withdrawn' => 0];
        foreach ($internships as $i) {
            if (isset($counts[$i['status']])) {
                $counts[$i['status']]++;
            }
        }
        $ids = array_column($internships, 'id');

        $avgHours = 0.0;
        if (!empty($ids)) {
            $attendance = $this->client->restGet('attendance', 'internship_id=in.(' . implode(',', $ids) . ')&total_hours=not.is.null&select=internship_id,total_hours');
            $hoursByInternship = [];
            foreach ($attendance as $a) {
                $hoursByInternship[$a['internship_id']] = ($hoursByInternship[$a['internship_id']] ?? 0) + (float) $a['total_hours'];
            }
            if (!empty($hoursByInternship)) {
                $avgHours = round(array_sum($hoursByInternship) / count($hoursByInternship), 1);
            }
        }

        // Approximation: averages the per-evaluator total_score of rows that already have a
        // combined grade (i.e. both company_final and teacher_final exist — EvaluationService::
        // maybeComputeCombinedGrade()) rather than the weighted final_score itself, which isn't
        // persisted anywhere (only the letter grade is). Documented in ISSUES.md.
        $avgFinalScore = 0.0;
        if (!empty($ids)) {
            $templates = $this->client->restGet('evaluation_templates', 'evaluator_type=in.(company_final,teacher_final)&select=id');
            $finalTemplateIds = array_map(static fn (array $row): int => (int) $row['id'], $templates);
            $finals = [];
            if (!empty($finalTemplateIds)) {
                $finals = $this->client->restGet(
                    'evaluations',
                    'internship_id=in.(' . implode(',', $ids) . ')'
                    . '&template_id=in.(' . implode(',', $finalTemplateIds) . ')'
                    . '&status=eq.submitted&select=total_score'
                );
            }
            if (!empty($finals)) {
                $avgFinalScore = round(array_sum(array_column($finals, 'total_score')) / count($finals), 1);
            }
        }

        return [
            'total_students' => count($internships),
            'active' => $counts['active'],
            'completed' => $counts['completed'],
            'terminated' => $counts['terminated'],
            'withdrawn' => $counts['withdrawn'],
            'avg_attendance_hours' => $avgHours,
            'avg_final_score' => $avgFinalScore,
        ];
    }

    /**
     * UI_UX.md §4.4 `/teacher/reports` — per-advisee row (name/company/hours/avg weekly
     * score/status). Phase 11: this page had never been wired to real data since Phase 0.
     *
     * @return array<int, array{name:string,company:string,hours:float,avg_score:float,status:string}>
     */
    public function getTeacherReport(int $teacherId): array
    {
        $rows = $this->client->restGet(
            'internships',
            'teacher_id=eq.' . $teacherId . '&deleted_at=is.null&select=id,student_id,company_id,status&order=created_at.desc'
        );
        $weeklyTemplate = $this->client->restGet('evaluation_templates', 'evaluator_type=eq.company_weekly&select=id');
        $weeklyTemplateId = $weeklyTemplate[0]['id'] ?? null;

        $out = [];
        foreach ($rows as $r) {
            $stu = $this->client->restGet('students', 'id=eq.' . $r['student_id'] . '&select=first_name,last_name');
            $com = $this->client->restGet('companies', 'id=eq.' . $r['company_id'] . '&select=name');
            $attendance = $this->client->restGet('attendance', 'internship_id=eq.' . $r['id'] . '&total_hours=not.is.null&select=total_hours');
            $hours = round((float) array_sum(array_column($attendance, 'total_hours')), 1);

            $avgScore = 0.0;
            if ($weeklyTemplateId !== null) {
                $weekly = $this->client->restGet('evaluations', 'internship_id=eq.' . $r['id'] . '&template_id=eq.' . $weeklyTemplateId . '&status=eq.submitted&select=total_score');
                if (!empty($weekly)) {
                    $avgScore = round(array_sum(array_column($weekly, 'total_score')) / count($weekly), 1);
                }
            }

            $out[] = [
                'name' => isset($stu[0]) ? trim($stu[0]['first_name'] . ' ' . $stu[0]['last_name']) : '-',
                'company' => $com[0]['name'] ?? '-',
                'hours' => $hours,
                'avg_score' => $avgScore,
                'status' => $r['status'],
            ];
        }
        return $out;
    }
}
