<?php

namespace App\Services;

/**
 * File storage via the Google Apps Script Web App proxy (google-apps-script/Code.gs) —
 * replaces SupabaseClient::storageUpload() as the primary store for attendance photos,
 * daily-log attachments, leave certificates, and digital signatures (2026-08-10, per user
 * request — GAS/Google Drive instead of Supabase Storage, see CHANGELOG.md).
 *
 * config/google_drive.php holds the deployed Web App URL + shared secret — not committed
 * (see that file's own docblock). This class throws SupabaseException on failure for now
 * (reusing the existing exception type rather than adding a parallel one, since every current
 * caller already catches SupabaseException the same way it caught storageUpload() failures).
 */
final class GoogleDriveStorageClient
{
    private const USER_AGENT = 'ims-app-server/1.0 (+google-drive-storage-proxy)';

    private string $webAppUrl;
    private string $sharedSecret;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/google_drive.php';
        $this->webAppUrl = (string) $config['web_app_url'];
        $this->sharedSecret = (string) $config['shared_secret'];
    }

    /**
     * @param string $category one of: attendance-photos, daily-logs, leave-certificates, signatures
     * @return string the file's viewable Drive URL (https://drive.google.com/uc?export=view&id=...)
     */
    public function upload(string $category, string $filename, string $binaryData, string $contentType, string $uploadedBy = ''): string
    {
        $payload = json_encode([
            'secret' => $this->sharedSecret,
            'category' => $category,
            'filename' => $filename,
            'mimeType' => $contentType,
            'base64Data' => base64_encode($binaryData),
            'uploadedBy' => $uploadedBy,
        ], JSON_UNESCAPED_UNICODE);

        // Apps Script's /exec URL actually RUNS doPost() on THIS request, then always
        // 302-redirects to a script.googleusercontent.com/macros/echo?... URL that just serves
        // the already-computed response body — and confirmed live 2026-08-10 via verbose cURL
        // that this specific PHP/cURL build preserves POST across that redirect regardless of
        // CURLOPT_FOLLOWLOCATION/CURLOPT_POSTREDIR, which 405s ("Allow: HEAD, GET") because the
        // echo endpoint only accepts GET/HEAD — even though the actual upload had already
        // succeeded on request #1 by that point. So: follow the redirect manually as a fresh GET
        // rather than relying on cURL's automatic (and here, wrong-method) redirect handling.
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
            throw new SupabaseException($status, [], 'Google Drive upload failed: unexpected response (is the Web App deployed and config/google_drive.php filled in?)');
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
            // Apps Script cold starts + a real Drive write can take a few seconds — more
            // generous than SupabaseClient's typical REST call timeouts on purpose.
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => false, // handled manually — see upload()'s docblock
        ]);
        return $this->exec($ch, includesHeaders: true);
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
        return $this->exec($ch, includesHeaders: false);
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
        // $includesHeaders: post() needs the raw headers prepended (CURLOPT_HEADER) so
        // extractLocation() can find the redirect target; get()'s plain body doesn't.
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
