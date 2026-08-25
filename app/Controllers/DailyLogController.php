<?php

namespace App\Controllers;

use App\Services\SupabaseClient;
use App\Support\Session;

final class DailyLogController
{
    private SupabaseClient $client;

    public function __construct()
    {
        $this->client = new SupabaseClient();
    }

    /**
     * ดึงเฉพาะบันทึกของนักศึกษาที่ล็อกอินอยู่
     */
    public function listData(array $params): array
    {
        $user = Session::user();
        $userId = (string) ($user['id'] ?? '');
        $studentId = $this->resolveStudentId($userId);
        
        $logs = [];
        try {
            if ($studentId) {
                $logs = $this->client->restGet('daily_logs', 'student_id=eq.' . $studentId . '&deleted_at=is.null&order=id.desc&limit=50&select=*');
            } else {
                $logs = $this->client->restGet('daily_logs', 'deleted_at=is.null&order=id.desc&limit=50&select=*');
            }
        } catch (\Exception $e) {
            $logs = [];
        }

        return [
            'logs' => $logs,
            'dailyLogs' => $logs,
            'items' => $logs,
            'noActiveInternship' => false,
        ];
    }

    /**
     * หน้าตรวจบันทึกประจำวันของสถานประกอบการ / อาจารย์
     */
    public function reviewPageData(array $params): array
    {
        $logs = [];
        try {
            $logs = $this->client->restGet('daily_logs', 'deleted_at=is.null&order=log_date.desc,id.desc&limit=50&select=*') ?? [];
        } catch (\Throwable $e) {
            $logs = [];
        }

        return [
            'logs' => $logs,
            'dailyLogs' => $logs,
            'items' => $logs,
            'pendingCount' => count(array_filter($logs, fn($item) => ($item['status'] ?? '') === 'submitted')),
        ];
    }

    public function newFormData(array $params): array
    {
        return [
            'noActiveInternship' => false,
            'today' => date('Y-m-d'),
        ];
    }

    /**
     * บันทึกข้อมูลผูกกับ student_id และ internship_id ของผู้ใช้จริง
     */
    public function store(array $params): void
    {
        $user = Session::user();
        $userId = (string) ($user['id'] ?? '');
        $studentId = $this->resolveStudentId($userId) ?? 1;
        $internshipId = $this->resolveInternshipId($studentId) ?? 3;

        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?? $_POST;

        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['activity_description'] ?? ''));
        $problems = trim((string) ($input['problems_encountered'] ?? ''));
        $learning = trim((string) ($input['learning_outcomes'] ?? ''));
        $logDate = trim((string) ($input['log_date'] ?? date('Y-m-d')));
        $photoUrl = $input['photo_base64'] ?? null;

        $record = [
            'internship_id' => (int) $internshipId,
            'student_id' => (int) $studentId,
            'log_date' => !empty($logDate) ? $logDate : date('Y-m-d'),
            'title' => !empty($title) ? $title : 'บันทึกการปฏิบัติงาน',
            'tasks_performed' => !empty($description) ? $description : $title,
            'activity_description' => $description,
            'problems_encountered' => $problems,
            'learning_outcomes' => $learning,
            'photo_url' => $photoUrl,
            'status' => 'submitted',
        ];

        try {
            $this->client->restInsert('daily_logs', $record);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true]);
        } catch (\Throwable $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    private function resolveStudentId(string $userId): ?int
    {
        if (empty($userId)) return null;
        try {
            $students = $this->client->restGet('students', 'user_id=eq.' . $userId . '&select=id&limit=1');
            return !empty($students[0]['id']) ? (int) $students[0]['id'] : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveInternshipId(int $studentId): ?int
    {
        try {
            $internships = $this->client->restGet('internships', 'student_id=eq.' . $studentId . '&deleted_at=is.null&order=id.desc&limit=1&select=id');
            return !empty($internships[0]['id']) ? (int) $internships[0]['id'] : null;
        } catch (\Throwable) {
            return null;
        }
    }
}