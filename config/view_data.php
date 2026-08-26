<?php
/**
 * GET routes that need real Supabase data instead of a view's own `$x ?? [mock...]` fallback
 * (Phase 0). path/pattern => [ControllerClass, method]; method receives the route's captured
 * `{id}`-style params (array, possibly empty) and returns an assoc array merged into the data
 * passed to View::render() — see public/index.php. Any GET route NOT listed here keeps
 * rendering its view's built-in mock data unchanged, exactly as before Phase 3.
 */

use App\Controllers\AnnouncementController;
use App\Controllers\ApplicationController;
use App\Controllers\AttendanceController;
use App\Controllers\AuditLogController;
use App\Controllers\AuthController;
use App\Controllers\BatchController;
use App\Controllers\CompanyController;
use App\Controllers\CompanyProfileController;
use App\Controllers\DailyLogController;
use App\Controllers\DashboardController;
use App\Controllers\EvaluationController;
use App\Controllers\EvaluationTemplateController;
use App\Controllers\ImportController;
use App\Controllers\InternshipController;
use App\Controllers\LeaveController;
use App\Controllers\NotificationController;
use App\Controllers\OrgController;
use App\Controllers\ProfileController;
use App\Controllers\ReportController;
use App\Controllers\SettingsController;
use App\Controllers\StudentTimelineController;
use App\Controllers\SuperAdminAdminController;
use App\Controllers\SuperAdminEvaluationController;
use App\Controllers\SuperAdminPermissionController;
use App\Controllers\SuperAdminPinController;
use App\Controllers\TeacherController;

return [
    '/login' => [AuthController::class, 'loginPageData'],
    '/register' => [OrgController::class, 'registerFacultiesData'],
    '/admin/organization' => [OrgController::class, 'organizationPageData'],
    '/admin/companies' => [CompanyController::class, 'pendingApprovalsData'],
    '/admin/companies/new' => [CompanyController::class, 'newCompanyFormData'],
    '/admin/users' => [CompanyController::class, 'pendingApprovalsData'],
    '/admin/users/{id}' => [CompanyController::class, 'userApprovalDetailData'],
    '/company/profile' => [CompanyProfileController::class, 'gpsSetupData'],

    // Dashboards + student profile — the last views that still rendered their Phase-0 mock
    // fallbacks whenever no loader was wired. See App\Controllers\DashboardController and
    // App\Controllers\ProfileController.
    '/student/dashboard' => [DashboardController::class, 'studentData'],
    '/company/dashboard' => [DashboardController::class, 'companyData'],
    '/admin/dashboard' => [DashboardController::class, 'adminData'],
    '/super-admin/dashboard' => [DashboardController::class, 'adminData'],
    '/student/profile' => [ProfileController::class, 'profilePageData'],

    // Phase 4 — Internship Application & Matching
    '/student/companies' => [ApplicationController::class, 'companySearchData'],
    '/student/companies/{id}' => [ApplicationController::class, 'companyDetailData'],
    '/company/applications' => [ApplicationController::class, 'companyApplicationsData'],
    '/admin/matching' => [InternshipController::class, 'matchingListData'],
    '/admin/matching/{id}' => [InternshipController::class, 'matchingFormData'],
    '/admin/internships' => [InternshipController::class, 'adminListData'],
    '/admin/teachers/new' => [TeacherController::class, 'newTeacherFormData'],

    // Phase 5 — Attendance & GPS
    '/student/attendance' => [AttendanceController::class, 'checkinPageData'],
    '/student/attendance/history' => [AttendanceController::class, 'historyPageData'],

    // Phase 6 — Daily Log & Leave Request
    '/student/daily-logs/new' => [DailyLogController::class, 'newFormData'],
    '/company/daily-logs' => [DailyLogController::class, 'reviewPageData'],
    '/teacher/daily-logs' => [DailyLogController::class, 'reviewPageData'],
    '/student/leave-requests/new' => [LeaveController::class, 'newFormData'],
    '/company/leave-requests' => [LeaveController::class, 'reviewListData'],
    '/teacher/leave-requests' => [LeaveController::class, 'reviewListData'],

    // Phase 7 — Evaluation & Digital Signature
    '/company/evaluations/weekly' => [EvaluationController::class, 'weeklyListData'],
    '/company/evaluations/weekly/{id}' => [EvaluationController::class, 'weeklyFormData'],
    '/company/evaluations/final' => [EvaluationController::class, 'companyFinalListData'],
    '/company/evaluations/final/{id}' => [EvaluationController::class, 'companyFinalFormData'],
    '/teacher/evaluations/final' => [EvaluationController::class, 'teacherFinalListData'],
    '/teacher/evaluations/final/{id}' => [EvaluationController::class, 'teacherFinalFormData'],
    '/student/evaluations' => [EvaluationController::class, 'studentHistoryData'],
    '/super-admin/evaluations/{id}/override' => [SuperAdminEvaluationController::class, 'formData'],

    // Phase 8 — Admin Console (Batch, Import, Settings, Reports)
    '/admin/batches' => [BatchController::class, 'listData'],
    '/admin/batches/new' => [BatchController::class, 'newFormData'],
    '/admin/import/students' => [ImportController::class, 'pageData'],
    '/admin/settings' => [SettingsController::class, 'pageData'],
    '/admin/reports' => [ReportController::class, 'summaryData'],
    '/admin/evaluation-templates' => [EvaluationTemplateController::class, 'listData'],
    '/admin/evaluation-templates/{id}' => [EvaluationTemplateController::class, 'formData'],

    // Phase 9 — Notifications, Audit Log, Cron Jobs
    '/student/notifications' => [NotificationController::class, 'listPageData'],
    '/company/notifications' => [NotificationController::class, 'listPageData'],
    '/teacher/notifications' => [NotificationController::class, 'listPageData'],
    '/admin/notifications' => [NotificationController::class, 'listPageData'],
    '/super-admin/notifications' => [NotificationController::class, 'listPageData'],
    '/admin/audit-logs' => [AuditLogController::class, 'listData'],

    // Phase 10 — Super Admin Console & PIN Security
    '/super-admin/admins' => [SuperAdminAdminController::class, 'listData'],
    '/super-admin/permissions' => [SuperAdminPermissionController::class, 'listData'],
    '/super-admin/pin/setup' => [SuperAdminPinController::class, 'setupFormData'],
    '/super-admin/internships' => [InternshipController::class, 'adminListData'],
    '/super-admin/audit-logs' => [AuditLogController::class, 'listData'],

    // Phase 11 — Frontend Polish, Responsive, Reports Export
    '/student/applications' => [ApplicationController::class, 'myApplicationsData'],
    '/student/daily-logs' => [DailyLogController::class, 'listData'],
    '/student/daily-logs/{id}/edit' => [DailyLogController::class, 'editFormData'],
    '/student/leave-requests' => [LeaveController::class, 'myListData'],
    '/teacher/dashboard' => [StudentTimelineController::class, 'teacherDashboardData'],
    '/teacher/students' => [StudentTimelineController::class, 'teacherDashboardData'],
    '/teacher/students/{id}' => [StudentTimelineController::class, 'teacherDetailData'],
    '/teacher/reports' => [ReportController::class, 'teacherReportData'],
    '/company/students' => [StudentTimelineController::class, 'companyListData'],
    '/company/students/{id}' => [StudentTimelineController::class, 'companyDetailData'],
    '/company/supervisors' => [CompanyProfileController::class, 'supervisorsData'],
    '/admin/import/companies' => [ImportController::class, 'companiesPageData'],
    '/admin/announcements' => [AnnouncementController::class, 'listData'],
];
