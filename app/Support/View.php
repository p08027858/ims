<?php

namespace App\Support;

use App\Services\NotificationService;

/**
 * Shared rendering path so `public/index.php` (GET routes) and controllers (POST actions that
 * re-render a form with an error, e.g. AuthController::login on bad credentials) produce
 * pixel-identical output instead of two copies of the same layout-wiring logic.
 */
final class View
{
    /**
     * @param array<string, mixed> $data Extra variables made available to the view
     *   (e.g. `loginError`, `old`, `faculties`) plus optional `pageTitle`/`activeNav` overrides.
     */
    public static function render(string $viewName, string $role, array $data = []): never
    {
        $pageTitle = $data['pageTitle'] ?? 'IMS THAI';
        $activeNav = $data['activeNav'] ?? '';
        $contentView = __DIR__ . '/../Views/' . $viewName . '.php';

        $sessionUser = Session::user();
        $user = $sessionUser ?? ($data['user'] ?? null);

        // Phase 9: real notification dropdown data on EVERY page render (not just specific
        // routes) since partials/topbar.php's bell icon is part of the universal layout —
        // living here (not public/index.php) means POST-triggered re-renders (e.g. a failed
        // login re-showing the form) get real data too, not just the GET-route dispatch path.
        $unreadNotifications = 0;
        $notifications = [];
        if ($sessionUser !== null) {
            try {
                $notificationService = new NotificationService();
                $notifications = $notificationService->listRecentForDropdown((string) $sessionUser['id']);
                $unreadNotifications = $notificationService->countUnread((string) $sessionUser['id']);
            } catch (\Throwable) {
                // never let a notification-fetch failure break page rendering
            }
        }
        // A route's own loader (e.g. the "all notifications" list page itself) may still override.
        $unreadNotifications = $data['unreadNotifications'] ?? $unreadNotifications;
        $notifications = $data['notifications'] ?? $notifications;

        // Make any remaining controller-supplied keys (loginError, registerError, old, message,
        // faculties, ...) available to the view under their own names.
        unset($data['pageTitle'], $data['activeNav'], $data['unreadNotifications'], $data['user'], $data['notifications']);
        extract($data, EXTR_SKIP);

        require __DIR__ . '/../Views/layouts/app.php';
        exit;
    }
}
