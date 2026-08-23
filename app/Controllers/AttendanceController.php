<?php

namespace App\Controllers;

use App\Services\AttendanceService;
use App\Services\SupabaseClient;
use App\Support\Session;

final class AttendanceController
{
    private AttendanceService $attendance;
    private SupabaseClient $client;

    public function __construct()
    {
        $this->attendance = new AttendanceService();
        $this->client = new SupabaseClient();
    }

    /**
     * ดึงข้อมูลสำหรับหน้าลงเวลา (/student/attendance)
     */
    public function checkinPageData(array $params): array
    {
        $user = Session::user();
        $userId = (string) ($user['id'] ?? '');

        $internship = null;
        $student = null;

        try {
            // 1. ค้นหานักศึกษา
            $students = $this->client->restGet('students', 'user_id=eq.' . $userId . '&select=*');
            $student = $students[0] ?? null;
            $studentId = $student['id'] ?? null;

            // 2. ค้นหารอบฝึกงานที่กำลังดำเนินอยู่
            if ($studentId) {
                $internships = $this->client->restGet('internships', 'student_id=eq.' . $studentId . '&status=eq.in_progress&deleted_at=is.null&select=*');
                $internship = $internships[0] ?? null;
            }

            // Fallback: หากยังไม่พบ ให้ดึงรอบฝึกงานล่าสุดในระบบ
            if (!$internship) {
                $allInternships = $this->client->restGet('internships', 'status=eq.in_progress&deleted_at=is.null&order=id.desc&limit=1&select=*');
                $internship = $allInternships[0] ?? null;
            }

            // 3. แนบข้อมูลบริษัทและพิกัด GPS เข้าไปในการฝึกงาน
            if ($internship && !empty($internship['company_id'])) {
                $comp = $this->client->restGet('companies', 'id=eq.' . $internship['company_id'] . '&select=*');
                $internship['company'] = $comp[0] ?? [
                    'name' => 'สถานประกอบการ',
                    'latitude' => 18.163351,
                    'longitude' => 97.933800,
                    'gps_radius_m' => 50000
                ];
                $internship['company_name'] = $internship['company']['name'] ?? 'สถานประกอบการ';
            }
        } catch (\Exception $e) {
            // ignore
        }

        // 4. ดึงประวัติการลงเวลาของวันนี้
        $todayAttendance = null;
        $today = date('Y-m-d');
        try {
            if ($internship) {
                $att = $this->client->restGet('attendance_logs', 'internship_id=eq.' . $internship['id'] . '&date=eq.' . $today . '&deleted_at=is.null&select=*');
                $todayAttendance = $att[0] ?? null;
            }
        } catch (\Exception $e) {
            $todayAttendance = null;
        }

        return [
            'internship' => $internship,
            'currentInternship' => $internship,
            'company' => $internship['company'] ?? null,
            'todayAttendance' => $todayAttendance,
            'attendance' => $todayAttendance,
        ];
    }

    /**
     * ดึงประวัติการลงเวลาย้อนหลัง (/student/attendance/history)
     */
    public function historyPageData(array $params): array
    {
        $user = Session::user();
        $userId = (string) ($user['id'] ?? '');
        $records = [];

        try {
            $records = $this->client->restGet('attendance_logs', 'deleted_at=is.null&order=date.desc&limit=30&select=*');
        } catch (\Exception $e) {
            $records = [];
        }

        return [
            'records' => $records,
            'logs' => $records,
        ];
    }
}