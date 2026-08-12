<?php

namespace App\Middleware;

use App\Services\SupabaseClient;
use App\Support\Session;

/**
 * Route guard used by public/index.php right after a GET route is matched, before the view
 * is rendered — implements the `auth` -> `role` -> `status:active` middleware chain from
 * Internship_Project_Blueprint/SITEMAP.md §8. `$requiredRole` is simply the `role` column
 * already present in config/routes.php for that route ('guest' = public, no check).
 */
final class AuthGuard
{
    private const DASHBOARD_BY_ROLE = [
        'student' => '/student/dashboard',
        'company' => '/company/dashboard',
        'teacher' => '/teacher/dashboard',
        'admin' => '/admin/dashboard',
        'super_admin' => '/super-admin/dashboard',
    ];

    public static function requireRole(string $requiredRole): void
    {
        $user = Session::user();

        if ($requiredRole === 'guest') {
            // Logged-in users hitting /login, /register etc. get sent to their own dashboard
            // instead of seeing the guest page again.
            if ($user !== null) {
                self::redirect(self::dashboardPathFor($user['role']));
            }
            return;
        }

        if ($user === null) {
            self::redirect('/login');
        }

        if ($user['status'] === 'pending') {
            self::redirect('/pending-approval');
        }
        if (in_array($user['status'], ['suspended', 'disabled'], true)) {
            Session::destroy();
            self::redirect('/login');
        }

        if (Session::tokenExpired()) {
            self::tryRefreshOrLogout($user);
        }

        // Exact match, or one of a `|`-delimited set (Phase 6: daily_logs/leave_requests review
        // actions are shared by both `company` and `teacher` — PERMISSIONS.md §2.6/§2.7 grants
        // both roles review/decide — rather than duplicating every such route the way
        // admin/super_admin routes are duplicated per-view elsewhere in routes.php).
        $allowedRoles = str_contains($requiredRole, '|') ? explode('|', $requiredRole) : [$requiredRole];
        if (!in_array($user['role'], $allowedRoles, true)) {
            self::forbidden();
        }
    }

    public static function dashboardPathFor(string $role): string
    {
        return self::DASHBOARD_BY_ROLE[$role] ?? '/login';
    }

    private static function tryRefreshOrLogout(array $user): void
    {
        try {
            $resp = (new SupabaseClient())->authRefresh($user['refresh_token']);
            Session::set('access_token', $resp['access_token']);
            Session::set('refresh_token', $resp['refresh_token']);
            Session::set('expires_at', time() + (int) ($resp['expires_in'] ?? 3600));
        } catch (\Throwable) {
            Session::destroy();
            self::redirect('/login');
        }
    }

    private static function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }

    private static function forbidden(): never
    {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="th"><head><meta charset="utf-8"><title>403</title></head>'
            . '<body style="font-family:sans-serif;text-align:center;padding:4rem;">'
            . '<h1>403 — คุณไม่มีสิทธิ์เข้าถึงหน้านี้</h1><p><a href="/login">กลับไปหน้าเข้าสู่ระบบ</a></p></body></html>';
        exit;
    }
}
