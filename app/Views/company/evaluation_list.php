<?php
/**
 * "Pick a student to evaluate" queue — new page (not part of the 27 Stitch exports), shared by
 * /company/evaluations/weekly, /company/evaluations/final, and /teacher/evaluations/final (each
 * links to its own {internshipId} form route). Not present as a distinct Stitch screen because
 * Phase 0's evaluation_form.php mocks jumped straight to "the" student — Phase 7 needed a real
 * way to pick WHICH student first.
 */
$students = $students ?? [
    ['internship_id' => 1, 'student_name' => 'สมชาย ใจดี', 'next_week' => 4, 'already_done' => false],
];
$weekly = $weekly ?? true;
$listTitle = $listTitle ?? 'ประเมินรายสัปดาห์';
$linkBase = $linkBase ?? '/company/evaluations/weekly';
?>
<div class="flex flex-col gap-6">
  <div>
    <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($listTitle) ?></h1>
    <p class="font-body-md text-body-md text-on-surface-variant">เลือกนักศึกษาที่ต้องการประเมิน</p>
  </div>

  <?php if (empty($students)): ?>
    <div class="flex flex-col items-center justify-center py-12 text-center">
      <span class="material-symbols-outlined text-5xl text-outline-variant mb-3">groups</span>
      <p class="font-body-md text-body-md text-on-surface-variant">ยังไม่มีนักศึกษาในความดูแลของคุณ</p>
    </div>
  <?php else: ?>
    <div class="flex flex-col gap-3">
      <?php foreach ($students as $s): ?>
        <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-5 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex items-center justify-between gap-4 flex-wrap">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-surface-variant flex items-center justify-center text-on-surface-variant font-headline-md shrink-0"><?= htmlspecialchars(mb_substr($s['student_name'], 0, 1)) ?></div>
            <div>
              <p class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($s['student_name']) ?></p>
              <?php if ($weekly): ?>
                <p class="font-metadata text-metadata text-on-surface-variant">ยังไม่ได้ประเมินสัปดาห์ที่ <?= (int) $s['next_week'] ?></p>
              <?php elseif ($s['already_done']): ?>
                <p class="font-metadata text-metadata text-status-success">ประเมินแล้ว</p>
              <?php else: ?>
                <p class="font-metadata text-metadata text-on-surface-variant">ยังไม่ได้ประเมิน</p>
              <?php endif; ?>
            </div>
          </div>
          <?php if (!$weekly && $s['already_done']): ?>
            <span class="px-4 py-2 rounded-lg bg-status-success/10 text-status-success font-label-md text-label-md flex items-center gap-1">
              <span class="material-symbols-outlined text-[18px]">check_circle</span> ประเมินแล้ว
            </span>
          <?php else: ?>
            <a href="<?= $linkBase ?>/<?= $s['internship_id'] ?>" class="px-4 py-2 rounded-lg bg-primary text-on-primary font-label-md text-label-md flex items-center gap-1">
              <span class="material-symbols-outlined text-[18px]">rate_review</span> ประเมิน
            </a>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
