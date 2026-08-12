<?php

namespace App\Controllers;

use App\Middleware\ActionTokenGuard;
use App\Services\ApiException;
use App\Services\ApplicationService;
use App\Services\AuditLogger;
use App\Services\AuthException;
use App\Services\InternshipService;
use App\Support\Session;

/** internships — admin matching/approve/terminate (Phase 4 items 2-4). */
final class InternshipController
{
    private InternshipService $internships;
    private ApplicationService $applications;

    public function __construct()
    {
        $this->internships = new InternshipService();
        $this->applications = new ApplicationService();
    }

    /** GET /admin/matching loader — accepted applications not yet turned into an internship. */
    public function matchingListData(array $params): array
    {
        return ['unmatched' => $this->applications->listAcceptedUnmatched()];
    }

    /** GET /admin/matching/{id} loader. */
    public function matchingFormData(array $params): array
    {
        $applicationId = (int) $params['id'];
        $context = $this->internships->getApplicationMatchContext($applicationId);
        return [
            'applicationId' => $applicationId,
            'student' => $context['student'] ?? ['name' => 'ไม่พบข้อมูล', 'code' => '', 'department' => ''],
            'company' => $context['company'] ?? ['name' => 'ไม่พบข้อมูล', 'address' => ''],
            'teachers' => $this->internships->listTeachers(),
            'formError' => Session::pullFlashError(),
        ];
    }

    /** POST /admin/matching/{id}. */
    public function createFromApplication(array $params): void
    {
        try {
            $this->internships->createFromApplication((int) $params['id'], $_POST);
            $user = Session::user();
            (new AuditLogger())->log((string) $user['id'], (string) $user['role'], 'create', 'internships', 'internships', null, null, ['application_id' => (int) $params['id']]);
            header('Location: /admin/matching');
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
            header('Location: /admin/matching/' . $params['id']);
        }
        exit;
    }

    /** GET /admin/internships loader. */
    public function adminListData(array $params): array
    {
        return [
            'internships' => $this->internships->listForAdminConsole(),
            'flashError' => Session::pullFlashError(),
        ];
    }

    public function approve(array $params): void
    {
        $user = Session::user();
        try {
            $this->internships->approve((int) $params['id'], (string) $user['id']);
            // DoD example action (AI_AGENT_PHASES.md Phase 9): "approve internships" must appear in audit_logs.
            (new AuditLogger())->log((string) $user['id'], (string) $user['role'], 'approve', 'internships', 'internships', (int) $params['id'], ['status' => 'pending_approval'], ['status' => 'approved']);
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
        }
        header('Location: /admin/internships');
        exit;
    }

    public function terminate(array $params): void
    {
        $user = Session::user();
        try {
            $reason = (string) ($_POST['termination_reason'] ?? '');
            $this->internships->terminate((int) $params['id'], $reason);
            (new AuditLogger())->log((string) $user['id'], (string) $user['role'], 'update', 'internships', 'internships', (int) $params['id'], null, ['status' => 'terminated', 'termination_reason' => $reason]);
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
        }
        header('Location: /admin/internships');
        exit;
    }

    /**
     * DELETE /admin/internships/{id} (RULE-SEC-01, API_SPEC.md §13) — the project's first real
     * hard-delete endpoint. Route-gated to role=super_admin only (config/actions.php), same as
     * every other super-admin-only action, and additionally requires a fresh action_token from
     * ActionTokenGuard so PIN verification is a real prerequisite for THIS session, not merely
     * role membership (see CHANGELOG.md Phase 10 for why admin never even reaches this route).
     */
    public function destroy(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            ActionTokenGuard::require();
            $user = Session::user();
            $deleted = $this->internships->delete((int) $params['id']);
            (new AuditLogger())->log((string) $user['id'], (string) $user['role'], 'delete', 'internships', 'internships', (int) $params['id'], $deleted, null);
            http_response_code(200);
            echo json_encode(['success' => true, 'data' => ['id' => (int) $params['id']]], JSON_UNESCAPED_UNICODE);
        } catch (AuthException $e) {
            $status = $e->errorCode === 'ACTION_TOKEN_REQUIRED' ? 403 : 400;
            http_response_code($status);
            echo json_encode(['success' => false, 'error' => ['code' => $e->errorCode, 'message' => $e->getMessage()]], JSON_UNESCAPED_UNICODE);
        } catch (ApiException $e) {
            http_response_code($e->httpStatus);
            echo json_encode(['success' => false, 'error' => ['code' => $e->errorCode, 'message' => $e->getMessage()]], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}
