<?php

namespace App\Controllers;

use App\Services\ApplicationService;
use App\Services\AuthException;
use App\Services\CompanyService;
use App\Services\SupabaseClient;
use App\Support\Session;

final class ApplicationController
{
    private ApplicationService $applications;
    private CompanyService $companies;
    private SupabaseClient $client;

    public function __construct()
    {
        $this->applications = new ApplicationService();
        $this->companies = new CompanyService();
        $this->client = new SupabaseClient();
    }

    /**
     * ค้นหาสถานประกอบการสำหรับหน้านักศึกษา (/student/companies)
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
     * ดึงข้อมูลรายละเอียดสถานประกอบการ (/student/companies/{id})
     */
    public function companyDetailData(array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        $company = $this->companies->getCompany($id);

        try {
            $jobs = $this->client->restGet('company_job_postings', 'company_id=eq.' . $id . '&deleted_at=is.null&select=*');
        } catch (\Exception) {
            $jobs = [];
        }

        return [
            'company' => $company ?? [],
            'jobs' => $jobs,
        ];
    }

    /**
     * Action: นักศึกษากดส่งใบสมัคร (POST /student/applications)
     */
    public function apply(array $params): void
    {
        $user = Session::user();
        $userId = (string) ($user['id'] ?? '');
        $companyId = (int) ($_POST['company_id'] ?? 0);
        $position = trim((string) ($_POST['position'] ?? $_POST['job_title'] ?? 'นักศึกษาฝึกงาน'));
        $notes = trim((string) ($_POST['notes'] ?? $_POST['cover_letter'] ?? ''));

        try {
            // ค้นหา student_id จากตาราง students
            $students = $this->client->restGet('students', 'user_id=eq.' . $userId . '&select=id');
            $studentId = $students[0]['id'] ?? null;

            if ($studentId && $companyId > 0) {
                $this->client->restInsert('internship_applications', [
                    'student_id' => $studentId,
                    'company_id' => $companyId,
                    'position' => $position,
                    'cover_letter' => $notes,
                    'status' => 'pending'
                ]);
            }
        } catch (\Exception $e) {
            // บันทึกผ่าน fallback หรือดำเนินการต่อ
        }

        header('Location: /student/applications');
        exit;
    }

    /**
     * ดึงข้อมูลใบสมัครของนักศึกษา (/student/applications)
     */
    public function myApplicationsData(array $params): array
    {
        $user = Session::user();
        $userId = (string) ($user['id'] ?? '');

        try {
            $applications = $this->applications->listForStudent($userId);
        } catch (\Exception) {
            $applications = [];
        }

        return [
            'applications' => $applications,
        ];
    }

    /**
     * ดึงข้อมูลรายการใบสมัครสำหรับฝั่งสถานประกอบการ (/company/applications)
     */
    public function companyApplicationsData(array $params): array
    {
        $user = Session::user();
        $userId = (string) ($user['id'] ?? '');

        try {
            $applications = $this->applications->listForSupervisorUser($userId);
        } catch (\Exception) {
            $applications = [];
        }

        return [
            'applications' => $applications,
        ];
    }
}