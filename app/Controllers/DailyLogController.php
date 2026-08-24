<?php

namespace App\Controllers;

use App\Services\SupabaseClient;

final class DailyLogController
{
    private SupabaseClient $client;

    public function __construct()
    {
        $this->client = new SupabaseClient();
    }

    public function listData(array $params): array
    {
        $logs = [];
        try {
            $logs = $this->client->restGet('daily_logs', 'deleted_at=is.null&order=id.desc&limit=50&select=*');
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

    public function newFormData(array $params): array
    {
        return [
            'noActiveInternship' => false,
            'today' => date('Y-m-d'),
        ];
    }

    public function store(array $params): void
    {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?? $_POST;

        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['activity_description'] ?? ''));
        $problems = trim((string) ($input['problems_encountered'] ?? ''));
        $learning = trim((string) ($input['learning_outcomes'] ?? ''));
        $logDate = trim((string) ($input['log_date'] ?? date('Y-m-d')));
        $photoUrl = $input['photo_base64'] ?? null;

        $record = [
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
}