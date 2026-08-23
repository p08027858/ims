<?php

namespace App\Controllers;

use App\Services\ApplicationService;
use App\Services\CompanyService;
use App\Services\OrgService;
use App\Services\SupabaseClient;
use App\Support\Session;

final class ApplicationController
{
    private ApplicationService $applications;
    private CompanyService $companies;

    public function __construct()
    {
        $this->applications = new ApplicationService();
        $this->companies = new CompanyService();
    }

    /**
     * ดึงข้อมูลสถานประกอบการสำหรับหน้านักศึกษาค้นหาสถานที่ฝึกงาน (/student/companies)
     */
    public function companySearchData(array $params): array
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $province = trim((string) ($_GET['province'] ?? ''));
        $industry = trim((string) ($_GET['industry'] ?? ''));

        $companyList = $this->companies->listCompanies($q, $province, $industry);

        return [
            'companies' => $companyList,
            'items' => $companyList,
            'q' => $q,
            'selectedProvince' => $province,
            'selectedIndustry' => $industry,
        ];
    }

    /**
     * ดึงข้อมูลรายละเอียดสถานประกอบการรายแห่ง (/student/companies/{id})
     */
    public function companyDetailData(array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        $company = $this->companies->getCompany($id);

        return [
            'company' => $company ?? [],
            'jobs' => [],
        ];
    }

    /**
     * ดึงข้อมูลใบสมัครของนักศึกษา (/student/applications)
     */
    public function myApplicationsData(array $params): array
    {
        $user = Session::user();
        $userId = (string) ($user['id'] ?? '');

        return [
            'applications' => $this->applications->listForStudent($userId),
        ];
    }

    /**
     * ดึงข้อมูลรายการใบสมัครสำหรับฝั่งสถานประกอบการ (/company/applications)
     */
    public function companyApplicationsData(array $params): array
    {
        $user = Session::user();
        $userId = (string) ($user['id'] ?? '');

        return [
            'applications' => $this->applications->listForSupervisorUser($userId),
        ];
    }
}