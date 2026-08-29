<?php

namespace App\Services;

/** daily_logs + daily_log_attachments aligned to the current production schema. */
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

    public function saveOrSubmit(int $internshipId, array $data, array $files, bool $submit): void
    {
        $logDate = trim((string) ($data['log_date'] ?? date('Y-m-d')));
        $existing = $this->getLog($internshipId, $logDate);
        $studentId = $this->studentIdForInternship($internshipId);

        if ($existing !== null && !in_array((string) ($existing['status'] ?? ''), ['draft', 'revision_requested'], true)) {
            throw new AuthException('VALIDATION_ERROR', 'This daily log has already been submitted and can no longer be edited.');
        }
        if (trim((string) ($data['activity_description'] ?? '')) === '') {
            throw new AuthException('VALIDATION_ERROR', 'Activity description is required.');
        }

        $payload = [
            'internship_id' => $internshipId,
            'student_id' => $studentId,
            'log_date' => $logDate,
            'title' => trim((string) ($data['title'] ?? '')) ?: null,
            'activity_description' => trim((string) ($data['activity_description'] ?? '')),
            'problems_encountered' => trim((string) ($data['problems_encountered'] ?? '')) ?: null,
            'learning_outcomes' => trim((string) ($data['learning_outcomes'] ?? '')) ?: null,
            'photo_url' => trim((string) ($data['photo_url'] ?? '')) ?: null,
            'status' => $submit ? 'submitted' : (($existing['status'] ?? '') === 'revision_requested' ? 'draft' : ((string) ($existing['status'] ?? 'draft') ?: 'draft')),
        ];

        try {
            $rows = $existing !== null
                ? $this->client->restUpdate('daily_logs', 'id=eq.' . $existing['id'], $payload)
                : $this->client->restInsert('daily_logs', $payload);
        } catch (SupabaseException $e) {
            throw new AuthException('VALIDATION_ERROR', $e->getMessage(), ['cause' => $e->getMessage()]);
        }
        $logId = (int) ($rows[0]['id'] ?? 0);

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

    public function listForStudent(int $internshipId): array
    {
        return $this->client->restGet(
            'daily_logs',
            'internship_id=eq.' . $internshipId . '&select=id,log_date,status,title,activity_description,problems_encountered,learning_outcomes,photo_url&order=log_date.desc'
        );
    }

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

    public function updateById(int $logId, int $internshipId, array $data, array $files, bool $submit): void
    {
        $log = $this->getLogById($logId, $internshipId);
        if ($log === null) {
            throw new AuthException('VALIDATION_ERROR', 'Log not found.');
        }
        if (!in_array((string) ($log['status'] ?? ''), ['draft', 'revision_requested'], true)) {
            throw new AuthException('VALIDATION_ERROR', 'This daily log has already been submitted and can no longer be edited.');
        }
        if (trim((string) ($data['activity_description'] ?? '')) === '') {
            throw new AuthException('VALIDATION_ERROR', 'Activity description is required.');
        }

        $payload = [
            'student_id' => $this->studentIdForInternship($internshipId),
            'log_date' => trim((string) ($data['log_date'] ?? $log['log_date'] ?? date('Y-m-d'))),
            'title' => trim((string) ($data['title'] ?? '')) ?: null,
            'activity_description' => trim((string) ($data['activity_description'] ?? '')),
            'problems_encountered' => trim((string) ($data['problems_encountered'] ?? '')) ?: null,
            'learning_outcomes' => trim((string) ($data['learning_outcomes'] ?? '')) ?: null,
            'photo_url' => trim((string) ($data['photo_url'] ?? '')) ?: null,
            'status' => $submit ? 'submitted' : (($log['status'] ?? '') === 'revision_requested' ? 'draft' : (string) ($log['status'] ?? 'draft')),
        ];
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
        if ($teacherId <= 0) {
            return null;
        }

        try {
            $internships = $this->client->restGet(
                'internships',
                'teacher_id=eq.' . $teacherId . '&status=in.(approved,active,ongoing,in_progress,completed,pending_approval)&deleted_at=is.null&select=id'
            );
        } catch (SupabaseException $e) {
            error_log('IMS teacher daily log fallback[deleted_at]: teacher_id=' . $teacherId . ' message=' . $e->getMessage());
            $internships = $this->client->restGet(
                'internships',
                'teacher_id=eq.' . $teacherId . '&status=in.(approved,active,ongoing,in_progress,completed,pending_approval)&select=id'
            );
        }

        return $this->findOldestPendingAmong(array_column($internships, 'id'));
    }

    private function findOldestPendingAmong(array $internshipIds): ?array
    {
        $internshipIds = array_values(array_filter(array_map('intval', $internshipIds), static fn (int $id): bool => $id > 0));
        if (empty($internshipIds)) {
            return null;
        }

        $rows = $this->client->restGet(
            'daily_logs',
            'internship_id=in.(' . implode(',', $internshipIds) . ')&status=in.(submitted,pending)&order=created_at.asc&limit=1&select=*'
        );
        if (!isset($rows[0])) {
            return null;
        }

        $log = $rows[0];

        try {
            $log['attachments'] = $this->client->restGet('daily_log_attachments', 'daily_log_id=eq.' . $log['id'] . '&select=file_name,file_path');
        } catch (\Throwable $e) {
            error_log('IMS daily log attachments fallback: daily_log_id=' . ($log['id'] ?? 0) . ' message=' . $e->getMessage());
            $log['attachments'] = [];
        }

        $studentId = isset($log['student_id']) ? (int) $log['student_id'] : 0;
        if ($studentId <= 0) {
            $internship = $this->client->restGet('internships', 'id=eq.' . $log['internship_id'] . '&select=student_id&limit=1');
            $studentId = (int) ($internship[0]['student_id'] ?? 0);
        }

        $student = $studentId > 0
            ? $this->client->restGet('students', 'id=eq.' . $studentId . '&select=first_name,last_name,student_code&limit=1')
            : [];
        $log['student_name'] = isset($student[0])
            ? trim((string) (($student[0]['first_name'] ?? '') . ' ' . ($student[0]['last_name'] ?? '')))
            : '-';
        $log['student_code'] = (string) ($student[0]['student_code'] ?? '-');

        $logDate = substr((string) ($log['log_date'] ?? ''), 0, 10);
        $log['check_in'] = '-';
        $log['check_out'] = '-';
        $log['gps_accuracy_m'] = null;

        if ($logDate !== '') {
            $nextDate = date('Y-m-d', strtotime($logDate . ' +1 day'));
            try {
                $attendance = $this->client->restGet(
                    'attendance',
                    'internship_id=eq.' . $log['internship_id']
                    . '&created_at=gte.' . rawurlencode($logDate . 'T00:00:00')
                    . '&created_at=lt.' . rawurlencode($nextDate . 'T00:00:00')
                    . '&select=check_in_at,check_out_at'
                );
            } catch (\Throwable $e) {
                error_log('IMS daily log attendance lookup fallback: internship_id=' . ($log['internship_id'] ?? 0) . ' message=' . $e->getMessage());
                $attendance = [];
            }

            $log['check_in'] = isset($attendance[0]['check_in_at']) ? date('H:i', strtotime((string) $attendance[0]['check_in_at'])) : '-';
            $log['check_out'] = isset($attendance[0]['check_out_at']) ? date('H:i', strtotime((string) $attendance[0]['check_out_at'])) : '-';
        }

        return $log;
    }
    public function review(int $logId, string $decision, string $comment, string $reviewerUserId, string $reviewerRole): void
    {
        if (!in_array($decision, ['reviewed', 'revision_requested'], true)) {
            throw new AuthException('VALIDATION_ERROR', 'Invalid daily log status.');
        }
        $rows = $this->client->restGet('daily_logs', 'id=eq.' . $logId . '&status=in.(submitted,pending)&select=id,internship_id,status');
        if (!isset($rows[0])) {
            throw new AuthException('VALIDATION_ERROR', 'Daily log not found or already reviewed.');
        }
        $this->assertReviewAuthority((int) $rows[0]['internship_id'], $reviewerUserId, $reviewerRole);
        $this->client->restUpdate('daily_logs', 'id=eq.' . $logId, [
            'status' => $decision,
            'supervisor_comment' => trim($comment) ?: null,
        ]);
    }

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
                continue;
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
                    'title' => 'Missing daily log',
                    'message' => 'No daily log has been recorded for 2 consecutive days. Please review this internship.',
                ]);
                $notified++;
            }
        }
        return $notified;
    }

    private function studentIdForInternship(int $internshipId): int
    {
        $rows = $this->client->restGet('internships', 'id=eq.' . $internshipId . '&select=student_id&limit=1');
        $studentId = (int) ($rows[0]['student_id'] ?? 0);
        if ($studentId <= 0) {
            throw new AuthException('VALIDATION_ERROR', 'No student_id found for this internship.');
        }
        return $studentId;
    }
}
