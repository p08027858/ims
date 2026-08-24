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
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['activity_description'] ?? ''));
        $problems = trim((string) ($input['problems_encountered'] ?? ''));
        $learning = trim((string) ($input['learning_outcomes'] ?? ''));
        $logDate = trim((string) ($input['log_date'] ?? date('Y-m-d')));
        $photoUrl = $input['photo_base64'] ?? null;

        try {
            $this->client->restInsert('daily_logs', [
                'internship_id' => 3,
                'student_id' => 1,
                'log_date' => !empty($logDate) ? $logDate : date('Y-m-d'),
                'title' => !empty($title) ? $title : 'บันทึกการปฏิบัติงาน',
                'tasks_performed' => !empty($description) ? $description : $title,
                'activity_description' => $description,
                'problems_encountered' => $problems,
                'learning_outcomes' => $learning,
                'photo_url' => $photoUrl,
                'status' => 'submitted',
            ]);
        } catch (\Exception $e) {
            // ignore
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true]);
        exit;
    }
}