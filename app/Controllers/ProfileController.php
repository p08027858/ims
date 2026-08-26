<?php

namespace App\Controllers;

use App\Services\AuthException;
use App\Services\SupabaseClient;
use App\Support\Session;

/**
 * Student profile (`/student/profile`) — GET loader + POST saver.
 *
 * Until now the page rendered a fabricated fallback profile and its form posted to a route that
 * did not exist (config/actions.php had no POST /student/profile → 405 on submit). Contact
 * columns (phone/address/emergency_*) require database/migrations/004_add_student_contact_fields.sql;
 * before that migration is applied the loader still returns the REAL academic identity fields
 * (student_code/faculty/department) and simply leaves the contact inputs empty rather than
 * inventing values — same honest-empty-state principle as every other de-mocked page.
 */
final class ProfileController
{
    private SupabaseClient $client;

    public function __construct()
    {
        $this->client = new SupabaseClient();
    }

    public function profilePageData(array $params): array
    {
        $profile = [
            'student_code' => '', 'faculty' => '', 'department' => '',
            'phone' => '', 'address' => '',
            'emergency_name' => '', 'emergency_relation' => '', 'emergency_phone' => '',
        ];

        try {
            $userId = (string) Session::user()['id'];
            try {
                $rows = $this->client->restGet(
                    'students',
                    'user_id=eq.' . $userId . '&select=student_code,faculty_id,department_id,phone,address,emergency_contact_name,emergency_relation,emergency_contact_phone'
                );
            } catch (\Throwable) {
                // migration 004 not applied yet — fall back to the academic columns only
                $rows = $this->client->restGet('students', 'user_id=eq.' . $userId . '&select=student_code,faculty_id,department_id');
            }
            $row = $rows[0] ?? [];

            $profile['student_code'] = (string) ($row['student_code'] ?? '');
            $profile['faculty'] = $this->lookupName('faculties', $row['faculty_id'] ?? null);
            $profile['department'] = $this->lookupName('departments', $row['department_id'] ?? null);
            $profile['phone'] = (string) ($row['phone'] ?? '');
            $profile['address'] = (string) ($row['address'] ?? '');
            $profile['emergency_name'] = (string) ($row['emergency_contact_name'] ?? '');
            $profile['emergency_relation'] = (string) ($row['emergency_relation'] ?? '');
            $profile['emergency_phone'] = (string) ($row['emergency_contact_phone'] ?? '');
        } catch (\Throwable) {
            // render the empty form rather than fabricating a profile
        }

        return ['profile' => $profile, 'formError' => Session::pullFlashError()];
    }

    /** POST /student/profile — saves the editable contact fields back to public.students. */
    public function update(array $params): void
    {
        try {
            $userId = (string) Session::user()['id'];
            $students = $this->client->restGet('students', 'user_id=eq.' . $userId . '&select=id');
            $studentId = (int) ($students[0]['id'] ?? 0);
            if ($studentId <= 0) {
                throw new AuthException('VALIDATION_ERROR', 'ไม่พบข้อมูลนักศึกษาของบัญชีนี้');
            }

            $emergencyName = trim((string) ($_POST['emergency_contact_name'] ?? ''));
            $emergencyPhone = trim((string) ($_POST['emergency_contact_phone'] ?? ''));
            if ($emergencyName === '' || $emergencyPhone === '') {
                throw new AuthException('VALIDATION_ERROR', 'กรุณากรอกชื่อและเบอร์โทรศัพท์ของผู้ติดต่อกรณีฉุกเฉิน');
            }

            $this->client->restUpdate('students', 'id=eq.' . $studentId, [
                'phone' => trim((string) ($_POST['phone'] ?? '')),
                'address' => trim((string) ($_POST['address'] ?? '')),
                'emergency_contact_name' => $emergencyName,
                'emergency_relation' => trim((string) ($_POST['emergency_relation'] ?? '')),
                'emergency_contact_phone' => $emergencyPhone,
            ]);
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
        } catch (\Throwable) {
            Session::flashError('บันทึกไม่สำเร็จ — โปรดตรวจว่าได้รัน database/migrations/004_add_student_contact_fields.sql ใน Supabase แล้ว');
        }

        header('Location: /student/profile');
        exit;
    }

    private function lookupName(string $table, mixed $id): string
    {
        if ($id === null || $id === '') {
            return '';
        }
        $rows = $this->client->restGet($table, 'id=eq.' . (int) $id . '&select=name_th');
        return (string) ($rows[0]['name_th'] ?? '');
    }
}
