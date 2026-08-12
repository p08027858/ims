<?php

namespace App\Services;

/**
 * Super Admin manages Admin accounts (AI_AGENT_PHASES.md Phase 10 item 4, ROLES.md §5). Admin has
 * no profile table of its own (ROLES.md §4 — unlike student/company/teacher), so this is a thin
 * wrapper over `public.users` scoped to role in (admin, super_admin).
 */
final class SuperAdminAdminService
{
    private SupabaseClient $client;

    public function __construct(?SupabaseClient $client = null)
    {
        $this->client = $client ?? new SupabaseClient();
    }

    /** @return array<int, array<string,mixed>> */
    public function listAdmins(): array
    {
        return $this->client->restGet('users', 'role=in.(admin,super_admin)&select=id,email,role,status&order=created_at.asc');
    }

    /**
     * Admin accounts never appear in CompanyService::listPendingApprovals() (deliberately excludes
     * role=admin — see that method) because a Super Admin creating one already IS the approval, so
     * this activates the account immediately instead of leaving it in handle_new_user()'s default
     * 'pending' status.
     *
     * @return string the newly created user id
     */
    public function create(string $email, string $password): string
    {
        if (trim($email) === '' || trim($password) === '') {
            throw new AuthException('VALIDATION_ERROR', 'กรุณากรอกอีเมลและรหัสผ่าน');
        }
        if (mb_strlen($password) < 8) {
            throw new AuthException('VALIDATION_ERROR', 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร');
        }

        try {
            $resp = $this->client->authAdminCreateUser($email, $password, ['role' => 'admin']);
        } catch (SupabaseException $e) {
            throw new AuthException('VALIDATION_ERROR', 'สร้างบัญชีผู้ดูแลระบบไม่สำเร็จ (อีเมลนี้อาจมีอยู่แล้ว)', ['cause' => $e->getMessage()]);
        }
        $userId = $resp['id'] ?? $resp['user']['id'] ?? null;
        if ($userId === null) {
            throw new AuthException('VALIDATION_ERROR', 'สร้างบัญชีผู้ดูแลระบบไม่สำเร็จ');
        }

        $this->client->restUpdate('users', 'id=eq.' . $userId, ['status' => 'active']);
        return (string) $userId;
    }

    /**
     * Deliberately scoped to role=admin only — a Super Admin should never be able to suspend
     * another super_admin (or themselves) through this simple toggle (no confirmation/PIN step
     * here since it's a reversible status change, not a RULE-SEC-01 delete).
     */
    public function setStatus(string $userId, string $status): void
    {
        if (!in_array($status, ['active', 'suspended'], true)) {
            throw new AuthException('VALIDATION_ERROR', 'สถานะไม่ถูกต้อง');
        }
        $rows = $this->client->restGet('users', 'id=eq.' . $userId . '&select=role');
        if (!isset($rows[0]) || $rows[0]['role'] !== 'admin') {
            throw new AuthException('VALIDATION_ERROR', 'ไม่พบบัญชีผู้ดูแลระบบนี้');
        }
        $this->client->restUpdate('users', 'id=eq.' . $userId, ['status' => $status]);
    }
}
