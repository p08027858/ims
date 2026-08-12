<?php

namespace App\Services;

/**
 * Thrown by AuthService with one of the error codes from API_SPEC.md §0.3/§1
 * (INVALID_CREDENTIALS, ACCOUNT_LOCKED, ACCOUNT_PENDING, ACCOUNT_DISABLED*) so
 * AuthController can render the right inline message.
 * *ACCOUNT_DISABLED is not in the original API_SPEC.md error list — added here to cover
 * suspended/disabled accounts at login, a gap in the original spec (flagged in ISSUES.md).
 */
final class AuthException extends \RuntimeException
{
    /**
     * Named $errorCode (not $code) because \Exception already declares a non-readonly
     * $code property — redeclaring it as `readonly string` here caused a fatal
     * "Cannot redeclare non-readonly property" error the moment this class was ever
     * instantiated (caught during Phase 2 manual testing, 2026-07-30).
     */
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly array $details = []
    ) {
        parent::__construct($message);
    }
}
