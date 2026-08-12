<?php
/**
 * Student's own daily log history (SITEMAP.md §2 `/student/daily-logs`, Phase 11) — replaces the
 * attendance_history.php placeholder this route used since Phase 6 (see the TODO comment that was
 * in config/routes.php). Wired to App\Controllers\DailyLogController::listData().
 */
$noActiveInternship = $noActiveInternship ?? false;
$logs = $logs ?? [
    ['id' => 1, 'log_date' => '22 ก.ค. 2569', 'status' => 'submitted', 'status_label' => 'ส่งแล้ว', 'editable' => false, 'work_description' => 'ออกแบบหน้า Dashboard ด้วย React'],
];
$statusClass = [
    'draft' => 'bg-surface-variant text-on-surface-variant',
    'submitted' => 'bg-status-warning/10 text-status-warning',
    'reviewed' => 'bg-status-success/10 text-status-success',
    'revision_requested' => 'bg-status-error/10 text-status-error',
];
?>
<div class="flex flex-col gap-6">
  <div class="flex items-center justify-between flex-wrap gap-3">
    <div>
      <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode">บันทึกงานประจำวัน</h1>
      <p class="font-body-md text-body-md text-on-surface-variant">ประวัติบันทึกงานทั้งหมด</p>
    </div>
    <a href="/student/daily-logs/new" class="px-5 py-2.5 rounded-xl bg-primary text-on-primary font-label-md text-label-md flex items-center gap-2">
      <span class="material-symbols-outlined text-[20px]">add</span> บันทึกวันนี้
    </a>
  </div>

  <?php if ($noActiveInternship): ?>
    <div class="flex flex-col items-center justify-center py-16 text-center bg-surface-container-lowest dark:bg-surface-dark rounded-xl border border-surface-variant dark:border-outline-variant/20">
      <span class="material-symbols-outlined text-5xl text-outline-variant mb-3">work_off</span>
      <p class="font-body-md text-body-md text-on-surface-variant">ไม่พบการฝึกงานที่กำลังดำเนินอยู่ของคุณในขณะนี้</p>
    </div>
  <?php elseif (empty($logs)): ?>
    <div class="flex flex-col items-center justify-center py-16 text-center">
      <span class="material-symbols-outlined text-5xl text-outline-variant mb-3">description</span>
      <p class="font-body-md text-body-md text-on-surface-variant">ยังไม่มีบันทึกงาน</p>
    </div>
  <?php else: ?>
    <div class="flex flex-col gap-3">
      <?php foreach ($logs as $l): ?>
        <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-4 shadow-sm border border-surface-variant/50 dark:border-outline-variant/10 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 sm:justify-between">
          <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2 mb-1">
              <p class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($l['log_date']) ?></p>
              <span class="px-2 py-0.5 rounded-full font-metadata text-metadata <?= $statusClass[$l['status']] ?? 'bg-surface-variant text-on-surface-variant' ?>"><?= htmlspecialchars($l['status_label']) ?></span>
            </div>
            <p class="font-metadata text-metadata text-on-surface-variant truncate"><?= htmlspecialchars($l['work_description']) ?></p>
          </div>
          <?php if ($l['editable']): ?>
            <a href="/student/daily-logs/<?= $l['id'] ?>/edit" class="shrink-0 px-4 py-2 rounded-lg border border-primary text-primary font-label-md text-label-md hover:bg-primary/10 transition-colors">แก้ไข</a>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
