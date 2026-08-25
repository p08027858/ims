<?php

namespace App\Services;

final class ApplicationService
{
    private SupabaseClient $client;

    public function __construct(?SupabaseClient $client = null)
    {
        $this->client = $client ?? new SupabaseClient();
    }

    /**
     * ดึงรายการใบสมัครของนักศึกษา
     */
    public function listForStudent(string $userId): array
    {
        try {
            // 1. หา student_id จากตาราง students
            $students = $this->client->restGet('students', 'user_id=eq.' . $userId . '&select=id');
            $studentId = $students[0]['id'] ?? null;

            if (!$studentId) {
                return [];
            }

            // 2. ดึงรายการใบสมัคร
            $apps = $this->client->restGet('internship_applications', 'student_id=eq.' . $studentId . '&deleted_at=is.null&order=id.desc&select=*');

            // 3. แนบข้อมูลบริษัทเข้าไปในแต่ละใบสมัคร
            foreach ($apps as &$app) {
                if (!empty($app['company_id'])) {
                    $comp = $this->client->restGet('companies', 'id=eq.' . $app['company_id'] . '&select=name,address,province,business_type');
                    $app['company'] = $comp[0] ?? ['name' => 'สถานประกอบการ', 'province' => '-', 'business_type' => '-'];
                    $app['company_name'] = $comp[0]['name'] ?? 'สถานประกอบการ';
                }
            }

            return $apps;
        } catch (\Exception) {
            return [];
        }
    }

    /**
     * ดึงรายการใบสมัครสำหรับฝั่งสถานประกอบการ
     */
    public function listForSupervisorUser(string $userId): array
    {
        try {
            $supervisors = $this->client->restGet('company_supervisors', 'user_id=eq.' . $userId . '&select=company_id');
            $companyId = $supervisors[0]['company_id'] ?? null;

            if (!$companyId) {
                return [];
            }

            $apps = $this->client->restGet('internship_applications', 'company_id=eq.' . $companyId . '&deleted_at=is.null&order=id.desc&select=*');

            foreach ($apps as &$app) {
                if (!empty($app['student_id'])) {
                    $stu = $this->client->restGet('students', 'id=eq.' . $app['student_id'] . '&select=student_code,first_name,last_name');
                    $app['student'] = $stu[0] ?? null;
                    $app['student_name'] = isset($stu[0]) ? trim(($stu[0]['first_name'] ?? '') . ' ' . ($stu[0]['last_name'] ?? '')) : 'นักศึกษา';
                }
            }

            return $apps;
        } catch (\Exception) {
            return [];
        }
    }

    /**
     * ดึงรายการคำขอฝึกงานที่ตอบรับแล้ว แต่ยังไม่ได้จับคู่อาจารย์นิเทศก์ (สำหรับหน้าจับคู่นิเทศของ Admin)
     */
    public function listAcceptedUnmatched(): array
    {
        try {
            $apps = $this->client->restGet(
                'internship_applications',
                'status=in.(accepted,approved)&order=id.desc&limit=100&select=*'
            ) ?? [];

            if (empty($apps)) {
                return $this->client->restGet(
                    'internships',
                    'teacher_id=is.null&deleted_at=is.null&order=id.desc&limit=100&select=*'
                ) ?? [];
            }

            return $apps;
        } catch (\Throwable $e) {
            return [];
        }
    }
}