<?php

namespace App\Services;

/**
 * Companies + company_supervisors service with student search support.
 */
final class CompanyService
{
    private SupabaseClient $client;

    public function __construct(?SupabaseClient $client = null)
    {
        $this->client = $client ?? new SupabaseClient();
    }

    /**
     * ค้นหารายชื่อสถานประกอบการสำหรับหน้านักศึกษา (/student/companies)
     */
    public function searchCompaniesForStudent(string $query = '', string $province = '', string $industryType = ''): array
    {
        $filters = ['deleted_at=is.null', 'status=eq.active'];

        if (!empty($province) && $province !== 'ทั้งหมด') {
            $filters[] = 'province=eq.' . urlencode($province);
        }

        if (!empty($industryType) && $industryType !== 'ทั้งหมด') {
            $filters[] = 'business_type=eq.' . urlencode($industryType);
        }

        if (!empty($query)) {
            $filters[] = 'or=(name.ilike.*' . urlencode($query) . '*,description.ilike.*' . urlencode($query) . '*)';
        }

        $queryString = implode('&', $filters) . '&select=*&order=name.asc';
        
        try {
            $companies = $this->client->restGet('companies', $queryString);
        } catch (SupabaseException) {
            $companies = [];
        }

        // เสริมข้อมูลตำแหน่งงาน (Jobs) ให้แต่ละบริษัท
        foreach ($companies as &$company) {
            try {
                $jobs = $this->client->restGet('company_job_postings', 'company_id=eq.' . $company['id'] . '&deleted_at=is.null&status=eq.open&select=*');
                $company['jobs'] = $jobs;
                $company['available_positions'] = count($jobs);
            } catch (\Exception) {
                $company['jobs'] = [];
                $company['available_positions'] = 0;
            }
        }

        return $companies;
    }

    public function listApprovedForDirectory(): array
    {
        return $this->searchCompaniesForStudent();
    }

    public function listPendingApprovals(): array
    {
        $items = [];

        $pendingUsers = $this->client->restGet(
            'users',
            'status=eq.pending&role=in.(student,company,teacher)&select=id,role,email'
        );
        foreach ($pendingUsers as $u) {
            [$name, $meta] = $this->resolvePendingProfile($u['role'], $u['id'], $u['email']);
            $items[] = ['id' => $u['id'], 'type' => $u['role'], 'name' => $name, 'meta' => $meta];
        }

        $pendingCompanies = $this->client->restGet(
            'companies',
            'status=eq.pending&deleted_at=is.null&select=id,name,business_type,province'
        );
        foreach ($pendingCompanies as $c) {
            $meta = 'สถานประกอบการ';
            if (!empty($c['business_type'])) {
                $meta .= ' · ' . $c['business_type'];
            }
            $items[] = ['id' => $c['id'], 'type' => 'company', 'name' => $c['name'], 'meta' => $meta];
        }

        return $items;
    }

    private function resolvePendingProfile(string $role, string $userId, string $email): array
    {
        try {
            if ($role === 'student') {
                $rows = $this->client->restGet('students', 'user_id=eq.' . $userId . '&select=student_code,first_name,last_name,department_id');
                if (isset($rows[0])) {
                    $dept = $this->client->restGet('departments', 'id=eq.' . $rows[0]['department_id'] . '&select=name_th');
                    $deptName = $dept[0]['name_th'] ?? '';
                    return [trim($rows[0]['first_name'] . ' ' . $rows[0]['last_name']), 'นักศึกษา · ' . $deptName];
                }
            } elseif ($role === 'company') {
                $rows = $this->client->restGet('company_supervisors', 'user_id=eq.' . $userId . '&select=first_name,last_name,company_id');
                if (isset($rows[0])) {
                    $company = $this->client->restGet('companies', 'id=eq.' . $rows[0]['company_id'] . '&select=name');
                    return [trim($rows[0]['first_name'] . ' ' . $rows[0]['last_name']), 'ผู้ติดต่อสถานประกอบการ · ' . ($company[0]['name'] ?? '')];
                }
            } elseif ($role === 'teacher') {
                $rows = $this->client->restGet('teachers', 'user_id=eq.' . $userId . '&select=first_name,last_name,department_id');
                if (isset($rows[0])) {
                    $dept = $this->client->restGet('departments', 'id=eq.' . $rows[0]['department_id'] . '&select=name_th');
                    return [trim($rows[0]['first_name'] . ' ' . $rows[0]['last_name']), 'ครูนิเทศ · ' . ($dept[0]['name_th'] ?? '')];
                }
            }
        } catch (SupabaseException) {
            // fall through to fallback
        }
        return [$email, ucfirst($role)];
    }

    public function getCompany(int $id): ?array
    {
        $rows = $this->client->restGet('companies', 'id=eq.' . $id . '&deleted_at=is.null&select=*');
        return $rows[0] ?? null;
    }

    public function getCompanyForSupervisorUser(string $userId): ?array
    {
        $rows = $this->client->restGet('company_supervisors', 'user_id=eq.' . $userId . '&select=company_id');
        if (!isset($rows[0])) {
            return null;
        }
        return $this->getCompany((int) $rows[0]['company_id']);
    }

    public function createCompanyWithSupervisor(array $companyData, array $supervisorData): void
    {
        foreach (['name', 'address'] as $field) {
            if (trim((string) ($companyData[$field] ?? '')) === '') {
                throw new AuthException('VALIDATION_ERROR', 'กรุณากรอกชื่อและที่อยู่ของสถานประกอบการให้ครบ');
            }
        }
        foreach (['email', 'password', 'first_name', 'last_name'] as $field) {
            if (trim((string) ($supervisorData[$field] ?? '')) === '') {
                throw new AuthException('VALIDATION_ERROR', 'กรุณากรอกอีเมล รหัสผ่าน และชื่อ-นามสกุลผู้ติดต่อหลักให้ครบ');
            }
        }

        try {
            $companyRows = $this->client->restInsert('companies', [
                'name' => trim($companyData['name']),
                'tax_id' => trim((string) ($companyData['tax_id'] ?? '')) ?: null,
                'address' => trim($companyData['address']),
                'province' => trim((string) ($companyData['province'] ?? '')) ?: 'แม่ฮ่องสอน',
                'business_type' => trim((string) ($companyData['business_type'] ?? '')) ?: 'ทั่วไป',
                'phone' => trim((string) ($companyData['phone'] ?? '')) ?: null,
                'email' => trim((string) ($companyData['email'] ?? '')) ?: null,
                'status' => 'active'
            ]);
        } catch (SupabaseException $e) {
            throw new AuthException('VALIDATION_ERROR', 'สร้างสถานประกอบการไม่สำเร็จ', ['cause' => $e->getMessage()]);
        }
        $companyId = $companyRows[0]['id'] ?? null;
        if ($companyId === null) {
            throw new AuthException('VALIDATION_ERROR', 'สร้างสถานประกอบการไม่สำเร็จ');
        }

        try {
            $signUpResp = $this->client->authAdminCreateUser($supervisorData['email'], $supervisorData['password'], ['role' => 'company']);
        } catch (SupabaseException $e) {
            $this->client->restUpdate('companies', 'id=eq.' . $companyId, ['deleted_at' => date('c')]);
            throw new AuthException('VALIDATION_ERROR', 'สร้างบัญชีผู้ติดต่อไม่สำเร็จ (อีเมลนี้อาจมีอยู่แล้ว)', ['cause' => $e->getMessage()]);
        }
        $userId = $signUpResp['id'] ?? $signUpResp['user']['id'] ?? null;
        if ($userId === null) {
            $this->client->restUpdate('companies', 'id=eq.' . $companyId, ['deleted_at' => date('c')]);
            throw new AuthException('VALIDATION_ERROR', 'สร้างบัญชีผู้ติดต่อไม่สำเร็จ');
        }

        try {
            $this->client->restInsert('company_supervisors', [
                'user_id' => $userId,
                'company_id' => $companyId,
                'first_name' => trim($supervisorData['first_name']),
                'last_name' => trim($supervisorData['last_name']),
                'position' => trim((string) ($supervisorData['position'] ?? '')) ?: null,
                'phone' => trim((string) ($supervisorData['phone'] ?? '')) ?: null,
                'is_primary' => true,
            ]);
        } catch (SupabaseException $e) {
            $this->client->authAdminDeleteUser($userId);
            $this->client->restUpdate('companies', 'id=eq.' . $companyId, ['deleted_at' => date('c')]);
            throw new AuthException('VALIDATION_ERROR', 'บันทึกข้อมูลผู้ติดต่อไม่สำเร็จ', ['cause' => $e->getMessage()]);
        }
    }

    public function setCompanyStatus(int $id, string $status, string $approvedByUserId): void
    {
        if (!in_array($status, ['active', 'approved', 'rejected', 'suspended'], true)) {
            throw new AuthException('VALIDATION_ERROR', 'สถานะไม่ถูกต้อง');
        }
        $this->client->restUpdate('companies', 'id=eq.' . $id, ['status' => $status]);
    }

    public function approveOrRejectUser(string $userId, string $decision): void
    {
        if ($decision === 'approved') {
            $this->client->restUpdate('users', 'id=eq.' . $userId, ['status' => 'active']);
            return;
        }
        if ($decision === 'rejected') {
            $this->client->authAdminDeleteUser($userId);
            return;
        }
        throw new AuthException('VALIDATION_ERROR', 'สถานะไม่ถูกต้อง');
    }

    public function updateOwnGps(int $companyId, float $latitude, float $longitude, int $radiusM, int $maxRadiusM): void
    {
        if ($radiusM < 10 || $radiusM > $maxRadiusM) {
            throw new AuthException('VALIDATION_ERROR', "รัศมีต้องอยู่ระหว่าง 10 ถึง {$maxRadiusM} เมตร");
        }
        $this->client->restUpdate('companies', 'id=eq.' . $companyId, [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'gps_radius_m' => $radiusM,
        ]);
    }

    public function listSupervisors(int $companyId): array
    {
        $rows = $this->client->restGet('company_supervisors', 'company_id=eq.' . $companyId . '&select=user_id,first_name,last_name,position,is_primary&order=is_primary.desc');
        $out = [];
        foreach ($rows as $r) {
            $user = $this->client->restGet('users', 'id=eq.' . $r['user_id'] . '&select=email,status');
            $out[] = [
                'id' => $r['user_id'],
                'name' => trim($r['first_name'] . ' ' . $r['last_name']),
                'email' => $user[0]['email'] ?? '-',
                'position' => $r['position'] ?? '-',
                'is_primary' => (bool) $r['is_primary'],
                'status' => $user[0]['status'] ?? 'active',
            ];
        }
        return $out;
    }

    public function isPrimaryContact(string $userId): bool
    {
        $rows = $this->client->restGet('company_supervisors', 'user_id=eq.' . $userId . '&select=is_primary');
        return (bool) ($rows[0]['is_primary'] ?? false);
    }

    public function addSupervisor(int $companyId, array $data): void
    {
        foreach (['email', 'password', 'first_name', 'last_name'] as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                throw new AuthException('VALIDATION_ERROR', 'กรุณากรอกอีเมล รหัสผ่าน และชื่อ-นามสกุลให้ครบ');
            }
        }
        $signUp = $this->client->authAdminCreateUser($data['email'], $data['password'], ['role' => 'company']);
        $userId = $signUp['id'] ?? $signUp['user']['id'] ?? null;
        if ($userId === null) {
            throw new AuthException('VALIDATION_ERROR', 'สร้างบัญชีไม่สำเร็จ');
        }

        $this->client->restInsert('company_supervisors', [
            'user_id' => $userId,
            'company_id' => $companyId,
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']),
            'position' => trim((string) ($data['position'] ?? '')) ?: null,
            'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
            'is_primary' => false,
        ]);
        $this->client->restUpdate('users', 'id=eq.' . $userId, ['status' => 'active']);
    }
}