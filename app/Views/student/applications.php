<?php
/**
 * Student's own application history (SITEMAP.md §2 `/student/applications`, Phase 11). New page —
 * Phase 4 only ever needed the search/detail/apply flow, never a "my applications" list. Wired to
 * App\Controllers\ApplicationController::myApplicationsData().
 */
$applications = $applications ?? [
    ['id' => 1, 'company_name' => 'บริษัท เทคโนวา จำกัด', 'position_title' => 'Frontend Developer', 'status' => 'accepted', 'applied_at' => '2026-07-01T09:00:00+07:00'],
];
$statusLabel = ['pending' => 'รอพิจารณา', 'accepted' => 'ตอบรับแล้ว', 'rejected' => 'ปฏิเสธ', 'cancelled' => 'ยกเลิกแล้ว'];
$statusClass = [
    'pending' => 'bg-status-warning/10 text-status-warning',
    'accepted' => 'bg-status-success/10 text-status-success',
    'rejected' => 'bg-status-error/10 text-status-error',
    'cancelled' => 'bg-surface-variant text-on-surface-variant',
];
?>
<div class="flex flex-col gap-6">
  <div>
    <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode">ใบสมัครของฉัน</h1>
    <p class="font-body-md text-body-md text-on-surface-variant">สถานะใบสมัครฝึกงานทั้งหมดที่เคยยื่น</p>
  </div>

  <?php if (empty($applications)): ?>
    <div class="flex flex-col items-center justify-center py-16 text-center">
      <span class="material-symbols-outlined text-5xl text-outline-variant mb-3">description</span>
      <p class="font-body-md text-body-md text-on-surface-variant mb-4">ยังไม่มีใบสมัคร</p>
      <a href="/student/companies" class="px-5 py-2.5 rounded-xl bg-primary text-on-primary font-label-md text-label-md">ค้นหาสถานประกอบการ</a>
    </div>
  <?php else: ?>
    <div class="flex flex-col gap-3">
      <?php foreach ($applications as $a): ?>
        <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-4 shadow-sm border border-surface-variant/50 dark:border-outline-variant/10 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 sm:justify-between">
          <div class="min-w-0">
            <p class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode truncate"><?= htmlspecialchars($a['company_name']) ?></p>
            <p class="font-metadata text-metadata text-on-surface-variant truncate"><?= htmlspecialchars($a['position_title']) ?> · <?= htmlspecialchars(date('j M Y', strtotime($a['applied_at']))) ?></p>
          </div>
          <span class="shrink-0 px-2.5 py-1 rounded-full font-label-md text-label-md <?= $statusClass[$a['status']] ?? 'bg-surface-variant text-on-surface-variant' ?>"><?= htmlspecialchars($statusLabel[$a['status']] ?? $a['status']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
