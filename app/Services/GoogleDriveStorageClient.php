<?php

namespace App\Services;

class GoogleDriveStorageClient
{
    private const USER_AGENT = 'ims-app-server/1.0 (+google-drive-storage-proxy)';
    private string $webAppUrl = '';
    private string $sharedSecret = '';

    public function __construct()
    {
        $configFile = __DIR__ . '/../../config/google_drive.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
            $this->webAppUrl = (string) ($config['web_app_url'] ?? '');
            $this->sharedSecret = (string) ($config['shared_secret'] ?? '');
        }
    }

    public function upload(string $category, string $filename, string $binaryData, string $contentType, string $uploadedBy = ''): string
    {
        if (empty($this->webAppUrl)) return '';
        
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
            if ($location) [$status, $raw] = $this->get($location);
        }

        $decoded = json_decode((string) $raw, true);
        return (string) ($decoded['url'] ?? '');
    }

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
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $raw = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [$status, (string)$raw];
    }

    private function get(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPGET => true,
            CURLOPT_USERAGENT => self::USER_AGENT,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $raw = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [$status, (string)$raw];
    }

    private function extractLocation(string $rawResponseWithHeaders): ?string
    {
        return preg_match('/^location:\s*(\S+)/mi', $rawResponseWithHeaders, $m) ? trim($m[1]) : null;
    }
}