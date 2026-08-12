<?php
/**
 * Route table: path => [view, role, activeNav, pageTitle].
 * Paths mirror Internship_Project_Blueprint/SITEMAP.md exactly. `role` selects which
 * sidebar/bottom-nav config (partials/sidebar.php) renders; 'guest' skips the app shell
 * entirely (see layouts/app.php $showAppShell) AND skips the RBAC check.
 *
 * Since Phase 2: `role` here doubles as the RBAC requirement — public/index.php calls
 * App\Middleware\AuthGuard::requireRole($role) for every non-guest route before rendering,
 * so every row below is enforced, not just documentation (SITEMAP.md §8).
 */

return [
    // Public
    '/' => ['redirect', '/login'],
    '/login' => ['auth/login', 'guest', '', 'เข้าสู่ระบบ - IMS THAI'],
    '/register' => ['auth/register', 'guest', '', 'สมัครสมาชิกนักศึกษา - IMS THAI'],
    '/pending-approval' => ['auth/pending_approval', 'guest', '', 'รออนุมัติ - IMS THAI'],
    '/password/forgot' => ['auth/forgot_password', 'guest', '', 'ลืมรหัสผ่าน - IMS THAI'],
    '/password/reset' => ['auth/reset_password', 'guest', '', 'ตั้งรหัสผ่านใหม่ - IMS THAI'],

    // Student
    '/student/dashboard' => ['student/dashboard', 'student', 'dashboard', 'หน้าหลัก - IMS THAI'],
    '/student/companies' => ['student/company_search', 'student', 'dashboard', 'ค้นหาสถานประกอบการ - IMS THAI'],
    '/student/companies/{id}' => ['student/company_detail', 'student', 'dashboard', 'รายละเอียดบริษัท - IMS THAI'],
    '/student/applications' => ['student/applications', 'student', 'dashboard', 'ใบสมัครของฉัน - IMS THAI'],
    '/student/attendance' => ['student/attendance_checkin', 'student', 'attendance', 'ลงเวลา - IMS THAI'],
    '/student/attendance/history' => ['student/attendance_history', 'student', 'attendance', 'ประวัติการลงเวลา - IMS THAI'],
    // Phase 11: real list view, replacing the attendance_history.php placeholder used since Phase 6.
    '/student/daily-logs' => ['student/daily_logs_list', 'student', 'daily_logs', 'บันทึกประจำวัน - IMS THAI'],
    '/student/daily-logs/new' => ['student/daily_log_form', 'student', 'daily_logs', 'บันทึกงานประจำวัน - IMS THAI'],
    '/student/daily-logs/{id}/edit' => ['student/daily_log_form', 'student', 'daily_logs', 'แก้ไขบันทึกงาน - IMS THAI'],
    '/student/leave-requests' => ['student/leave_requests_list', 'student', 'daily_logs', 'คำขอลาของฉัน - IMS THAI'],
    '/student/leave-requests/new' => ['student/leave_request', 'student', 'daily_logs', 'ยื่นใบลา - IMS THAI'],
    '/student/evaluations' => ['student/evaluation_history', 'student', 'notifications', 'ผลการประเมิน - IMS THAI'],
    '/student/notifications' => ['student/notifications_list', 'student', 'notifications', 'การแจ้งเตือน - IMS THAI'],
    '/student/profile' => ['student/profile', 'student', 'profile', 'โปรไฟล์ - IMS THAI'],

    // Company
    '/company/dashboard' => ['company/dashboard', 'company', 'dashboard', 'แดชบอร์ด - Supervisor'],
    '/company/applications' => ['company/applications', 'company', 'applications', 'ใบสมัคร - Supervisor'],
    '/company/students' => ['company/students_list', 'company', 'students', 'นักศึกษาในความดูแล - Supervisor'],
    '/company/students/{id}' => ['teacher/student_timeline', 'company', 'students', 'รายละเอียดนักศึกษา - Supervisor'],
    '/company/profile' => ['company/gps_setup', 'company', 'gps_setup', 'ตั้งค่าพิกัด - Supervisor'],
    '/company/supervisors' => ['company/supervisors', 'company', 'supervisors', 'ผู้ติดต่อ/พี่เลี้ยง - Supervisor'],
    '/company/daily-logs' => ['company/daily_log_review', 'company', 'daily_logs', 'ตรวจบันทึกงาน - Supervisor'],
    '/company/evaluations/weekly' => ['company/evaluation_list', 'company', 'evaluations', 'ประเมินรายสัปดาห์ - Supervisor'],
    '/company/evaluations/weekly/{id}' => ['company/evaluation_form', 'company', 'evaluations', 'ประเมินรายสัปดาห์ - Supervisor'],
    '/company/evaluations/final' => ['company/evaluation_list', 'company', 'evaluations', 'ประเมินปลายภาค - Supervisor'],
    '/company/evaluations/final/{id}' => ['teacher/evaluation_form', 'company', 'evaluations', 'ประเมินปลายภาค - Supervisor'],
    '/company/leave-requests' => ['company/leave_approval', 'company', 'leave_requests', 'คำขอลา - Supervisor'],
    '/company/notifications' => ['student/notifications_list', 'company', '', 'การแจ้งเตือน - Supervisor'],

    // Teacher
    '/teacher/dashboard' => ['teacher/dashboard', 'teacher', 'dashboard', 'แดชบอร์ด - ครูนิเทศ'],
    '/teacher/students' => ['teacher/dashboard', 'teacher', 'students', 'นักศึกษาในความดูแล - ครูนิเทศ'],
    '/teacher/students/{id}' => ['teacher/student_timeline', 'teacher', 'students', 'รายละเอียดนักศึกษา - ครูนิเทศ'],
    '/teacher/daily-logs' => ['company/daily_log_review', 'teacher', 'daily_logs', 'ตรวจบันทึกงาน - ครูนิเทศ'],
    '/teacher/leave-requests' => ['company/leave_approval', 'teacher', 'leave_requests', 'คำขอลา - ครูนิเทศ'],
    '/teacher/evaluations/final' => ['company/evaluation_list', 'teacher', 'evaluations', 'ประเมินปลายภาค - ครูนิเทศ'],
    '/teacher/evaluations/final/{id}' => ['teacher/evaluation_form', 'teacher', 'evaluations', 'ประเมินปลายภาค - ครูนิเทศ'],
    '/teacher/reports' => ['teacher/reports', 'teacher', 'reports', 'รายงาน - ครูนิเทศ'],
    '/teacher/notifications' => ['student/notifications_list', 'teacher', '', 'การแจ้งเตือน - ครูนิเทศ'],

    // Admin
    '/admin/dashboard' => ['admin/dashboard', 'admin', 'dashboard', 'แดชบอร์ด - IMS Admin'],
    '/admin/users' => ['admin/pending_approvals', 'admin', 'users', 'อนุมัติผู้ใช้งาน - IMS Admin'],
    '/admin/users/{id}' => ['admin/user_approval_detail', 'admin', 'users', 'รายละเอียดผู้ใช้ - IMS Admin'],
    '/admin/companies' => ['admin/pending_approvals', 'admin', 'companies', 'อนุมัติสถานประกอบการ - IMS Admin'],
    '/admin/companies/new' => ['admin/company_form', 'admin', 'companies', 'เพิ่มสถานประกอบการ - IMS Admin'],
    '/admin/organization' => ['admin/organization', 'admin', 'organization', 'จัดการคณะ/สาขา - IMS Admin'],
    '/admin/batches' => ['admin/batch_management', 'admin', 'batches', 'จัดการรอบฝึกงาน - IMS Admin'],
    '/admin/batches/new' => ['admin/batch_form', 'admin', 'batches', 'เปิดรอบฝึกงานใหม่ - IMS Admin'],
    '/admin/matching' => ['admin/matching_list', 'admin', 'matching', 'จับคู่ครูนิเทศ - IMS Admin'],
    '/admin/matching/{id}' => ['admin/matching', 'admin', 'matching', 'จับคู่ครูนิเทศ - IMS Admin'],
    '/admin/internships' => ['admin/internship_approvals', 'admin', 'internships', 'การฝึกงานที่ยืนยันแล้ว - IMS Admin'],
    '/admin/teachers/new' => ['admin/teacher_form', 'admin', 'users', 'เพิ่มครูนิเทศ - IMS Admin'],
    '/admin/import/students' => ['admin/csv_import', 'admin', 'import', 'นำเข้าข้อมูล CSV - IMS Admin'],
    '/admin/import/companies' => ['admin/csv_import_companies', 'admin', 'import', 'นำเข้าสถานประกอบการ CSV - IMS Admin'],
    '/admin/announcements' => ['admin/announcements', 'admin', 'announcements', 'จัดการประกาศ - IMS Admin'],
    '/admin/evaluation-templates' => ['admin/evaluation_templates', 'admin', 'evaluation_templates', 'แบบประเมิน - IMS Admin'],
    '/admin/evaluation-templates/{id}' => ['admin/evaluation_template_form', 'admin', 'evaluation_templates', 'แก้ไขแบบประเมิน - IMS Admin'],
    '/admin/settings' => ['admin/settings', 'admin', 'settings', 'ตั้งค่าระบบ - IMS Admin'],
    '/admin/reports' => ['admin/reports', 'admin', 'reports', 'รายงาน - IMS Admin'],
    '/admin/audit-logs' => ['admin/audit_log', 'admin', 'audit_log', 'Audit Log - IMS Admin'],
    '/admin/notifications' => ['student/notifications_list', 'admin', '', 'การแจ้งเตือน - IMS Admin'],

    // Super Admin
    '/super-admin/dashboard' => ['admin/dashboard', 'super_admin', 'dashboard', 'แดชบอร์ด - Super Admin'],
    '/super-admin/admins' => ['super_admin/manage_admins', 'super_admin', 'admins', 'จัดการผู้ดูแลระบบ - Super Admin'],
    '/super-admin/permissions' => ['super_admin/permissions', 'super_admin', 'permissions', 'สิทธิ์การเข้าถึง - Super Admin'],
    // Phase 10: was 'guest' — a bug from Phase 0's original mock wiring left this PIN-confirmation
    // page reachable by anyone logged out, even though it now performs real destructive actions.
    '/super-admin/critical-actions' => ['super_admin/pin_modal', 'super_admin', 'critical_actions', 'ยืนยันตัวตน - Super Admin'],
    '/super-admin/pin/setup' => ['super_admin/pin_setup', 'super_admin', '', 'ตั้งค่า PIN - Super Admin'],
    '/super-admin/evaluations/{id}/override' => ['super_admin/evaluation_override', 'super_admin', 'admins', 'แก้ไขคะแนน - Super Admin'],
    '/super-admin/notifications' => ['student/notifications_list', 'super_admin', '', 'การแจ้งเตือน - Super Admin'],
    // Phase 10: super_admin needs its own path to the same internships list so the "ลบถาวร" button
    // (RULE-SEC-01, visible only to role=super_admin inside that view) is actually reachable —
    // '/admin/internships' itself stays role=admin only, matching the established per-role-route
    // pattern (see /company/daily-logs vs /teacher/daily-logs, Phase 6) rather than a pipe-delimited
    // role here, which partials/sidebar.php's exact-match $navByRole[$role] lookup can't handle.
    '/super-admin/internships' => ['admin/internship_approvals', 'super_admin', 'internships', 'การฝึกงาน - Super Admin'],
    '/super-admin/audit-logs' => ['admin/audit_log', 'super_admin', 'audit_log', 'Audit Log - Super Admin'],
];
