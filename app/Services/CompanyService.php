<?php

namespace App\Services;

/**
 * Companies + company_supervisors (AI_AGENT_PHASES.md Phase 3 items 2-4). RULE-AUTH-02: company
 * accounts are always Admin-created (supabase.auth.admin.createUser()), never self-registered —
 * the same admin-create + rollback-on-failure shape as AuthService::register() for students.
 */
final class CompanyService
{
    private SupabaseClient $client;

    public function __construct(?SupabaseClient $client = null)
    {
        $this->client = $client ?? new SupabaseClient();
    }

    /**
     * Unified queue for admin/pending_approvals.php: pending student self-registrations +
     * pending companies. `type` doubles as the view's icon/form-action switch (only 'company'
     * is special-cased there; everything else posts to /admin/users/{id}/approve).
     *
     * @return array<int, array{id:string|int, type:string, name:string, meta:string}>
     */
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
            'status=eq.pending&deleted_at=is.null&select=id,name,industry_type,province'
        );
        foreach ($pendingCompanies as $c) {
            $meta = 'สถานประกอบการ';
            if (!empty($c['industry_type'])) {
                $meta .= ' · ' . $c['industry_type'];
            }
            $items[] = ['id' => $c['id'], 'type' => 'company', 'name' => $c['name'], 'meta' => $meta];
        }

        return $items;
    }

    /** @return array{0:string,1:string} [name, meta] */
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
            // fall through to the email-only fallback below
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

    /**
     * Admin creates the company entity AND its primary contact account together
     * (WORKFLOW.md §3: "Admin สร้างบัญชีบริษัท + ผู้ติดต่อหลัก") — both must be a real Auth
     * account (RULE-AUTH-02), so a supervisor-creation failure rolls back the just-created
     * company row rather than leaving an orphaned, contact-less company behind.
     *
     * @param array{name:string,address:string,latitude:string,longitude:string,gps_radius_m?:string,
     *              tax_id?:string,subdistrict?:string,district?:string,province?:string,postcode?:string,
     *              phone?:string,email?:string,website?:string,industry_type?:string} $companyData
     * @param array{email:string,password:string,first_name:string,last_name:string,position?:string,phone?:string} $supervisorData
     */
    public function createCompanyWithSupervisor(array $companyData, array $supervisorData): void
    {
        foreach (['name', 'address', 'latitude', 'longitude'] as $field) {
            if (trim((string) ($companyData[$field] ?? '')) === '') {
                throw new AuthException('VALIDATION_ERROR', 'กรุณากรอกชื่อ ที่อยู่ และพิกัด GPS ของสถานประกอบการให้ครบ');
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
                'subdistrict' => trim((string) ($companyData['subdistrict'] ?? '')) ?: null,
                'district' => trim((string) ($companyData['district'] ?? '')) ?: null,
                'province' => trim((string) ($companyData['province'] ?? '')) ?: null,
                'postcode' => trim((string) ($companyData['postcode'] ?? '')) ?: null,
                'latitude' => (float) $companyData['latitude'],
                'longitude' => (float) $companyData['longitude'],
                'gps_radius_m' => (int) ($companyData['gps_radius_m'] ?? 100),
                'phone' => trim((string) ($companyData['phone'] ?? '')) ?: null,
                'email' => trim((string) ($companyData['email'] ?? '')) ?: null,
                'website' => trim((string) ($companyData['website'] ?? '')) ?: null,
                'industry_type' => trim((string) ($companyData['industry_type'] ?? '')) ?: null,
            ]);
        } catch (SupabaseException $e) {
            throw new AuthException('VALIDATION_ERROR', 'สร้างสถานประกอบการไม่สำเร็จ', ['cause' => $e->getMessage()]);
        }
        $companyId = $companyRows[0]['id'] ?? null;
        if ($companyId === null) {
            throw new AuthException('VALIDATION_ERROR', 'สร้างสถานประกอบการไม่สำเร็จ');
        }

        // RULE-AUTH-02: admin-create endpoint (email_confirm=true) — same reasoning as
        // AuthService::register(), otherwise Supabase's own unconfirmed-email gate would block
        // this supervisor's login even after the pending-approval step below is approved.
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

    /** DATABASE.md §0.2 `company_status` enum: pending/approved/rejected/suspended. */
    public function setCompanyStatus(int $id, string $status, string $approvedByUserId): void
    {
        if (!in_array($status, ['approved', 'rejected', 'suspended'], true)) {
            throw new AuthException('VALIDATION_ERROR', 'สถานะไม่ถูกต้อง');
        }
        $update = ['status' => $status];
        if ($status === 'approved') {
            $update['approved_by'] = $approvedByUserId;
            $update['approved_at'] = date('c');
        }
        $this->client->restUpdate('companies', 'id=eq.' . $id, $update);
    }

    /**
     * RULE-AUTH-01/02 shared pattern: a *pending* account being "rejected" is a declined
     * application, not a policy-violation suspension — user_status has no 'rejected' value
     * (DATABASE.md §0.2), so we delete the auth.users row outright (cascades to public.users
     * and the role-specific profile table) rather than writing an invalid enum value.
     */
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

    /**
     * GPS setup (AI_AGENT_PHASES.md Phase 3 item 3): gps_radius_m must not exceed
     * settings.max_gps_radius_m (SETTINGS.md §1).
     */
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

    /**
     * SITEMAP.md §3 `/company/supervisors` (Phase 11) — the primary contact manages the other
     * supervisor accounts at their own company (ROLES.md §2: "ผู้ติดต่อรองทำได้เฉพาะประเมิน/ตรวจงาน").
     *
     * @return array<int, array{id:string,name:string,email:string,position:string,is_primary:bool,status:string}>
     */
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

    /**
     * Only the primary contact may add a secondary supervisor account (ROLES.md §2). Same
     * admin-create-account shape as createCompanyWithSupervisor()'s supervisor half, minus the
     * company-creation/rollback part since the company already exists here.
     *
     * @param array{email:string,password:string,first_name:string,last_name:string,position?:string,phone?:string} $data
     */
    public function addSupervisor(int $companyId, array $data): void
    {
        foreach (['email', 'password', 'first_name', 'last_name'] as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                throw new AuthException('VALIDATION_ERROR', 'กรุณากรอกอีเมล รหัสผ่าน และชื่อ-นามสกุลให้ครบ');
            }
        }
        try {
            $signUp = $this->client->authAdminCreateUser($data['email'], $data['password'], ['role' => 'company']);
        } catch (SupabaseException $e) {
            throw new AuthException('VALIDATION_ERROR', 'สร้างบัญชีไม่สำเร็จ (อีเมลนี้อาจมีอยู่แล้ว)', ['cause' => $e->getMessage()]);
        }
        $userId = $signUp['id'] ?? $signUp['user']['id'] ?? null;
        if ($userId === null) {
            throw new AuthException('VALIDATION_ERROR', 'สร้างบัญชีไม่สำเร็จ');
        }
        try {
            $this->client->restInsert('company_supervisors', [
                'user_id' => $userId,
                'company_id' => $companyId,
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'position' => trim((string) ($data['position'] ?? '')) ?: null,
                'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
                'is_primary' => false,
            ]);
        } catch (SupabaseException $e) {
            $this->client->authAdminDeleteUser($userId);
            throw new AuthException('VALIDATION_ERROR', 'บันทึกข้อมูลผู้ติดต่อไม่สำเร็จ', ['cause' => $e->getMessage()]);
        }
        // New supervisors are activated immediately, unlike the pending-approval queue for the
        // *first* (primary) contact at a new company — the company itself is already approved by
        // the time a secondary contact is being added, so there's nothing left to approve.
        $this->client->restUpdate('users', 'id=eq.' . $userId, ['status' => 'active']);
    }
}
