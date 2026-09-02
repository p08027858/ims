<?php

namespace App\Services;

/**
 * audit_logs (AI_AGENT_PHASES.md Phase 9 item 3, SECURITY.md §5) — every create/update/delete/
 * approve/reject action of consequence calls this. Immutable by design: this class only ever
 * inserts, there is no update()/delete() here, matching SignatureService's precedent for
 * RULE-SIG-01-style immutability (SECURITY.md §5: "ห้ามแก้ไข/ลบแม้แต่ Super Admin").
 */
final class AuditLogger
{
    private SupabaseClient $client;

    public function __construct(?SupabaseClient $client = null)
    {
        $this->client = $client ?? new SupabaseClient();
    }

    /**
     * Never throws — an audit-logging failure must not block the real action it's describing
     * (same principle as AuthService::logAttempt()).
     */
    public function log(
        string $userId,
        string $role,
        string $action,
        string $module,
        string $entityType,
        ?int $entityId,
        ?array $oldValue = null,
        ?array $newValue = null
    ): void {
        try {
            $this->client->restInsert('audit_logs', [
                'user_id' => $userId,
                'action' => $action,
                'entity_name' => $entityType,
                'entity_id' => $entityId,
                'details' => [
                    'role' => $role,
                    'module' => $module,
                    'before' => $oldValue,
                    'after' => $newValue,
                ],
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ]);
        } catch (\Throwable) {
            // best-effort only
        }
    }
}
