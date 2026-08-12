<?php

namespace App\Controllers;

use App\Services\AuditLogger;
use App\Services\AuthException;
use App\Services\OrgService;
use App\Services\TeacherService;
use App\Support\Session;

/** Admin creates teacher accounts (RULE-AUTH-02) — prerequisite for Phase 4 matching. */
final class TeacherController
{
    private TeacherService $teachers;

    public function __construct()
    {
        $this->teachers = new TeacherService();
    }

    /** GET /admin/teachers/new loader. */
    public function newTeacherFormData(array $params): array
    {
        return [
            'faculties' => (new OrgService())->listFacultiesWithDepartments(),
            'flashError' => Session::pullFlashError(),
            'old' => [],
        ];
    }

    public function store(array $params): void
    {
        try {
            $this->teachers->createTeacher($_POST);
            $user = Session::user();
            (new AuditLogger())->log((string) $user['id'], (string) $user['role'], 'create', 'teachers', 'teachers', null, null, ['email' => $_POST['email'] ?? '']);
            header('Location: /admin/users');
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
            header('Location: /admin/teachers/new');
        }
        exit;
    }
}
