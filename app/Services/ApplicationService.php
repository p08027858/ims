<?php

namespace App\Services;

/**
 * internship_applications (AI_AGENT_PHASES.md Phase 4 item 1) — student applies to an approved
 * company, company accepts/rejects. RULE-MATCH-01/02.
 */
final class ApplicationService
{
    private SupabaseClient $client;

    public function __construct(?SupabaseClient $client = null)
    {
        $this->client = $client ?? new SupabaseClient();
    }

    /**
     * RULE-MATCH-01: only approved, non-deleted companies are ever visible to students.
     *
     * TC-SEC-TEST-001 (Phase 12): a crafted $search value can trip Supabase's own edge-layer
     * WAF into rejecting the request outright (observed a 403 for a payload containing
     * "DROP TABLE" — PostgREST itself never concatenates raw SQL, so this was never a real
     * injection, just an upstream 4xx) — treated as "no matches" rather than surfacing an
     * error, since a search box rejecting unusual input with a hard error is worse UX than
     * just showing zero results.
     */
    public function listApprovedCompanies(?string $search = null): array
    {
        $query = 'status=eq.approved&deleted_at=is.null&select=id,name,industry_type,province&order=name.asc';
        if ($search !== null && trim($search) !== '') {
            $query .= '&name=ilike.*' . rawurlencode(trim($search)) . '*';
        }
        try {
            $rows = $this->client->restGet('companies', $query);
        } catch (SupabaseException $e) {
            $rows = [];
        }
        // 'positions'/'tags' shown in the mock UI have no backing DB column yet (ISSUES.md) —
        // default them so the existing view still renders without crashing.
        return array_map(static fn (array $c) => [
            'id' => $c['id'], 'name' => $c['name'], 'industry' => $c['industry_type'] ?? '',
            'province' => $c['province'] ?? '', 'positions' => 0, 'tags' => [],
        ], $rows);
    }

    /** RULE-MATCH-01: applying directly to a non-approved company id must fail, not just be hidden from search. */
    public function getApprovedCompany(int $id): ?array
    {
        $rows = $this->client->restGet('companies', 'id=eq.' . $id . '&status=eq.approved&deleted_at=is.null&select=*');
        if (!isset($rows[0])) {
            return null;
        }
        $c = $rows[0];
        return [
            'id' => $c['id'], 'name' => $c['name'], 'industry' => $c['industry_type'] ?? '',
            'province' => $c['province'] ?? '', 'address' => $c['address'], 'positions' => 0,
            'description' => '', 'tags' => [],
        ];
    }

    /** No batch-management UI yet (Phase 8) — applications go against whichever batch is currently open. */
    public function getCurrentOpenBatch(): ?array
    {
        $rows = $this->client->restGet('internship_batches', 'status=eq.open&order=id.desc&limit=1&select=*');
        return $rows[0] ?? null;
    }

    public function getStudentIdForUser(string $userId): ?int
    {
        $rows = $this->client->restGet('students', 'user_id=eq.' . $userId . '&select=id');
        return isset($rows[0]) ? (int) $rows[0]['id'] : null;
    }

    /**
     * RULE-MATCH-02: a student may apply to multiple companies in the same batch — the DB unique
     * constraint is (student_id, batch_id, company_id), not (student_id, batch_id) here (that
     * stricter constraint is on `internships`, enforced in InternshipService).
     */
    public function apply(string $userId, array $data): void
    {
        $studentId = $this->getStudentIdForUser($userId);
        if ($studentId === null) {
            throw new AuthException('VALIDATION_ERROR', 'ไม่พบข้อมูลนักศึกษาของบัญชีนี้');
        }

        $companyId = (int) ($data['company_id'] ?? 0);
        if ($this->getApprovedCompany($companyId) === null) {
            throw new AuthException('VALIDATION_ERROR', 'ไม่พบสถานประกอบการนี้ หรือยังไม่ได้รับการอนุมัติ');
        }

        $batch = $this->getCurrentOpenBatch();
        if ($batch === null) {
            throw new AuthException('VALIDATION_ERROR', 'ขณะนี้ไม่มีรอบฝึกงานที่เปิดรับสมัครอยู่');
        }

        try {
            $this->client->restInsert('internship_applications', [
                'student_id' => $studentId,
                'company_id' => $companyId,
                'batch_id' => (int) $batch['id'],
                'position_title' => trim((string) ($data['position_title'] ?? '')) ?: null,
                'note' => trim((string) ($data['note'] ?? '')) ?: null,
            ]);
        } catch (SupabaseException $e) {
            throw new AuthException('VALIDATION_ERROR', 'คุณได้สมัครสถานประกอบการนี้ในรอบนี้ไปแล้ว', ['cause' => $e->getMessage()]);
        }
    }

    /**
     * SITEMAP.md §2 `/student/applications` — the student's own application history across every
     * batch, newest first (Phase 11; Phase 4 only ever needed per-company detail, never a list).
     *
     * @return array<int, array{id:int,company_name:string,position_title:string,status:string,applied_at:string}>
     */
    public function listForStudent(string $userId): array
    {
        $studentId = $this->getStudentIdForUser($userId);
        if ($studentId === null) {
            return [];
        }
        $rows = $this->client->restGet(
            'internship_applications',
            'student_id=eq.' . $studentId . '&select=id,company_id,position_title,status,applied_at&order=applied_at.desc'
        );
        $out = [];
        foreach ($rows as $r) {
            $com = $this->client->restGet('companies', 'id=eq.' . $r['company_id'] . '&select=name');
            $out[] = [
                'id' => $r['id'],
                'company_name' => $com[0]['name'] ?? '-',
                'position_title' => $r['position_title'] ?? '-',
                'status' => $r['status'],
                'applied_at' => $r['applied_at'],
            ];
        }
        return $out;
    }

    public function getCompanyIdForSupervisorUser(string $userId): ?int
    {
        $rows = $this->client->restGet('company_supervisors', 'user_id=eq.' . $userId . '&select=company_id');
        return isset($rows[0]) ? (int) $rows[0]['company_id'] : null;
    }

    /** @return array<int, array{id:int,name:string,position:string}> */
    public function listPendingForCompany(int $companyId): array
    {
        $rows = $this->client->restGet(
            'internship_applications',
            'company_id=eq.' . $companyId . '&status=eq.pending&select=id,student_id,position_title&order=applied_at.asc'
        );
        $out = [];
        foreach ($rows as $r) {
            $stu = $this->client->restGet('students', 'id=eq.' . $r['student_id'] . '&select=first_name,last_name');
            $out[] = [
                'id' => $r['id'],
                'name' => isset($stu[0]) ? trim($stu[0]['first_name'] . ' ' . $stu[0]['last_name']) : 'ไม่ทราบชื่อ',
                'position' => $r['position_title'] ?? '-',
            ];
        }
        return $out;
    }

    /** RULE-MATCH: only the company the application was sent to may accept/reject it, and only while still pending. */
    public function decide(int $applicationId, string $decision, int $companyId): void
    {
        if (!in_array($decision, ['accepted', 'rejected'], true)) {
            throw new AuthException('VALIDATION_ERROR', 'สถานะไม่ถูกต้อง');
        }
        $rows = $this->client->restGet(
            'internship_applications',
            'id=eq.' . $applicationId . '&company_id=eq.' . $companyId . '&status=eq.pending&select=id'
        );
        if (!isset($rows[0])) {
            throw new AuthException('VALIDATION_ERROR', 'ไม่พบใบสมัครนี้ หรือมีการตัดสินใจไปแล้ว');
        }
        $this->client->restUpdate('internship_applications', 'id=eq.' . $applicationId, [
            'status' => $decision,
            'decided_at' => date('c'),
        ]);
    }

    /** @return array<int, array{application_id:int,student_name:string,student_code:string,department_name:string,company_name:string,company_address:string,position_title:string}> */
    public function listAcceptedUnmatched(): array
    {
        $accepted = $this->client->restGet(
            'internship_applications',
            'status=eq.accepted&select=id,student_id,company_id,position_title'
        );
        $out = [];
        foreach ($accepted as $a) {
            $existing = $this->client->restGet('internships', 'application_id=eq.' . $a['id'] . '&select=id');
            if (!empty($existing)) {
                continue; // already turned into an internship — not "unmatched" anymore
            }
            $stu = $this->client->restGet('students', 'id=eq.' . $a['student_id'] . '&select=first_name,last_name,student_code,department_id');
            $deptName = '';
            if (isset($stu[0])) {
                $dept = $this->client->restGet('departments', 'id=eq.' . $stu[0]['department_id'] . '&select=name_th');
                $deptName = $dept[0]['name_th'] ?? '';
            }
            $com = $this->client->restGet('companies', 'id=eq.' . $a['company_id'] . '&select=name,address');
            $out[] = [
                'application_id' => $a['id'],
                'student_name' => isset($stu[0]) ? trim($stu[0]['first_name'] . ' ' . $stu[0]['last_name']) : '-',
                'student_code' => $stu[0]['student_code'] ?? '-',
                'department_name' => $deptName,
                'company_name' => $com[0]['name'] ?? '-',
                'company_address' => $com[0]['address'] ?? '-',
                'position_title' => $a['position_title'] ?? '-',
            ];
        }
        return $out;
    }
}
