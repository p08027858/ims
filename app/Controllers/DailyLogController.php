<?php

namespace App\Controllers;

use App\Services\AuditLogger;
use App\Services\AuthException;
use App\Services\DailyLogService;
use App\Services\SupabaseClient;
use App\Support\Session;

/** daily_logs (Phase 6 items 1-2). */
final class DailyLogController
{
    private DailyLogService $logs;

    public function __construct()
    {
        $this->logs = new DailyLogService();
    }

    /** GET /student/daily-logs/new loader. */
    public function newFormData(array $params): array
    {
        $userId = (string) Session::user()['id'];
        $internshipId = $this->logs->getActiveInternshipId($userId);
        if ($internshipId === null) {
            return ['noActiveInternship' => true];
        }
        $log = $this->logs->getLog($internshipId, date('Y-m-d'));
        $locked = $log !== null && !in_array($log['status'], ['draft', 'revision_requested'], true);

        return [
            'noActiveInternship' => false,
            'locked' => $locked,
            'log' => [
                'log_date' => date('j F Y', strtotime('+543 years')),
                'work_description' => $log['work_description'] ?? '',
                'learning_outcome' => $log['learning_outcome'] ?? '',
                'problem_found' => $log['problem_found'] ?? '',
                'status' => $log['status'] ?? null,
                'reviewer_comment' => $log['reviewer_comment'] ?? null,
            ],
            'formError' => Session::pullFlashError(),
        ];
    }

    public function save(array $params): void
    {
        $userId = (string) Session::user()['id'];
        try {
            $internshipId = $this->logs->getActiveInternshipId($userId);
            if ($internshipId === null) {
                throw new AuthException('VALIDATION_ERROR', 'ไม่พบการฝึกงานที่กำลังดำเนินอยู่ของบัญชีนี้');
            }
            $submit = ($_POST['action'] ?? 'draft') === 'submit';
            $this->logs->saveOrSubmit($internshipId, $_POST, $this->normalizeFiles('attachments'), $submit);
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
        }
        header('Location: /student/daily-logs/new');
        exit;
    }

    /** GET /student/daily-logs loader (SITEMAP.md §2 — Phase 11, replaces the attendance_history.php placeholder). */
    public function listData(array $params): array
    {
        $userId = (string) Session::user()['id'];
        $internshipId = $this->logs->getActiveInternshipId($userId);
        if ($internshipId === null) {
            return ['noActiveInternship' => true, 'logs' => []];
        }
        $statusLabel = ['draft' => 'แบบร่าง', 'submitted' => 'ส่งแล้ว', 'reviewed' => 'ตรวจแล้ว', 'revision_requested' => 'ขอให้แก้ไข'];
        $logs = array_map(static fn (array $l) => [
            'id' => $l['id'],
            'log_date' => date('j M Y', strtotime('+543 years', strtotime($l['log_date']))),
            'status' => $l['status'],
            'status_label' => $statusLabel[$l['status']] ?? $l['status'],
            'editable' => in_array($l['status'], ['draft', 'revision_requested'], true),
            'work_description' => mb_substr((string) $l['work_description'], 0, 80),
        ], $this->logs->listForStudent($internshipId));
        return ['noActiveInternship' => false, 'logs' => $logs];
    }

    /** GET /student/daily-logs/{id}/edit loader (SITEMAP.md §2 — Phase 11). Reuses daily_log_form.php. */
    public function editFormData(array $params): array
    {
        $userId = (string) Session::user()['id'];
        $internshipId = $this->logs->getActiveInternshipId($userId);
        if ($internshipId === null) {
            return ['noActiveInternship' => true];
        }
        $log = $this->logs->getLogById((int) $params['id'], $internshipId);
        if ($log === null) {
            return ['noActiveInternship' => true];
        }
        $locked = !in_array($log['status'], ['draft', 'revision_requested'], true);

        return [
            'noActiveInternship' => false,
            'locked' => $locked,
            'editId' => (int) $params['id'],
            'log' => [
                'log_date' => date('j F Y', strtotime('+543 years', strtotime($log['log_date']))),
                'work_description' => $log['work_description'] ?? '',
                'learning_outcome' => $log['learning_outcome'] ?? '',
                'problem_found' => $log['problem_found'] ?? '',
                'status' => $log['status'] ?? null,
                'reviewer_comment' => $log['reviewer_comment'] ?? null,
            ],
            'formError' => Session::pullFlashError(),
        ];
    }

    /** POST /student/daily-logs/{id}/edit. */
    public function update(array $params): void
    {
        $userId = (string) Session::user()['id'];
        $logId = (int) $params['id'];
        try {
            $internshipId = $this->logs->getActiveInternshipId($userId);
            if ($internshipId === null) {
                throw new AuthException('VALIDATION_ERROR', 'ไม่พบการฝึกงานที่กำลังดำเนินอยู่ของบัญชีนี้');
            }
            $submit = ($_POST['action'] ?? 'draft') === 'submit';
            $this->logs->updateById($logId, $internshipId, $_POST, $this->normalizeFiles('attachments'), $submit);
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
        }
        header('Location: /student/daily-logs/' . $logId . '/edit');
        exit;
    }

    /** GET /company/daily-logs and /teacher/daily-logs loader — oldest submitted log for this reviewer's scope. */
    public function reviewPageData(array $params): array
    {
        $user = Session::user();
        $log = $user['role'] === 'teacher'
            ? $this->logs->findOldestPendingForTeacher($this->resolveTeacherId((string) $user['id']))
            : $this->logs->findOldestPendingForCompany($this->resolveCompanyId((string) $user['id']));

        if ($log === null) {
            return ['log' => null, 'student' => null];
        }
        return [
            'student' => ['name' => $log['student_name']],
            'log' => [
                'id' => $log['id'],
                'date' => $log['log_date'],
                'submitted_at' => $log['submitted_at'] !== null ? date('H:i', strtotime($log['submitted_at'])) . ' น.' : '-',
                'work_description' => $log['work_description'],
                'learning_outcome' => $log['learning_outcome'] ?? '',
                'check_in' => $log['check_in'],
                'check_out' => $log['check_out'],
                'gps_accuracy_m' => $log['gps_accuracy_m'],
                'attachments' => array_map(static fn (array $a) => ['name' => $a['file_name']], $log['attachments']),
            ],
        ];
    }

    public function review(array $params): void
    {
        $user = Session::user();
        try {
            $decision = ($_POST['status'] ?? '') === 'revision_requested' ? 'revision_requested' : 'reviewed';
            $this->logs->review(
                (int) ($_POST['daily_log_id'] ?? 0),
                $decision,
                (string) ($_POST['reviewer_comment'] ?? ''),
                (string) $user['id'],
                (string) $user['role']
            );
            (new AuditLogger())->log((string) $user['id'], (string) $user['role'], 'update', 'daily_logs', 'daily_logs', (int) ($_POST['daily_log_id'] ?? 0), null, ['status' => $decision]);
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
        }
        $redirectPath = $user['role'] === 'teacher' ? '/teacher/daily-logs' : '/company/daily-logs';
        header('Location: ' . $redirectPath);
        exit;
    }

    private function resolveCompanyId(string $userId): int
    {
        $rows = (new SupabaseClient())->restGet('company_supervisors', 'user_id=eq.' . $userId . '&select=company_id');
        return (int) ($rows[0]['company_id'] ?? 0);
    }

    private function resolveTeacherId(string $userId): int
    {
        $rows = (new SupabaseClient())->restGet('teachers', 'user_id=eq.' . $userId . '&select=id');
        return (int) ($rows[0]['id'] ?? 0);
    }

    /** Normalizes PHP's `$_FILES['name']['name'][]`-style multi-file array into a flat list of single-file arrays. */
    private function normalizeFiles(string $field): array
    {
        if (!isset($_FILES[$field])) {
            return [];
        }
        $out = [];
        foreach ($_FILES[$field]['name'] as $i => $name) {
            if ($name === '') {
                continue;
            }
            $out[] = [
                'name' => $name,
                'type' => $_FILES[$field]['type'][$i],
                'tmp_name' => $_FILES[$field]['tmp_name'][$i],
                'error' => $_FILES[$field]['error'][$i],
                'size' => $_FILES[$field]['size'][$i],
            ];
        }
        return $out;
    }
}
