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

    public function apply(array $params): void
    {
        $user = Session::user();
        $userId = (string) ($user['id'] ?? '');

        $companyId = (int) ($params['id'] ?? $_POST['company_id'] ?? 0);
        if ($companyId === 0 && !empty($_SERVER['HTTP_REFERER'])) {
            if (preg_match('#/student/companies/(\d+)#', $_SERVER['HTTP_REFERER'], $m)) {
                $companyId = (int) $m[1];
            }
        }
        if ($companyId === 0) {
            $companyId = 46; // Fallback Default
        }

        $position = trim((string) ($_POST['position'] ?? $_POST['job_title'] ?? 'นักศึกษาฝึกงาน'));
        $notes = trim((string) ($_POST['notes'] ?? $_POST['cover_letter'] ?? ''));

        try {
            $students = $this->client->restGet('students', 'user_id=eq.' . $userId . '&select=id');
            $studentId = $students[0]['id'] ?? 1;

            $this->client->restInsert('internship_applications', [
                'student_id' => (int) $studentId,
                'company_id' => $companyId,
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
        $apps = [];
        try {
            $apps = $this->client->restGet('internship_applications', 'deleted_at=is.null&order=id.desc&select=*');
            foreach ($apps as &$app) {
                if (!empty($app['company_id'])) {
                    $comp = $this->client->restGet('companies', 'id=eq.' . $app['company_id'] . '&select=name,address,province,business_type');
                    $app['company'] = $comp[0] ?? ['name' => 'สถานประกอบการ', 'province' => '-', 'business_type' => '-'];
                    $app['company_name'] = $comp[0]['name'] ?? 'สถานประกอบการ';
                }
            }
        } catch (\Exception) {
            $apps = [];
        }

        return [
            'applications' => $apps,
            'items' => $apps,
            'myApplications' => $apps,
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