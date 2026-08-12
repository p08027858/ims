<?php

namespace App\Services;

/**
 * Thrown by JSON API endpoints (Phase 5's attendance check-in/out is the first of these —
 * see ISSUES.md history: prior phases only ever needed redirect-with-flash via AuthException,
 * but live GPS coordinates only exist in the browser, so checkin/checkout must be a real
 * fetch()-based JSON call per API_SPEC.md's {success,data}/{success,error} envelope).
 */
final class ApiException extends \RuntimeException
{
    public function __construct(
        public readonly int $httpStatus,
        public readonly string $errorCode,
        string $message,
        public readonly array $details = []
    ) {
        parent::__construct($message);
    }
}
