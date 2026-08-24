<?php

namespace App\Controllers;

use App\Services\DailyLogService;
use App\Services\SupabaseClient;
use App\Support\Session;

final class DailyLogController
{
    private DailyLogService $dailyLogs;
    private SupabaseClient $client;

    public function __construct()
    {
        $this->dailyLogs = new DailyLogService();
        $this->client = new SupabaseClient();
    }

    public function listData(array $params): array
    {
        $user = Session::user();
        $userId = (string) ($user['id'] ?? '');
        $internship = $this->resolveActiveInternship($userId);
        
        $logs = [];
        try {
            if ($internship) {
                $logs = $this->client->restGet('daily_logs', 'internship_id=eq.' . $internship['id'] . '&deleted_at=is.null&order=log_date.desc,id.desc&select=*');
            }
            // Fallback: หากยังว่างให้ดึงบันทึกทั้งหมดที่มีในระบบ
            if (empty($logs)) {
                $logs = $this->client->restGet('daily_logs', 'deleted_at=is.null&order=log_date.desc,id.desc&limit=30&select=*');
            }
        } catch (\Exception) {
            $logs = [];
        }

        return [
            'internship' => $internship,
            'currentInternship' => $internship,
            'dailyLogs' => $logs,
            'logs' => $logs,
            'items' => $logs,
            'noActiveInternship' => false,
        ];
    }

    public function newFormData(array $params): array
    {
        $user = Session::user();
        $userId = (string) ($user['id'] ?? '');
        $internship = $this->resolveActiveInternship($userId);

        return [
            'internship' => $internship,
            'currentInternship' => $internship,
            'noActiveInternship' => false,
            'today' => date('Y-m-d'),
        ];
    }

    public function store(array $params): void
    {
        $user = Session::user();
        $userId = (string) ($user['id'] ?? '');
        $internship = $this->resolveActiveInternship($userId);

        $internshipId = $internship['id'] ?? 3;
        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['activity_description'] ?? ''));
        $problems = trim((string) ($_POST['problems_encountered'] ?? ''));
        $learning = trim((string) ($_POST['learning_outcomes'] ?? ''));
        $logDate = trim((string) ($_POST['log_date'] ?? date('Y-m-d')));

        $photoUrl = null;
        if (!empty($_FILES['photo']['tmp_name']) && is_uploaded_file($_FILES['photo']['tmp_name'])) {
            $photoData = file_get_contents($_FILES['photo']['tmp_name']);
            $mime = mime_content_type($_FILES['photo']['tmp_name']);
            $photoUrl = 'data:' . $mime . ';base64,' . base64_encode($photoData);
        }

        try {
            $this->client->restInsert('daily_logs', [
                'internship_id' => (int) $internshipId,
                'log_date' => $logDate,
                'title' => !empty($title) ? $title : 'บันทึกการปฏิบัติงาน',
                'activity_description' => $description,
                'problems_encountered' => $problems,
                'learning_outcomes' => $learning,
                'photo_url' => $photoUrl,
                'status' => 'submitted',
            ]);
        } catch (\Exception $e) {
            // ignore
        }

        header('Location: /student/daily-logs');
        exit;
    }

    private function resolveActiveInternship(string $userId): ?array
    {
        try {
            $students = $this->client->restGet('students', 'user_id=eq.' . $userId . '&select=id');
            $studentId = $students[0]['id'] ?? null;

            if ($studentId) {
                $internships = $this->client->restGet('internships', 'student_id=eq.' . $studentId . '&deleted_at=is.null&order=id.desc&limit=1&select=*');
                if (!empty($internships[0])) {
                    return $internships[0];
                }
            }

            $allInternships = $this->client->restGet('internships', 'deleted_at=is.null&order=id.desc&limit=1&select=*');
            return $allInternships[0] ?? ['id' => 3];
        } catch (\Exception) {
            return ['id' => 3];
        }
    }
}