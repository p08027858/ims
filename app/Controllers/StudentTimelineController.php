<?php

namespace App\Controllers;

use App\Services\InternshipService;
use App\Services\StudentTimelineService;
use App\Services\SupabaseClient;
use App\Support\Session;

/**
 * `/teacher/dashboard`, `/teacher/students`, `/teacher/students/{id}`, `/company/students`,
 * `/company/students/{id}` (SITEMAP.md §3/§4, Phase 11) — advisee list + shared detail/timeline
 * page. None of these had ever been wired to real data before this phase (teacher/dashboard.php
 * and teacher/student_timeline.php were pure Phase-0 mocks the entire time).
 */
final class StudentTimelineController
{
    private InternshipService $internships;
    private StudentTimelineService $timeline;

    public function __construct()
    {
        $this->internships = new InternshipService();
        $this->timeline = new StudentTimelineService();
    }

    /** GET /teacher/dashboard and /teacher/students loader (both render teacher/dashboard.php). */
    public function teacherDashboardData(array $params): array
    {
        $teacherId = $this->resolveTeacherId((string) Session::user()['id']);
        $advisees = $this->internships->listAdviseesWithProgress('teacher_id', $teacherId);

        return [
            'teacher' => ['name' => (string) (Session::user()['first_name'] ?? 'ครูนิเทศ')],
            'stats' => [
                'total_students' => count($advisees),
                'departments' => count(array_unique(array_filter(array_column($advisees, 'department_id')))),
                'not_logged_2days' => count(array_filter($advisees, static fn (array $a) => $a['flag'] !== null)),
                'pending_final_eval' => count(array_filter($advisees, static fn (array $a) => $a['pending_teacher_final'])),
            ],
            'students' => array_map(static fn (array $a) => [
                'internship_id' => $a['internship_id'], 'name' => $a['name'], 'company' => $a['company'],
                'hours' => $a['hours'], 'hours_required' => $a['hours_required'], 'flag' => $a['flag'],
            ], $advisees),
        ];
    }

    /** GET /teacher/students/{id} loader. */
    public function teacherDetailData(array $params): array
    {
        $internshipId = (int) $params['id'];
        $teacherId = $this->resolveTeacherId((string) Session::user()['id']);
        if (!$this->timeline->belongsToTeacher($internshipId, $teacherId)) {
            return ['notFound' => true];
        }
        return $this->buildDetailData($internshipId);
    }

    /** GET /company/students loader (SITEMAP.md §3 — Phase 11). */
    public function companyListData(array $params): array
    {
        $companyId = $this->resolveCompanyId((string) Session::user()['id']);
        $advisees = $this->internships->listAdviseesWithProgress('company_id', $companyId);
        return ['students' => array_map(static fn (array $a) => [
            'internship_id' => $a['internship_id'], 'name' => $a['name'], 'company' => $a['company'],
            'hours' => $a['hours'], 'hours_required' => $a['hours_required'], 'flag' => $a['flag'],
        ], $advisees)];
    }

    /** GET /company/students/{internship_id} loader. */
    public function companyDetailData(array $params): array
    {
        $internshipId = (int) $params['id'];
        $companyId = $this->resolveCompanyId((string) Session::user()['id']);
        if (!$this->timeline->belongsToCompany($internshipId, $companyId)) {
            return ['notFound' => true];
        }
        return $this->buildDetailData($internshipId);
    }

    private function buildDetailData(int $internshipId): array
    {
        $context = $this->timeline->getContext($internshipId);
        if ($context === null) {
            return ['notFound' => true];
        }
        $tab = (string) ($_GET['tab'] ?? 'overview');
        if (!in_array($tab, ['overview', 'attendance', 'daily_logs', 'leave', 'evaluation'], true)) {
            $tab = 'overview';
        }
        return [
            'notFound' => false,
            'student' => $context,
            'activeTab' => $tab,
            'timeline' => $this->timeline->getTimeline($internshipId, $tab),
        ];
    }

    private function resolveTeacherId(string $userId): int
    {
        $rows = (new SupabaseClient())->restGet('teachers', 'user_id=eq.' . $userId . '&select=id');
        return (int) ($rows[0]['id'] ?? 0);
    }

    private function resolveCompanyId(string $userId): int
    {
        $rows = (new SupabaseClient())->restGet('company_supervisors', 'user_id=eq.' . $userId . '&select=company_id');
        return (int) ($rows[0]['company_id'] ?? 0);
    }
}
