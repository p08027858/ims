<?php

namespace App\Controllers;

use App\Services\AuditLogger;
use App\Services\AuthException;
use App\Services\SettingsService;
use App\Support\Session;

/** public.settings admin console (Phase 8 item 3) — only the 5 fields admin/settings.php exposes. */
final class SettingsController
{
    private SettingsService $settings;

    public function __construct()
    {
        $this->settings = new SettingsService();
    }

    public function pageData(array $params): array
    {
        $rows = $this->settings->listAll();
        $byKey = array_column($rows, 'setting_value', 'setting_key');
        return [
            'settings' => [
                'default_gps_radius_m' => (int) ($byKey['default_gps_radius_m'] ?? 100),
                'min_hours_before_checkout' => (float) ($byKey['min_hours_before_checkout'] ?? 4.0),
                'max_upload_size_kb' => (int) ($byKey['max_upload_size_kb'] ?? 1024),
                'sick_leave_certificate_min_days' => (int) ($byKey['sick_leave_certificate_min_days'] ?? 3),
                'score_visible_to_student' => ($byKey['score_visible_to_student'] ?? 'true') === 'true',
            ],
            'formError' => Session::pullFlashError(),
            'formSuccess' => Session::pullFlashData('settings_saved') !== null,
        ];
    }

    /** Validates every field first — only commits if ALL pass (no partial application). */
    public function update(array $params): void
    {
        $user = Session::user();
        $fields = [
            'default_gps_radius_m' => (string) ($_POST['default_gps_radius_m'] ?? ''),
            'min_hours_before_checkout' => (string) ($_POST['min_hours_before_checkout'] ?? ''),
            'max_upload_size_kb' => (string) ($_POST['max_upload_size_kb'] ?? ''),
            'sick_leave_certificate_min_days' => (string) ($_POST['sick_leave_certificate_min_days'] ?? ''),
            'score_visible_to_student' => isset($_POST['score_visible_to_student']) ? 'true' : 'false',
        ];
        try {
            foreach ($fields as $key => $value) {
                $this->settings->assertValid($key, $value);
            }
            foreach ($fields as $key => $value) {
                $this->settings->updateSetting($key, $value, (string) $user['id']);
            }
            (new AuditLogger())->log((string) $user['id'], (string) $user['role'], 'update', 'settings', 'settings', null, null, $fields);
            Session::flashData('settings_saved', ['ok' => true]);
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
        }
        header('Location: /admin/settings');
        exit;
    }
}
