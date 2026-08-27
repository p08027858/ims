<?php

namespace App\Controllers;

use App\Services\AuthException;
use App\Services\DailyLogService;
use App\Services\SupabaseClient;
use App\Support\Session;

final class DailyLogController
{
    private DailyLogService $logs;
    private SupabaseClient $client;

    public function __construct()
    {
        $this->client = new SupabaseClient();
        $this->logs = new DailyLogService($this->client);
    }

    public function listData(array $params): array
    {
        try {
            $internshipId = $this->activeInternshipId();
            if ($internshipId === null) {
                return ['logs' => [], 'dailyLogs' => [], 'items' => [], 'noActiveInternship' => true];
            }
            try {
                $logs = $this->logs->listForStudent($internshipId);
            } catch (\Throwable $e) {
                error_log('IMS daily log list fallback: internship_id=' . $internshipId . ' message=' . $e->getMessage());
                $logs = [];
            }

            return ['logs' => $logs, 'dailyLogs' => $logs, 'items' => $logs, 'noActiveInternship' => false];
        } catch (\Throwable $e) {
            error_log('IMS daily log current internship lookup failed: message=' . $e->getMessage());
            return ['logs' => [], 'dailyLogs' => [], 'items' => [], 'noActiveInternship' => true];
        }
    }

    public function newFormData(array $params): array
    {
        return ['noActiveInternship' => $this->activeInternshipId() === null, 'today' => date('Y-m-d')];
    }

    public function editFormData(array $params): array
    {
        $internshipId = $this->activeInternshipId();
        $log = $internshipId === null ? null : $this->logs->getLogById((int) ($params['id'] ?? 0), $internshipId);
        return ['log' => $log, 'noActiveInternship' => $internshipId === null || $log === null, 'today' => date('Y-m-d')];
    }

    public function store(array $params): void
    {
        try {
            $this->logs->saveOrSubmit($this->requireActiveInternshipId(), $this->submissionData(), [], true);
            $this->respondJson(200, ['success' => true]);
        } catch (AuthException $e) {
            $this->respondJson(422, ['success' => false, 'error' => ['code' => $e->errorCode(), 'message' => $e->getMessage()]]);
        } catch (\Throwable) {
            $this->respondJson(500, ['success' => false, 'error' => ['code' => 'DAILY_LOG_SAVE_FAILED', 'message' => 'ไม่สามารถบันทึกข้อมูลได้ กรุณาลองใหม่อีกครั้ง']]);
        }
    }

    public function update(array $params): void
    {
        try {
            $logId = (int) ($params['id'] ?? 0);
            if ($logId <= 0) {
                throw new AuthException('VALIDATION_ERROR', 'ไม่พบบันทึกที่ต้องการแก้ไข');
            }
            $this->logs->updateById($logId, $this->requireActiveInternshipId(), $this->submissionData(), [], true);
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
        } catch (\Throwable) {
            Session::flashError('ไม่สามารถบันทึกข้อมูลได้ กรุณาลองใหม่อีกครั้ง');
        }
        header('Location: /student/daily-logs');
        exit;
    }

    public function reviewPageData(array $params): array
    {
        try {
            $user = Session::user() ?? [];
            $role = (string) ($user['role'] ?? '');
            $userId = (string) ($user['id'] ?? '');
            $log = match ($role) {
                'company' => $this->logs->findOldestPendingForCompany($this->companyIdForUser($userId)),
                'teacher' => $this->logs->findOldestPendingForTeacher($this->teacherIdForUser($userId)),
                default => null,
            };
            if ($log !== null) {
                $log['date'] = (string) ($log['log_date'] ?? '');
                $log['attachments'] = array_map(static fn (array $file): array => [
                    'name' => (string) ($file['file_name'] ?? ''),
                    'path' => (string) ($file['file_path'] ?? ''),
                ], $log['attachments'] ?? []);
            }
            return ['log' => $log, 'student' => $log === null ? null : ['name' => (string) ($log['student_name'] ?? '-')]];
        } catch (\Throwable) {
            return ['log' => null, 'student' => null];
        }
    }

    public function review(array $params): void
    {
        $user = Session::user() ?? [];
        $role = (string) ($user['role'] ?? '');
        $redirect = $role === 'teacher' ? '/teacher/daily-logs' : '/company/daily-logs';
        try {
            $this->logs->review(
                (int) ($_POST['daily_log_id'] ?? 0),
                (string) ($_POST['status'] ?? ''),
                (string) ($_POST['reviewer_comment'] ?? ''),
                (string) ($user['id'] ?? ''),
                $role
            );
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
        } catch (\Throwable) {
            Session::flashError('ไม่สามารถบันทึกผลการตรวจได้ กรุณาลองใหม่อีกครั้ง');
        }
        header('Location: ' . $redirect);
        exit;
    }

    private function activeInternshipId(): ?int
    {
        return $this->logs->getActiveInternshipId((string) ((Session::user() ?? [])['id'] ?? ''));
    }

    private function requireActiveInternshipId(): int
    {
        $internshipId = $this->activeInternshipId();
        if ($internshipId === null) {
            throw new AuthException('VALIDATION_ERROR', 'ไม่พบการฝึกงานที่กำลังดำเนินอยู่');
        }
        return $internshipId;
    }

    private function submissionData(): array
    {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);
        $input = is_array($input) ? $input : $_POST;
        return [
            'work_description' => trim((string) ($input['work_description'] ?? $input['activity_description'] ?? '')),
            'learning_outcome' => trim((string) ($input['learning_outcome'] ?? $input['learning_outcomes'] ?? '')),
            'problem_found' => trim((string) ($input['problem_found'] ?? $input['problems_encountered'] ?? '')),
        ];
    }

    private function companyIdForUser(string $userId): int
    {
        $rows = $this->client->restGet('company_supervisors', 'user_id=eq.' . $userId . '&select=company_id&limit=1');
        $companyId = (int) ($rows[0]['company_id'] ?? 0);
        if ($companyId <= 0) {
            throw new AuthException('FORBIDDEN', 'ไม่พบบริษัทที่คุณมีสิทธิ์ตรวจบันทึก');
        }
        return $companyId;
    }

    private function teacherIdForUser(string $userId): int
    {
        $rows = $this->client->restGet('teachers', 'user_id=eq.' . $userId . '&select=id&limit=1');
        $teacherId = (int) ($rows[0]['id'] ?? 0);
        if ($teacherId <= 0) {
            throw new AuthException('FORBIDDEN', 'ไม่พบข้อมูลอาจารย์นิเทศของบัญชีนี้');
        }
        return $teacherId;
    }

    private function respondJson(int $status, array $payload): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
