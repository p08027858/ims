<?php

namespace App\Services;

/** Thin read helper over public.settings (SETTINGS.md §1) shared by services that need a single value. */
final class SettingsService
{
    private SupabaseClient $client;

    public function __construct(?SupabaseClient $client = null)
    {
        $this->client = $client ?? new SupabaseClient();
    }

    public function getInt(string $key, int $default): int
    {
        $value = $this->getRaw($key);
        return $value !== null ? (int) $value : $default;
    }

    public function getFloat(string $key, float $default): float
    {
        $value = $this->getRaw($key);
        return $value !== null ? (float) $value : $default;
    }

    public function getBool(string $key, bool $default): bool
    {
        $value = $this->getRaw($key);
        return $value !== null ? $value === 'true' : $default;
    }

    public function getString(string $key, string $default): string
    {
        $value = $this->getRaw($key);
        return $value ?? $default;
    }

    private function getRaw(string $key): ?string
    {
        try {
            $rows = $this->client->restGet('settings', 'setting_key=eq.' . rawurlencode($key) . '&select=setting_value');
            return $rows[0]['setting_value'] ?? null;
        } catch (SupabaseException) {
            return null;
        }
    }

    /** GET /settings (admin console). */
    public function listAll(): array
    {
        return $this->client->restGet('settings', 'select=setting_key,setting_value,value_type,description&order=setting_key.asc');
    }

    /**
     * SETTINGS.md §5 — only the keys with a documented rule are checked; every other key
     * (booleans, json, keys with no stated rule) passes through unchecked, matching the table
     * in that section exactly rather than inventing rules the blueprint doesn't specify.
     */
    public function assertValid(string $key, string $value): void
    {
        switch ($key) {
            case 'default_gps_radius_m':
                $v = (int) $value;
                $max = $this->getInt('max_gps_radius_m', 500);
                if ($v <= 0 || $v > $max) {
                    throw new AuthException('VALIDATION_ERROR', "รัศมี GPS เริ่มต้นต้องเป็นจำนวนเต็มบวก และไม่เกิน {$max} เมตร");
                }
                break;
            case 'min_hours_before_checkout':
                $v = (float) $value;
                if ($v <= 0 || $v > 12) {
                    throw new AuthException('VALIDATION_ERROR', 'ชั่วโมงขั้นต่ำก่อนลงเวลาออกต้องมากกว่า 0 และไม่เกิน 12 ชั่วโมง');
                }
                break;
            case 'max_upload_size_kb':
                $v = (int) $value;
                if ($v < 100 || $v > 10240) {
                    throw new AuthException('VALIDATION_ERROR', 'ขนาดไฟล์แนบสูงสุดต้องอยู่ระหว่าง 100-10240 KB');
                }
                break;
            case 'sick_leave_certificate_min_days':
                $v = (int) $value;
                if ($v < 1) {
                    throw new AuthException('VALIDATION_ERROR', 'เกณฑ์บังคับแนบใบรับรองแพทย์ต้องเป็นจำนวนเต็มบวก');
                }
                break;
            default:
                // no documented rule (SETTINGS.md §5) — accept as-is
        }
    }

    public function updateSetting(string $key, string $rawValue, string $updatedByUserId): void
    {
        $this->assertValid($key, $rawValue);
        $this->client->restUpdate('settings', 'setting_key=eq.' . rawurlencode($key), [
            'setting_value' => $rawValue,
            'updated_by' => $updatedByUserId,
            'updated_at' => date('c'),
        ]);
    }
}
