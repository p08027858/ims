<?php
/**
 * Left sidebar (desktop, lg:flex) — collapses to bottom_nav.php on mobile.
 * Nav items per role are defined here once instead of duplicated across 27 view files
 * (the original Stitch exports repeated this block in every code.html).
 * TODO: hrefs are placeholder paths matching Internship_Project_Blueprint/SITEMAP.md —
 * wire real auth/session (Phase 2) before these routes are protected.
 */

$navByRole = [
    'student' => [
        ['icon' => 'home', 'label' => 'หน้าหลัก', 'href' => '/student/dashboard', 'key' => 'dashboard'],
        ['icon' => 'fingerprint', 'label' => 'ลงเวลา', 'href' => '/student/attendance', 'key' => 'attendance'],
        ['icon' => 'description', 'label' => 'บันทึกประจำวัน', 'href' => '/student/daily-logs', 'key' => 'daily_logs'],
        ['icon' => 'contact_page', 'label' => 'ใบสมัครของฉัน', 'href' => '/student/applications', 'key' => 'my_applications'],
        ['icon' => 'notifications', 'label' => 'การแจ้งเตือน', 'href' => '/student/notifications', 'key' => 'notifications'],
        ['icon' => 'person', 'label' => 'โปรไฟล์', 'href' => '/student/profile', 'key' => 'profile'],
    ],
    'company' => [
        ['icon' => 'dashboard', 'label' => 'แดชบอร์ด', 'href' => '/company/dashboard', 'key' => 'dashboard'],
        ['icon' => 'contact_page', 'label' => 'ใบสมัคร', 'href' => '/company/applications', 'key' => 'applications'],
        ['icon' => 'groups', 'label' => 'นักศึกษาในความดูแล', 'href' => '/company/students', 'key' => 'students'],
        ['icon' => 'location_on', 'label' => 'ตั้งค่าพิกัด', 'href' => '/company/profile', 'key' => 'gps_setup'],
        ['icon' => 'badge', 'label' => 'ผู้ติดต่อ/พี่เลี้ยง', 'href' => '/company/supervisors', 'key' => 'supervisors'],
        ['icon' => 'fact_check', 'label' => 'ตรวจบันทึกงาน', 'href' => '/company/daily-logs', 'key' => 'daily_logs'],
        ['icon' => 'grading', 'label' => 'ประเมินรายสัปดาห์', 'href' => '/company/evaluations/weekly', 'key' => 'evaluations'],
        ['icon' => 'workspace_premium', 'label' => 'ประเมินปลายภาค', 'href' => '/company/evaluations/final', 'key' => 'evaluations'],
        ['icon' => 'event_busy', 'label' => 'คำขอลา', 'href' => '/company/leave-requests', 'key' => 'leave_requests'],
    ],
    'teacher' => [
        ['icon' => 'dashboard', 'label' => 'แดชบอร์ด', 'href' => '/teacher/dashboard', 'key' => 'dashboard'],
        ['icon' => 'groups', 'label' => 'นักศึกษาในความดูแล', 'href' => '/teacher/students', 'key' => 'students'],
        ['icon' => 'fact_check', 'label' => 'ตรวจบันทึกงาน', 'href' => '/teacher/daily-logs', 'key' => 'daily_logs'],
        ['icon' => 'event_busy', 'label' => 'คำขอลา', 'href' => '/teacher/leave-requests', 'key' => 'leave_requests'],
        ['icon' => 'grading', 'label' => 'ประเมินปลายภาค', 'href' => '/teacher/evaluations/final', 'key' => 'evaluations'],
        ['icon' => 'summarize', 'label' => 'รายงาน', 'href' => '/teacher/reports', 'key' => 'reports'],
    ],
    'admin' => [
        ['icon' => 'dashboard', 'label' => 'แดชบอร์ด', 'href' => '/admin/dashboard', 'key' => 'dashboard'],
        ['icon' => 'how_to_reg', 'label' => 'ผู้ใช้งาน', 'href' => '/admin/users', 'key' => 'users'],
        ['icon' => 'apartment', 'label' => 'สถานประกอบการ', 'href' => '/admin/companies', 'key' => 'companies'],
        ['icon' => 'account_balance', 'label' => 'คณะ/สาขา', 'href' => '/admin/organization', 'key' => 'organization'],
        ['icon' => 'calendar_month', 'label' => 'รอบฝึกงาน', 'href' => '/admin/batches', 'key' => 'batches'],
        ['icon' => 'link', 'label' => 'จับคู่ครูนิเทศ', 'href' => '/admin/matching', 'key' => 'matching'],
        ['icon' => 'work_history', 'label' => 'การฝึกงานที่ยืนยันแล้ว', 'href' => '/admin/internships', 'key' => 'internships'],
        ['icon' => 'upload_file', 'label' => 'นำเข้า CSV', 'href' => '/admin/import/students', 'key' => 'import'],
        ['icon' => 'grading', 'label' => 'แบบประเมิน', 'href' => '/admin/evaluation-templates', 'key' => 'evaluation_templates'],
        ['icon' => 'settings', 'label' => 'ตั้งค่าระบบ', 'href' => '/admin/settings', 'key' => 'settings'],
        ['icon' => 'campaign', 'label' => 'ประกาศ', 'href' => '/admin/announcements', 'key' => 'announcements'],
        ['icon' => 'summarize', 'label' => 'รายงาน', 'href' => '/admin/reports', 'key' => 'reports'],
        ['icon' => 'history', 'label' => 'Audit Log', 'href' => '/admin/audit-logs', 'key' => 'audit_log'],
    ],
    'super_admin' => [
        ['icon' => 'dashboard', 'label' => 'แดชบอร์ด', 'href' => '/super-admin/dashboard', 'key' => 'dashboard'],
        ['icon' => 'work_history', 'label' => 'การฝึกงาน', 'href' => '/super-admin/internships', 'key' => 'internships'],
        ['icon' => 'admin_panel_settings', 'label' => 'จัดการผู้ดูแลระบบ', 'href' => '/super-admin/admins', 'key' => 'admins'],
        ['icon' => 'security', 'label' => 'สิทธิ์การเข้าถึง', 'href' => '/super-admin/permissions', 'key' => 'permissions'],
        ['icon' => 'history', 'label' => 'Audit Log', 'href' => '/super-admin/audit-logs', 'key' => 'audit_log'],
    ],
];

$brandByRole = [
    'student' => ['title' => 'ระบบฝึกงาน', 'subtitle' => 'มหาวิทยาลัย', 'logo_class' => 'bg-primary'],
    'company' => ['title' => 'Supervisor', 'subtitle' => $user['company_name'] ?? 'สถานประกอบการ', 'logo_class' => 'bg-secondary'],
    'teacher' => ['title' => 'ครูนิเทศ', 'subtitle' => 'ระบบฝึกงาน', 'logo_class' => 'bg-tertiary'],
    'admin' => ['title' => 'IMS Admin', 'subtitle' => $user['faculty_name'] ?? 'ผู้ดูแลระบบ', 'logo_class' => 'bg-primary'],
    'super_admin' => ['title' => 'Super Admin', 'subtitle' => 'ควบคุมระบบทั้งหมด', 'logo_class' => 'bg-error'],
];

$items = $navByRole[$role] ?? [];
$brand = $brandByRole[$role] ?? ['title' => 'IMS THAI', 'subtitle' => '', 'logo_class' => 'bg-primary'];
?>
<nav class="fixed left-0 top-0 h-full w-sidebar-width hidden lg:flex flex-col bg-surface-container-low dark:bg-surface-dark border-r border-outline-variant dark:border-surface-variant z-40">
  <div class="flex flex-col gap-2 p-4 w-sidebar-width h-full">
    <div class="flex items-center gap-3 mb-8 px-4">
      <div class="w-10 h-10 rounded-full <?= $brand['logo_class'] ?> flex items-center justify-center text-on-primary">
        <span class="material-symbols-outlined">school</span>
      </div>
      <div>
        <h1 class="font-headline-md text-headline-md font-black text-primary truncate"><?= htmlspecialchars($brand['title']) ?></h1>
        <p class="font-metadata text-metadata text-on-surface-variant truncate"><?= htmlspecialchars($brand['subtitle']) ?></p>
      </div>
    </div>
    <div class="flex-1 flex flex-col gap-2">
      <?php foreach ($items as $item): $isActive = $activeNav === $item['key']; ?>
        <a href="<?= htmlspecialchars($item['href']) ?>"
           class="flex items-center gap-3 rounded-xl px-4 py-3 transition-all active:scale-[0.97] <?= $isActive ? 'bg-primary-container text-on-primary-container' : 'text-on-surface-variant hover:bg-surface-container-highest' ?>">
          <span class="material-symbols-outlined<?= $isActive ? ' fill' : '' ?>"><?= $item['icon'] ?></span>
          <span class="font-label-md text-label-md"><?= htmlspecialchars($item['label']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
    <div class="mt-auto flex flex-col gap-2">
      <?php if ($role === 'student'): ?>
        <button class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-primary to-secondary text-on-primary rounded-xl px-4 py-4 font-label-md text-label-md shadow-md active:scale-[0.97] transition-transform hover:opacity-90">
          <span class="material-symbols-outlined">where_to_vote</span>
          เช็คอินเข้างาน
        </button>
      <?php endif; ?>
      <a href="/logout" class="flex items-center gap-3 text-error px-4 py-3 rounded-xl hover:bg-error-container/40 transition-all">
        <span class="material-symbols-outlined">logout</span>
        <span class="font-label-md text-label-md">ออกจากระบบ</span>
      </a>
    </div>
  </div>
</nav>
