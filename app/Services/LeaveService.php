<?php

namespace App\Services;

/** leave_requests (AI_AGENT_PHASES.md Phase 6 items 3-5). */
final class LeaveService
{
    private SupabaseClient $client;
    private FileUploadService $uploads;
    private SettingsService $settings;

    public function __construct(?SupabaseClient $client = null)
    {
        $this->client = $client ?? new SupabaseClient();
        $this->uploads = new FileUploadService($this->client);
        $this->settings = new SettingsService($this->client);
    }

    public function getActiveInternshipId(string $userId): ?int
    {
        $students = $this->client->restGet('students', 'user_id=eq.' . $userId . '&select=id');
        if (!isset($students[0])) {
            return null;
        }
        $internships = $this->client->restGet(
            'internships',
            'student_id=eq.' . $students[0]['id'] . '&status=eq.active&deleted_at=is.null&select=id'
        );
        return isset($internships[0]) ? (int) $internships[0]['id'] : null;
    }

    /**
     * RULE-LEAVE-01: sick leave of `sick_leave_certificate_min_days`+ requires a certificate
     * before it can even be submitted (not just before approval).
     *
     * @param array{name:string,type:string,tmp_name:string,error:int,size:int}|null $file
     */
    public function apply(int $internshipId, array $data, ?array $file): void
    {
        $leaveType = (string) ($data['leave_type'] ?? '');
        if (!in_array($leaveType, ['sick', 'personal', 'other'], true)) {
            throw new AuthException('VALIDATION_ERROR', 'ประเภทการลาไม่ถูกต้อง');
        }
        $start = (string) ($data['start_date'] ?? '');
        $end = (string) ($data['end_date'] ?? '');
        $reason = trim((string) ($data['reason'] ?? ''));
        if ($start === '' || $end === '' || $reason === '') {
            throw new AuthException('VALIDATION_ERROR', 'กรุณากรอกวันที่และเหตุผลให้ครบ');
        }
        $startTs = strtotime($start);
        $endTs = strtotime($end);
        if ($startTs === false || $endTs === false || $endTs < $startTs) {
            throw new AuthException('VALIDATION_ERROR', 'ช่วงวันที่ลาไม่ถูกต้อง');
        }
        $totalDays = (int) round(($endTs - $startTs) / 86400) + 1;

        $minCertDays = $this->settings->getInt('sick_leave_certificate_min_days', 3);
        $hasFile = $file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        if ($leaveType === 'sick' && $totalDays >= $minCertDays && !$hasFile) {
            throw new AuthException('MEDICAL_CERT_REQUIRED', 'กรุณาแนบใบรับรองแพทย์ (ลาป่วยตั้งแต่ ' . $minCertDays . ' วันขึ้นไป)');
        }

        $attachmentPath = null;
        if ($hasFile) {
            $uploaded = $this->uploads->validateAndUpload($file, 'leave-certificates', 'internship-' . $internshipId);
            $attachmentPath = $uploaded['path'];
        }

        $this->client->restInsert('leave_requests', [
            'internship_id' => $internshipId,
            'leave_type' => $leaveType,
            'start_date' => $start,
            'end_date' => $end,
            'total_days' => $totalDays,
            'reason' => $reason,
            'attachment_path' => $attachmentPath,
        ]);
    }

    /**
     * SITEMAP.md §2 `/student/leave-requests` — the student's own leave request history, newest
     * first (Phase 11; Phase 6 only ever needed the "new" form and reviewer queues).
     *
     * @return array<int, array{id:int,type:string,range:string,reason:string,status:string}>
     */
    public function listForStudent(int $internshipId): array
    {
        $rows = $this->client->restGet(
            'leave_requests',
            'internship_id=eq.' . $internshipId . '&select=id,leave_type,start_date,end_date,total_days,reason,status&order=created_at.desc'
        );
        $typeLabels = ['sick' => 'ลาป่วย', 'personal' => 'ลากิจ', 'other' => 'อื่นๆ'];
        return array_map(static fn (array $lr) => [
            'id' => $lr['id'],
            'type' => $typeLabels[$lr['leave_type']] ?? $lr['leave_type'],
            'range' => $lr['start_date'] . ' ถึง ' . $lr['end_date'] . ' (' . $lr['total_days'] . ' วัน)',
            'reason' => $lr['reason'],
            'status' => $lr['status'],
            'cancellable' => $lr['status'] === 'pending',
        ], $rows);
    }

    /** @return array<int, array{id:int,student:string,type:string,range:string,reason:string,attachment:?string}> */
    public function listPendingForCompany(int $companyId): array
    {
        return $this->listPendingAmong('company_id=eq.' . $companyId);
    }

    public function listPendingForTeacher(int $teacherId): array
    {
        return $this->listPendingAmong('teacher_id=eq.' . $teacherId);
    }

    private function listPendingAmong(string $internshipFilter): array
    {
        $internships = $this->client->restGet('internships', $internshipFilter . '&select=id');
        $ids = array_column($internships, 'id');
        if (empty($ids)) {
            return [];
        }
        $rows = $this->client->restGet(
            'leave_requests',
            'internship_id=in.(' . implode(',', $ids) . ')&status=eq.pending&select=*&order=created_at.asc'
        );
        $typeLabels = ['sick' => 'ลาป่วย', 'personal' => 'ลากิจ', 'other' => 'อื่นๆ'];
        $out = [];
        foreach ($rows as $lr) {
            $internship = $this->client->restGet('internships', 'id=eq.' . $lr['internship_id'] . '&select=student_id');
            $studentId = $internship[0]['student_id'] ?? null;
            $student = $studentId !== null ? $this->client->restGet('students', 'id=eq.' . $studentId . '&select=first_name,last_name') : [];
            $out[] = [
                'id' => $lr['id'],
                'student' => isset($student[0]) ? trim($student[0]['first_name'] . ' ' . $student[0]['last_name']) : '-',
                'type' => $typeLabels[$lr['leave_type']] ?? $lr['leave_type'],
                'range' => $lr['start_date'] . ' ถึง ' . $lr['end_date'] . ' (' . $lr['total_days'] . ' วัน)',
                'reason' => $lr['reason'],
                'attachment' => $lr['attachment_path'] ? basename((string) $lr['attachment_path']) : null,
            ];
        }
        return $out;
    }

    /** RULE-LEAVE-03/04. */
    public function decide(int $leaveId, string $decision, string $note, string $reviewerUserId, string $reviewerRole): void
    {
        if (!in_array($decision, ['approved', 'rejected'], true)) {
            throw new AuthException('VALIDATION_ERROR', 'สถานะไม่ถูกต้อง');
        }
        $rows = $this->client->restGet('leave_requests', 'id=eq.' . $leaveId . '&status=eq.pending&select=*');
        if (!isset($rows[0])) {
            throw new AuthException('VALIDATION_ERROR', 'ไม่พบคำขอลานี้ หรือถูกดำเนินการไปแล้ว');
        }
        $leave = $rows[0];

        $this->client->restUpdate('leave_requests', 'id=eq.' . $leaveId, [
            'status' => $decision,
            'reviewed_by' => $reviewerUserId,
            'reviewer_role' => $reviewerRole,
            'reviewed_at' => date('c'),
            'review_note' => trim($note) ?: null,
        ]);

        if ($decision === 'approved') {
            $this->applyLeaveToAttendance($leave);
            $this->warnAdminsIfOverLeaveLimit((int) $leave['internship_id']);
        }
    }

    /** RULE-LEAVE-02: only a still-pending request owned by this student may be cancelled. */
    public function cancel(int $leaveId, int $internshipId): void
    {
        $rows = $this->client->restGet('leave_requests', 'id=eq.' . $leaveId . '&internship_id=eq.' . $internshipId . '&status=eq.pending&select=id');
        if (!isset($rows[0])) {
            throw new AuthException('VALIDATION_ERROR', 'ไม่สามารถยกเลิกคำขอนี้ได้ (ต้องเป็นสถานะรออนุมัติเท่านั้น)');
        }
        $this->client->restUpdate('leave_requests', 'id=eq.' . $leaveId, ['status' => 'cancelled']);
    }

    /** RULE-LEAVE-03: every date in the approved range gets attendance.day_status='leave' — upserted, no checkin/checkout required that day. */
    private function applyLeaveToAttendance(array $leave): void
    {
        $internshipId = (int) $leave['internship_id'];
        $cursor = strtotime($leave['start_date']);
        $end = strtotime($leave['end_date']);
        while ($cursor <= $end) {
            $date = date('Y-m-d', $cursor);
            $nextDate = date('Y-m-d', strtotime($date . ' +1 day'));
            $existing = $this->client->restGet(
                'attendance',
                'internship_id=eq.' . $internshipId
                . '&created_at=gte.' . rawurlencode($date . 'T00:00:00')
                . '&created_at=lt.' . rawurlencode($nextDate . 'T00:00:00')
                . '&select=id'
            );
            if (isset($existing[0])) {
                $this->client->restUpdate('attendance', 'id=eq.' . $existing[0]['id'], ['status' => 'leave']);
            } else {
                $this->client->restInsert('attendance', [
                    'internship_id' => $internshipId,
                    'created_at' => $date . 'T00:00:00',
                    'status' => 'leave',
                ]);
            }
            $cursor = strtotime('+1 day', $cursor);
        }
    }

    /** RULE-LEAVE-04: doesn't block approval — only notifies admins once cumulative approved leave exceeds the cap. */
    private function warnAdminsIfOverLeaveLimit(int $internshipId): void
    {
        $maxDays = $this->settings->getInt('max_total_leave_days', 15);
        $rows = $this->client->restGet('leave_requests', 'internship_id=eq.' . $internshipId . '&status=eq.approved&select=total_days');
        $total = array_sum(array_column($rows, 'total_days'));
        if ($total <= $maxDays) {
            return;
        }
        $admins = $this->client->restGet('users', 'role=in.(admin,super_admin)&status=eq.active&select=id');
        foreach ($admins as $a) {
            $this->client->restInsert('notifications', [
                'user_id' => $a['id'],
                'type' => 'leave_days_exceeded',
                'title' => 'นักศึกษาลาเกินเกณฑ์',
                'message' => "การฝึกงาน #{$internshipId} มีวันลาสะสมที่อนุมัติแล้ว {$total} วัน (เกินเกณฑ์ {$maxDays} วัน)",
            ]);
        }
    }
}
