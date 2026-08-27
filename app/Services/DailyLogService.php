<?php

namespace App\Services;

/** daily_logs + daily_log_attachments (AI_AGENT_PHASES.md Phase 6 items 1-2). */
final class DailyLogService
{
    private SupabaseClient $client;
    private FileUploadService $uploads;

    public function __construct(?SupabaseClient $client = null)
    {
        $this->client = $client ?? new SupabaseClient();
        $this->uploads = new FileUploadService($this->client);
    }

    public function getActiveInternshipId(string $userId): ?int
    {
        $internship = (new InternshipService($this->client))->getCurrentInternshipForStudentUser($userId);
        return isset($internship['id']) ? (int) $internship['id'] : null;
    }

    public function getLog(int $internshipId, string $logDate): ?array
    {
        $rows = $this->client->restGet('daily_logs', 'internship_id=eq.' . $internshipId . '&log_date=eq.' . $logDate . '&select=*');
        if (!isset($rows[0])) {
            return null;
        }
        $log = $rows[0];
        $log['attachments'] = $this->client->restGet('daily_log_attachments', 'daily_log_id=eq.' . $log['id'] . '&select=file_name,file_path');
        return $log;
    }

    /**
     * RULE-LOG-01 (unique per day — enforced here by checking-then-updating instead of ever
     * attempting a raw duplicate insert; DB unique constraint still backstops it, verified
     * directly at the DB level during Phase 6 testing) and RULE-LOG-02 (locked once submitted).
     *
     * @param array<int, array{name:string,type:string,tmp_name:string,error:int,size:int}> $files
     */
    public function saveOrSubmit(int $internshipId, array $data, array $files, bool $submit): void
    {
        $logDate = date('Y-m-d');
        $existing = $this->getLog($internshipId, $logDate);

        if ($existing !== null && !in_array($existing['status'], ['draft', 'revision_requested'], true)) {
            throw new AuthException('VALIDATION_ERROR', 'บันทึกนี้ถูกส่งไปแล้ว ไม่สามารถแก้ไขได้ (ต้องรอผู้ตรวจตีกลับก่อน)');
        }
        if (trim((string) ($data['work_description'] ?? '')) === '') {
            throw new AuthException('VALIDATION_ERROR', 'กรุณากรอกงานที่ทำวันนี้');
        }

        $payload = [
            'internship_id' => $internshipId,
            'log_date' => $logDate,
            'work_description' => trim($data['work_description']),
            'learning_outcome' => trim((string) ($data['learning_outcome'] ?? '')) ?: null,
            'problem_found' => trim((string) ($data['problem_found'] ?? '')) ?: null,
        ];
        if ($submit) {
            $payload['status'] = 'submitted';
            $payload['submitted_at'] = date('c');
        } elseif ($existing !== null && $existing['status'] === 'revision_requested') {
            $payload['status'] = 'draft'; // editing after a revision request reopens it as draft until resubmitted
        }

        try {
            $rows = $existing !== null
                ? $this->client->restUpdate('daily_logs', 'id=eq.' . $existing['id'], $payload)
                : $this->client->restInsert('daily_logs', $payload);
        } catch (SupabaseException $e) {
            throw new AuthException('VALIDATION_ERROR', 'บันทึกไม่สำเร็จ (มีบันทึกของวันนี้อยู่แล้ว)', ['cause' => $e->getMessage()]);
        }
        $logId = $rows[0]['id'];

        foreach ($files as $file) {
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue; // empty file input slot
            }
            $uploaded = $this->uploads->validateAndUpload($file, 'daily-logs', 'internship-' . $internshipId);
            $this->client->restInsert('daily_log_attachments', [
                'daily_log_id' => $logId,
                'file_path' => $uploaded['path'],
                'file_name' => $uploaded['name'],
                'file_type' => $uploaded['type'],
                'file_size_kb' => $uploaded['size_kb'],
            ]);
        }
    }

    /**
     * SITEMAP.md §2 `/student/daily-logs` — full history for the student's current internship,
     * newest first (Phase 11; `/student/daily-logs/new` never needed more than "today").
     *
     * @return array<int, array{id:int,log_date:string,status:string,work_description:string}>
     */
    public function listForStudent(int $internshipId): array
    {
        return $this->client->restGet(
            'daily_logs',
            'internship_id=eq.' . $internshipId . '&select=id,log_date,status,work_description&order=log_date.desc'
        );
    }

    /** Ownership-checked lookup for the `/student/daily-logs/{id}/edit` page — must belong to this internship. */
    public function getLogById(int $logId, int $internshipId): ?array
    {
        $rows = $this->client->restGet('daily_logs', 'id=eq.' . $logId . '&internship_id=eq.' . $internshipId . '&select=*');
        if (!isset($rows[0])) {
            return null;
        }
        $log = $rows[0];
        $log['attachments'] = $this->client->restGet('daily_log_attachments', 'daily_log_id=eq.' . $log['id'] . '&select=file_name,file_path');
        return $log;
    }

    /**
     * RULE-LOG-02: editing a specific past log (as opposed to saveOrSubmit()'s always-today
     * behavior for the `/student/daily-logs/new` page) — only while still draft/revision_requested.
     *
     * @param array<int, array{name:string,type:string,tmp_name:string,error:int,size:int}> $files
     */
    public function updateById(int $logId, int $internshipId, array $data, array $files, bool $submit): void
    {
        $log = $this->getLogById($logId, $internshipId);
        if ($log === null) {
            throw new AuthException('VALIDATION_ERROR', 'ไม่พบบันทึกนี้');
        }
        if (!in_array($log['status'], ['draft', 'revision_requested'], true)) {
            throw new AuthException('VALIDATION_ERROR', 'บันทึกนี้ถูกส่งไปแล้ว ไม่สามารถแก้ไขได้');
        }
        if (trim((string) ($data['work_description'] ?? '')) === '') {
            throw new AuthException('VALIDATION_ERROR', 'กรุณากรอกงานที่ทำวันนี้');
        }

        $payload = [
            'work_description' => trim($data['work_description']),
            'learning_outcome' => trim((string) ($data['learning_outcome'] ?? '')) ?: null,
            'problem_found' => trim((string) ($data['problem_found'] ?? '')) ?: null,
        ];
        if ($submit) {
            $payload['status'] = 'submitted';
            $payload['submitted_at'] = date('c');
        } elseif ($log['status'] === 'revision_requested') {
            $payload['status'] = 'draft';
        }
        $this->client->restUpdate('daily_logs', 'id=eq.' . $logId, $payload);

        foreach ($files as $file) {
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $uploaded = $this->uploads->validateAndUpload($file, 'daily-logs', 'internship-' . $internshipId);
            $this->client->restInsert('daily_log_attachments', [
                'daily_log_id' => $logId,
                'file_path' => $uploaded['path'],
                'file_name' => $uploaded['name'],
                'file_type' => $uploaded['type'],
                'file_size_kb' => $uploaded['size_kb'],
            ]);
        }
    }

    public function findOldestPendingForCompany(int $companyId): ?array
    {
        $internships = $this->client->restGet('internships', 'company_id=eq.' . $companyId . '&select=id');
        return $this->findOldestPendingAmong(array_column($internships, 'id'));
    }

    public function findOldestPendingForTeacher(int $teacherId): ?array
    {
        $internships = $this->client->restGet('internships', 'teacher_id=eq.' . $teacherId . '&select=id');
        return $this->findOldestPendingAmong(array_column($internships, 'id'));
    }

    private function findOldestPendingAmong(array $internshipIds): ?array
    {
        if (empty($internshipIds)) {
            return null;
        }
        $rows = $this->client->restGet(
            'daily_logs',
            'internship_id=in.(' . implode(',', $internshipIds) . ')&status=eq.submitted&order=submitted_at.asc&limit=1&select=*'
        );
        if (!isset($rows[0])) {
            return null;
        }
        $log = $rows[0];
        $log['attachments'] = $this->client->restGet('daily_log_attachments', 'daily_log_id=eq.' . $log['id'] . '&select=file_name,file_path');

        $internship = $this->client->restGet('internships', 'id=eq.' . $log['internship_id'] . '&select=student_id');
        $studentId = $internship[0]['student_id'] ?? null;
        $student = $studentId !== null ? $this->client->restGet('students', 'id=eq.' . $studentId . '&select=first_name,last_name') : [];
        $log['student_name'] = isset($student[0]) ? trim($student[0]['first_name'] . ' ' . $student[0]['last_name']) : '-';

        $attendance = $this->client->restGet('attendance', 'internship_id=eq.' . $log['internship_id'] . '&work_date=eq.' . $log['log_date'] . '&select=check_in_at,check_out_at,check_in_accuracy_m');
        $log['check_in'] = isset($attendance[0]['check_in_at']) ? date('H:i', strtotime($attendance[0]['check_in_at'])) : '-';
        $log['check_out'] = isset($attendance[0]['check_out_at']) ? date('H:i', strtotime($attendance[0]['check_out_at'])) : '-';
        $log['gps_accuracy_m'] = $attendance[0]['check_in_accuracy_m'] ?? null;

        return $log;
    }

    /** RULE-LOG-02: only the reviewer whose role matches (company/teacher) may act, and only while still submitted. */
    public function review(int $logId, string $decision, string $comment, string $reviewerUserId, string $reviewerRole): void
    {
        if (!in_array($decision, ['reviewed', 'revision_requested'], true)) {
            throw new AuthException('VALIDATION_ERROR', 'สถานะไม่ถูกต้อง');
        }
        $rows = $this->client->restGet('daily_logs', 'id=eq.' . $logId . '&status=eq.submitted&select=id,internship_id');
        if (!isset($rows[0])) {
            throw new AuthException('VALIDATION_ERROR', 'ไม่พบบันทึกนี้ หรือถูกตรวจไปแล้ว');
        }
        $this->assertReviewAuthority((int) $rows[0]['internship_id'], $reviewerUserId, $reviewerRole);
        $this->client->restUpdate('daily_logs', 'id=eq.' . $logId, [
            'status' => $decision,
            'reviewed_by' => $reviewerUserId,
            'reviewer_role' => $reviewerRole,
            'reviewed_at' => date('c'),
            'reviewer_comment' => trim($comment) ?: null,
        ]);
    }

    /** The service-role key bypasses RLS, so reviewer ownership must be checked here. */
    private function assertReviewAuthority(int $internshipId, string $reviewerUserId, string $reviewerRole): void
    {
        if ($internshipId <= 0 || $reviewerUserId === '') {
            throw new AuthException('FORBIDDEN', 'You are not allowed to review this daily log.');
        }

        if ($reviewerRole === 'company') {
            $supervisors = $this->client->restGet('company_supervisors', 'user_id=eq.' . $reviewerUserId . '&select=company_id&limit=1');
            $companyId = (int) ($supervisors[0]['company_id'] ?? 0);
            $internship = $companyId > 0
                ? $this->client->restGet('internships', 'id=eq.' . $internshipId . '&company_id=eq.' . $companyId . '&select=id&limit=1')
                : [];
        } elseif ($reviewerRole === 'teacher') {
            $teachers = $this->client->restGet('teachers', 'user_id=eq.' . $reviewerUserId . '&select=id&limit=1');
            $teacherId = (int) ($teachers[0]['id'] ?? 0);
            $internship = $teacherId > 0
                ? $this->client->restGet('internships', 'id=eq.' . $internshipId . '&teacher_id=eq.' . $teacherId . '&select=id&limit=1')
                : [];
        } else {
            $internship = [];
        }

        if (!isset($internship[0])) {
            throw new AuthException('FORBIDDEN', 'You are not allowed to review this daily log.');
        }
    }

    /**
     * RULE-LOG-04 — prepared for Phase 9's cron (same pattern as
     * AttendanceService::closeStaleIncompleteDays()), not scheduled yet.
     */
    public function notifyMissedLogs(): int
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $twoDaysAgo = date('Y-m-d', strtotime('-2 days'));
        $internships = $this->client->restGet('internships', 'status=eq.active&deleted_at=is.null&select=id,student_id,teacher_id');

        $notified = 0;
        foreach ($internships as $i) {
            $logs = $this->client->restGet(
                'daily_logs',
                'internship_id=eq.' . $i['id'] . '&log_date=in.(' . $yesterday . ',' . $twoDaysAgo . ')&select=log_date'
            );
            if (!empty($logs)) {
                continue; // logged at least one of the last 2 days
            }
            $student = $this->client->restGet('students', 'id=eq.' . $i['student_id'] . '&select=user_id');
            $teacher = $this->client->restGet('teachers', 'id=eq.' . $i['teacher_id'] . '&select=user_id');
            foreach ([$student[0]['user_id'] ?? null, $teacher[0]['user_id'] ?? null] as $userId) {
                if ($userId === null) {
                    continue;
                }
                $this->client->restInsert('notifications', [
                    'user_id' => $userId,
                    'type' => 'daily_log_missed',
                    'title' => 'ยังไม่ได้บันทึกงานประจำวัน',
                    'message' => 'ไม่มีการบันทึกงานประจำวันติดต่อกัน 2 วัน กรุณาตรวจสอบ',
                ]);
                $notified++;
            }
        }
        return $notified;
    }
}
