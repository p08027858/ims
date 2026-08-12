<?php

namespace App\Services;

/**
 * Thrown by SupabaseClient on any non-2xx response. Carries the raw HTTP status and the
 * decoded JSON error body so callers (AuthService) can branch on Supabase's error_code/msg
 * (e.g. GoTrue's "invalid_grant" for wrong password, "email_address_invalid" for blocked
 * domains — see the manual verification notes in ISSUES.md from Phase 1 testing).
 */
final class SupabaseException extends \RuntimeException
{
    public function __construct(
        public readonly int $statusCode,
        public readonly array $body,
        string $message = ''
    ) {
        parent::__construct($message !== '' ? $message : ($body['msg'] ?? $body['message'] ?? 'Supabase request failed'));
    }

    public function errorCode(): ?string
    {
        return $this->body['error_code'] ?? $this->body['code'] ?? null;
    }
}
