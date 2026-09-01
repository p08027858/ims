<?php
/**
 * Evaluation template list (Phase 8 item 5) - new page, not part of the 27 Stitch exports.
 * The evaluator_type enum only has 3 fixed values (DATABASE.md section 0.2) so this lists
 * exactly 3 rows always; each links to a criteria-editing form (RULE-EVAL-03 enforced there).
 */
$templates = $templates ?? [
    ['id' => 1, 'label' => 'ประเมินรายสัปดาห์ (ผู้ประกอบการ)', 'max_score' => 20, 'criteria_count' => 4],
];
?>
<div class="flex flex-col gap-6">
  <div>
    <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode">แบบประเมิน</h1>
    <p class="font-body-md text-body-md text-on-surface-variant">แก้ไขหัวข้อและคะแนนเต็มของแบบประเมินแต่ละประเภท โดยผลรวมหัวข้อย่อยต้องเท่ากับคะแนนเต็มเสมอ</p>
  </div>

  <div class="flex flex-col gap-3">
    <?php foreach ($templates as $t): ?>
      <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-5 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex items-center justify-between gap-4 flex-wrap">
        <div>
          <p class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($t['label']) ?></p>
          <p class="font-metadata text-metadata text-on-surface-variant"><?= $t['criteria_count'] ?> หัวข้อ · คะแนนเต็ม <?= $t['max_score'] ?></p>
        </div>
        <a href="/admin/evaluation-templates/<?= $t['id'] ?>" class="px-4 py-2 rounded-lg bg-primary text-on-primary font-label-md text-label-md flex items-center gap-1">
          <span class="material-symbols-outlined text-[18px]">edit</span> แก้ไข
        </a>
      </div>
    <?php endforeach; ?>
  </div>
</div>