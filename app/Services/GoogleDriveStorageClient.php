<?php

namespace App\Services;

/**
 * File storage via the Google Apps Script Web App proxy (google-apps-script/Code.gs) —
 * replaces SupabaseClient::storageUpload() as the primary store for attendance photos,
 * daily-log attachments, leave certificates, and digital signatures.
 */
final class GoogleDriveStorageClient
{
    private const USER_AGENT = 'ims-app-server/1.0 (+google-drive-storage-proxy)';

    private string $webAppUrl;
    private string $sharedSecret;

    public function __construct()
    {
        $configFile = __DIR__ . '/../../config/google_drive.php';
        $config = file_exists($configFile) ? require $configFile : [];

        $this->webAppUrl = (string) ($config['web_app_url'] ?? getenv('GOOGLE_DRIVE_WEB_APP_URL') ?: '');
        $this->sharedSecret = (string) ($config['shared_secret'] ?? getenv('GOOGLE_DRIVE_SHARED_SECRET') ?: '');
    }

    /**
     * @param string $category one of: attendance-photos, daily-logs, leave-certificates, signatures
     * @return string the file's viewable Drive URL
     */
    public function upload(string $category, string $filename, string $binaryData, string $contentType, string $uploadedBy = ''): string
    {
        if (empty($this->webAppUrl) || empty($this->sharedSecret)) {
            // หากยังไม่ได้ตั้งค่า Google Apps Script ให้ผ่านไปก่อนโดยไม่ทำให้ระบบล่ม
            return '';
        }

        $payload = json_encode([
            'secret' => $this->sharedSecret,
            'category' => $category,
            'filename' => $filename,
            'mimeType' => $contentType,
            'base64Data' => base64_encode($binaryData),
            'uploadedBy' => $uploadedBy,
        ], JSON_UNESCAPED_UNICODE);

        [$status, $raw] = $this->post($this->webAppUrl, $payload);
        if ($status === 302 || $status === 301) {
            $location = $this->extractLocation($raw);
            if ($location === null) {
                throw new SupabaseException($status, [], 'Google Drive upload failed: redirect with no Location header');
            }
            [$status, $raw] = $this->get($location);
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            throw new SupabaseException($status, [], 'Google Drive upload failed: unexpected response');
        }
        if (($decoded['success'] ?? false) !== true) {
            throw new SupabaseException($status, $decoded, 'Google Drive upload failed: ' . ($decoded['message'] ?? $decoded['error'] ?? 'unknown error'));
        }

        return (string) $decoded['url'];
    }

    /** @return array{0:int,1:string} [http_status, raw_body_including_headers] */
    private function post(string $url, string $jsonPayload): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_USERAGENT => self::USER_AGENT,
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        return $this->exec($ch, true);
    }

    /** @return array{0:int,1:string} [http_status, raw_body] */
    private function get(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPGET => true,
            CURLOPT_USERAGENT => self::USER_AGENT,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        return $this->exec($ch, false);
    }

    /** @return array{0:int,1:string} */
    private function exec(\CurlHandle $ch, bool $includesHeaders): array
    {
        $raw = curl_exec($ch);
        if ($raw === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new SupabaseException(0, [], "Google Drive upload failed (network): {$error}");
        }
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [$status, $raw];
    }

    private function extractLocation(string $rawResponseWithHeaders): ?string
    {
        if (preg_match('/^location:\s*(\S+)/mi', $rawResponseWithHeaders, $m)) {
            return trim($m[1]);
        }
        return null;
    }
}