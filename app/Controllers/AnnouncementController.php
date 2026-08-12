<?php

namespace App\Controllers;

use App\Services\AnnouncementService;
use App\Services\AuditLogger;
use App\Services\AuthException;
use App\Support\Session;

/** /admin/announcements — manage announcements (SITEMAP.md §5, Phase 11). Not RULE-SEC-01-gated (not internships/evaluations/companies/users). */
final class AnnouncementController
{
    private AnnouncementService $announcements;

    public function __construct()
    {
        $this->announcements = new AnnouncementService();
    }

    public function listData(array $params): array
    {
        return [
            'announcements' => $this->announcements->listAll(),
            'formError' => Session::pullFlashError(),
        ];
    }

    public function store(array $params): void
    {
        $user = Session::user();
        try {
            $this->announcements->create($_POST, (string) $user['id']);
            (new AuditLogger())->log((string) $user['id'], (string) $user['role'], 'create', 'announcements', 'announcements', null, null, ['title' => $_POST['title'] ?? '']);
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
        }
        header('Location: /admin/announcements');
        exit;
    }

    public function destroy(array $params): void
    {
        $user = Session::user();
        $this->announcements->delete((int) $params['id']);
        (new AuditLogger())->log((string) $user['id'], (string) $user['role'], 'delete', 'announcements', 'announcements', (int) $params['id'], null, null);
        header('Location: /admin/announcements');
        exit;
    }
}
