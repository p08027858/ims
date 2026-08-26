<?php
/**
 * POST/action route table: [METHOD, pattern, [ControllerClass, method], requiredRole].
 * Checked BEFORE the GET view routes in config/routes.php (see public/index.php) — routes.php
 * stays GET-view-only so it keeps doubling as the RBAC role table (SITEMAP.md §8).
 *
 * `pattern` supports the same `{id}`-style dynamic segments as routes.php (public/index.php's
 * matchPath()). `requiredRole` is passed straight to AuthGuard::requireRole() before the
 * controller runs — null skips the check entirely (only for the guest-facing auth endpoints
 * below, which ARE the auth mechanism itself).
 */

use App\Controllers\AnnouncementController;
use App\Controllers\ApplicationController;
use App\Controllers\AttendanceController;
use App\Controllers\AuthController;
use App\Controllers\BatchController;
use App\Controllers\CompanyController;
use App\Controllers\CompanyProfileController;
use App\Controllers\DailyLogController;
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
use App\Controllers\SuperAdminAdminController;
use App\Controllers\SuperAdminEvaluationController;
use App\Controllers\SuperAdminPinController;
use App\Controllers\TeacherController;

return [
    ['POST', '/auth/register', [AuthController::class, 'register'], null],
    ['POST', '/auth/login', [AuthController::class, 'login'], null],
    ['GET', '/logout', [AuthController::class, 'logout'], null],
    ['POST', '/auth/password/forgot', [AuthController::class, 'forgotPassword'], null],
    ['POST', '/auth/password/reset', [AuthController::class, 'resetPassword'], null],

    // Phase 3 — Organization & Company Management
    ['POST', '/admin/faculties', [OrgController::class, 'createFaculty'], 'admin'],
    ['POST', '/admin/faculties/{id}/status', [OrgController::class, 'toggleFacultyStatus'], 'admin'],
    ['POST', '/admin/departments', [OrgController::class, 'createDepartment'], 'admin'],
    ['POST', '/admin/departments/{id}/status', [OrgController::class, 'toggleDepartmentStatus'], 'admin'],

    ['POST', '/admin/companies', [CompanyController::class, 'store'], 'admin'],
    ['POST', '/admin/companies/{id}/approve', [CompanyController::class, 'approveCompany'], 'admin'],
    ['POST', '/admin/users/{id}/approve', [CompanyController::class, 'approveUser'], 'admin'],
    ['POST', '/admin/users/approve', [CompanyController::class, 'approveUserFromDetail'], 'admin'],

    ['POST', '/company/profile', [CompanyProfileController::class, 'updateGps'], 'company'],

    // Phase 4 — Internship Application & Matching
    ['POST', '/student/applications', [ApplicationController::class, 'apply'], 'student'],
    ['POST', '/company/applications/{id}/decision', [ApplicationController::class, 'decide'], 'company'],
    ['POST', '/admin/matching/{id}', [InternshipController::class, 'createFromApplication'], 'admin'],
    ['POST', '/admin/internships/{id}/approve', [InternshipController::class, 'approve'], 'admin'],
    ['POST', '/admin/internships/{id}/terminate', [InternshipController::class, 'terminate'], 'admin'],
    ['POST', '/admin/teachers', [TeacherController::class, 'store'], 'admin'],

    // Phase 5 — Attendance & GPS
    ['POST', '/attendance/checkin', [AttendanceController::class, 'checkIn'], 'student'],
    ['POST', '/attendance/checkout', [AttendanceController::class, 'checkOut'], 'student'],
    ['POST', '/student/attendance', [AttendanceController::class, 'checkIn'], 'student'],
    ['POST', '/student/profile', [ProfileController::class, 'update'], 'student'],

    // Phase 6 — Daily Log & Leave Request
    ['POST', '/student/daily-logs', [DailyLogController::class, 'store'], 'student'],
    ['POST', '/student/daily-logs/new', [DailyLogController::class, 'store'], 'student'],
    ['POST', '/student/daily-logs/{id}/edit', [DailyLogController::class, 'update'], 'student'],
    ['POST', '/company/daily-logs/review', [DailyLogController::class, 'review'], 'company|teacher'],
    ['POST', '/student/leave-requests', [LeaveController::class, 'apply'], 'student'],
    ['POST', '/student/leave-requests/{id}/cancel', [LeaveController::class, 'cancel'], 'student'],
    ['POST', '/company/leave-requests/{id}/decision', [LeaveController::class, 'decide'], 'company|teacher'],

    // Phase 7 — Evaluation & Digital Signature
    ['POST', '/company/evaluations/weekly/{id}', [EvaluationController::class, 'submitWeekly'], 'company'],
    ['POST', '/company/evaluations/final/{id}', [EvaluationController::class, 'submitCompanyFinal'], 'company'],
    ['POST', '/teacher/evaluations/final/{id}', [EvaluationController::class, 'submitTeacherFinal'], 'teacher'],
    ['POST', '/super-admin/evaluations/{id}/override', [SuperAdminEvaluationController::class, 'override'], 'super_admin'],

    // Phase 8 — Admin Console
    ['POST', '/admin/batches', [BatchController::class, 'store'], 'admin'],
    ['POST', '/admin/batches/close', [BatchController::class, 'close'], 'admin'],
    ['POST', '/admin/import/students', [ImportController::class, 'importStudents'], 'admin'],
    ['POST', '/admin/settings', [SettingsController::class, 'update'], 'admin'],
    ['POST', '/admin/evaluation-templates/{id}', [EvaluationTemplateController::class, 'update'], 'admin'],

    // Phase 9 — Notifications, Audit Log, Cron Jobs
    ['GET', '/notifications/{id}/read', [NotificationController::class, 'markRead'], 'student|company|teacher|admin|super_admin'],
    ['POST', '/notifications/read-all', [NotificationController::class, 'markAllRead'], 'student|company|teacher|admin|super_admin'],

    // Phase 10 — Super Admin Console & PIN Security
    ['POST', '/super-admin/pin/setup', [SuperAdminPinController::class, 'setup'], 'super_admin'],
    ['POST', '/super-admin/verify-pin', [SuperAdminPinController::class, 'verifyPin'], 'super_admin'],
    ['POST', '/super-admin/admins', [SuperAdminAdminController::class, 'store'], 'super_admin'],
    ['POST', '/super-admin/admins/{id}/status', [SuperAdminAdminController::class, 'setStatus'], 'super_admin'],
    ['DELETE', '/admin/internships/{id}', [InternshipController::class, 'destroy'], 'super_admin'],

    // Phase 11 — Frontend Polish, Responsive, Reports Export
    ['POST', '/company/supervisors', [CompanyProfileController::class, 'addSupervisor'], 'company'],
    ['POST', '/admin/import/companies', [ImportController::class, 'importCompanies'], 'admin'],
    ['POST', '/admin/announcements', [AnnouncementController::class, 'store'], 'admin'],
    ['POST', '/admin/announcements/{id}/delete', [AnnouncementController::class, 'destroy'], 'admin'],
    ['GET', '/admin/reports/export-csv', [ReportController::class, 'exportAdminCsv'], 'admin'],
    ['GET', '/admin/reports/export-pdf', [ReportController::class, 'exportAdminPrint'], 'admin'],
    ['GET', '/teacher/reports/export-csv', [ReportController::class, 'exportTeacherCsv'], 'teacher'],
    ['GET', '/teacher/reports/export-pdf', [ReportController::class, 'exportTeacherPrint'], 'teacher'],
];