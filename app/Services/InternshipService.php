<?php

namespace App\Services;

/**
 * internships (AI_AGENT_PHASES.md Phase 4 items 2-4) — the confirmed internship "contract"
 * created from an accepted application, then approved/terminated. State machine: WORKFLOW.md §1.
 */
final class InternshipService
{
    private SupabaseClient $client;

    public function __construct(?SupabaseClient $client = null)
    {
        $this->client = $client ?? new SupabaseClient();
    }

    /** GET /admin/matching/{id} loader context — the accepted application plus readable student/company names. */
    public function getApplicationMatchContext(int $applicationId): ?array
    {
        $apps = $this->client->restGet('internship_applications', 'id=eq.' . $applicationId . '&status=eq.accepted&select=*');
        if (!isset($apps[0])) {
            return null;
        }
        $app = $apps[0];
        $stu = $this->client->restGet('students', 'id=eq.' . $app['student_id'] . '&select=first_name,last_name,student_code,department_id');
        $deptName = '';
        if (isset($stu[0])) {
            $dept = $this->client->restGet('departments', 'id=eq.' . $stu[0]['department_id'] . '&select=name_th');
            $deptName = $dept[0]['name_th'] ?? '';
        }
        $com = $this->client->restGet('companies', 'id=eq.' . $app['company_id'] . '&select=name,address');

        return [
            'application_id' => $applicationId,
            'student' => [
                'name' => isset($stu[0]) ? trim($stu[0]['first_name'] . ' ' . $stu[0]['last_name']) : '-',
                'code' => $stu[0]['student_code'] ?? '-',
                'department' => $deptName,
            ],
            'company' => ['name' => $com[0]['name'] ?? '-', 'address' => $com[0]['address'] ?? '-'],
        ];
    }

    public function listTeachers(): array
    {
        return (new TeacherService($this->client))->listTeachers();
    }

    /**
     * RULE-MATCH-02: unique(student_id, batch_id) is also enforced at the DB level
     * (DATABASE.md §4.2) — TC-MATCH-004 relies on that constraint surfacing as a conflict here.
     */
    public function createFromApplication(int $applicationId, array $data): void
    {
        $apps = $this->client->restGet('internship_applications', 'id=eq.' . $applicationId . '&status=eq.accepted&select=*');
        if (!isset($apps[0])) {
            throw new AuthException('VALIDATION_ERROR', 'ไม่พบใบสมัครนี้ หรือยังไม่ถูกตอบรับจากบริษัท');
        }
        $app = $apps[0];

        $teacherId = (int) ($data['teacher_id'] ?? 0);
        $startDate = trim((string) ($data['start_date'] ?? ''));
        $endDate = trim((string) ($data['end_date'] ?? ''));
        if ($teacherId <= 0 || $startDate === '' || $endDate === '') {
            throw new AuthException('VALIDATION_ERROR', 'กรุณาเลือกครูนิเทศและกรอกวันที่เริ่ม-สิ้นสุดให้ครบ');
        }

        try {
            $this->client->restInsert('internships', [
                'student_id' => $app['student_id'],
                'company_id' => $app['company_id'],
                'teacher_id' => $teacherId,
                'batch_id' => $app['batch_id'],
                'application_id' => $applicationId,
                'position_title' => $app['position_title'],
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_required_hours' => (int) ($data['total_required_hours'] ?? 400),
            ]);
        } catch (SupabaseException $e) {
            // TC-MATCH-004: Postgres unique_violation (23505) surfaces as a 409 from PostgREST.
            throw new AuthException(
                'VALIDATION_ERROR',
                'นักศึกษาคนนี้มีการฝึกงานที่ยืนยันแล้วในรอบนี้อยู่แล้ว (สร้างซ้ำไม่ได้)',
                ['cause' => $e->getMessage()]
            );
        }
    }

    /** Unified admin console list: pending_approval (→ approve) and approved/active (→ terminate). */
    public function listForAdminConsole(): array
    {
        $rows = $this->client->restGet(
            'internships',
            'status=in.(pending_approval,approved,active)&deleted_at=is.null&select=id,student_id,company_id,start_date,end_date,status&order=created_at.desc'
        );
        $out = [];
        foreach ($rows as $r) {
            $stu = $this->client->restGet('students', 'id=eq.' . $r['student_id'] . '&select=first_name,last_name');
            $com = $this->client->restGet('companies', 'id=eq.' . $r['company_id'] . '&select=name');
            $out[] = [
                'id' => $r['id'],
                'student_name' => isset($stu[0]) ? trim($stu[0]['first_name'] . ' ' . $stu[0]['last_name']) : '-',
                'company_name' => $com[0]['name'] ?? '-',
                'start_date' => $r['start_date'],
                'end_date' => $r['end_date'],
                'status' => $r['status'],
            ];
        }
        return $out;
    }

    /** RULE-MATCH-03: approving auto-cancels this student's other still-pending applications in the same batch. */
    public function approve(int $internshipId, string $approvedByUserId): void
    {
        $rows = $this->client->restGet('internships', 'id=eq.' . $internshipId . '&select=id,student_id,batch_id,status');
        if (!isset($rows[0]) || $rows[0]['status'] !== 'pending_approval') {
            throw new AuthException('VALIDATION_ERROR', 'ไม่พบรายการนี้ หรือถูกดำเนินการไปแล้ว');
        }
        $studentId = $rows[0]['student_id'];
        $batchId = $rows[0]['batch_id'];

        $this->client->restUpdate('internships', 'id=eq.' . $internshipId, [
            'status' => 'approved',
            'approved_by' => $approvedByUserId,
            'approved_at' => date('c'),
        ]);

        $others = $this->client->restGet(
            'internship_applications',
            'student_id=eq.' . $studentId . '&batch_id=eq.' . $batchId . '&status=eq.pending&select=id'
        );
        foreach ($others as $o) {
            $this->client->restUpdate('internship_applications', 'id=eq.' . $o['id'], ['status' => 'cancelled']);
        }
    }

    public function terminate(int $internshipId, string $reason): void
    {
        if (trim($reason) === '') {
            throw new AuthException('VALIDATION_ERROR', 'กรุณาระบุเหตุผลการยุติการฝึกงาน');
        }
        $this->client->restUpdate('internships', 'id=eq.' . $internshipId, [
            'status' => 'terminated',
            'termination_reason' => trim($reason),
            'actual_end_date' => date('Y-m-d'),
        ]);
    }

    /**
     * cron/activate_internships.php (DEPLOYMENT.md §7, WORKFLOW.md §1 state machine) —
     * approved → active once start_date is reached. Not wired to a real scheduler yet
     * (Phase 9 prepares the logic; Phase 9 also owns actually running it, unlike earlier phases
     * where the cron itself was deferred to Phase 9 — this one just never had a script before).
     *
     * @return int number of internships activated
     */
    public function activateApproved(): int
    {
        $today = date('Y-m-d');
        $rows = $this->client->restGet('internships', 'status=eq.approved&start_date=lte.' . $today . '&deleted_at=is.null&select=id');
        foreach ($rows as $r) {
            $this->client->restUpdate('internships', 'id=eq.' . $r['id'], ['status' => 'active']);
        }
        return count($rows);
    }

    /**
     * cron/complete_internships.php — RULE-GRADE-01: active → completed once (1) accumulated
     * hours ≥ total_required_hours, (2) both company_final and teacher_final are submitted
     * (checked via `grade` being set, since EvaluationService::maybeComputeCombinedGrade() only
     * ever sets it once both exist), (3) no pending leave_requests remain.
     *
     * @return int number of internships completed
     */
    public function completeEligible(): int
    {
        $rows = $this->client->restGet('internships', 'status=eq.active&deleted_at=is.null&select=id,total_required_hours');
        $completed = 0;
        foreach ($rows as $r) {
            $attendance = $this->client->restGet('attendance', 'internship_id=eq.' . $r['id'] . '&total_hours=not.is.null&select=total_hours');
            $totalHours = array_sum(array_column($attendance, 'total_hours'));
            if ($totalHours < (float) $r['total_required_hours']) {
                continue;
            }
            $gradedEvals = $this->client->restGet('evaluations', 'internship_id=eq.' . $r['id'] . '&grade=not.is.null&select=id&limit=1');
            if (empty($gradedEvals)) {
                continue;
            }
            $pendingLeaves = $this->client->restGet('leave_requests', 'internship_id=eq.' . $r['id'] . '&status=eq.pending&select=id&limit=1');
            if (!empty($pendingLeaves)) {
                continue;
            }
            $this->client->restUpdate('internships', 'id=eq.' . $r['id'], ['status' => 'completed']);
            $completed++;
        }
        return $completed;
    }

    /**
     * SITEMAP.md §3/§4 `/company/students` and `/teacher/students` (Phase 11) — same shape for
     * both, filtered by whichever scope column applies (`company_id` or `teacher_id`); the
     * dashboards for both roles reuse this too (never wired to real data since Phase 0's mock).
     *
     * @return array<int, array{internship_id:int,name:string,company:string,department_id:?int,
     *               hours:float,hours_required:int,flag:?string,pending_teacher_final:bool}>
     */
    public function listAdviseesWithProgress(string $scopeColumn, int $scopeId): array
    {
        $rows = $this->client->restGet(
            'internships',
            $scopeColumn . '=eq.' . $scopeId . '&status=in.(active,completed)&deleted_at=is.null&select=id,student_id,company_id,total_required_hours&order=created_at.desc'
        );
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $twoDaysAgo = date('Y-m-d', strtotime('-2 days'));
        $teacherFinalTemplate = $this->client->restGet('evaluation_templates', "evaluator_type=eq.teacher_final&select=id");
        $teacherFinalTemplateId = $teacherFinalTemplate[0]['id'] ?? null;

        $out = [];
        foreach ($rows as $r) {
            $stu = $this->client->restGet('students', 'id=eq.' . $r['student_id'] . '&select=first_name,last_name,department_id');
            $com = $this->client->restGet('companies', 'id=eq.' . $r['company_id'] . '&select=name');
            $attendance = $this->client->restGet('attendance', 'internship_id=eq.' . $r['id'] . '&total_hours=not.is.null&select=total_hours');
            $hours = array_sum(array_column($attendance, 'total_hours'));
            $logs = $this->client->restGet('daily_logs', 'internship_id=eq.' . $r['id'] . '&log_date=in.(' . $yesterday . ',' . $twoDaysAgo . ')&select=id');

            $pendingTeacherFinal = false;
            if ($teacherFinalTemplateId !== null) {
                $submitted = $this->client->restGet('evaluations', 'internship_id=eq.' . $r['id'] . '&template_id=eq.' . $teacherFinalTemplateId . '&status=eq.submitted&select=id');
                $pendingTeacherFinal = empty($submitted);
            }

            $out[] = [
                'internship_id' => $r['id'],
                'name' => isset($stu[0]) ? trim($stu[0]['first_name'] . ' ' . $stu[0]['last_name']) : '-',
                'company' => $com[0]['name'] ?? '-',
                'department_id' => $stu[0]['department_id'] ?? null,
                'hours' => round((float) $hours, 1),
                'hours_required' => (int) $r['total_required_hours'],
                'flag' => empty($logs) ? 'ไม่บันทึกงาน 2 วันขึ้นไป' : null,
                'pending_teacher_final' => $pendingTeacherFinal,
            ];
        }
        return $out;
    }

    /**
     * Real hard delete (RULE-SEC-01, Phase 10) — distinct from terminate() above, which only ever
     * sets status='terminated' and keeps the row (and every child record) around. This is the
     * first genuine `DELETE` the project ever performs; every child table's `internship_id` FK is
     * declared `on delete cascade` (DATABASE.md §5-8), so Postgres removes attendance/daily_logs/
     * leave_requests/evaluations (and their own children) automatically — the caller is
     * responsible for gating this behind Super Admin + PIN (App\Middleware\ActionTokenGuard) and
     * writing the audit_logs old_value snapshot *before* calling this, since nothing survives after.
     *
     * @return array<string,mixed> the deleted row, for the caller's audit_logs old_value
     */
    public function delete(int $internshipId): array
    {
        $rows = $this->client->restGet('internships', 'id=eq.' . $internshipId . '&select=*');
        if (!isset($rows[0])) {
            throw new AuthException('VALIDATION_ERROR', 'ไม่พบการฝึกงานนี้');
        }
        $this->client->restDelete('internships', 'id=eq.' . $internshipId);
        return $rows[0];
    }
}
