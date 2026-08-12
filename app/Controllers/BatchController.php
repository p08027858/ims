<?php

namespace App\Controllers;

use App\Services\AuditLogger;
use App\Services\AuthException;
use App\Services\BatchService;
use App\Support\Session;

/** internship_batches admin console (Phase 8 item 1). */
final class BatchController
{
    private BatchService $batches;

    public function __construct()
    {
        $this->batches = new BatchService();
    }

    public function listData(array $params): array
    {
        $current = $this->batches->getCurrentBatch();
        return [
            'currentBatch' => $current !== null ? [
                'id' => $current['id'],
                'name' => $current['name'],
                'program' => 'ทุกคณะ/สาขา', // internship_batches has no per-program scoping in the schema (DATABASE.md §1.3) — batches are university-wide
                'status' => $current['status'] === 'open' ? 'เปิดรับสมัคร' : ($current['status'] === 'active' ? 'กำลังดำเนินการ' : 'ร่าง'),
                'start_date' => $current['start_date'],
                'end_date' => $current['end_date'],
                'min_hours_total' => $current['min_hours_total'],
                'min_hours_before_checkout' => $current['min_hours_before_checkout'],
            ] : null,
            'pastBatches' => array_map(static fn (array $b) => ['name' => $b['name'], 'status' => 'ปิดรอบแล้ว'], $this->batches->listPastBatches()),
            'formError' => Session::pullFlashError(),
        ];
    }

    public function newFormData(array $params): array
    {
        return ['formError' => Session::pullFlashError()];
    }

    public function store(array $params): void
    {
        try {
            $this->batches->create($_POST);
            $user = Session::user();
            (new AuditLogger())->log((string) $user['id'], (string) $user['role'], 'create', 'internship_batches', 'internship_batches', null, null, ['name' => $_POST['name'] ?? '']);
            header('Location: /admin/batches');
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
            header('Location: /admin/batches/new');
        }
        exit;
    }

    public function close(array $params): void
    {
        try {
            $current = $this->batches->getCurrentBatch();
            if ($current === null) {
                throw new AuthException('VALIDATION_ERROR', 'ไม่พบรอบฝึกงานที่กำลังดำเนินอยู่');
            }
            $this->batches->close((int) $current['id']);
            $user = Session::user();
            (new AuditLogger())->log((string) $user['id'], (string) $user['role'], 'update', 'internship_batches', 'internship_batches', (int) $current['id'], ['status' => $current['status']], ['status' => 'closed']);
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
        }
        header('Location: /admin/batches');
        exit;
    }
}
