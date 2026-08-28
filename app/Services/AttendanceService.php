<?php

namespace App\Services;

/**
 * Attendance service aligned with the current production `attendance` schema.
 *
 * Production currently stores a compact row shape around:
 * - internship_id
 * - check_in_at / check_out_at
 * - check_in_lat / check_in_lng
 * - status
 * - created_at / deleted_at
 */
final class AttendanceService
{
    private SupabaseClient $client;
    private SettingsService $settings;

    public function __construct(?SupabaseClient $client = null)
    {
        $this->client = $client ?? new SupabaseClient();
        $this->settings = new SettingsService($this->client);
    }

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
     * @return array{id:int,student_id:int,company_id:int,company_name:string,latitude:float,longitude:float,gps_radius_m:float,min_hours_before_checkout:float}|null
     */
    public function getActiveInternshipContext(string $userId): ?array
    {
        $internship = (new InternshipService($this->client))->getCurrentInternshipForStudentUser($userId);
        if ($internship === null) {
            return null;
        }

        try {
            $companies = $this->client->restGet(
                'companies',
                'id=eq.' . $internship['company_id'] . '&select=latitude,longitude,gps_radius_m,name'
            );
        } catch (\Throwable $e) {
            error_log(
                'IMS attendance company lookup fallback: internship_id='
                . (int) ($internship['id'] ?? 0)
                . ' message='
                . $e->getMessage()
            );
            $companies = [];
        }

        $batchId = (int) ($internship['batch_id'] ?? 0);
        if ($batchId > 0) {
            try {
                $batches = $this->client->restGet(
                    'batches',
                    'id=eq.' . $batchId . '&select=min_hours_before_checkout'
                );
            } catch (\Throwable $e) {
                error_log(
                    'IMS attendance batch lookup fallback: internship_id='
                    . (int) ($internship['id'] ?? 0)
                    . ' batch_id='
                    . $batchId
                    . ' message='
                    . $e->getMessage()
                );
                $batches = [];
            }
        } else {
            $batches = [];
        }

        return [
            'id' => (int) $internship['id'],
            'student_id' => (int) ($internship['student_id'] ?? 0),
            'company_id' => (int) $internship['company_id'],
            'company_name' => (string) ($companies[0]['name'] ?? ''),
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
        [$start, $end] = $this->dayRange(date('Y-m-d'));
        $rows = $this->client->restGet(
            'attendance',
            'internship_id=eq.' . $internshipId
            . '&created_at=gte.' . rawurlencode($start)
            . '&created_at=lt.' . rawurlencode($end)
            . '&order=created_at.desc&select=*'
        );

        return $rows[0] ?? null;
    }

    public function checkin(array $context, float $lat, float $lng, float $accuracyM, ?string $photoBase64): array
    {
        $internshipId = (int) $context['id'];
        $studentId = (int) ($context['student_id'] ?? 0);
        if ($studentId <= 0) {
            throw new ApiException(422, 'STUDENT_NOT_FOUND', 'No student_id was resolved for this attendance record.');
        }
        $existing = $this->getTodayAttendance($internshipId);

        if ($existing !== null && !empty($existing['check_in_at'])) {
            throw new ApiException(409, 'ALREADY_CHECKED_IN', 'You have already checked in today.', [
                'attendance_id' => $existing['id'],
                'check_in_at' => $existing['check_in_at'],
                'check_in_status' => $existing['status'] ?? null,
            ]);
        }

        $photoRequired = $this->settings->getBool('attendance_photo_required', false);
        if ($photoRequired && ($photoBase64 === null || trim($photoBase64) === '')) {
            throw new ApiException(422, 'PHOTO_REQUIRED', 'A confirmation photo is required for check-in.');
        }

        $distance = self::haversineDistanceMeters($lat, $lng, $context['latitude'], $context['longitude']);
        if ($distance > (float) $context['gps_radius_m']) {
            $payload = [
                'internship_id' => $internshipId,
                'student_id' => $studentId,
                'check_in_lat' => $lat,
                'check_in_lng' => $lng,
                'status' => 'out_of_range',
            ];
            if ($existing !== null) {
                $this->client->restUpdate('attendance', 'id=eq.' . $existing['id'], $payload);
            } else {
                $this->client->restInsert('attendance', $payload);
            }

            throw new ApiException(422, 'OUT_OF_GPS_RANGE', 'You are outside the allowed GPS range.', [
                'distance_m' => round($distance, 2),
                'allowed_m' => $context['gps_radius_m'],
            ]);
        }

        $status = $this->isPastLateThreshold() ? 'late' : 'present';
        $payload = [
            'internship_id' => $internshipId,
            'student_id' => $studentId,
            'check_in_at' => date('c'),
            'check_in_lat' => $lat,
            'check_in_lng' => $lng,
            'status' => $status,
        ];

        $rows = $existing !== null
            ? $this->client->restUpdate('attendance', 'id=eq.' . $existing['id'], $payload)
            : $this->client->restInsert('attendance', $payload);
        $row = $rows[0] ?? $payload;

        return [
            'attendance_id' => $row['id'] ?? null,
            'check_in_at' => $row['check_in_at'] ?? $payload['check_in_at'],
            'check_in_distance_m' => round($distance, 2),
            'check_in_status' => (string) ($row['status'] ?? $status),
        ];
    }

    public function checkout(array $context, float $lat, float $lng, float $accuracyM): array
    {
        $internshipId = (int) $context['id'];
        $existing = $this->getTodayAttendance($internshipId);

        if ($existing === null || empty($existing['check_in_at'])) {
            throw new ApiException(422, 'NOT_CHECKED_IN', 'You have not checked in today.');
        }
        if (!empty($existing['check_out_at'])) {
            throw new ApiException(409, 'ALREADY_CHECKED_OUT', 'You have already checked out today.', [
                'attendance_id' => $existing['id'],
                'check_out_at' => $existing['check_out_at'],
                'total_hours' => self::deriveHours($existing),
            ]);
        }

        $elapsedHours = self::deriveHours([
            'check_in_at' => $existing['check_in_at'],
            'check_out_at' => date('c'),
        ]);
        $requiredHours = (float) $context['min_hours_before_checkout'];
        if ($elapsedHours < $requiredHours) {
            throw new ApiException(
                422,
                'CHECKOUT_TOO_EARLY',
                sprintf('You must work at least %.1f hours before checking out.', $requiredHours),
                ['elapsed_hours' => round($elapsedHours, 2), 'required_hours' => $requiredHours]
            );
        }

        $distance = self::haversineDistanceMeters($lat, $lng, $context['latitude'], $context['longitude']);
        if ($distance > (float) $context['gps_radius_m']) {
            throw new ApiException(422, 'OUT_OF_GPS_RANGE', 'You are outside the allowed GPS range for check-out.', [
                'distance_m' => round($distance, 2),
                'allowed_m' => $context['gps_radius_m'],
            ]);
        }

        $rows = $this->client->restUpdate('attendance', 'id=eq.' . $existing['id'], [
            'check_out_at' => date('c'),
        ]);
        $row = $rows[0] ?? [];

        return [
            'attendance_id' => $row['id'] ?? $existing['id'],
            'check_out_at' => $row['check_out_at'] ?? date('c'),
            'total_hours' => $elapsedHours,
            'check_out_status' => 'normal',
        ];
    }

    public function closeStaleIncompleteDays(): int
    {
        [$startToday] = $this->dayRange(date('Y-m-d'));
        $rows = $this->client->restGet(
            'attendance',
            'created_at=lt.' . rawurlencode($startToday) . '&check_in_at=not.is.null&check_out_at=is.null&select=id'
        );

        foreach ($rows as $row) {
            $this->client->restUpdate('attendance', 'id=eq.' . $row['id'], ['status' => 'absent']);
        }

        return count($rows);
    }

    public function listHistory(int $internshipId): array
    {
        $rows = $this->client->restGet(
            'attendance',
            'internship_id=eq.' . $internshipId . '&order=created_at.desc&select=created_at,check_in_at,check_out_at,status'
        );

        return array_map(function (array $row): array {
            return [
                'date' => $this->rowDate($row),
                'check_in' => !empty($row['check_in_at']) ? date('H:i', strtotime((string) $row['check_in_at'])) : '-',
                'check_out' => !empty($row['check_out_at']) ? date('H:i', strtotime((string) $row['check_out_at'])) : '-',
                'hours' => self::deriveHours($row),
                'status' => (string) ($row['status'] ?? ''),
            ];
        }, $rows);
    }

    public function getSummary(int $internshipId, int $totalRequiredHours): array
    {
        $rows = $this->client->restGet(
            'attendance',
            'internship_id=eq.' . $internshipId . '&select=check_in_at,check_out_at,status'
        );

        $totalHours = 0.0;
        $daysPresent = 0;
        $daysLate = 0;
        $daysAbsent = 0;

        foreach ($rows as $row) {
            $totalHours += self::deriveHours($row);
            match ((string) ($row['status'] ?? '')) {
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

    private function isPastLateThreshold(): bool
    {
        $cutoff = $this->settings->getString('notification_no_checkin_time', '09:00');
        return date('H:i') > $cutoff;
    }

    /** @return array{0:string,1:string} */
    private function dayRange(string $day): array
    {
        return [$day . 'T00:00:00', date('Y-m-d\\T00:00:00', strtotime($day . ' +1 day'))];
    }

    private function rowDate(array $row): string
    {
        $source = (string) ($row['check_in_at'] ?? $row['created_at'] ?? date('c'));
        return substr($source, 0, 10);
    }

    private static function deriveHours(array $row): float
    {
        if (empty($row['check_in_at']) || empty($row['check_out_at'])) {
            return 0.0;
        }

        $in = strtotime((string) $row['check_in_at']);
        $out = strtotime((string) $row['check_out_at']);
        if ($in === false || $out === false || $out <= $in) {
            return 0.0;
        }

        return round(($out - $in) / 3600, 2);
    }
}
