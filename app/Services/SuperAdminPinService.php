<?php

namespace App\Services;

use App\Support\Session;

/**
 * super_admin_pins (Phase 10, SECURITY.md §6, RULE-SEC-01). PIN is 6 digits, hashed with bcrypt,
 * entirely separate from the Supabase Auth login password (DATABASE.md §2.4). A successful
 * verify() mints a short-lived action_token stored server-side in the current session
 * (App\Support\Session::setActionToken()) — see DATABASE.md §2.4 for why there's no separate
 * action-token table.
 */
final class SuperAdminPinService
{
    private const LOCKOUT_MINUTES = 30;
    private const ACTION_TOKEN_TTL_SECONDS = 300; // API_SPEC.md §13: expires_in 300

    private SupabaseClient $client;
    private SettingsService $settings;

    public function __construct(?SupabaseClient $client = null)
    {
        $this->client = $client ?? new SupabaseClient();
        $this->settings = new SettingsService($this->client);
    }

    public function hasPin(string $userId): bool
    {
        $rows = $this->client->restGet('super_admin_pins', 'user_id=eq.' . $userId . '&select=user_id');
        return !empty($rows);
    }

    /** First-time PIN setup only — SECURITY.md §6 ("ตั้งครั้งแรกตอนสร้างบัญชี"), no reset path yet (see ISSUES.md). */
    public function setup(string $userId, string $pin, string $confirmPin): void
    {
        if ($this->hasPin($userId)) {
            throw new AuthException('PIN_ALREADY_SET', 'ตั้งค่า PIN ไปแล้ว ไม่สามารถตั้งใหม่ผ่านหน้านี้ได้');
        }
        $this->assertValidPinFormat($pin);
        if ($pin !== $confirmPin) {
            throw new AuthException('VALIDATION_ERROR', 'PIN ทั้งสองช่องไม่ตรงกัน');
        }
        $this->client->restInsert('super_admin_pins', [
            'user_id' => $userId,
            'pin_hash' => password_hash($pin, PASSWORD_BCRYPT),
        ]);
    }

    /** @return string the newly issued action_token */
    public function verify(string $userId, string $pin): string
    {
        $rows = $this->client->restGet('super_admin_pins', 'user_id=eq.' . $userId . '&select=*');
        if (!isset($rows[0])) {
            throw new AuthException('PIN_NOT_SET', 'บัญชีนี้ยังไม่ได้ตั้งค่า PIN กรุณาตั้งค่าก่อนใช้งาน');
        }
        $row = $rows[0];

        if ($row['locked_until'] !== null && strtotime((string) $row['locked_until']) > time()) {
            $minutesLeft = (int) ceil((strtotime((string) $row['locked_until']) - time()) / 60);
            throw new AuthException('PIN_LOCKED', "ยืนยัน PIN ถูกล็อกชั่วคราว กรุณาลองใหม่ในอีก {$minutesLeft} นาที");
        }

        if (!password_verify($pin, (string) $row['pin_hash'])) {
            $maxAttempts = $this->settings->getInt('super_admin_pin_attempts', 3);
            $attempts = (int) $row['failed_attempts'] + 1;
            if ($attempts >= $maxAttempts) {
                $this->client->restUpdate('super_admin_pins', 'user_id=eq.' . $userId, [
                    'failed_attempts' => 0,
                    'locked_until' => date('c', time() + self::LOCKOUT_MINUTES * 60),
                ]);
                $this->notifyAllSuperAdminsOfLockout();
                throw new AuthException('PIN_LOCKED', "กรอก PIN ผิดครบ {$maxAttempts} ครั้งติดต่อกัน — ถูกล็อกชั่วคราว 30 นาที");
            }
            $this->client->restUpdate('super_admin_pins', 'user_id=eq.' . $userId, ['failed_attempts' => $attempts]);
            throw new AuthException('INVALID_PIN', 'PIN ไม่ถูกต้อง');
        }

        if ((int) $row['failed_attempts'] !== 0) {
            $this->client->restUpdate('super_admin_pins', 'user_id=eq.' . $userId, ['failed_attempts' => 0]);
        }

        $token = bin2hex(random_bytes(32));
        Session::setActionToken($token, time() + self::ACTION_TOKEN_TTL_SECONDS);
        return $token;
    }

    /** SECURITY.md §6: notify every super_admin on lockout — substituted with in-app notifications (Phase 9) since no SMTP is configured yet (see ISSUES.md). */
    private function notifyAllSuperAdminsOfLockout(): void
    {
        try {
            $admins = $this->client->restGet('users', 'role=eq.super_admin&status=eq.active&select=id');
            $notifications = new NotificationService($this->client);
            foreach ($admins as $a) {
                $notifications->send(
                    (string) $a['id'],
                    'pin_locked',
                    'บัญชี Super Admin ถูกล็อกการยืนยัน PIN',
                    'มีการกรอก PIN ผิดครบจำนวนครั้งที่กำหนด การยืนยัน PIN ถูกล็อกชั่วคราว 30 นาที',
                    '/super-admin/dashboard'
                );
            }
        } catch (\Throwable) {
            // best-effort only, matching AuditLogger's own never-throw stance (Phase 9)
        }
    }

    private function assertValidPinFormat(string $pin): void
    {
        if (!preg_match('/^\d{6}$/', $pin)) {
            throw new AuthException('VALIDATION_ERROR', 'PIN ต้องเป็นตัวเลข 6 หลักเท่านั้น');
        }
    }
}
