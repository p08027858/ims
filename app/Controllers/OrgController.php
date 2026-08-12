<?php

namespace App\Controllers;

use App\Services\AuditLogger;
use App\Services\AuthException;
use App\Services\OrgService;
use App\Support\Session;

/** Faculties & departments CRUD (AI_AGENT_PHASES.md Phase 3 item 1). */
final class OrgController
{
    private OrgService $org;

    public function __construct()
    {
        $this->org = new OrgService();
    }

    /**
     * GET /register loader (config/view_data.php). registerError surfaces the flash set by
     * public/index.php's central CSRF check (Phase 12) on a rejected submission.
     */
    public function registerFacultiesData(array $params): array
    {
        return [
            'faculties' => $this->org->listFacultiesWithDepartments(),
            'registerError' => Session::pullFlashError(),
        ];
    }

    /** GET /admin/organization loader. */
    public function organizationPageData(array $params): array
    {
        return [
            'faculties' => $this->org->listFacultiesWithDepartments(),
            'flashError' => Session::pullFlashError(),
        ];
    }

    public function createFaculty(array $params): void
    {
        $this->runAndRedirect(function () {
            $this->org->createFaculty($_POST);
            $user = Session::user();
            (new AuditLogger())->log((string) $user['id'], (string) $user['role'], 'create', 'organization', 'faculties', null, null, ['name' => $_POST['name'] ?? '']);
        });
    }

    public function toggleFacultyStatus(array $params): void
    {
        $status = (string) ($_POST['status'] ?? '');
        $this->runAndRedirect(function () use ($params, $status) {
            $this->org->setFacultyStatus((int) $params['id'], $status);
            $user = Session::user();
            (new AuditLogger())->log((string) $user['id'], (string) $user['role'], 'update', 'organization', 'faculties', (int) $params['id'], null, ['status' => $status]);
        });
    }

    public function createDepartment(array $params): void
    {
        $this->runAndRedirect(function () {
            $this->org->createDepartment($_POST);
            $user = Session::user();
            (new AuditLogger())->log((string) $user['id'], (string) $user['role'], 'create', 'organization', 'departments', null, null, ['name' => $_POST['name'] ?? '']);
        });
    }

    public function toggleDepartmentStatus(array $params): void
    {
        $status = (string) ($_POST['status'] ?? '');
        $this->runAndRedirect(function () use ($params, $status) {
            $this->org->setDepartmentStatus((int) $params['id'], $status);
            $user = Session::user();
            (new AuditLogger())->log((string) $user['id'], (string) $user['role'], 'update', 'organization', 'departments', (int) $params['id'], null, ['status' => $status]);
        });
    }

    private function runAndRedirect(callable $action): void
    {
        try {
            $action();
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
        }
        header('Location: /admin/organization');
        exit;
    }
}
