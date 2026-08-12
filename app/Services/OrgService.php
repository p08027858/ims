<?php

namespace App\Services;

/**
 * Faculties & departments CRUD (AI_AGENT_PHASES.md Phase 3 item 1) — admin/super_admin only,
 * gated at the route level (config/actions.php, config/routes.php), not here.
 */
final class OrgService
{
    private SupabaseClient $client;

    public function __construct(?SupabaseClient $client = null)
    {
        $this->client = $client ?? new SupabaseClient();
    }

    /** @return array<int, array{id:int,code:string,name:string,status:string,departments:array}> */
    public function listFacultiesWithDepartments(): array
    {
        $faculties = $this->client->restGet('faculties', 'select=id,code,name_th,status&order=name_th.asc');
        $departments = $this->client->restGet('departments', 'select=id,faculty_id,code,name_th,status&order=name_th.asc');

        $byFaculty = [];
        foreach ($departments as $d) {
            $byFaculty[$d['faculty_id']][] = [
                'id' => $d['id'],
                'code' => $d['code'],
                'name' => $d['name_th'],
                'status' => $d['status'],
            ];
        }

        return array_map(static fn (array $f) => [
            'id' => $f['id'],
            'code' => $f['code'],
            'name' => $f['name_th'],
            'status' => $f['status'],
            'departments' => $byFaculty[$f['id']] ?? [],
        ], $faculties);
    }

    /** @param array{code:string,name_th:string,name_en?:string} $data */
    public function createFaculty(array $data): void
    {
        $code = trim((string) ($data['code'] ?? ''));
        $nameTh = trim((string) ($data['name_th'] ?? ''));
        if ($code === '' || $nameTh === '') {
            throw new AuthException('VALIDATION_ERROR', 'กรุณากรอกรหัสคณะและชื่อคณะให้ครบ');
        }

        try {
            $this->client->restInsert('faculties', [
                'code' => $code,
                'name_th' => $nameTh,
                'name_en' => trim((string) ($data['name_en'] ?? '')) ?: null,
            ]);
        } catch (SupabaseException $e) {
            throw new AuthException('VALIDATION_ERROR', 'เพิ่มคณะไม่สำเร็จ (รหัสคณะนี้อาจมีอยู่แล้ว)', ['cause' => $e->getMessage()]);
        }
    }

    public function setFacultyStatus(int $id, string $status): void
    {
        $this->assertActiveStatus($status);
        $this->client->restUpdate('faculties', 'id=eq.' . $id, ['status' => $status]);
    }

    /** @param array{faculty_id:int|string,code:string,name_th:string,name_en?:string} $data */
    public function createDepartment(array $data): void
    {
        $facultyId = (int) ($data['faculty_id'] ?? 0);
        $code = trim((string) ($data['code'] ?? ''));
        $nameTh = trim((string) ($data['name_th'] ?? ''));
        if ($facultyId <= 0 || $code === '' || $nameTh === '') {
            throw new AuthException('VALIDATION_ERROR', 'กรุณาเลือกคณะและกรอกรหัส/ชื่อสาขาให้ครบ');
        }

        try {
            $this->client->restInsert('departments', [
                'faculty_id' => $facultyId,
                'code' => $code,
                'name_th' => $nameTh,
                'name_en' => trim((string) ($data['name_en'] ?? '')) ?: null,
            ]);
        } catch (SupabaseException $e) {
            throw new AuthException('VALIDATION_ERROR', 'เพิ่มสาขาไม่สำเร็จ (รหัสสาขานี้อาจมีอยู่แล้วในคณะเดียวกัน)', ['cause' => $e->getMessage()]);
        }
    }

    public function setDepartmentStatus(int $id, string $status): void
    {
        $this->assertActiveStatus($status);
        $this->client->restUpdate('departments', 'id=eq.' . $id, ['status' => $status]);
    }

    /** DATABASE.md §0.2 `active_status` enum only allows these two values. */
    private function assertActiveStatus(string $status): void
    {
        if (!in_array($status, ['active', 'inactive'], true)) {
            throw new AuthException('VALIDATION_ERROR', 'สถานะไม่ถูกต้อง');
        }
    }
}
