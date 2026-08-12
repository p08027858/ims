<?php

namespace App\Controllers;

use App\Services\ApiException;
use App\Services\AttendanceService;
use App\Services\SettingsService;
use App\Services\SupabaseClient;
use App\Support\Session;

/**
 * attendance (Phase 5). checkin()/checkout() are this project's first real JSON API endpoints —
 * every prior phase only ever needed redirect-with-flash forms, but live GPS coordinates only
 * exist in the browser (navigator.geolocation), so the check-in page must call these via
 * fetch() and handle the {success,data}/{success,error} envelope itself (API_SPEC.md §0.1/§5).
 */
final class AttendanceController
{
    private AttendanceService $attendance;

    public function __construct()
    {
        $this->attendance = new AttendanceService();
    }

    /** GET /student/attendance loader — check-in/out page. */
    public function checkinPageData(array $params): array
    {
        $userId = (string) Session::user()['id'];
        $context = $this->attendance->getActiveInternshipContext($userId);
        if ($context === null) {
            return ['noActiveInternship' => true];
        }
        $today = $this->attendance->getTodayAttendance($context['id']);
        $checkedIn = $today !== null && $today['check_in_at'] !== null;
        $checkedOut = $today !== null && $today['check_out_at'] !== null;
        $elapsedHours = $checkedIn ? (time() - strtotime($today['check_in_at'])) / 3600 : 0;

        return [
            'noActiveInternship' => false,
            'companyName' => $context['company_name'],
            'companyLat' => $context['latitude'],
            'companyLng' => $context['longitude'],
            'allowedRadiusM' => $context['gps_radius_m'],
            'minHoursBeforeCheckout' => $context['min_hours_before_checkout'],
            'photoRequired' => (new SettingsService())->getBool('attendance_photo_required', false),
            'checkedIn' => $checkedIn,
            'checkedOut' => $checkedOut,
            'checkInAt' => $checkedIn ? date('H:i', strtotime($today['check_in_at'])) : null,
            'elapsedHours' => round($elapsedHours, 2),
        ];
    }

    public function checkin(array $params): void
    {
        $this->respondJson(function () {
            $this->enforceRateLimit();
            $userId = (string) Session::user()['id'];
            $context = $this->attendance->getActiveInternshipContext($userId);
            if ($context === null) {
                throw new ApiException(404, 'NO_ACTIVE_INTERNSHIP', 'ไม่พบการฝึกงานที่กำลังดำเนินอยู่ของบัญชีนี้');
            }
            $body = $this->jsonBody();
            [$lat, $lng, $accuracy] = $this->requireCoordinates($body);
            return $this->attendance->checkin($context, $lat, $lng, $accuracy, $body['photo'] ?? null);
        });
    }

    public function checkout(array $params): void
    {
        $this->respondJson(function () {
            $this->enforceRateLimit();
            $userId = (string) Session::user()['id'];
            $context = $this->attendance->getActiveInternshipContext($userId);
            if ($context === null) {
                throw new ApiException(404, 'NO_ACTIVE_INTERNSHIP', 'ไม่พบการฝึกงานที่กำลังดำเนินอยู่ของบัญชีนี้');
            }
            $body = $this->jsonBody();
            [$lat, $lng, $accuracy] = $this->requireCoordinates($body);
            return $this->attendance->checkout($context, $lat, $lng, $accuracy);
        });
    }

    /** GET /student/attendance/history loader. */
    public function historyPageData(array $params): array
    {
        $userId = (string) Session::user()['id'];
        $context = $this->attendance->getActiveInternshipContext($userId);
        if ($context === null) {
            return ['summary' => ['total_hours_logged' => 0, 'total_required_hours' => 0, 'percent_complete' => 0, 'days_present' => 0, 'days_late' => 0, 'days_absent' => 0], 'records' => []];
        }
        $internships = (new SupabaseClient())->restGet('internships', 'id=eq.' . $context['id'] . '&select=total_required_hours');
        $totalRequired = (int) ($internships[0]['total_required_hours'] ?? 0);

        return [
            'summary' => $this->attendance->getSummary($context['id'], $totalRequired),
            'records' => $this->attendance->listHistory($context['id']),
        ];
    }

    /** API_SPEC.md §15: 10 req/min per user across all POST /attendance/* endpoints. */
    private function enforceRateLimit(): void
    {
        if (!Session::checkRateLimit('attendance', 10, 60)) {
            throw new ApiException(429, 'RATE_LIMITED', 'เรียกใช้งานถี่เกินไป กรุณาลองใหม่ภายหลัง');
        }
    }

    /** @return array{0:float,1:float,2:float} [lat, lng, accuracy_m] */
    private function requireCoordinates(array $body): array
    {
        if (!isset($body['lat'], $body['lng'])) {
            throw new ApiException(422, 'GPS_PERMISSION_DENIED', 'ไม่พบพิกัด GPS กรุณาอนุญาตการเข้าถึงตำแหน่ง');
        }
        return [(float) $body['lat'], (float) $body['lng'], (float) ($body['accuracy_m'] ?? 0)];
    }

    private function jsonBody(): array
    {
        $decoded = json_decode((string) file_get_contents('php://input'), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function respondJson(callable $fn): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $data = $fn();
            http_response_code(200);
            echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        } catch (ApiException $e) {
            http_response_code($e->httpStatus);
            echo json_encode([
                'success' => false,
                'error' => ['code' => $e->errorCode, 'message' => $e->getMessage(), 'details' => $e->details],
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}
