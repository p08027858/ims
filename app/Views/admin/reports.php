<?php
/**
 * Admin-wide reports (all faculties/departments the admin is scoped to). Reuses the same
 * table pattern as teacher/reports.php for visual consistency; not a distinct Stitch export.
 * Wired to App\Controllers\ReportController::summaryData() (Phase 8) — GET /admin/reports/summary.
 * Export PDF/Excel wired for real in Phase 11 — see ReportController's docblock for the
 * print-optimized-HTML / CSV approach (no PDF/XLSX library in this dependency-free project).
 */
$summary = $summary ?? ['total_students' => 120, 'active' => 96, 'completed' => 8, 'terminated' => 2, 'withdrawn' => 4, 'avg_final_score' => 85.2];
?>
<div class="flex flex-col gap-6">
  <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode">รายงานภาพรวม</h1>

  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-4 shadow-soft border border-surface-variant dark:border-outline-variant/20"><p class="font-label-md text-label-md text-on-surface-variant mb-1">นักศึกษาทั้งหมด</p><p class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode"><?= $summary['total_students'] ?></p></div>
    <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-4 shadow-soft border border-surface-variant dark:border-outline-variant/20"><p class="font-label-md text-label-md text-on-surface-variant mb-1">กำลังฝึกงาน</p><p class="font-headline-lg text-headline-lg text-primary"><?= $summary['active'] ?></p></div>
    <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-4 shadow-soft border border-surface-variant dark:border-outline-variant/20"><p class="font-label-md text-label-md text-on-surface-variant mb-1">เสร็จสิ้น</p><p class="font-headline-lg text-headline-lg text-status-success"><?= $summary['completed'] ?></p></div>
    <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-4 shadow-soft border border-surface-variant dark:border-outline-variant/20"><p class="font-label-md text-label-md text-on-surface-variant mb-1">คะแนนเฉลี่ยรวม</p><p class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode"><?= $summary['avg_final_score'] ?></p></div>
  </div>

  <div class="flex gap-3">
    <a href="/admin/reports/export-pdf" target="_blank" class="px-5 py-3 rounded-xl bg-surface-container dark:bg-surface-container-high/10 text-on-surface-variant font-label-md text-label-md flex items-center gap-2 hover:bg-surface-container-high transition-colors">
      <span class="material-symbols-outlined text-[20px]">picture_as_pdf</span> Export PDF
    </a>
    <a href="/admin/reports/export-csv" class="px-5 py-3 rounded-xl bg-surface-container dark:bg-surface-container-high/10 text-on-surface-variant font-label-md text-label-md flex items-center gap-2 hover:bg-surface-container-high transition-colors">
      <span class="material-symbols-outlined text-[20px]">grid_on</span> Export Excel
    </a>
  </div>
</div>
