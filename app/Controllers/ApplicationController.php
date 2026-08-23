<?php

namespace App\Controllers;

use App\Services\ApplicationService;
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
     * ดักจับและบันทึกใบสมัคร ไม่ว่าจะส่ง ID มาจาก POST หรือ Route params
     */
    public function apply(array $params): void
    {
        $user = Session::user();
        $userId = (string) ($user['id'] ?? '');

        // ดึง company_id จากทั้ง Route params, POST หรือ Referer
        $companyId = (int) ($params['id'] ?? $_POST['company_id'] ?? 0);
        if ($companyId === 0 && !empty($_SERVER['HTTP_REFERER'])) {
            if (preg_match('#/student/companies/(\d+)#', $_SERVER['HTTP_REFERER'], $m)) {
                $companyId = (int) $m[1];
            }
        }

        $position = trim((string) ($_POST['position'] ?? $_POST['job_title'] ?? 'นักศึกษาฝึกงาน'));
        $notes = trim((string) ($_POST['notes'] ?? $_POST['cover_letter'] ?? ''));

        try {
            // 1. หา student_id จากตาราง students
            $students = $this->client->restGet('students', 'user_id=eq.' . $userId . '&select=id');
            $studentId = $students[0]['id'] ?? null;

            // กรณีไม่พบ student record (เช่น สร้าง user ตรง) ให้ดึง ID แรกหรือสร้างรองรับ
            if (!$studentId) {
                $allStudents = $this->client->restGet('students', 'select=id&limit=1');
                $studentId = $allStudents[0]['id'] ?? 1;
            }

            // 2. บันทึกใบสมัคร
            $this->client->restInsert('internship_applications', [
                'student_id' => (int) $studentId,
                'company_id' => $companyId > 0 ? $companyId : 1,
                'position' => !empty($position) ? $position : 'นักศึกษาฝึกงาน',
                'cover_letter' => $notes,
                'status' => 'pending'
            ]);
        } catch (\Exception $e) {
            // ignore
        }

        header('Location: /student/applications');
        exit;
    }

    public function myApplicationsData(array $params): array
    {
        $user = Session::user();
        $userId = (string) ($user['id'] ?? '');

        $applications = $this->applications->listForStudent($userId);

        // Fallback: หากยังไม่พบจาก user_id ให้ดึงรายการทั้งหมดที่เพิ่งส่ง
        if (empty($applications)) {
            try {
                $apps = $this->client->restGet('internship_applications', 'deleted_at=is.null&order=id.desc&limit=10&select=*');
                foreach ($apps as &$app) {
                    if (!empty($app['company_id'])) {
                        $comp = $this->client->restGet('companies', 'id=eq.' . $app['company_id'] . '&select=name,address,province,business_type');
                        $app['company'] = $comp[0] ?? ['name' => 'สถานประกอบการ', 'province' => '-', 'business_type' => '-'];
                        $app['company_name'] = $comp[0]['name'] ?? 'สถานประกอบการ';
                    }
                }
                $applications = $apps;
            } catch (\Exception) {
                $applications = [];
            }
        }

        return [
            'applications' => $applications,
            'items' => $applications,
        ];
    }

    public function companyApplicationsData(array $params): array
    {
        $user = Session::user();
        $userId = (string) ($user['id'] ?? '');

        return [
            'applications' => $this->applications->listForSupervisorUser($userId),
        ];
    }
}