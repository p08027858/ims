<?php

namespace App\Controllers;

use App\Services\AuditLogger;
use App\Services\AuthException;
use App\Services\CompanyService;
use App\Services\SettingsService;
use App\Support\Session;
use App\Support\View;

/**
 * Admin-side company + pending-approval management (AI_AGENT_PHASES.md Phase 3 items 2 & 4).
 * Company-side GPS self-service lives in CompanyProfileController (item 3).
 */
final class CompanyController
{
    private CompanyService $companies;

    public function __construct()
    {
        $this->companies = new CompanyService();
    }

    /** GET /admin/companies and GET /admin/users loader (both render admin/pending_approvals.php). */
    public function pendingApprovalsData(array $params): array
    {
        return [
            // Named `approvalItems`, not `items` — see the docblock at the top of
            // admin/pending_approvals.php for why `items` collides with sidebar.php.
            'approvalItems' => $this->companies->listPendingApprovals(),
            'flashError' => Session::pullFlashError(),
        ];
    }

    /** GET /admin/users/{id} loader — admin/user_approval_detail.php's single-record view. */
    public function userApprovalDetailData(array $params): array
    {
        $userId = $params['id'];
        $rows = $this->companies->listPendingApprovals();
        foreach ($rows as $row) {
            if ((string) $row['id'] === $userId) {
                return ['user' => ['id' => $userId, 'name' => $row['name'], 'faculty' => $row['meta'], 'student_code' => '']];
            }
        }
        return ['user' => ['id' => $userId, 'name' => 'ไม่พบข้อมูล', 'faculty' => '', 'student_code' => '']];
    }

    public function newCompanyFormData(array $params): array
    {
        return [
            'maxRadiusM' => (new SettingsService())->getInt('max_gps_radius_m', 500),
            'flashError' => Session::pullFlashError(),
        ];
    }

    public function store(array $params): void
    {
        $company = [
            'name' => $_POST['name'] ?? '', 'tax_id' => $_POST['tax_id'] ?? '',
            'address' => $_POST['address'] ?? '', 'subdistrict' => $_POST['subdistrict'] ?? '',
            'district' => $_POST['district'] ?? '', 'province' => $_POST['province'] ?? '',
            'postcode' => $_POST['postcode'] ?? '', 'latitude' => $_POST['latitude'] ?? '',
            'longitude' => $_POST['longitude'] ?? '', 'gps_radius_m' => $_POST['gps_radius_m'] ?? '100',
            'phone' => $_POST['phone'] ?? '', 'email' => $_POST['company_email'] ?? '',
            'website' => $_POST['website'] ?? '', 'industry_type' => $_POST['industry_type'] ?? '',
        ];
        $supervisor = [
            'email' => $_POST['supervisor_email'] ?? '', 'password' => $_POST['supervisor_password'] ?? '',
            'first_name' => $_POST['supervisor_first_name'] ?? '', 'last_name' => $_POST['supervisor_last_name'] ?? '',
            'position' => $_POST['supervisor_position'] ?? '', 'phone' => $_POST['supervisor_phone'] ?? '',
        ];

        try {
            $this->companies->createCompanyWithSupervisor($company, $supervisor);
            $adminUser = Session::user();
            (new AuditLogger())->log((string) $adminUser['id'], (string) $adminUser['role'], 'create', 'companies', 'companies', null, null, ['name' => $company['name']]);
            header('Location: /admin/companies');
            exit;
        } catch (AuthException $e) {
            View::render('admin/company_form', 'admin', [
                'pageTitle' => 'เพิ่มสถานประกอบการ - IMS Admin',
                'activeNav' => 'companies',
                'formError' => $e->getMessage(),
                'maxRadiusM' => (new SettingsService())->getInt('max_gps_radius_m', 500),
                'old' => $_POST,
            ]);
        }
    }

    public function approveCompany(array $params): void
    {
        $this->runAndRedirect(function () use ($params) {
            $status = (string) ($_POST['status'] ?? '');
            $status = $status === 'rejected' ? 'rejected' : 'approved';
            $this->companies->setCompanyStatus((int) $params['id'], $status, (string) Session::user()['id']);
            $user = Session::user();
            (new AuditLogger())->log((string) $user['id'], (string) $user['role'], $status === 'rejected' ? 'reject' : 'approve', 'companies', 'companies', (int) $params['id'], null, ['status' => $status]);
        });
    }

    public function approveUser(array $params): void
    {
        $this->runAndRedirect(function () use ($params) {
            $decision = ($_POST['status'] ?? '') === 'rejected' ? 'rejected' : 'approved';
            $this->companies->approveOrRejectUser((string) $params['id'], $decision);
            $user = Session::user();
            (new AuditLogger())->log((string) $user['id'], (string) $user['role'], $decision === 'rejected' ? 'reject' : 'approve', 'users', 'users', null, null, ['user_id' => $params['id'], 'status' => $decision]);
        }, '/admin/users');
    }

    /** admin/user_approval_detail.php posts here with a hidden `id` field instead of a URL segment. */
    public function approveUserFromDetail(array $params): void
    {
        $userId = (string) ($_POST['id'] ?? '');
        $this->runAndRedirect(function () use ($userId) {
            $decision = ($_POST['status'] ?? '') === 'rejected' ? 'rejected' : 'approved';
            $this->companies->approveOrRejectUser($userId, $decision);
            $user = Session::user();
            (new AuditLogger())->log((string) $user['id'], (string) $user['role'], $decision === 'rejected' ? 'reject' : 'approve', 'users', 'users', null, null, ['user_id' => $userId, 'status' => $decision]);
        }, '/admin/users');
    }

    private function runAndRedirect(callable $action, string $redirectTo = '/admin/companies'): void
    {
        try {
            $action();
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
        }
        header('Location: ' . $redirectTo);
        exit;
    }
}
