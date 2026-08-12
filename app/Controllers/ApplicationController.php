<?php

namespace App\Controllers;

use App\Services\ApplicationService;
use App\Services\AuditLogger;
use App\Services\AuthException;
use App\Support\Session;

/** internship_applications — student search/apply + company accept/reject (Phase 4 item 1). */
final class ApplicationController
{
    private ApplicationService $applications;

    public function __construct()
    {
        $this->applications = new ApplicationService();
    }

    /** GET /student/companies loader. */
    public function companySearchData(array $params): array
    {
        return ['companies' => $this->applications->listApprovedCompanies($_GET['q'] ?? null)];
    }

    /** GET /student/companies/{id} loader. */
    public function companyDetailData(array $params): array
    {
        $company = $this->applications->getApprovedCompany((int) $params['id']);
        return [
            'company' => $company ?? ['id' => (int) $params['id'], 'name' => 'ไม่พบข้อมูล', 'industry' => '', 'province' => '', 'address' => '', 'positions' => 0, 'description' => '', 'tags' => []],
            'flashError' => Session::pullFlashError(),
        ];
    }

    /** GET /student/applications loader (SITEMAP.md §2 — Phase 11). */
    public function myApplicationsData(array $params): array
    {
        $userId = (string) Session::user()['id'];
        return ['applications' => $this->applications->listForStudent($userId)];
    }

    /** POST /student/applications. */
    public function apply(array $params): void
    {
        $userId = (string) Session::user()['id'];
        $companyId = (string) ($_POST['company_id'] ?? '0');
        try {
            $this->applications->apply($userId, $_POST);
            header('Location: /student/companies'); // back to the list — no per-item success banner built yet (ISSUES.md)
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
            header('Location: /student/companies/' . $companyId);
        }
        exit;
    }

    /** GET /company/applications loader. */
    public function companyApplicationsData(array $params): array
    {
        $companyId = $this->applications->getCompanyIdForSupervisorUser((string) Session::user()['id']);
        return [
            'applications' => $companyId !== null ? $this->applications->listPendingForCompany($companyId) : [],
            'flashError' => Session::pullFlashError(),
        ];
    }

    /** POST /company/applications/{id}/decision. */
    public function decide(array $params): void
    {
        $companyId = $this->applications->getCompanyIdForSupervisorUser((string) Session::user()['id']);
        try {
            if ($companyId === null) {
                throw new AuthException('VALIDATION_ERROR', 'ไม่พบข้อมูลสถานประกอบการของบัญชีนี้');
            }
            $decision = ($_POST['status'] ?? '') === 'rejected' ? 'rejected' : 'accepted';
            $this->applications->decide((int) $params['id'], $decision, $companyId);
            $user = Session::user();
            (new AuditLogger())->log((string) $user['id'], (string) $user['role'], $decision === 'rejected' ? 'reject' : 'approve', 'internship_applications', 'internship_applications', (int) $params['id'], null, ['status' => $decision]);
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
        }
        header('Location: /company/applications');
        exit;
    }
}
