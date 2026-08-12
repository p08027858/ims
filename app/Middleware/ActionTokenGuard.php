<?php

namespace App\Middleware;

use App\Services\AuthException;
use App\Support\Session;

/**
 * Guards destructive/critical-edit endpoints per SECURITY.md §6 / RULE-SEC-01 — requires a
 * still-valid action_token minted by SuperAdminPinService::verify() moments earlier. Accepts the
 * token via the `X-Action-Token` header (JSON API endpoints, matches API_SPEC.md §13 literally,
 * e.g. `DELETE /admin/internships/{id}`) or an `action_token` POST field (traditional form
 * endpoints that predate this phase, e.g. the Super Admin evaluation-override form) — both paths
 * verify the exact same session-held token, just carried differently depending on how that
 * endpoint already talks to the browser.
 *
 * Throws AuthException (not ApiException) so it plugs into both endpoint styles unchanged: JSON
 * controllers translate it to a 403 in their own respondJson() catch block, traditional-form
 * controllers already catch AuthException for their normal flash-and-redirect error path.
 */
final class ActionTokenGuard
{
    public static function require(): void
    {
        $token = $_SERVER['HTTP_X_ACTION_TOKEN'] ?? ($_POST['action_token'] ?? '');
        if (!is_string($token) || $token === '' || !Session::consumeActionToken($token)) {
            throw new AuthException('ACTION_TOKEN_REQUIRED', 'ต้องยืนยันตัวตนด้วย PIN ก่อนทำรายการนี้ (token หมดอายุ ไม่ถูกต้อง หรือถูกใช้ไปแล้ว)');
        }
    }
}
