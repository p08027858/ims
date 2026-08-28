<?php

namespace App\Controllers;

use App\Services\AuthException;
use App\Services\DailyLogService;
use App\Services\InternshipService;
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
        } catch (\Throwable $e) {
            error_log('IMS daily log store failed: ' . $e->getMessage());
            $this->respondJson(500, ['success' => false, 'error' => ['code' => 'DAILY_LOG_SAVE_FAILED', 'message' => $e->getMessage()]]);
        }
    }

    public function update(array $params): void
    {
        try {
            $logId = (int) ($params['id'] ?? 0);
            if ($logId <= 0) {
                throw new AuthException('VALIDATION_ERROR', 'Log not found.');
            }
            $this->logs->updateById($logId, $this->requireActiveInternshipId(), $this->submissionData(), [], true);
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
        } catch (\Throwable $e) {
            error_log('IMS daily log update failed: ' . $e->getMessage());
            Session::flashError($e->getMessage());
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
        } catch (\Throwable $e) {
            error_log('IMS daily log review failed: ' . $e->getMessage());
            Session::flashError($e->getMessage());
        }
        header('Location: ' . $redirect);
        exit;
    }

    private function activeInternshipId(): ?int
    {
        $userId = (string) ((Session::user() ?? [])['id'] ?? '');
        $internshipId = $this->logs->getActiveInternshipId($userId);
        if ($internshipId !== null) {
            return $internshipId;
        }

        $internship = (new InternshipService($this->client))->getCurrentInternshipForStudentUser($userId);
        return isset($internship['id']) ? (int) $internship['id'] : null;
    }

    private function requireActiveInternshipId(): int
    {
        $internshipId = $this->activeInternshipId();
        if ($internshipId === null) {
            throw new AuthException('VALIDATION_ERROR', 'No active internship found.');
        }
        return $internshipId;
    }

    private function submissionData(): array
    {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);
        $input = is_array($input) ? $input : $_POST;

        return [
            'log_date' => trim((string) ($input['log_date'] ?? date('Y-m-d'))),
            'title' => trim((string) ($input['title'] ?? '')),
            'activity_description' => trim((string) ($input['activity_description'] ?? $input['work_description'] ?? '')),
            'problems_encountered' => trim((string) ($input['problems_encountered'] ?? $input['problem_found'] ?? '')),
            'learning_outcomes' => trim((string) ($input['learning_outcomes'] ?? $input['learning_outcome'] ?? '')),
            'photo_url' => trim((string) ($input['photo_url'] ?? $input['photo_base64'] ?? '')),
        ];
    }

    private function companyIdForUser(string $userId): int
    {
        $rows = $this->client->restGet('company_supervisors', 'user_id=eq.' . $userId . '&select=company_id&limit=1');
        $companyId = (int) ($rows[0]['company_id'] ?? 0);
        if ($companyId <= 0) {
            throw new AuthException('FORBIDDEN', 'Company context not found.');
        }
        return $companyId;
    }

    private function teacherIdForUser(string $userId): int
    {
        $rows = $this->client->restGet('teachers', 'user_id=eq.' . $userId . '&select=id&limit=1');
        $teacherId = (int) ($rows[0]['id'] ?? 0);
        if ($teacherId <= 0) {
            throw new AuthException('FORBIDDEN', 'Teacher context not found.');
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
