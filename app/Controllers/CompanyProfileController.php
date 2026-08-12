<?php

namespace App\Controllers;

use App\Services\AuthException;
use App\Services\CompanyService;
use App\Services\SettingsService;
use App\Support\Session;
use App\Support\View;

/** Company supervisor's own GPS/location settings (AI_AGENT_PHASES.md Phase 3 item 3). */
final class CompanyProfileController
{
    private CompanyService $companies;

    public function __construct()
    {
        $this->companies = new CompanyService();
    }

    /** GET /company/profile loader. */
    public function gpsSetupData(array $params): array
    {
        $userId = (string) Session::user()['id'];
        $company = $this->companies->getCompanyForSupervisorUser($userId);
        return [
            'company' => $company ?? ['name' => '', 'latitude' => 13.736717, 'longitude' => 100.523186, 'gps_radius_m' => 100],
            'maxRadiusM' => (new SettingsService())->getInt('max_gps_radius_m', 500),
        ];
    }

    public function updateGps(array $params): void
    {
        $userId = (string) Session::user()['id'];
        $company = $this->companies->getCompanyForSupervisorUser($userId);
        $maxRadiusM = (new SettingsService())->getInt('max_gps_radius_m', 500);

        if ($company === null) {
            Session::flashError('ไม่พบข้อมูลสถานประกอบการของบัญชีนี้');
            header('Location: /company/profile');
            exit;
        }

        try {
            $this->companies->updateOwnGps(
                (int) $company['id'],
                (float) ($_POST['latitude'] ?? 0),
                (float) ($_POST['longitude'] ?? 0),
                (int) ($_POST['gps_radius_m'] ?? 100),
                $maxRadiusM
            );
        } catch (AuthException $e) {
            View::render('company/gps_setup', 'company', [
                'pageTitle' => 'ตั้งค่าพิกัด - Supervisor',
                'activeNav' => 'gps_setup',
                'company' => array_merge($company, $_POST),
                'maxRadiusM' => $maxRadiusM,
                'formError' => $e->getMessage(),
            ]);
        }

        header('Location: /company/profile');
        exit;
    }

    /** GET /company/supervisors loader (SITEMAP.md §3 — Phase 11). */
    public function supervisorsData(array $params): array
    {
        $userId = (string) Session::user()['id'];
        $company = $this->companies->getCompanyForSupervisorUser($userId);
        return [
            'supervisors' => $company !== null ? $this->companies->listSupervisors((int) $company['id']) : [],
            'canAdd' => $this->companies->isPrimaryContact($userId),
            'formError' => Session::pullFlashError(),
        ];
    }

    /** POST /company/supervisors — primary contact only (ROLES.md §2). */
    public function addSupervisor(array $params): void
    {
        $userId = (string) Session::user()['id'];
        try {
            if (!$this->companies->isPrimaryContact($userId)) {
                throw new AuthException('VALIDATION_ERROR', 'เฉพาะผู้ติดต่อหลักเท่านั้นที่เพิ่มบัญชีผู้ติดต่อรองได้');
            }
            $company = $this->companies->getCompanyForSupervisorUser($userId);
            if ($company === null) {
                throw new AuthException('VALIDATION_ERROR', 'ไม่พบข้อมูลสถานประกอบการของบัญชีนี้');
            }
            $this->companies->addSupervisor((int) $company['id'], $_POST);
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
        }
        header('Location: /company/supervisors');
        exit;
    }
}
