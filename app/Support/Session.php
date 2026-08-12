<?php

namespace App\Support;

/**
 * Thin wrapper around PHP's native session — holds the signed-in user snapshot
 * (uuid, role, status, display fields) plus the Supabase access/refresh tokens.
 * TODO Phase 2 hardening: move to a server-side session store (DB/Redis) if this
 * ever runs across multiple PHP-FPM workers behind a load balancer — file-based
 * sessions are fine for a single-instance deployment (DEPLOYMENT.md §5).
 */
final class Session
{
    private const KEY = 'ims_user';

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => (($_SERVER['HTTPS'] ?? '') !== ''), // true once served over HTTPS (DEPLOYMENT.md §5)
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }

    /**
     * @param array{
     *   id:string, role:string, status:string, email:string,
     *   access_token:string, refresh_token:string, expires_at:int,
     *   first_name?:string, avatar_initial?:string, company_id?:int,
     *   company_name?:string, faculty_id?:int, faculty_name?:string
     * } $user
     */
    public static function login(array $user): void
    {
        self::start();
        session_regenerate_id(true); // RULE-AUTH: prevent session fixation across the login boundary
        $_SESSION[self::KEY] = $user;
    }

    public static function user(): ?array
    {
        self::start();
        return $_SESSION[self::KEY] ?? null;
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[self::KEY][$key] = $value;
    }

    public static function isLoggedIn(): bool
    {
        return self::user() !== null;
    }

    /** RULE-AUTH-05: access token TTL — true once the cached token is past its expiry. */
    public static function tokenExpired(): bool
    {
        $user = self::user();
        return $user === null || time() >= ($user['expires_at'] ?? 0);
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    /** One-shot flash data for re-rendering a form with an error after a redirect-free POST. */
    public static function flashError(string $message): void
    {
        self::start();
        $_SESSION['ims_flash_error'] = $message;
    }

    public static function pullFlashError(): ?string
    {
        self::start();
        $message = $_SESSION['ims_flash_error'] ?? null;
        unset($_SESSION['ims_flash_error']);
        return $message;
    }

    /**
     * One-shot flash data for a redirect-then-render result (e.g. CSV import summary) — separate
     * from set() above, which writes into the logged-in user snapshot on purpose (access_token
     * refresh etc.) and would be the wrong place for unrelated request-scoped data like this.
     */
    public static function flashData(string $key, array $value): void
    {
        self::start();
        $_SESSION['ims_flash_data'][$key] = $value;
    }

    public static function pullFlashData(string $key): ?array
    {
        self::start();
        $value = $_SESSION['ims_flash_data'][$key] ?? null;
        unset($_SESSION['ims_flash_data'][$key]);
        return $value;
    }

    /**
     * Short-lived action token (SECURITY.md §6, Phase 10) proving the current super_admin session
     * just verified their PIN — kept server-side here rather than a DB table (see DATABASE.md §2.4
     * for why): same trust boundary as access_token/refresh_token above, no extra round-trip
     * needed to check a token that only ever lives 5 minutes.
     */
    public static function setActionToken(string $token, int $expiresAt): void
    {
        self::start();
        $_SESSION['ims_action_token'] = ['token' => $token, 'expires_at' => $expiresAt];
    }

    /** Single-use: the stored token is cleared on every call regardless of outcome. */
    public static function consumeActionToken(string $providedToken): bool
    {
        self::start();
        $stored = $_SESSION['ims_action_token'] ?? null;
        unset($_SESSION['ims_action_token']);
        if ($stored === null || $providedToken === '') {
            return false;
        }
        if (time() >= $stored['expires_at']) {
            return false;
        }
        return hash_equals($stored['token'], $providedToken);
    }

    /**
     * CSRF token (SECURITY.md §3, Phase 12 Go-Live checklist — ISSUES.md had flagged this as
     * "accepted for now, same-origin only" but required before real production). One token per
     * session (not per-form/single-use like the action_token above) — reused across every form
     * for the session's lifetime, validated centrally in public/index.php's action dispatcher
     * before any controller runs, same choke point AuthGuard already uses.
     */
    public static function csrfToken(): string
    {
        self::start();
        if (!isset($_SESSION['ims_csrf_token'])) {
            $_SESSION['ims_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['ims_csrf_token'];
    }

    public static function validateCsrfToken(string $providedToken): bool
    {
        self::start();
        $stored = $_SESSION['ims_csrf_token'] ?? null;
        return $stored !== null && $providedToken !== '' && hash_equals($stored, $providedToken);
    }

    /**
     * Sliding-window rate limit (API_SPEC.md §15, Phase 12/TC-PERF-002) — per-session counter,
     * good enough for this single-instance PHP dev-server deployment (DEPLOYMENT.md §5 already
     * notes sessions aren't shared across workers, same caveat applies here).
     */
    public static function checkRateLimit(string $key, int $maxRequests, int $windowSeconds): bool
    {
        self::start();
        $now = time();
        $bucket = $_SESSION['ims_rate_limit'][$key] ?? [];
        $bucket = array_values(array_filter($bucket, static fn (int $ts): bool => $ts > $now - $windowSeconds));
        if (count($bucket) >= $maxRequests) {
            $_SESSION['ims_rate_limit'][$key] = $bucket;
            return false;
        }
        $bucket[] = $now;
        $_SESSION['ims_rate_limit'][$key] = $bucket;
        return true;
    }
}
