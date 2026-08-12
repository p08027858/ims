<?php

namespace App\Controllers;

use App\Services\AuditLogger;
use App\Services\AuthException;
use App\Services\SuperAdminAdminService;
use App\Support\Session;

/** /super-admin/admins — manage Admin accounts (Phase 10 item 4). */
final class SuperAdminAdminController
{
    private SuperAdminAdminService $admins;

    public function __construct()
    {
        $this->admins = new SuperAdminAdminService();
    }

    /** GET /super-admin/admins loader. */
    public function listData(array $params): array
    {
        $rows = $this->admins->listAdmins();
        return [
            'admins' => array_map(static fn (array $u) => [
                'id' => $u['id'],
                'username' => $u['email'],
                'scope' => $u['role'] === 'super_admin' ? 'Super Admin (ทุกคณะ)' : 'Admin',
                'status' => $u['status'],
                'isSuperAdmin' => $u['role'] === 'super_admin',
            ], $rows),
            'formError' => Session::pullFlashError(),
        ];
    }

    public function store(array $params): void
    {
        $user = Session::user();
        try {
            $email = (string) ($_POST['email'] ?? '');
            $newId = $this->admins->create($email, (string) ($_POST['password'] ?? ''));
            (new AuditLogger())->log((string) $user['id'], (string) $user['role'], 'create', 'super_admin_console', 'users', null, null, ['user_id' => $newId, 'email' => $email, 'role' => 'admin']);
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
        }
        header('Location: /super-admin/admins');
        exit;
    }

    public function setStatus(array $params): void
    {
        $user = Session::user();
        try {
            $status = ($_POST['status'] ?? '') === 'suspended' ? 'suspended' : 'active';
            $this->admins->setStatus((string) $params['id'], $status);
            (new AuditLogger())->log((string) $user['id'], (string) $user['role'], 'update', 'super_admin_console', 'users', null, null, ['user_id' => $params['id'], 'status' => $status]);
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
        }
        header('Location: /super-admin/admins');
        exit;
    }
}
