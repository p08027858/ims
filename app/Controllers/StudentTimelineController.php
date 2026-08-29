<?php

namespace App\Controllers;

use App\Services\InternshipService;
use App\Services\StudentTimelineService;
use App\Services\SupabaseClient;
use App\Services\TeacherService;
use App\Support\Session;

/**
 * `/teacher/dashboard`, `/teacher/students`, `/teacher/students/{id}`, `/company/students`,
 * `/company/students/{id}` loaders.
 */
final class StudentTimelineController
{
    private InternshipService $internships;
    private StudentTimelineService $timeline;
    private TeacherService $teachers;

    public function __construct()
    {
        $this->internships = new InternshipService();
        $this->timeline = new StudentTimelineService();
        $this->teachers = new TeacherService();
    }

    public function teacherDashboardData(array $params): array
    {
        $user = Session::user() ?? [];
        $userId = (string) ($user['id'] ?? '');
        $teacherId = $this->resolveTeacherId($userId);
        $advisees = $teacherId > 0
            ? $this->internships->listAdviseesWithProgress('teacher_id', $teacherId)
            : [];
        $teacherName = $this->teachers->getTeacherDisplayNameByUserId($userId);

        return [
            'teacher' => ['name' => $teacherName !== '' ? $teacherName : (string) ($user['first_name'] ?? 'ครูนิเทศ')],
            'stats' => [
                'total_students' => count($advisees),
                'departments' => count(array_unique(array_filter(array_column($advisees, 'department_id')))),
                'not_logged_2days' => count(array_filter($advisees, static fn (array $a) => $a['flag'] !== null)),
                'pending_final_eval' => count(array_filter($advisees, static fn (array $a) => $a['pending_teacher_final'])),
            ],
            'students' => array_map(static fn (array $a) => [
                'internship_id' => $a['internship_id'],
                'name' => $a['name'],
                'company' => $a['company'],
                'student_code' => $a['student_code'] ?? '-',
                'hours' => $a['hours'],
                'hours_required' => $a['hours_required'],
                'flag' => $a['flag'],
            ], $advisees),
        ];
    }

    public function teacherDetailData(array $params): array
    {
        $internshipId = (int) ($params['id'] ?? 0);
        $teacherId = $this->resolveTeacherId((string) ((Session::user() ?? [])['id'] ?? ''));
        if ($teacherId <= 0 || !$this->timeline->belongsToTeacher($internshipId, $teacherId)) {
            return ['notFound' => true];
        }
        return $this->buildDetailData($internshipId);
    }

    public function companyListData(array $params): array
    {
        $companyId = $this->resolveCompanyId((string) ((Session::user() ?? [])['id'] ?? ''));
        $advisees = $companyId > 0
            ? $this->internships->listAdviseesWithProgress('company_id', $companyId)
            : [];

        return ['students' => array_map(static fn (array $a) => [
            'internship_id' => $a['internship_id'],
            'name' => $a['name'],
            'company' => $a['company'],
            'student_code' => $a['student_code'] ?? '-',
            'hours' => $a['hours'],
            'hours_required' => $a['hours_required'],
            'flag' => $a['flag'],
        ], $advisees)];
    }

    public function companyDetailData(array $params): array
    {
        $internshipId = (int) ($params['id'] ?? 0);
        $companyId = $this->resolveCompanyId((string) ((Session::user() ?? [])['id'] ?? ''));
        if ($companyId <= 0 || !$this->timeline->belongsToCompany($internshipId, $companyId)) {
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
        return $this->teachers->resolveTeacherIdByUserId($userId);
    }

    private function resolveCompanyId(string $userId): int
    {
        $rows = (new SupabaseClient())->restGet('company_supervisors', 'user_id=eq.' . $userId . '&select=company_id&limit=1');
        return (int) ($rows[0]['company_id'] ?? 0);
    }
}
