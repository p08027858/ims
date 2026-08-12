<?php
/**
 * Company's list of students in its care (SITEMAP.md §3 `/company/students`, Phase 11). New page.
 * Wired to App\Controllers\StudentTimelineController::companyListData().
 */
$students = $students ?? [
    ['internship_id' => 1, 'name' => 'สมชาย ใจดี', 'company' => 'บ.เอบีซี จำกัด', 'hours' => 210, 'hours_required' => 400, 'flag' => null],
];
?>
<div class="flex flex-col gap-6">
  <div>
    <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode">นักศึกษาในความดูแล</h1>
    <p class="font-body-md text-body-md text-on-surface-variant">นักศึกษาที่กำลังฝึกงาน/ฝึกงานเสร็จสิ้นกับบริษัทนี้</p>
  </div>

  <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-2xl shadow-sm">
    <div class="flex flex-col divide-y divide-outline-variant/20">
      <?php if (empty($students)): ?>
        <p class="px-4 lg:px-6 py-8 text-center font-body-md text-body-md text-on-surface-variant">ยังไม่มีนักศึกษาในความดูแล</p>
      <?php endif; ?>
      <?php foreach ($students as $s): $pct = $s['hours_required'] > 0 ? round($s['hours'] / $s['hours_required'] * 100) : 0; ?>
        <a href="/company/students/<?= $s['internship_id'] ?>" class="flex items-center justify-between gap-4 px-4 lg:px-6 py-4 hover:bg-surface-container-low dark:hover:bg-surface-container/10 transition-colors">
          <div class="flex items-center gap-3 min-w-0">
            <div class="w-10 h-10 rounded-full bg-surface-variant flex items-center justify-center text-on-surface-variant font-headline-md shrink-0"><?= htmlspecialchars(mb_substr($s['name'], 0, 1)) ?></div>
            <div class="min-w-0">
              <p class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode truncate"><?= htmlspecialchars($s['name']) ?></p>
            </div>
          </div>
          <div class="flex items-center gap-4 shrink-0">
            <div class="hidden sm:block w-32">
              <div class="w-full bg-surface-variant rounded-full h-2 overflow-hidden"><div class="bg-primary h-full rounded-full" style="width:<?= $pct ?>%"></div></div>
              <span class="font-metadata text-metadata text-on-surface-variant">ชม.สะสม <?= $s['hours'] ?>/<?= $s['hours_required'] ?></span>
            </div>
            <?php if ($s['flag']): ?><span class="px-2 py-1 rounded-full bg-status-warning/10 text-status-warning text-xs font-label-md">⚠ <?= htmlspecialchars($s['flag']) ?></span><?php endif; ?>
            <span class="material-symbols-outlined text-on-surface-variant">chevron_right</span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
