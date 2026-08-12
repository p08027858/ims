<?php

namespace App\Services;

/**
 * Minimal cURL wrapper around Supabase Auth (GoTrue) and PostgREST — no Composer dependency,
 * matching MASTER_SPEC.md §4 ("ไม่บังคับใช้ framework หนัก"). Mirrors exactly the request shape
 * proven to work during Phase 1 manual verification (custom non-browser User-Agent is required
 * or Supabase's secret-key/browser heuristic rejects the request — see ISSUES.md history).
 *
 * All REST (`/rest/v1/...`) calls use the service_role key, i.e. this class always runs
 * server-side and bypasses Row Level Security by design (DATABASE.md §0/§12) — the PHP layer
 * (AuthService/AuthGuard/controllers) is the real access-control boundary, per PERMISSIONS.md.
 */
final class SupabaseClient
{
    private string $url;
    private string $anonKey;
    private string $serviceRoleKey;
    private const USER_AGENT = 'IMS-THAI-Backend-PHP/1.0 (server-side; not a browser)';

    public function __construct(?string $configPath = null)
    {
        $configPath ??= __DIR__ . '/../../config/supabase.php';
        if (!is_file($configPath)) {
            throw new \RuntimeException(
                "Missing {$configPath} — copy config/supabase.example.php to config/supabase.php " .
                'and fill in your Supabase project values (DEPLOYMENT.md §3).'
            );
        }
        $config = require $configPath;
        $this->url = rtrim($config['url'], '/');
        $this->anonKey = $config['anon_key'];
        $this->serviceRoleKey = $config['service_role_key'];
    }

    // ---------------------------------------------------------------------
    // Auth (GoTrue) — /auth/v1/*
    // ---------------------------------------------------------------------

    /** Self-service signup (student self-register, RULE-AUTH-01). Uses the anon key like a real client. */
    public function authSignUp(string $email, string $password, array $userMetadata): array
    {
        return $this->request('POST', '/auth/v1/signup', $this->anonKey, [
            'email' => $email,
            'password' => $password,
            'data' => $userMetadata,
        ]);
    }

    /** Admin-provisioned account (company/teacher/admin, RULE-AUTH-02) — bypasses email confirmation. */
    public function authAdminCreateUser(string $email, string $password, array $userMetadata): array
    {
        return $this->request('POST', '/auth/v1/admin/users', $this->serviceRoleKey, [
            'email' => $email,
            'password' => $password,
            'email_confirm' => true,
            'user_metadata' => $userMetadata,
        ]);
    }

    public function authSignIn(string $email, string $password): array
    {
        return $this->request('POST', '/auth/v1/token?grant_type=password', $this->anonKey, [
            'email' => $email,
            'password' => $password,
        ]);
    }

    public function authRefresh(string $refreshToken): array
    {
        return $this->request('POST', '/auth/v1/token?grant_type=refresh_token', $this->anonKey, [
            'refresh_token' => $refreshToken,
        ]);
    }

    public function authSignOut(string $accessToken): void
    {
        try {
            $this->request('POST', '/auth/v1/logout', $this->anonKey, new \stdClass(), $accessToken);
        } catch (SupabaseException) {
            // Logout is best-effort: an already-expired token shouldn't block the local logout flow.
        }
    }

    public function authResetPasswordForEmail(string $email): void
    {
        $this->request('POST', '/auth/v1/recover', $this->anonKey, ['email' => $email]);
    }

    /** Used only by the one-off CLI bootstrap script (DEPLOYMENT.md §8) — never expose via a route. */
    public function authAdminDeleteUser(string $userId): void
    {
        $this->request('DELETE', "/auth/v1/admin/users/{$userId}", $this->serviceRoleKey);
    }

    // ---------------------------------------------------------------------
    // REST (PostgREST) — /rest/v1/* — always service_role (see class docblock)
    // ---------------------------------------------------------------------

    /** @return array<int, array<string, mixed>> */
    public function restGet(string $table, string $query = ''): array
    {
        $path = "/rest/v1/{$table}" . ($query !== '' ? "?{$query}" : '');
        return $this->request('GET', $path, $this->serviceRoleKey);
    }

    public function restInsert(string $table, array $data, string $onConflict = ''): array
    {
        $path = "/rest/v1/{$table}" . ($onConflict !== '' ? "?on_conflict={$onConflict}" : '');
        $prefer = $onConflict !== '' ? 'resolution=merge-duplicates,return=representation' : 'return=representation';
        return $this->request('POST', $path, $this->serviceRoleKey, $data, null, $prefer);
    }

    public function restUpdate(string $table, string $query, array $data): array
    {
        return $this->request('PATCH', "/rest/v1/{$table}?{$query}", $this->serviceRoleKey, $data, null, 'return=representation');
    }

    /**
     * Real hard delete — first used in Phase 10 for `DELETE /admin/internships/{id}` (RULE-SEC-01).
     * No caller before Phase 10 ever needed this (every prior "delete" in the app was a status
     * change or an admin-approval cascade handled by Postgres's own `on delete cascade`), which is
     * why it didn't exist until now — see ISSUES.md history.
     */
    public function restDelete(string $table, string $query): array
    {
        return $this->request('DELETE', "/rest/v1/{$table}?{$query}", $this->serviceRoleKey, null, null, 'return=representation');
    }

    // ---------------------------------------------------------------------
    // Storage — /storage/v1/object/* — raw binary body, not JSON, so this
    // bypasses request()'s always-json_encode behavior with its own curl call.
    // ---------------------------------------------------------------------

    /** @return string the storage object key (path) that was uploaded, unchanged from the input. */
    public function storageUpload(string $bucket, string $path, string $binaryData, string $contentType): string
    {
        $ch = curl_init($this->url . '/storage/v1/object/' . $bucket . '/' . ltrim($path, '/'));
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => [
                'apikey: ' . $this->serviceRoleKey,
                'Authorization: Bearer ' . $this->serviceRoleKey,
                'Content-Type: ' . $contentType,
                'x-upsert: true',
            ],
            CURLOPT_USERAGENT => self::USER_AGENT,
            CURLOPT_POSTFIELDS => $binaryData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $raw = curl_exec($ch);
        if ($raw === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException("Supabase Storage upload failed (network): {$error}");
        }
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($status >= 400) {
            $decoded = json_decode((string) $raw, true);
            throw new SupabaseException($status, is_array($decoded) ? $decoded : []);
        }
        return $path;
    }

    // ---------------------------------------------------------------------
    // Internal
    // ---------------------------------------------------------------------

    private function request(
        string $method,
        string $path,
        string $apiKey,
        array|\stdClass|null $body = null,
        ?string $bearerOverride = null,
        ?string $prefer = null
    ): array {
        $ch = curl_init($this->url . $path);
        $headers = [
            'apikey: ' . $apiKey,
            'Authorization: Bearer ' . ($bearerOverride ?? $apiKey),
            'Content-Type: application/json',
        ];
        if ($prefer !== null) {
            $headers[] = 'Prefer: ' . $prefer;
        }

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_USERAGENT => self::USER_AGENT,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if ($body !== null && $method !== 'GET') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        }

        $raw = curl_exec($ch);
        if ($raw === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException("Supabase request failed (network): {$error}");
        }
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = $raw === '' ? [] : (json_decode($raw, true) ?? []);

        if ($status >= 400) {
            throw new SupabaseException($status, is_array($decoded) ? $decoded : []);
        }

        // PostgREST returns a bare JSON array for collections; GoTrue returns an object.
        return is_array($decoded) ? $decoded : [];
    }
}
