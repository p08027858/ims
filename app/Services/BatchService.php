<?php

namespace App\Services;

/** internship_batches (AI_AGENT_PHASES.md Phase 8 item 1, second half). */
final class BatchService
{
    private SupabaseClient $client;
    private SettingsService $settings;

    public function __construct(?SupabaseClient $client = null)
    {
        $this->client = $client ?? new SupabaseClient();
        $this->settings = new SettingsService($this->client);
    }

    /** The most recent non-closed batch — the admin console shows one "current" batch at a time, matching the existing mock UI. */
    public function getCurrentBatch(): ?array
    {
        $rows = $this->client->restGet('internship_batches', 'status=neq.closed&order=id.desc&limit=1&select=*');
        return $rows[0] ?? null;
    }

    /** @return array<int, array{id:int,name:string,status:string}> */
    public function listPastBatches(): array
    {
        return $this->client->restGet('internship_batches', 'status=eq.closed&order=id.desc&select=id,name,status');
    }

    public function create(array $data): void
    {
        $name = trim((string) ($data['name'] ?? ''));
        $academicYear = (int) ($data['academic_year'] ?? 0);
        $semester = (int) ($data['semester'] ?? 0);
        $registerStart = (string) ($data['register_start_date'] ?? '');
        $registerEnd = (string) ($data['register_end_date'] ?? '');
        $startDate = (string) ($data['start_date'] ?? '');
        $endDate = (string) ($data['end_date'] ?? '');
        $minHoursTotal = (int) ($data['min_hours_total'] ?? 0);
        $minHoursBeforeCheckout = (float) ($data['min_hours_before_checkout'] ?? $this->settings->getFloat('min_hours_before_checkout', 4.0));

        if ($name === '' || $academicYear <= 0 || $semester <= 0) {
            throw new AuthException('VALIDATION_ERROR', 'กรุณากรอกชื่อรอบ ปีการศึกษา และภาคการศึกษาให้ครบ');
        }
        if (strtotime($registerStart) === false || strtotime($registerEnd) === false || strtotime($startDate) === false || strtotime($endDate) === false) {
            throw new AuthException('VALIDATION_ERROR', 'กรุณากรอกวันที่ให้ครบทุกช่อง');
        }
        if (strtotime($registerEnd) < strtotime($registerStart)) {
            throw new AuthException('VALIDATION_ERROR', 'วันสิ้นสุดรับสมัครต้องไม่ก่อนวันเริ่มรับสมัคร');
        }
        if (strtotime($endDate) < strtotime($startDate)) {
            throw new AuthException('VALIDATION_ERROR', 'วันสิ้นสุดการฝึกงานต้องไม่ก่อนวันเริ่มฝึกงาน');
        }
        if ($minHoursTotal <= 0) {
            throw new AuthException('VALIDATION_ERROR', 'ชั่วโมงฝึกงานขั้นต่ำต้องมากกว่า 0');
        }
        if ($minHoursBeforeCheckout <= 0 || $minHoursBeforeCheckout > 12) {
            throw new AuthException('VALIDATION_ERROR', 'ชั่วโมงขั้นต่ำก่อนลงเวลาออกต้องมากกว่า 0 และไม่เกิน 12 ชั่วโมง');
        }

        try {
            $this->client->restInsert('internship_batches', [
                'name' => $name,
                'academic_year' => $academicYear,
                'semester' => $semester,
                'register_start_date' => $registerStart,
                'register_end_date' => $registerEnd,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'min_hours_total' => $minHoursTotal,
                'min_hours_before_checkout' => $minHoursBeforeCheckout,
                'status' => 'open',
            ]);
        } catch (SupabaseException $e) {
            throw new AuthException('VALIDATION_ERROR', 'เปิดรอบฝึกงานไม่สำเร็จ (ชื่อรอบนี้อาจมีอยู่แล้ว)', ['cause' => $e->getMessage()]);
        }
    }

    public function close(int $batchId): void
    {
        $rows = $this->client->restGet('internship_batches', 'id=eq.' . $batchId . '&select=id,status');
        if (!isset($rows[0]) || $rows[0]['status'] === 'closed') {
            throw new AuthException('VALIDATION_ERROR', 'ไม่พบรอบฝึกงานนี้ หรือปิดไปแล้ว');
        }
        $this->client->restUpdate('internship_batches', 'id=eq.' . $batchId, ['status' => 'closed']);
    }
}
