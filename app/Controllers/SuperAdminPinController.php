<?php

namespace App\Controllers;

use App\Services\ApiException;
use App\Services\AuthException;
use App\Services\SuperAdminPinService;
use App\Support\Session;

/** super_admin_pins (Phase 10, SECURITY.md §6). */
final class SuperAdminPinController
{
    private SuperAdminPinService $pins;

    public function __construct()
    {
        $this->pins = new SuperAdminPinService();
    }

    /** GET /super-admin/pin/setup loader. */
    public function setupFormData(array $params): array
    {
        $userId = (string) Session::user()['id'];
        return [
            'alreadySet' => $this->pins->hasPin($userId),
            'formError' => Session::pullFlashError(),
        ];
    }

    /** POST /super-admin/pin/setup — first-time PIN setup only (SECURITY.md §6), no reset path yet. */
    public function setup(array $params): void
    {
        $userId = (string) Session::user()['id'];
        try {
            $this->pins->setup($userId, (string) ($_POST['pin'] ?? ''), (string) ($_POST['pin_confirm'] ?? ''));
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
        }
        header('Location: /super-admin/pin/setup');
        exit;
    }

    /**
     * POST /super-admin/verify-pin (API_SPEC.md §13) — the project's first JSON endpoint outside
     * Phase 5's attendance check-in/out, for the same reason: the browser-side PIN modal needs to
     * hold the resulting action_token in memory and attach it to the very next fetch()/form submit
     * without a full page reload.
     */
    public function verifyPin(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $user = Session::user();
            if (($user['role'] ?? null) !== 'super_admin') {
                throw new ApiException(403, 'FORBIDDEN', 'เฉพาะ Super Admin เท่านั้น');
            }
            $body = json_decode((string) file_get_contents('php://input'), true);
            $pin = is_array($body) ? (string) ($body['pin'] ?? '') : '';
            $token = $this->pins->verify((string) $user['id'], $pin);
            http_response_code(200);
            echo json_encode(['success' => true, 'data' => ['action_token' => $token, 'expires_in' => 300]], JSON_UNESCAPED_UNICODE);
        } catch (AuthException $e) {
            $status = match ($e->errorCode) {
                'PIN_LOCKED' => 423,
                'PIN_NOT_SET' => 409,
                default => 401,
            };
            http_response_code($status);
            echo json_encode(['success' => false, 'error' => ['code' => $e->errorCode, 'message' => $e->getMessage()]], JSON_UNESCAPED_UNICODE);
        } catch (ApiException $e) {
            http_response_code($e->httpStatus);
            echo json_encode(['success' => false, 'error' => ['code' => $e->errorCode, 'message' => $e->getMessage()]], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}
