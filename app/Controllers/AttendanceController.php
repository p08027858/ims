<?php

namespace App\Controllers;

use App\Services\ApiException;
use App\Services\AttendanceService;
use App\Services\InternshipService;
use App\Services\SettingsService;
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
        $userId = (string) (Session::user()['id'] ?? '');
        $settings = new SettingsService();
        $currentInternship = null;
        $context = null;
        $internship = null;
        $company = null;
        $todayAttendance = null;

        try {
            $currentInternship = (new InternshipService($this->client))->getCurrentInternshipForStudentUser($userId);
            $context = $this->attendance->getActiveInternshipContext($userId);
            if ($currentInternship !== null) {
                $internship = $currentInternship;
            }
            if ($context !== null) {
                $internship = $this->getInternshipById((int) $context['id']);
                $company = [
                    'name' => (string) ($context['company_name'] ?? ''),
                    'latitude' => $context['latitude'],
                    'longitude' => $context['longitude'],
                    'gps_radius_m' => (float) $context['gps_radius_m'],
                ];
                $todayAttendance = $this->normalizeAttendanceRow(
                    $this->attendance->getTodayAttendance((int) $context['id'])
                );
            }
        } catch (\Throwable $e) {
            error_log('IMS attendance page data fallback: user_id=' . $userId . ' message=' . $e->getMessage());
            $context = null;
            $internship = $currentInternship;
            $company = null;
            $todayAttendance = null;
        }

        $defaultRadius = (float) $settings->getInt('default_gps_radius_m', 100);
        $minHoursBeforeCheckout = $context !== null
            ? (float) $context['min_hours_before_checkout']
            : (float) $settings->getFloat('min_hours_before_checkout', 4.0);
        $allowedRadiusM = $company !== null
            ? (float) ($company['gps_radius_m'] ?? $defaultRadius)
            : $defaultRadius;
        $elapsedHours = $this->elapsedHoursFromAttendance($todayAttendance);
        $photoRequired = $settings->getBool('attendance_photo_required', false);

        return [
            'noActiveInternship' => $context === null && $internship === null,
            'today' => date('j F Y', strtotime('+543 years')),
            'companyName' => (string) ($company['name'] ?? ''),
            'companyLat' => $company['latitude'] ?? null,
            'companyLng' => $company['longitude'] ?? null,
            'allowedRadiusM' => $allowedRadiusM,
            'minHoursBeforeCheckout' => $minHoursBeforeCheckout,
            'photoRequired' => $photoRequired,
            'elapsedHours' => $elapsedHours,
            'canCheckout' => $elapsedHours >= $minHoursBeforeCheckout,
            'internship' => $internship,
            'currentInternship' => $internship,
            'company' => $company,
            'todayAttendance' => $todayAttendance,
            'attendance' => $todayAttendance,
        ];
    }

    public function checkIn(array $params): void
    {
        $this->handleAttendanceAction('in');
    }

    public function checkOut(array $params): void
    {
        $this->handleAttendanceAction('out');
    }

    public function historyPageData(array $params): array
    {
        $records = [];
        $summary = [
            'total_hours_logged' => 0,
            'total_required_hours' => 0,
            'percent_complete' => 0,
            'days_present' => 0,
            'days_late' => 0,
            'days_absent' => 0,
        ];

        try {
            $context = $this->attendance->getActiveInternshipContext((string) (Session::user()['id'] ?? ''));
            if ($context !== null) {
                $internship = $this->getInternshipById((int) $context['id']);
                $requiredHours = max(0, (int) ($internship['total_required_hours'] ?? 0));
                $records = $this->attendance->listHistory((int) $context['id']);
                $summary = $this->attendance->getSummary((int) $context['id'], $requiredHours);
            }
        } catch (\Throwable) {
            $records = [];
        }

        return [
            'summary' => $summary,
            'records' => $records,
            'logs' => $records,
        ];
    }

    private function handleAttendanceAction(string $defaultType): void
    {
        $input = $this->requestInput();
        $type = ($input['type'] ?? $defaultType) === 'out' ? 'out' : 'in';
        $lat = (float) ($input['latitude'] ?? $input['lat'] ?? 0);
        $lng = (float) ($input['longitude'] ?? $input['lng'] ?? 0);
        $accuracyM = (float) ($input['accuracy_m'] ?? $input['accuracy'] ?? 0);
        $photoBase64 = isset($input['photo']) && is_string($input['photo']) ? $input['photo'] : null;

        try {
            $context = $this->attendance->getActiveInternshipContext((string) (Session::user()['id'] ?? ''));
            if ($context === null) {
                throw new ApiException(422, 'NO_ACTIVE_INTERNSHIP', 'No active internship found.');
            }

            $data = $type === 'out'
                ? $this->attendance->checkout($context, $lat, $lng, $accuracyM)
                : $this->attendance->checkin($context, $lat, $lng, $accuracyM, $photoBase64);

            if ($this->wantsJson()) {
                $this->respondJson(200, ['success' => true, 'data' => $data]);
            }
        } catch (ApiException $e) {
            if ($this->wantsJson()) {
                $this->respondJson($e->httpStatus, [
                    'success' => false,
                    'error' => [
                        'code' => $e->errorCode,
                        'message' => $e->getMessage(),
                        'details' => $e->details,
                    ],
                ]);
            }
            Session::flashError($e->getMessage());
        } catch (\Throwable $e) {
            if ($this->wantsJson()) {
                $this->respondJson(422, [
                    'success' => false,
                    'error' => [
                        'code' => 'ATTENDANCE_FAILED',
                        'message' => $e->getMessage(),
                    ],
                ]);
            }
            Session::flashError($e->getMessage());
        }

        header('Location: /student/attendance');
        exit;
    }

    /** @return array<string, mixed> */
    private function requestInput(): array
    {
        if ($this->wantsJson()) {
            $decoded = json_decode((string) file_get_contents('php://input'), true);
            return is_array($decoded) ? $decoded : [];
        }

        return $_POST;
    }

    private function wantsJson(): bool
    {
        return str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')
            || isset($_SERVER['HTTP_X_REQUESTED_WITH']);
    }

    private function respondJson(int $status, array $payload): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function getInternshipById(int $internshipId): ?array
    {
        try {
            $rows = $this->client->restGet(
                'internships',
                'id=eq.' . $internshipId . '&deleted_at=is.null&limit=1&select=*'
            );
        } catch (\Throwable) {
            $rows = $this->client->restGet(
                'internships',
                'id=eq.' . $internshipId . '&limit=1&select=*'
            );
        }

        return $rows[0] ?? null;
    }

    private function normalizeAttendanceRow(?array $row): ?array
    {
        if ($row === null) {
            return null;
        }

        return [
            'id' => $row['id'] ?? null,
            'date' => (string) substr((string) ($row['check_in_at'] ?? $row['created_at'] ?? date('c')), 0, 10),
            'check_in_time' => $row['check_in_at'] ?? null,
            'check_out_time' => $row['check_out_at'] ?? null,
            'status' => $row['status'] ?? null,
            'check_in_status' => $row['status'] ?? null,
            'check_out_status' => !empty($row['check_out_at']) ? 'normal' : null,
            'total_hours' => null,
        ];
    }

    private function elapsedHoursFromAttendance(?array $attendance): float
    {
        if ($attendance === null || empty($attendance['check_in_time'])) {
            return 0.0;
        }

        if (isset($attendance['total_hours']) && $attendance['total_hours'] !== null) {
            return round((float) $attendance['total_hours'], 2);
        }

        $inTs = strtotime((string) $attendance['check_in_time']);
        $outTs = !empty($attendance['check_out_time'])
            ? strtotime((string) $attendance['check_out_time'])
            : time();

        if ($inTs === false || $outTs === false || $outTs < $inTs) {
            return 0.0;
        }

        return round(($outTs - $inTs) / 3600, 2);
    }
}

