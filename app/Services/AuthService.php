<?php

namespace App\Services;

use App\Support\Session;

/**
 * Business logic for Phase 2 (AI_AGENT_PHASES.md) — implements RULE-AUTH-01..05 (RULES.md §2)
 * on top of SupabaseClient. This is the ONLY place that should decide what counts as a
 * successful/failed login, so the lockout counter and login_logs stay consistent everywhere.
 */
final class AuthService
{
    private SupabaseClient $client;

    public function __construct(?SupabaseClient $client = null)
    {
        $this->client = $client ?? new SupabaseClient();
    }

    /**
     * RULE-AUTH-01: student self-registration. Always lands in status=pending — the
     * handle_new_user() trigger enforces that regardless of what we send (DATABASE.md §11.2).
     *
     * @param array{student_code:string, full_name:string, citizen_id:string, faculty_id:string|int,
     *              department_id:string|int, year_level:string|int, email:string,
     *              password:string, password_confirmation:string} $data
     * @return array{user_id:string, status:string}
     */
    public function register(array $data): array
    {
        // citizen_id added post-1.0.0 (migration 003) — found missing while reviewing
        // "IMS Prototype.dc.html" (design mockup) against this real build; required here to
        // match the mockup's intent, not just accepted-if-present, per user confirmation.
        $required = ['student_code', 'full_name', 'citizen_id', 'faculty_id', 'department_id', 'year_level', 'email', 'password'];
        foreach ($required as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                throw new AuthException('VALIDATION_ERROR', 'กรุณากรอกข้อมูลให้ครบทุกช่อง');
            }
        }
        // RULE-AUTH-04: minimum 8 chars, mixed letters+numbers — mirrors the client-side
        // strength meter in register.php but must be re-checked server-side (RULES.md §1).
        if (strlen($data['password']) < 8 || !preg_match('/[A-Za-z]/', $data['password']) || !preg_match('/[0-9]/', $data['password'])) {
            throw new AuthException('VALIDATION_ERROR', 'รหัสผ่านต้องยาวอย่างน้อย 8 ตัวอักษร และมีทั้งตัวอักษรและตัวเลข');
        }
        if (($data['password_confirmation'] ?? '') !== $data['password']) {
            throw new AuthException('VALIDATION_ERROR', 'รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน');
        }
        $citizenId = preg_replace('/\D/', '', (string) $data['citizen_id']);
        if (!self::validateThaiCitizenId($citizenId)) {
            throw new AuthException('VALIDATION_ERROR', 'เลขบัตรประชาชนไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง');
        }

        $existing = $this->client->restGet('students', 'student_code=eq.' . rawurlencode($data['student_code']) . '&select=id');
        if (count($existing) > 0) {
            throw new AuthException('VALIDATION_ERROR', 'รหัสนักศึกษานี้มีอยู่ในระบบแล้ว');
        }
        $existingCitizen = $this->client->restGet('students', 'citizen_id=eq.' . rawurlencode($citizenId) . '&select=id');
        if (count($existingCitizen) > 0) {
            throw new AuthException('VALIDATION_ERROR', 'เลขบัตรประชาชนนี้มีอยู่ในระบบแล้ว');
        }

        [$firstName, $lastName] = self::splitFullName($data['full_name']);

        // Uses the admin-create endpoint (email_confirm=true) rather than the public signup
        // endpoint, even for student self-registration: RULE-AUTH-01's single intended gate is
        // our own Admin approval (users.status pending->active) — Supabase's separate "confirm
        // your email" flow would otherwise silently block login even after Admin approves,
        // since signInWithPassword rejects unconfirmed accounts. Confirmed by manual testing
        // 2026-07-30: a real signUp() call left email_confirmed_at null and every subsequent
        // login attempt failed at Supabase, burning through the RULE-AUTH-03 lockout counter
        // while showing a misleading "wrong password" message. This call is safe server-side
        // only (service_role key, never exposed to the browser — DATABASE.md §0).
        $signUpResp = $this->client->authAdminCreateUser($data['email'], $data['password'], ['role' => 'student']);
        $userId = $signUpResp['id'] ?? $signUpResp['user']['id'] ?? null;
        if ($userId === null) {
            throw new AuthException('VALIDATION_ERROR', 'สมัครสมาชิกไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }

        try {
            $this->client->restInsert('students', [
                'user_id' => $userId,
                'student_code' => $data['student_code'],
                'citizen_id' => $citizenId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'faculty_id' => (int) $data['faculty_id'],
                'department_id' => (int) $data['department_id'],
                'year_level' => (int) $data['year_level'],
            ]);
        } catch (SupabaseException $e) {
            // Roll back the orphaned auth.users row (cascades to public.users) so a failed
            // registration doesn't leave a half-created account behind.
            $this->client->authAdminDeleteUser($userId);
            throw new AuthException('VALIDATION_ERROR', 'สมัครสมาชิกไม่สำเร็จ (ข้อมูลนักศึกษาไม่ถูกต้องหรือซ้ำ)', ['cause' => $e->getMessage()]);
        }

        return ['user_id' => $userId, 'status' => 'pending'];
    }

    /**
     * RULE-AUTH-03: 5 wrong attempts locks the account for 15 minutes — checked BEFORE ever
     * calling Supabase, since GoTrue itself has no concept of this. Accepts either an email or
     * a student_code as $identifier (DATABASE.md §0.1).
     *
     * @return array{access_token:string, refresh_token:string, expires_at:int, id:string,
     *               role:string, status:string, email:string, first_name:string,
     *               avatar_initial:string, company_id?:int, company_name?:string,
     *               faculty_name?:string}
     */
    public function login(string $identifier, string $password, string $ip): array
    {
        $identifier = trim($identifier);
        $email = str_contains($identifier, '@') ? $identifier : $this->resolveEmailFromStudentCode($identifier);

        if ($email === null) {
            $this->logAttempt(null, $identifier, $ip, 'failed_password');
            throw new AuthException('INVALID_CREDENTIALS', 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง');
        }

        $record = $this->loadUserRecord($email);
        if ($record === null) {
            $this->logAttempt(null, $identifier, $ip, 'failed_password');
            throw new AuthException('INVALID_CREDENTIALS', 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง');
        }

        if ($record['status'] === 'pending') {
            $this->logAttempt($record['id'], $identifier, $ip, 'failed_disabled');
            throw new AuthException('ACCOUNT_PENDING', 'บัญชีของคุณอยู่ระหว่างการอนุมัติ');
        }
        if (in_array($record['status'], ['suspended', 'disabled'], true)) {
            $this->logAttempt($record['id'], $identifier, $ip, 'failed_disabled');
            throw new AuthException('ACCOUNT_DISABLED', 'บัญชีนี้ถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ');
        }

        $lockoutMinutes = $this->getSettingInt('failed_login_lockout_minutes', 15);
        if (!empty($record['locked_until']) && strtotime($record['locked_until']) > time()) {
            $this->logAttempt($record['id'], $identifier, $ip, 'failed_locked');
            $remaining = (int) ceil((strtotime($record['locked_until']) - time()) / 60);
            throw new AuthException('ACCOUNT_LOCKED', "บัญชีถูกล็อกชั่วคราว ลองใหม่ในอีก {$remaining} นาที", ['minutes_remaining' => $remaining]);
        }

        try {
            $tokenResp = $this->client->authSignIn($email, $password);
        } catch (SupabaseException $e) {
            // Defense-in-depth: register() now always auto-confirms email (see comment there),
            // so this should never fire for new accounts — kept in case older/manually-created
            // rows exist without email_confirmed_at set. Must NOT count against RULE-AUTH-03's
            // lockout, since it isn't a wrong-password event.
            if ($e->errorCode() === 'email_not_confirmed') {
                $this->logAttempt($record['id'], $identifier, $ip, 'failed_disabled');
                throw new AuthException('ACCOUNT_DISABLED', 'บัญชีนี้ยังไม่ได้ยืนยันอีเมล กรุณาติดต่อผู้ดูแลระบบ');
            }
            $this->handleFailedPassword($record, $identifier, $ip); // always throws AuthException
        }

        // Success: reset the lockout counter and record the login.
        $this->client->restUpdate('users', 'id=eq.' . $record['id'], [
            'failed_login_count' => 0,
            'locked_until' => null,
            'last_login_at' => date('c'),
            'last_login_ip' => $ip,
        ]);
        $this->logAttempt($record['id'], $identifier, $ip, 'success');

        $profile = $this->loadDisplayProfile($record['role'], $record['id']);

        return array_merge([
            'id' => $record['id'],
            'email' => $record['email'],
            'role' => $record['role'],
            'status' => $record['status'],
            'access_token' => $tokenResp['access_token'],
            'refresh_token' => $tokenResp['refresh_token'],
            'expires_at' => time() + (int) ($tokenResp['expires_in'] ?? 3600),
        ], $profile);
    }

    public function logout(): void
    {
        $user = Session::user();
        if ($user !== null && !empty($user['access_token'])) {
            $this->client->authSignOut($user['access_token']);
        }
        Session::destroy();
    }

    /** Always succeeds from the caller's perspective — never reveals whether the email exists. */
    public function forgotPassword(string $email): void
    {
        try {
            $this->client->authResetPasswordForEmail($email);
        } catch (SupabaseException) {
            // swallow — RULE-SEC: don't leak account existence via timing/response differences
        }
    }

    /** Called from the recovery landing page after Supabase redirects with a recovery access token in the URL fragment. */
    public function resetPassword(string $recoveryAccessToken, string $newPassword): void
    {
        if (strlen($newPassword) < 8 || !preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
            throw new AuthException('VALIDATION_ERROR', 'รหัสผ่านต้องยาวอย่างน้อย 8 ตัวอักษร และมีทั้งตัวอักษรและตัวเลข');
        }

        // TC-AUTH-007: Supabase does NOT invalidate a recovery access_token after first use
        // (confirmed by live testing 2026-07-30 — the same token successfully reset the password
        // twice). We enforce single-use ourselves by remembering the token's `iat` claim per user
        // (public.users.last_password_reset_token_iat, migration 001) and rejecting any token
        // that isn't strictly newer than the last one we've already consumed. Signature
        // verification of the token itself still happens for real at Supabase's /auth/v1/user
        // call below — this check is purely additive bookkeeping on top of that.
        $claims = self::decodeJwtPayload($recoveryAccessToken);
        $userId = $claims['sub'] ?? null;
        $tokenIat = $claims['iat'] ?? null;

        if ($userId !== null && $tokenIat !== null) {
            $rows = $this->client->restGet('users', 'id=eq.' . $userId . '&select=last_password_reset_token_iat');
            $lastUsedIat = $rows[0]['last_password_reset_token_iat'] ?? null;
            if ($lastUsedIat !== null && (int) $tokenIat <= (int) $lastUsedIat) {
                throw new AuthException('TOKEN_ALREADY_USED', 'ลิงก์นี้ถูกใช้ไปแล้ว กรุณาขอลิงก์ใหม่จากหน้าลืมรหัสผ่าน');
            }
        }

        // PUT /auth/v1/user with the user's own recovery bearer token (not service_role) —
        // SupabaseClient's restUpdate/authAdminX methods don't fit this shape, so call directly.
        $config = require __DIR__ . '/../../config/supabase.php';
        $ch = curl_init(rtrim($config['url'], '/') . '/auth/v1/user');
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_HTTPHEADER => [
                'apikey: ' . $config['anon_key'],
                'Authorization: Bearer ' . $recoveryAccessToken,
                'Content-Type: application/json',
            ],
            CURLOPT_USERAGENT => 'IMS-THAI-Backend-PHP/1.0 (server-side; not a browser)',
            CURLOPT_POSTFIELDS => json_encode(['password' => $newPassword]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $raw = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($status >= 400) {
            $body = json_decode((string) $raw, true) ?? [];
            throw new AuthException('VALIDATION_ERROR', $body['msg'] ?? 'ลิงก์รีเซ็ตรหัสผ่านหมดอายุหรือไม่ถูกต้อง กรุณาขอลิงก์ใหม่');
        }

        // Only mark the token as consumed once Supabase has actually accepted the new password —
        // a failed attempt above (e.g. expired token) must not burn a not-yet-used token.
        if ($userId !== null && $tokenIat !== null) {
            $this->client->restUpdate('users', 'id=eq.' . $userId, ['last_password_reset_token_iat' => (int) $tokenIat]);
        }
    }

    // -------------------------------------------------------------------
    // Internal
    // -------------------------------------------------------------------

    private function handleFailedPassword(array $record, string $identifier, string $ip): never
    {
        $threshold = $this->getSettingInt('failed_login_lockout_threshold', 5);
        $lockoutMinutes = $this->getSettingInt('failed_login_lockout_minutes', 15);
        $newCount = ((int) $record['failed_login_count']) + 1;

        $update = ['failed_login_count' => $newCount];
        if ($newCount >= $threshold) {
            $update['locked_until'] = date('c', time() + $lockoutMinutes * 60);
        }
        $this->client->restUpdate('users', 'id=eq.' . $record['id'], $update);

        if ($newCount >= $threshold) {
            $this->logAttempt($record['id'], $identifier, $ip, 'failed_locked');
            throw new AuthException('ACCOUNT_LOCKED', "บัญชีถูกล็อกชั่วคราว ลองใหม่ในอีก {$lockoutMinutes} นาที", ['minutes_remaining' => $lockoutMinutes]);
        }

        $this->logAttempt($record['id'], $identifier, $ip, 'failed_password');
        $remaining = $threshold - $newCount;
        throw new AuthException('INVALID_CREDENTIALS', "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง (เหลืออีก {$remaining} ครั้ง)", ['attempts_remaining' => $remaining]);
    }

    private function resolveEmailFromStudentCode(string $studentCode): ?string
    {
        $rows = $this->client->restGet('students', 'student_code=eq.' . rawurlencode($studentCode) . '&select=user_id');
        if (count($rows) === 0) {
            return null;
        }
        $users = $this->client->restGet('users', 'id=eq.' . $rows[0]['user_id'] . '&select=email');
        return $users[0]['email'] ?? null;
    }

    private function loadUserRecord(string $email): ?array
    {
        $rows = $this->client->restGet(
            'users',
            'email=eq.' . rawurlencode($email) . '&select=id,email,role,status,failed_login_count,locked_until'
        );
        return $rows[0] ?? null;
    }

    private function loadDisplayProfile(string $role, string $userId): array
    {
        $initial = static fn (string $first) => mb_substr($first, 0, 1);

        try {
            switch ($role) {
                case 'student':
                    $rows = $this->client->restGet('students', 'user_id=eq.' . $userId . '&select=first_name,last_name');
                    $firstName = $rows[0]['first_name'] ?? 'นักศึกษา';
                    return ['first_name' => $firstName, 'avatar_initial' => $initial($firstName)];

                case 'teacher':
                    $rows = $this->client->restGet('teachers', 'user_id=eq.' . $userId . '&select=first_name,last_name');
                    $firstName = $rows[0]['first_name'] ?? 'ครูนิเทศ';
                    return ['first_name' => $firstName, 'avatar_initial' => $initial($firstName)];

                case 'company':
                    $rows = $this->client->restGet('company_supervisors', 'user_id=eq.' . $userId . '&select=first_name,last_name,company_id');
                    $firstName = $rows[0]['first_name'] ?? 'ผู้ประกอบการ';
                    $companyName = null;
                    if (!empty($rows[0]['company_id'])) {
                        $companies = $this->client->restGet('companies', 'id=eq.' . $rows[0]['company_id'] . '&select=name');
                        $companyName = $companies[0]['name'] ?? null;
                    }
                    return [
                        'first_name' => $firstName,
                        'avatar_initial' => $initial($firstName),
                        'company_id' => $rows[0]['company_id'] ?? null,
                        'company_name' => $companyName,
                    ];

                default: // admin, super_admin — no dedicated profile table (DATA_DICTIONARY.md §4)
                    return ['first_name' => $role === 'super_admin' ? 'Super Admin' : 'Admin', 'avatar_initial' => 'A'];
            }
        } catch (SupabaseException) {
            return ['first_name' => 'ผู้ใช้งาน', 'avatar_initial' => 'U'];
        }
    }

    private function getSettingInt(string $key, int $default): int
    {
        // TODO Phase 2 hardening: cache settings in APCu per SETTINGS.md §4 instead of a fresh
        // REST call on every login attempt.
        try {
            $rows = $this->client->restGet('settings', 'setting_key=eq.' . rawurlencode($key) . '&select=setting_value');
            return isset($rows[0]['setting_value']) ? (int) $rows[0]['setting_value'] : $default;
        } catch (SupabaseException) {
            return $default;
        }
    }

    private function logAttempt(?string $userId, string $identifier, string $ip, string $result): void
    {
        try {
            $this->client->restInsert('login_logs', [
                'user_id' => $userId,
                'username_attempt' => $identifier,
                'ip_address' => $ip,
                'result' => $result,
            ]);
        } catch (SupabaseException) {
            // Never let audit logging failure block the auth flow itself.
        }
    }

    /** @return array{0:string,1:string} [first_name, last_name] */
    private static function splitFullName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), 2);
        return [$parts[0], $parts[1] ?? ''];
    }

    /**
     * Standard Thai national ID mod-11 checksum: sum of digit[i] * (13-i) for i=0..11, check
     * digit = (11 - (sum mod 11)) mod 10, must equal digit[12]. Added post-1.0.0 (migration 003)
     * — see AuthService::register()'s docblock for why.
     */
    private static function validateThaiCitizenId(string $id): bool
    {
        if (!preg_match('/^\d{13}$/', $id)) {
            return false;
        }
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $id[$i] * (13 - $i);
        }
        $checkDigit = (11 - ($sum % 11)) % 10;
        return $checkDigit === (int) $id[12];
    }

    /**
     * Decodes a JWT payload without verifying its signature. Safe here because the claims are
     * only used for our own reuse-tracking bookkeeping (resetPassword()) — Supabase itself still
     * cryptographically verifies the token when we PUT /auth/v1/user with it, right after this.
     *
     * @return array<string,mixed>
     */
    private static function decodeJwtPayload(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return [];
        }
        $padded = strtr($parts[1], '-_', '+/') . str_repeat('=', (4 - strlen($parts[1]) % 4) % 4);
        $payload = base64_decode($padded, true);
        if ($payload === false) {
            return [];
        }
        $decoded = json_decode($payload, true);
        return is_array($decoded) ? $decoded : [];
    }
}
