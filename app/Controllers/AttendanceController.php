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

    public function checkinPageData(array $params): array
    {
        $user = Session::user();
        $userId = (string) ($user['id'] ?? '');

        $internship = null;
        try {
            $students = $this->client->restGet('students', 'user_id=eq.' . $userId . '&select=id');
            $studentId = $students[0]['id'] ?? null;

            if ($studentId) {
                $internships = $this->client->restGet('internships', 'student_id=eq.' . $studentId . '&deleted_at=is.null&order=id.desc&limit=1&select=*');
                $internship = $internships[0] ?? null;
            }

            if (!$internship) {
                $allInternships = $this->client->restGet('internships', 'deleted_at=is.null&order=id.desc&limit=1&select=*');
                $internship = $allInternships[0] ?? null;
            }

            if ($internship && !empty($internship['company_id'])) {
                $comp = $this->client->restGet('companies', 'id=eq.' . $internship['company_id'] . '&select=*');
                $internship['company'] = $comp[0] ?? [
                    'name' => 'สถานประกอบการ',
                    'latitude' => 18.163351,
                    'longitude' => 97.933800,
                    'gps_radius_m' => 1000000
                ];
                $internship['company_name'] = $internship['company']['name'] ?? 'สถานประกอบการ';
            }
        } catch (\Exception $e) {
            // ignore
        }

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
            'allowedRadiusM' => 1000000,
        ];
    }

    /**
     * Action: บันทึกเวลาเข้างาน (POST /student/attendance/check-in หรือ /student/attendance)
     */
    public function checkIn(array $params): void
    {
        $this->handleCheckinAction('in');
    }

    public function checkOut(array $params): void
    {
        $this->handleCheckinAction('out');
    }

    private function handleCheckinAction(string $type): void
    {
        $user = Session::user();
        $userId = (string) ($user['id'] ?? '');
        $lat = (float) ($_POST['latitude'] ?? $_POST['lat'] ?? 18.163351);
        $lng = (float) ($_POST['longitude'] ?? $_POST['lng'] ?? 97.933800);
        $today = date('Y-m-d');
        $now = date('c');

        try {
            $students = $this->client->restGet('students', 'user_id=eq.' . $userId . '&select=id');
            $studentId = $students[0]['id'] ?? 1;

            $internships = $this->client->restGet('internships', 'student_id=eq.' . $studentId . '&deleted_at=is.null&order=id.desc&limit=1&select=id');
            $internshipId = $internships[0]['id'] ?? 3;

            $existing = $this->client->restGet('attendance_logs', 'internship_id=eq.' . $internshipId . '&date=eq.' . $today . '&deleted_at=is.null&select=*');

            if (empty($existing)) {
                $this->client->restInsert('attendance_logs', [
                    'internship_id' => $internshipId,
                    'date' => $today,
                    'check_in_time' => $now,
                    'check_in_latitude' => $lat,
                    'check_in_longitude' => $lng,
                    'status' => 'present',
                ]);
            } else {
                $logId = $existing[0]['id'];
                $this->client->restUpdate('attendance_logs', 'id=eq.' . $logId, [
                    'check_out_time' => $now,
                    'check_out_latitude' => $lat,
                    'check_out_longitude' => $lng,
                ]);
            }

            if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') || isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true]);
                exit;
            }
        } catch (\Exception $e) {
            // ignore
        }

        header('Location: /student/attendance');
        exit;
    }

    public function historyPageData(array $params): array
    {
        $records = [];
        try {
            $records = $this->client->restGet('attendance_logs', 'deleted_at=is.null&order=date.desc&limit=30&select=*');
        } catch (\Exception) {
            $records = [];
        }

        return [
            'records' => $records,
            'logs' => $records,
        ];
    }
}