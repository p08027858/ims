<?php

namespace App\Controllers;

use App\Services\AuditLogger;
use App\Services\AuthException;
use App\Services\LeaveService;
use App\Services\SettingsService;
use App\Services\SupabaseClient;
use App\Support\Session;

/** leave_requests (Phase 6 items 3-5). */
final class LeaveController
{
    private LeaveService $leaves;

    public function __construct()
    {
        $this->leaves = new LeaveService();
    }

    /** GET /student/leave-requests/new loader. */
    public function newFormData(array $params): array
    {
        return [
            'sickLeaveCertMinDays' => (new SettingsService())->getInt('sick_leave_certificate_min_days', 3),
            'formError' => Session::pullFlashError(),
        ];
    }

    public function apply(array $params): void
    {
        $userId = (string) Session::user()['id'];
        try {
            $internshipId = $this->leaves->getActiveInternshipId($userId);
            if ($internshipId === null) {
                throw new AuthException('VALIDATION_ERROR', 'ไม่พบการฝึกงานที่กำลังดำเนินอยู่ของบัญชีนี้');
            }
            $file = isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE ? $_FILES['attachment'] : null;
            $this->leaves->apply($internshipId, $_POST, $file);
            header('Location: /student/dashboard');
            exit;
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
            header('Location: /student/leave-requests/new');
            exit;
        }
    }

    /** GET /student/leave-requests loader (SITEMAP.md §2 — Phase 11). */
    public function myListData(array $params): array
    {
        $userId = (string) Session::user()['id'];
        $internshipId = $this->leaves->getActiveInternshipId($userId);
        return [
            'noActiveInternship' => $internshipId === null,
            'leaveRequests' => $internshipId !== null ? $this->leaves->listForStudent($internshipId) : [],
        ];
    }

    /** GET /company/leave-requests and /teacher/leave-requests loader. */
    public function reviewListData(array $params): array
    {
        $user = Session::user();
        $leaveRequests = $user['role'] === 'teacher'
            ? $this->leaves->listPendingForTeacher($this->resolveTeacherId((string) $user['id']))
            : $this->leaves->listPendingForCompany($this->resolveCompanyId((string) $user['id']));

        return ['leaveRequests' => $leaveRequests, 'flashError' => Session::pullFlashError()];
    }

    public function decide(array $params): void
    {
        $user = Session::user();
        try {
            $decision = ($_POST['status'] ?? '') === 'rejected' ? 'rejected' : 'approved';
            $this->leaves->decide((int) $params['id'], $decision, (string) ($_POST['review_note'] ?? ''), (string) $user['id'], (string) $user['role']);
            (new AuditLogger())->log((string) $user['id'], (string) $user['role'], $decision === 'rejected' ? 'reject' : 'approve', 'leave_requests', 'leave_requests', (int) $params['id'], null, ['status' => $decision]);
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
        }
        $redirectPath = $user['role'] === 'teacher' ? '/teacher/leave-requests' : '/company/leave-requests';
        header('Location: ' . $redirectPath);
        exit;
    }

    /** POST /student/leave-requests/{id}/cancel — RULE-LEAVE-02. */
    public function cancel(array $params): void
    {
        $userId = (string) Session::user()['id'];
        try {
            $internshipId = $this->leaves->getActiveInternshipId($userId);
            if ($internshipId === null) {
                throw new AuthException('VALIDATION_ERROR', 'ไม่พบการฝึกงานที่กำลังดำเนินอยู่ของบัญชีนี้');
            }
            $this->leaves->cancel((int) $params['id'], $internshipId);
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
        }
        header('Location: /student/leave-requests');
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
}
