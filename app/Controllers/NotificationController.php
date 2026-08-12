<?php

namespace App\Controllers;

use App\Services\NotificationService;
use App\Services\SupabaseClient;
use App\Support\Session;

/** notifications (Phase 9 item 1) — shared across every role. */
final class NotificationController
{
    private NotificationService $notifications;

    public function __construct()
    {
        $this->notifications = new NotificationService();
    }

    /** GET /{role}/notifications loader. */
    public function listPageData(array $params): array
    {
        $userId = (string) Session::user()['id'];
        return ['allNotifications' => $this->notifications->listAllForUser($userId)];
    }

    /** GET /notifications/{id}/read — marks read then navigates to the notification's link (same GET-navigate pattern as /logout). */
    public function markRead(array $params): void
    {
        $userId = (string) Session::user()['id'];
        $id = (int) $params['id'];
        $this->notifications->markRead($id, $userId);

        $rows = (new SupabaseClient())->restGet('notifications', 'id=eq.' . $id . '&user_id=eq.' . $userId . '&select=link_url');
        $target = $rows[0]['link_url'] ?? null;
        header('Location: ' . ($target ?: $this->notificationsListPath()));
        exit;
    }

    public function markAllRead(array $params): void
    {
        $this->notifications->markAllRead((string) Session::user()['id']);
        header('Location: ' . $this->notificationsListPath());
        exit;
    }

    private function notificationsListPath(): string
    {
        $role = (string) Session::user()['role'];
        return '/' . ($role === 'super_admin' ? 'super-admin' : $role) . '/notifications';
    }
}
