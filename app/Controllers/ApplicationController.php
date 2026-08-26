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
            $jobs = $this->client->restGet(
                'company_job_postings',
                'company_id=eq.' . $id . '&deleted_at=is.null&select=*'
            );
        } catch (\Throwable) {
            $jobs = [];
        }

        return [
            'company' => $company ?? [],
            'jobs' => $jobs,
        ];
    }

    public function apply(array $params): void
    {
        try {
            $userId = $this->requireUserId();
            $studentId = $this->requireStudentId($userId);
            $companyId = $this->requireCompanyId($params);
            $position = trim((string) ($_POST['position'] ?? $_POST['job_title'] ?? ''));
            $notes = trim((string) ($_POST['notes'] ?? $_POST['cover_letter'] ?? ''));

            $this->applications->assertCanApply($studentId, $companyId);

            $this->client->restInsert('internship_applications', [
                'student_id' => $studentId,
                'company_id' => $companyId,
                'position' => $position !== '' ? $position : 'Intern',
                'cover_letter' => $notes,
                'status' => 'pending',
            ]);
        } catch (\Throwable $e) {
            Session::flashError($e->getMessage());
        }

        header('Location: /student/applications');
        exit;
    }

    public function myApplicationsData(array $params): array
    {
        $apps = [];

        try {
            $userId = $this->requireUserId();
            $studentId = $this->requireStudentId($userId);
            $apps = $this->client->restGet(
                'internship_applications',
                'student_id=eq.' . $studentId . '&deleted_at=is.null&order=id.desc&select=*'
            );

            foreach ($apps as &$app) {
                if (!empty($app['company_id'])) {
                    $comp = $this->client->restGet(
                        'companies',
                        'id=eq.' . (int) $app['company_id'] . '&select=name,address,province,business_type'
                    );
                    $app['company'] = $comp[0] ?? [
                        'name' => 'Company',
                        'province' => '-',
                        'business_type' => '-',
                    ];
                    $app['company_name'] = $app['company']['name'];
                }
            }
            unset($app);
        } catch (\Throwable) {
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

    private function requireUserId(): string
    {
        $userId = (string) (Session::user()['id'] ?? '');
        if ($userId === '') {
            throw new \RuntimeException('Please sign in again before submitting an application.');
        }

        return $userId;
    }

    private function requireStudentId(string $userId): int
    {
        $students = $this->client->restGet('students', 'user_id=eq.' . $userId . '&select=id&limit=1');
        $studentId = (int) ($students[0]['id'] ?? 0);
        if ($studentId <= 0) {
            throw new \RuntimeException('Student profile was not found for the current account.');
        }

        return $studentId;
    }

    private function requireCompanyId(array $params): int
    {
        $companyId = (int) ($params['id'] ?? $_POST['company_id'] ?? 0);
        if ($companyId === 0 && !empty($_SERVER['HTTP_REFERER'])) {
            if (preg_match('#/student/companies/(\d+)#', (string) $_SERVER['HTTP_REFERER'], $m)) {
                $companyId = (int) $m[1];
            }
        }
        if ($companyId <= 0) {
            throw new \RuntimeException('Company could not be determined for this application.');
        }

        return $companyId;
    }
}
