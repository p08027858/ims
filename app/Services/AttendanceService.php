<?php

namespace App\Services;

/**
 * attendance (AI_AGENT_PHASES.md Phase 5) — GPS check-in/out with the Haversine distance rule
 * and the on_time/late/out_of_range decision table (ATTENDANCE_GPS.md §2-3).
 */
final class AttendanceService
{
    private SupabaseClient $client;
    private SettingsService $settings;
    private GoogleDriveStorageClient $drive;

    public function __construct(?SupabaseClient $client = null, ?GoogleDriveStorageClient $drive = null)
    {
        $this->client = $client ?? new SupabaseClient();
        $this->settings = new SettingsService($this->client);
        $this->drive = $drive ?? new GoogleDriveStorageClient();
    }

    /** ATTENDANCE_GPS.md §2 — verbatim formula. */
    public static function haversineDistanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $deltaPhi = deg2rad($lat2 - $lat1);
        $deltaLambda = deg2rad($lng2 - $lng1);

        $a = sin($deltaPhi / 2) ** 2 + cos($phi1) * cos($phi2) * sin($deltaLambda / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Resolves the logged-in student's currently active internship, plus everything checkin/
     * checkout need from company (GPS) and batches (min hours) — one lookup instead
     * of scattering restGet calls across the controller.
     *
     * @return array{id:int,company_id:int,latitude:float,longitude:float,gps_radius_m:float,min_hours_before_checkout:float}|null
     */
    public function getActiveInternshipContext(string $userId): ?array
    {
        $internship = (new InternshipService($this->client))->getCurrentInternshipForStudentUser($userId);
        if ($internship === null) {
            return null;
        }

        try {
            $companies = $this->client->restGet('companies', 'id=eq.' . $internship['company_id'] . '&select=latitude,longitude,gps_radius_m,name');
        } catch (\Throwable $e) {
            error_log('IMS attendance company lookup fallback: internship_id=' . (int) ($internship['id'] ?? 0) . ' message=' . $e->getMessage());
            $companies = [];
        }
        $batchId = (int) ($internship['batch_id'] ?? 0);
        if ($batchId > 0) {
            try {
                $batches = $this->client->restGet('batches', 'id=eq.' . $batchId . '&select=min_hours_before_checkout');
            } catch (\Throwable $e) {
                error_log('IMS attendance batch lookup fallback: internship_id=' . (int) ($internship['id'] ?? 0) . ' batch_id=' . $batchId . ' message=' . $e->getMessage());
                $batches = [];
            }
        } else {
            $batches = [];
        }

        return [
            'id' => (int) $internship['id'],
            'company_id' => (int) $internship['company_id'],
            'company_name' => $companies[0]['name'] ?? '',
            'latitude' => (float) ($companies[0]['latitude'] ?? 0),
            'longitude' => (float) ($companies[0]['longitude'] ?? 0),
            'gps_radius_m' => (float) ($companies[0]['gps_radius_m'] ?? 100),
            'min_hours_before_checkout' => isset($batches[0]['min_hours_before_checkout'])
                ? (float) $batches[0]['min_hours_before_checkout']
                : $this->settings->getFloat('min_hours_before_checkout', 4.0),
        ];
    }

    public function getTodayAttendance(int $internshipId): ?array
    {
        $rows = $this->client->restGet(
            'attendance',
            'internship_id=eq.' . $internshipId . '&work_date=eq.' . date('Y-m-d') . '&select=*'
        );
        return $rows[0] ?? null;
    }

    /**
     * RULE-ATT-01..03/06. Out-of-range attempts are upserted WITHOUT setting check_in_at (kept
     * as evidence per ATTENDANCE_GPS.md §3/§4) so the student can retry once actually in range —
     * only a genuinely successful check-in (check_in_at set) trips RULE-ATT-03's "already
     * checked in" gate. TC-ATT-008 idempotency: a duplicate call after a real success returns
     * the existing record's data inside the 409 error's `details`, never a raw crash.
     */
    public function checkin(array $context, float $lat, float $lng, float $accuracyM, ?string $photoBase64): array
    {
        $internshipId = $context['id'];
        $today = date('Y-m-d');
        $existing = $this->getTodayAttendance($internshipId);

        if ($existing !== null && $existing['check_in_at'] !== null) {
            throw new ApiException(409, 'ALREADY_CHECKED_IN', 'คุณลงเวลาเข้างานไปแล้ววันนี้', [
                'attendance_id' => $existing['id'],
                'check_in_at' => $existing['check_in_at'],
                'check_in_status' => $existing['check_in_status'],
            ]);
        }

        $photoRequired = $this->settings->getBool('attendance_photo_required', false);
        if ($photoRequired && ($photoBase64 === null || trim($photoBase64) === '')) {
            throw new ApiException(422, 'PHOTO_REQUIRED', 'ระบบกำหนดให้ถ่ายภาพยืนยันตอนลงเวลาเข้างาน');
        }

        $distance = self::haversineDistanceMeters($lat, $lng, $context['latitude'], $context['longitude']);
        $inRange = $distance <= $context['gps_radius_m'];

        $photoPath = null;
        if ($photoBase64 !== null && trim($photoBase64) !== '') {
            $photoPath = $this->uploadPhoto($photoBase64, $internshipId, 'checkin');
        }

        $data = [
            'internship_id' => $internshipId,
            'work_date' => $today,
            'check_in_lat' => $lat,
            'check_in_lng' => $lng,
            'check_in_accuracy_m' => $accuracyM,
            'check_in_distance_m' => round($distance, 2),
        ];
        if ($photoPath !== null) {
            $data['check_in_photo_path'] = $photoPath;
        }

        if (!$inRange) {
            // Evidence only — check_in_at stays null so RULE-ATT-03 doesn't block a real retry.
            $data['check_in_status'] = 'out_of_range';
            if ($existing !== null) {
                $this->client->restUpdate('attendance', 'id=eq.' . $existing['id'], $data);
            } else {
                $this->client->restInsert('attendance', $data);
            }
            throw new ApiException(422, 'OUT_OF_GPS_RANGE', 'อยู่นอกระยะที่กำหนด', [
                'distance_m' => round($distance, 2),
                'allowed_m' => $context['gps_radius_m'],
            ]);
        }

        $status = $this->isPastLateThreshold() ? 'late' : 'on_time';
        $data['check_in_at'] = date('c');
        $data['check_in_status'] = $status;
        // out_of_range attempts never reach here; a genuine in-range check-in always counts as
        // the student being present that day, whether on_time or late (day_status mirrors that).
        $data['day_status'] = $status === 'late' ? 'late' : 'present';

        if ($existing !== null) {
            $rows = $this->client->restUpdate('attendance', 'id=eq.' . $existing['id'], $data);
        } else {
            $rows = $this->client->restInsert('attendance', $data);
        }
        $row = $rows[0];

        return [
            'attendance_id' => $row['id'],
            'check_in_at' => $row['check_in_at'],
            'check_in_distance_m' => (float) $row['check_in_distance_m'],
            'check_in_status' => $row['check_in_status'],
        ];
    }

    /** RULE-ATT-04. */
    public function checkout(array $context, float $lat, float $lng, float $accuracyM): array
    {
        $internshipId = $context['id'];
        $existing = $this->getTodayAttendance($internshipId);

        if ($existing === null || $existing['check_in_at'] === null) {
            throw new ApiException(422, 'NOT_CHECKED_IN', 'คุณยังไม่ได้ลงเวลาเข้างานวันนี้');
        }
        if ($existing['check_out_at'] !== null) {
            throw new ApiException(409, 'ALREADY_CHECKED_OUT', 'คุณลงเวลาออกงานไปแล้ววันนี้', [
                'attendance_id' => $existing['id'],
                'check_out_at' => $existing['check_out_at'],
                'total_hours' => $existing['total_hours'],
            ]);
        }

        $elapsedHours = (time() - strtotime($existing['check_in_at'])) / 3600;
        $requiredHours = $context['min_hours_before_checkout'];
        if ($elapsedHours < $requiredHours) {
            throw new ApiException(
                422,
                'CHECKOUT_TOO_EARLY',
                sprintf('ต้องทำงานอย่างน้อย %.1f ชั่วโมงก่อนลงเวลาออก', $requiredHours),
                ['elapsed_hours' => round($elapsedHours, 2), 'required_hours' => $requiredHours]
            );
        }

        $distance = self::haversineDistanceMeters($lat, $lng, $context['latitude'], $context['longitude']);
        $inRange = $distance <= $context['gps_radius_m'];
        // §6: only a normal (in-range) checkout counts hours — out_of_range doesn't, same as check-in.
        $checkoutStatus = $inRange ? 'normal' : 'out_of_range';
        $totalHours = $inRange ? round($elapsedHours, 2) : null;

        $rows = $this->client->restUpdate('attendance', 'id=eq.' . $existing['id'], [
            'check_out_at' => date('c'),
            'check_out_lat' => $lat,
            'check_out_lng' => $lng,
            'check_out_accuracy_m' => $accuracyM,
            'check_out_distance_m' => round($distance, 2),
            'check_out_status' => $checkoutStatus,
            'total_hours' => $totalHours,
        ]);
        $row = $rows[0];

        if (!$inRange) {
            throw new ApiException(422, 'OUT_OF_GPS_RANGE', 'อยู่นอกระยะที่กำหนดตอนลงเวลาออก', [
                'distance_m' => round($distance, 2),
                'allowed_m' => $context['gps_radius_m'],
            ]);
        }

        return [
            'attendance_id' => $row['id'],
            'check_out_at' => $row['check_out_at'],
            'total_hours' => (float) $row['total_hours'],
            'check_out_status' => $row['check_out_status'],
        ];
    }

    /**
     * RULE-ATT-05 / TC-ATT-007 — the logic this rule needs, exposed so Phase 9's actual cron
     * scheduler (DEPLOYMENT.md §7, not built yet) can call it; not wired to a scheduled task in
     * this phase. Any row still missing a checkout from a PAST work_date gets check_out_status
     * = rejected. day_status intentionally untouched (RULES.md RULE-ATT-05, fixed 2026-07-30 —
     * there is no 'incomplete' value in the day_status enum).
     *
     * @return int number of rows closed
     */
    public function closeStaleIncompleteDays(): int
    {
        $rows = $this->client->restGet(
            'attendance',
            'work_date=lt.' . date('Y-m-d') . '&check_in_at=not.is.null&check_out_at=is.null&select=id'
        );
        foreach ($rows as $r) {
            $this->client->restUpdate('attendance', 'id=eq.' . $r['id'], ['check_out_status' => 'rejected']);
        }
        return count($rows);
    }

    /** GET /attendance — history list for student/attendance_history.php. */
    public function listHistory(int $internshipId): array
    {
        $rows = $this->client->restGet(
            'attendance',
            'internship_id=eq.' . $internshipId . '&order=work_date.desc&select=work_date,check_in_at,check_out_at,total_hours,day_status'
        );
        return array_map(static fn (array $r) => [
            'date' => $r['work_date'],
            'check_in' => $r['check_in_at'] !== null ? date('H:i', strtotime($r['check_in_at'])) : '-',
            'check_out' => $r['check_out_at'] !== null ? date('H:i', strtotime($r['check_out_at'])) : '-',
            'hours' => $r['total_hours'] !== null ? (float) $r['total_hours'] : 0,
            'status' => $r['day_status'],
        ], $rows);
    }

    /** GET /attendance/summary. */
    public function getSummary(int $internshipId, int $totalRequiredHours): array
    {
        $rows = $this->client->restGet('attendance', 'internship_id=eq.' . $internshipId . '&select=total_hours,day_status');
        $totalHours = 0.0;
        $daysPresent = 0;
        $daysLate = 0;
        $daysAbsent = 0;
        foreach ($rows as $r) {
            $totalHours += (float) ($r['total_hours'] ?? 0);
            match ($r['day_status']) {
                'present' => $daysPresent++,
                'late' => $daysLate++,
                'absent' => $daysAbsent++,
                default => null,
            };
        }
        return [
            'total_hours_logged' => round($totalHours, 2),
            'total_required_hours' => $totalRequiredHours,
            'percent_complete' => $totalRequiredHours > 0 ? round(min(100, $totalHours / $totalRequiredHours * 100), 1) : 0.0,
            'days_present' => $daysPresent,
            'days_late' => $daysLate,
            'days_absent' => $daysAbsent,
        ];
    }

    /** RULE-ATT-02 (2026-07-30 fix): reuses settings.notification_no_checkin_time (RULE-NOTI-01) as the late-cutoff clock time. */
    private function isPastLateThreshold(): bool
    {
        $cutoff = $this->settings->getString('notification_no_checkin_time', '09:00');
        return date('H:i') > $cutoff;
    }

    private function uploadPhoto(string $base64, int $internshipId, string $kind): string
    {
        // Accepts either a raw base64 string or a data: URI (data:image/jpeg;base64,....).
        $mime = 'image/jpeg';
        if (preg_match('/^data:(image\/[a-zA-Z]+);base64,(.+)$/', $base64, $m)) {
            $mime = $m[1];
            $base64 = $m[2];
        }
        $binary = base64_decode($base64, true);
        if ($binary === false) {
            throw new ApiException(422, 'VALIDATION_ERROR', 'ไฟล์ภาพไม่ถูกต้อง');
        }
        $ext = $mime === 'image/png' ? 'png' : 'jpg';
        $filename = "internship-{$internshipId}-{$kind}-" . date('Ymd-His') . ".{$ext}";
        return $this->drive->upload('attendance-photos', $filename, $binary, $mime);
    }
}
