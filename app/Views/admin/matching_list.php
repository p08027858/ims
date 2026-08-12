<?php
/**
 * List of accepted applications not yet turned into a confirmed internship. New page (not part
 * of the 27 Stitch exports — admin/matching.php itself, the per-application form, WAS one of
 * them). Wired to App\Controllers\InternshipController::matchingListData().
 */
$unmatched = $unmatched ?? [
    ['application_id' => 1, 'student_name' => 'สมชาย ใจดี', 'student_code' => '6512345678', 'department_name' => 'วิศวกรรมคอมพิวเตอร์', 'company_name' => 'บริษัท เทคโนวา จำกัด', 'company_address' => '-', 'position_title' => 'Frontend Developer Intern'],
];
?>
<div class="flex flex-col gap-6">
  <div>
    <h1 class="font-display-metrics text-[32px] text-on-surface dark:text-text-dark-mode mb-2">จับคู่ครูนิเทศ</h1>
    <p class="font-body-md text-body-md text-on-surface-variant">ใบสมัครที่บริษัทตอบรับแล้ว รอ Admin ยืนยันจับคู่ครูนิเทศเป็นการฝึกงานจริง</p>
  </div>

  <?php if (empty($unmatched)): ?>
    <div class="flex flex-col items-center justify-center py-16 text-center">
      <span class="material-symbols-outlined text-6xl text-status-success mb-4">task_alt</span>
      <h3 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode mb-2">ไม่มีรายการรอจับคู่</h3>
      <p class="font-body-md text-body-md text-on-surface-variant max-w-sm">ใบสมัครที่บริษัทตอบรับแล้วทั้งหมดถูกจับคู่ครูนิเทศเรียบร้อย</p>
    </div>
  <?php else: ?>
    <div class="flex flex-col gap-3">
      <?php foreach ($unmatched as $m): ?>
        <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-5 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex items-center justify-between gap-4 flex-wrap">
          <div>
            <p class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($m['student_name']) ?> <span class="font-metadata text-metadata text-on-surface-variant">(<?= htmlspecialchars($m['student_code']) ?>)</span></p>
            <p class="font-metadata text-metadata text-on-surface-variant"><?= htmlspecialchars($m['department_name']) ?> → <?= htmlspecialchars($m['company_name']) ?> · <?= htmlspecialchars($m['position_title']) ?></p>
          </div>
          <a href="/admin/matching/<?= $m['application_id'] ?>" class="px-4 py-2 rounded-lg bg-primary text-on-primary font-label-md text-label-md flex items-center gap-1">
            <span class="material-symbols-outlined text-[18px]">link</span> จับคู่
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
