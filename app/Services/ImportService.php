<?php

namespace App\Services;

/**
 * CSV bulk import (AI_AGENT_PHASES.md Phase 8 item 2 for students; Phase 11/SITEMAP.md §5
 * `/admin/import/companies` closes the company-import follow-up noted in ISSUES.md since Phase 8).
 * RULE-IMPORT-01/02.
 */
final class ImportService
{
    private const REQUIRED_HEADERS = ['student_code', 'prefix', 'first_name', 'last_name', 'email', 'faculty_code', 'department_code', 'year_level'];
    private const REQUIRED_COMPANY_HEADERS = ['name', 'tax_id', 'address', 'province', 'latitude', 'longitude', 'supervisor_email', 'supervisor_first_name', 'supervisor_last_name'];

    private SupabaseClient $client;

    public function __construct(?SupabaseClient $client = null)
    {
        $this->client = $client ?? new SupabaseClient();
    }

    /**
     * RULE-IMPORT-01: the whole file is validated (header shape) before anything is committed —
     * a structurally malformed file (missing columns) is rejected outright (TC-IMPORT-003).
     * Per-row problems (duplicate student_code, unknown faculty/department, missing required
     * field) don't abort the whole import — RULE-IMPORT-02 says duplicates are skipped, not
     * overwritten, and the same treatment extends naturally to other bad rows: skip + report.
     *
     * @return array{imported:int,skipped:int,errors:array<int,array{row:int,message:string}>}
     */
    public function importStudentsCsv(string $tmpPath): array
    {
        $rows = $this->parseCsv($tmpPath, self::REQUIRED_HEADERS);

        $faculties = $this->client->restGet('faculties', 'select=id,code');
        $facultyIdByCode = array_column($faculties, 'id', 'code');

        $departments = $this->client->restGet('departments', 'select=id,code,faculties(code)');
        $deptIdByKey = [];
        foreach ($departments as $d) {
            $facultyCode = $d['faculties']['code'] ?? null;
            if ($facultyCode !== null) {
                $deptIdByKey[$facultyCode . '|' . $d['code']] = $d['id'];
            }
        }

        $existingCodes = array_flip(array_column($this->client->restGet('students', 'select=student_code'), 'student_code'));

        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $i => $row) {
            $rowNum = $i + 2; // header is row 1
            $studentCode = trim((string) ($row['student_code'] ?? ''));
            $email = trim((string) ($row['email'] ?? ''));
            $firstName = trim((string) ($row['first_name'] ?? ''));
            $lastName = trim((string) ($row['last_name'] ?? ''));
            $facultyCode = trim((string) ($row['faculty_code'] ?? ''));
            $departmentCode = trim((string) ($row['department_code'] ?? ''));

            if ($studentCode === '' || $email === '' || $firstName === '' || $lastName === '') {
                $errors[] = ['row' => $rowNum, 'message' => 'ข้อมูลไม่ครบ (ต้องมี student_code, email, first_name, last_name)'];
                $skipped++;
                continue;
            }
            if (isset($existingCodes[$studentCode])) {
                $errors[] = ['row' => $rowNum, 'message' => "student_code '{$studentCode}' ซ้ำกับข้อมูลเดิมในระบบ"];
                $skipped++;
                continue;
            }
            $facultyId = $facultyIdByCode[$facultyCode] ?? null;
            $departmentId = $deptIdByKey[$facultyCode . '|' . $departmentCode] ?? null;
            if ($facultyId === null || $departmentId === null) {
                $errors[] = ['row' => $rowNum, 'message' => "ไม่พบคณะ/สาขา ({$facultyCode}/{$departmentCode})"];
                $skipped++;
                continue;
            }

            $userId = null;
            try {
                // Admin-created (RULE-AUTH-02 pattern) with a random temp password — no SMTP
                // configured yet to email credentials (ISSUES.md), account still lands
                // status=pending via handle_new_user() same as every other account and needs
                // the normal approval pass through /admin/users afterward.
                $password = bin2hex(random_bytes(6)) . 'Aa1';
                $signUp = $this->client->authAdminCreateUser($email, $password, ['role' => 'student']);
                $userId = $signUp['id'] ?? $signUp['user']['id'] ?? null;
                if ($userId === null) {
                    throw new \RuntimeException('ไม่ได้รับ user id จาก Supabase');
                }
                $this->client->restInsert('students', [
                    'user_id' => $userId,
                    'student_code' => $studentCode,
                    'prefix' => trim((string) ($row['prefix'] ?? '')) ?: null,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'faculty_id' => $facultyId,
                    'department_id' => $departmentId,
                    'year_level' => (int) ($row['year_level'] ?? 1) ?: 1,
                ]);
                $imported++;
                $existingCodes[$studentCode] = true; // catches duplicates within the same file too
            } catch (\Throwable $e) {
                if ($userId !== null) {
                    try {
                        $this->client->authAdminDeleteUser($userId);
                    } catch (\Throwable) {
                        // best-effort rollback only
                    }
                }
                $errors[] = ['row' => $rowNum, 'message' => 'สร้างบัญชีไม่สำเร็จ: ' . $e->getMessage()];
                $skipped++;
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * SITEMAP.md §5 `/admin/import/companies` (Phase 11) — same dry-run-then-commit shape as
     * importStudentsCsv() (RULE-IMPORT-01/02), dedup key is `tax_id` per RULE-IMPORT-02's own
     * wording. Creates the company row + its primary contact account together per row, same as
     * CompanyService::createCompanyWithSupervisor()'s single-entry flow, with the same
     * rollback-the-company-if-the-account-fails safety net.
     *
     * @return array{imported:int,skipped:int,errors:array<int,array{row:int,message:string}>}
     */
    public function importCompaniesCsv(string $tmpPath): array
    {
        $rows = $this->parseCsv($tmpPath, self::REQUIRED_COMPANY_HEADERS);
        $existingTaxIds = array_flip(array_filter(array_column($this->client->restGet('companies', 'select=tax_id'), 'tax_id')));

        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $i => $row) {
            $rowNum = $i + 2;
            $name = trim((string) ($row['name'] ?? ''));
            $taxId = trim((string) ($row['tax_id'] ?? ''));
            $address = trim((string) ($row['address'] ?? ''));
            $lat = trim((string) ($row['latitude'] ?? ''));
            $lng = trim((string) ($row['longitude'] ?? ''));
            $supervisorEmail = trim((string) ($row['supervisor_email'] ?? ''));
            $supervisorFirst = trim((string) ($row['supervisor_first_name'] ?? ''));
            $supervisorLast = trim((string) ($row['supervisor_last_name'] ?? ''));

            if ($name === '' || $taxId === '' || $address === '' || $lat === '' || $lng === '' || $supervisorEmail === '' || $supervisorFirst === '' || $supervisorLast === '') {
                $errors[] = ['row' => $rowNum, 'message' => 'ข้อมูลไม่ครบ (ต้องมี name, tax_id, address, latitude, longitude, supervisor_email, supervisor_first_name, supervisor_last_name)'];
                $skipped++;
                continue;
            }
            if (isset($existingTaxIds[$taxId])) {
                $errors[] = ['row' => $rowNum, 'message' => "tax_id '{$taxId}' ซ้ำกับข้อมูลเดิมในระบบ"];
                $skipped++;
                continue;
            }

            $companyId = null;
            try {
                $companyRows = $this->client->restInsert('companies', [
                    'name' => $name,
                    'tax_id' => $taxId,
                    'address' => $address,
                    'subdistrict' => trim((string) ($row['subdistrict'] ?? '')) ?: null,
                    'district' => trim((string) ($row['district'] ?? '')) ?: null,
                    'province' => trim((string) ($row['province'] ?? '')) ?: null,
                    'postcode' => trim((string) ($row['postcode'] ?? '')) ?: null,
                    'latitude' => (float) $lat,
                    'longitude' => (float) $lng,
                    'gps_radius_m' => (int) ($row['gps_radius_m'] ?? 100),
                    'phone' => trim((string) ($row['phone'] ?? '')) ?: null,
                    'email' => trim((string) ($row['email'] ?? '')) ?: null,
                    'website' => trim((string) ($row['website'] ?? '')) ?: null,
                    'industry_type' => trim((string) ($row['industry_type'] ?? '')) ?: null,
                ]);
                $companyId = $companyRows[0]['id'] ?? null;
                if ($companyId === null) {
                    throw new \RuntimeException('ไม่ได้รับ company id');
                }

                // Admin-created (RULE-AUTH-02) with a random temp password — same reasoning as
                // importStudentsCsv() (no SMTP configured yet, ISSUES.md).
                $password = bin2hex(random_bytes(6)) . 'Aa1';
                $signUp = $this->client->authAdminCreateUser($supervisorEmail, $password, ['role' => 'company']);
                $userId = $signUp['id'] ?? $signUp['user']['id'] ?? null;
                if ($userId === null) {
                    throw new \RuntimeException('ไม่ได้รับ user id จาก Supabase');
                }
                $this->client->restInsert('company_supervisors', [
                    'user_id' => $userId,
                    'company_id' => $companyId,
                    'first_name' => $supervisorFirst,
                    'last_name' => $supervisorLast,
                    'position' => trim((string) ($row['supervisor_position'] ?? '')) ?: null,
                    'phone' => trim((string) ($row['supervisor_phone'] ?? '')) ?: null,
                    'is_primary' => true,
                ]);
                $imported++;
                $existingTaxIds[$taxId] = true;
            } catch (\Throwable $e) {
                if ($companyId !== null) {
                    try {
                        $this->client->restUpdate('companies', 'id=eq.' . $companyId, ['deleted_at' => date('c')]);
                    } catch (\Throwable) {
                        // best-effort rollback only
                    }
                }
                $errors[] = ['row' => $rowNum, 'message' => 'สร้างสถานประกอบการไม่สำเร็จ: ' . $e->getMessage()];
                $skipped++;
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    /** @param array<int,string> $requiredHeaders
     * @return array<int, array<string,string>> */
    private function parseCsv(string $path, array $requiredHeaders): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new AuthException('VALIDATION_ERROR', 'ไม่สามารถอ่านไฟล์ CSV ได้');
        }
        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            throw new AuthException('VALIDATION_ERROR', 'ไฟล์ CSV ว่างเปล่า');
        }
        // Strip a UTF-8 BOM from the first header cell if present — Excel's "CSV UTF-8" export
        // and PowerShell's default UTF8 encoding both prepend one, which otherwise silently
        // corrupts the first column name and made a perfectly well-formed file look malformed
        // (caught live during Phase 8 testing: a real BOM'd file reported student_code "missing"
        // even though it was the first column).
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        }
        $header = array_map(static fn ($h) => trim((string) $h), $header);
        $missing = array_diff($requiredHeaders, $header);
        if (!empty($missing)) {
            fclose($handle);
            // TC-IMPORT-003: reject the whole file before touching the database.
            throw new AuthException('VALIDATION_ERROR', 'รูปแบบไฟล์ไม่ถูกต้อง ขาดคอลัมน์: ' . implode(', ', $missing));
        }

        $rows = [];
        while (($line = fgetcsv($handle)) !== false) {
            if (count($line) === 1 && trim((string) $line[0]) === '') {
                continue; // blank line
            }
            $row = [];
            foreach ($header as $i => $col) {
                $row[$col] = $line[$i] ?? '';
            }
            $rows[] = $row;
        }
        fclose($handle);

        if (empty($rows)) {
            throw new AuthException('VALIDATION_ERROR', 'ไม่มีข้อมูลในไฟล์ CSV');
        }
        return $rows;
    }
}
