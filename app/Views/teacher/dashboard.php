<?php
/**
 * Teacher/advisor dashboard. Loosely adapted from design-reference/_2 (an advisor-style
 * overview mixing personal greeting + review-queue stats) — rebuilt with clearer aggregate
 * stats across all advisees since _2's exact scope was ambiguous between company/teacher.
 * Wired to App\Controllers\StudentTimelineController::teacherDashboardData() (Phase 11) — real
 * data for both `/teacher/dashboard` and `/teacher/students` (same view, same loader).
 */
$teacher = $teacher ?? ['name' => 'อ.สมศรี รักศิษย์'];
$stats = $stats ?? ['total_students' => 15, 'departments' => 3, 'not_logged_2days' => 3, 'pending_final_eval' => 2];
$students = $students ?? [
    ['internship_id' => 1, 'name' => 'สมชาย ใจดี', 'company' => 'บ.เอบีซี จำกัด', 'hours' => 210, 'hours_required' => 400, 'flag' => null],
    ['internship_id' => 2, 'name' => 'สมหญิง ดีใจ', 'company' => 'บ.ดีอีเอฟ จำกัด', 'hours' => 180, 'hours_required' => 400, 'flag' => 'ไม่บันทึกงาน 3 วัน'],
];
?>
<div class="flex flex-col gap-6">
  <div>
    <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($teacher['name']) ?></h1>
    <p class="font-body-md text-body-md text-on-surface-variant">นักศึกษาในความดูแล <?= $stats['total_students'] ?> คน (<?= $stats['departments'] ?> สาขา)</p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="bg-status-warning/10 rounded-2xl p-5 flex items-center justify-between">
      <div><p class="font-label-md text-label-md text-on-surface-variant mb-1">ยังไม่บันทึกงาน 2 วันขึ้นไป</p><h3 class="font-display-metrics text-display-metrics text-status-warning"><?= $stats['not_logged_2days'] ?> <span class="font-body-md text-body-md">คน</span></h3></div>
      <span class="material-symbols-outlined text-status-warning text-4xl">warning</span>
    </div>
    <div class="bg-primary-container/10 rounded-2xl p-5 flex items-center justify-between">
      <div><p class="font-label-md text-label-md text-on-surface-variant mb-1">รอประเมินปลายภาค</p><h3 class="font-display-metrics text-display-metrics text-primary"><?= $stats['pending_final_eval'] ?> <span class="font-body-md text-body-md">คน</span></h3></div>
      <span class="material-symbols-outlined text-primary text-4xl">grading</span>
    </div>
  </div>

  <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-2xl shadow-sm">
    <div class="p-4 lg:p-6 flex justify-between items-center flex-wrap gap-3">
      <h2 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode">รายชื่อนักศึกษา</h2>
      <div class="flex gap-2">
        <select class="px-4 py-2 bg-surface-container dark:bg-surface-container-high/10 rounded-xl font-label-md text-label-md text-on-surface-variant"><option>บริษัททั้งหมด</option></select>
        <select class="px-4 py-2 bg-surface-container dark:bg-surface-container-high/10 rounded-xl font-label-md text-label-md text-on-surface-variant"><option>สถานะทั้งหมด</option></select>
      </div>
    </div>
    <div class="flex flex-col divide-y divide-outline-variant/20">
      <?php if (empty($students)): ?>
        <p class="px-4 lg:px-6 py-8 text-center font-body-md text-body-md text-on-surface-variant">ยังไม่มีนักศึกษาในความดูแล</p>
      <?php endif; ?>
      <?php foreach ($students as $s): $pct = $s['hours_required'] > 0 ? round($s['hours'] / $s['hours_required'] * 100) : 0; ?>
        <a href="/teacher/students/<?= $s['internship_id'] ?>" class="flex items-center justify-between gap-4 px-4 lg:px-6 py-4 hover:bg-surface-container-low dark:hover:bg-surface-container/10 transition-colors">
          <div class="flex items-center gap-3 min-w-0">
            <div class="w-10 h-10 rounded-full bg-surface-variant flex items-center justify-center text-on-surface-variant font-headline-md shrink-0"><?= htmlspecialchars(mb_substr($s['name'], 0, 1)) ?></div>
            <div class="min-w-0">
              <p class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode truncate"><?= htmlspecialchars($s['name']) ?></p>
              <p class="font-metadata text-metadata text-on-surface-variant truncate"><?= htmlspecialchars($s['company']) ?></p>
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
