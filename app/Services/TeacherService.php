<?php

namespace App\Services;

/**
 * Teacher account creation — RULE-AUTH-02 requires this to be Admin-created (no self-register),
 * same shape as CompanyService::createCompanyWithSupervisor(). Not explicitly listed in
 * AI_AGENT_PHASES.md Phase 3's task list (only company_supervisors was), but discovered as a
 * hard prerequisite for Phase 4 (POST /internships needs a real teacher_id to assign) — added
 * here rather than blocking Phase 4 on a missing Phase-3-adjacent feature.
 */
final class TeacherService
{
    private SupabaseClient $client;

    public function __construct(?SupabaseClient $client = null)
    {
        $this->client = $client ?? new SupabaseClient();
    }

    /** @return array<int, array{id:int,name:string}> */
    public function listTeachers(): array
    {
        $rows = $this->client->restGet('teachers', 'select=id,user_id,first_name,last_name&order=first_name.asc');
        $teachers = array_map(
            static function (array $t): array {
                $name = trim((string) (($t['first_name'] ?? '') . ' ' . ($t['last_name'] ?? '')));

                return [
                    'id' => (int) ($t['id'] ?? 0),
                    'name' => $name !== '' ? $name : ('Teacher #' . (int) ($t['id'] ?? 0)),
                ];
            },
            $rows
        );

        $teachers = array_values(array_filter($teachers, static fn (array $t): bool => $t['id'] > 0));
        return $teachers;
    }

    public function hasTeachers(): bool
    {
        return !empty($this->client->restGet('teachers', 'select=id&limit=1'));
    }

    /**
     * @param array{first_name:string,last_name:string,faculty_id:string|int,department_id:string|int,
     *              email:string,password:string,phone?:string,position?:string} $data
     */
    public function createTeacher(array $data): void
    {
        foreach (['first_name', 'last_name', 'faculty_id', 'department_id', 'email', 'password'] as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                throw new AuthException('VALIDATION_ERROR', 'กรุณากรอกข้อมูลครูนิเทศให้ครบทุกช่องที่จำเป็น');
            }
        }

        // RULE-AUTH-02: admin-create endpoint (email_confirm=true) — same reasoning as
        // AuthService::register()/CompanyService::createCompanyWithSupervisor().
        try {
            $signUpResp = $this->client->authAdminCreateUser($data['email'], $data['password'], ['role' => 'teacher']);
        } catch (SupabaseException $e) {
            throw new AuthException('VALIDATION_ERROR', 'สร้างบัญชีครูนิเทศไม่สำเร็จ (อีเมลนี้อาจมีอยู่แล้ว)', ['cause' => $e->getMessage()]);
        }
        $userId = $signUpResp['id'] ?? $signUpResp['user']['id'] ?? null;
        if ($userId === null) {
            throw new AuthException('VALIDATION_ERROR', 'สร้างบัญชีครูนิเทศไม่สำเร็จ');
        }

        try {
            $this->client->restInsert('teachers', [
                'user_id' => $userId,
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'faculty_id' => (int) $data['faculty_id'],
                'department_id' => (int) $data['department_id'],
                'position' => trim((string) ($data['position'] ?? '')) ?: null,
                'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
            ]);
        } catch (SupabaseException $e) {
            $this->client->authAdminDeleteUser($userId);
            throw new AuthException('VALIDATION_ERROR', 'บันทึกข้อมูลครูนิเทศไม่สำเร็จ', ['cause' => $e->getMessage()]);
        }
    }
}
