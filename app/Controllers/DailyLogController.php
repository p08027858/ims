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
        $logs = [];
        try {
            $logs = $this->client->restGet('daily_logs', 'deleted_at=is.null&order=log_date.desc,id.desc&limit=50&select=*');
        } catch (\Exception) {
            $logs = [];
        }

        return [
            'logs' => $logs,
            'dailyLogs' => $logs,
            'items' => $logs,
            'noActiveInternship' => false,
        ];
    }

    public function newFormData(array $params): array
    {
        return [
            'noActiveInternship' => false,
            'today' => date('Y-m-d'),
        ];
    }

    public function store(array $params): void
    {
        $user = Session::user();
        $userId = (string) ($user['id'] ?? '');

        // ค้นหา internship_id
        $internshipId = 3;
        try {
            $students = $this->client->restGet('students', 'user_id=eq.' . $userId . '&select=id');
            $studentId = $students[0]['id'] ?? null;
            if ($studentId) {
                $internships = $this->client->restGet('internships', 'student_id=eq.' . $studentId . '&deleted_at=is.null&order=id.desc&limit=1&select=id');
                if (!empty($internships[0]['id'])) {
                    $internshipId = (int) $internships[0]['id'];
                }
            }
        } catch (\Exception) {
            $internshipId = 3;
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['activity_description'] ?? ''));
        $problems = trim((string) ($_POST['problems_encountered'] ?? ''));
        $learning = trim((string) ($_POST['learning_outcomes'] ?? ''));
        $logDate = trim((string) ($_POST['log_date'] ?? date('Y-m-d')));

        // บันทึกรูปภาพถ้ามี
        $photoUrl = null;
        if (!empty($_POST['photo_base64'])) {
            $photoUrl = $_POST['photo_base64'];
        } elseif (!empty($_FILES['photo']['tmp_name']) && is_uploaded_file($_FILES['photo']['tmp_name'])) {
            $photoData = file_get_contents($_FILES['photo']['tmp_name']);
            $mime = mime_content_type($_FILES['photo']['tmp_name']);
            $photoUrl = 'data:' . $mime . ';base64,' . base64_encode($photoData);
        }

        try {
            $this->client->restInsert('daily_logs', [
                'internship_id' => (int) $internshipId,
                'log_date' => !empty($logDate) ? $logDate : date('Y-m-d'),
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
}