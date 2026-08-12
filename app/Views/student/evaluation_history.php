<?php
/**
 * Score/evaluation history. Adapted from design-reference/mobile_5. Wired to
 * App\Controllers\EvaluationController::studentHistoryData() (Phase 7) — respects
 * settings.score_visible_to_student (RULE-EVAL-06). The "รอบกลาง/รอบสุดท้าย" cards from the
 * original mock are repurposed to show the real company_final/teacher_final results (no
 * separate "mid-term" evaluator_type exists in the schema). $badges below stays pure mock —
 * gamification/achievements are not part of Phase 7's scope.
 */
$scoreVisible = $scoreVisible ?? true;
$weeklyScores = $weeklyScores ?? [
    ['week' => 1, 'score' => 20, 'max' => 20], ['week' => 2, 'score' => 18, 'max' => 20],
    ['week' => 3, 'score' => 19, 'max' => 20], ['week' => 4, 'score' => null, 'max' => 20],
];
$companyFinal = $companyFinal ?? null;
$teacherFinal = $teacherFinal ?? null;
$badges = $badges ?? [
    ['label' => 'เข้างานตรงเวลา 5 วัน', 'icon' => 'verified', 'unlocked' => true, 'color' => 'secondary-container'],
    ['label' => 'คะแนนเต็มสัปดาห์แรก', 'icon' => 'star', 'unlocked' => true, 'color' => 'tertiary-container'],
    ['label' => 'พนักงานดีเด่นเดือนแรก', 'icon' => 'emoji_events', 'unlocked' => false, 'color' => 'surface-variant'],
    ['label' => 'ส่งรายงานก่อนกำหนด', 'icon' => 'speed', 'unlocked' => false, 'color' => 'surface-variant'],
];
?>
<?php if (!$scoreVisible): ?>
  <div class="flex flex-col items-center text-center gap-4 py-16">
    <span class="material-symbols-outlined text-6xl text-outline-variant">lock</span>
    <h2 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode">คะแนนจะประกาศเมื่อสิ้นสุดการฝึกงาน</h2>
    <p class="font-body-md text-body-md text-on-surface-variant">อดใจรอสักนิดนะ 😊</p>
  </div>
<?php else: ?>
<div class="flex flex-col gap-8">
  <div class="flex flex-col gap-2">
    <h2 class="font-headline-lg-mobile lg:font-headline-lg text-headline-lg-mobile lg:text-headline-lg text-on-surface dark:text-text-dark-mode">ผลการประเมิน</h2>
    <p class="font-body-md text-body-md text-on-surface-variant">ติดตามความคืบหน้าและคะแนนของคุณ</p>
  </div>

  <div class="flex flex-col gap-4">
    <h3 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode">ความคืบหน้ารายสัปดาห์</h3>
    <div class="flex flex-col gap-4">
      <?php foreach ($weeklyScores as $w): $pct = $w['score'] === null ? 0 : round($w['score'] / $w['max'] * 100); ?>
        <div class="flex flex-col gap-1">
          <div class="flex justify-between items-center mb-1">
            <span class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode">สัปดาห์ที่ <?= $w['week'] ?></span>
            <span class="font-label-md text-label-md text-on-surface-variant"><?= $w['score'] === null ? 'รอประเมิน' : "{$w['score']}/{$w['max']}" ?></span>
          </div>
          <div class="w-full bg-surface-variant rounded-full h-3 overflow-hidden">
            <div class="<?= $w['score'] === null ? 'bg-status-inactive' : 'bg-primary' ?> h-full rounded-full transition-all duration-1000 ease-out" style="width:<?= $pct ?>%"></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="flex flex-col gap-4">
    <h3 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode">ผลการประเมินภาพรวม</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <?php foreach ([['label' => 'ประเมินโดยผู้ประกอบการ', 'data' => $companyFinal], ['label' => 'ประเมินโดยครูนิเทศ', 'data' => $teacherFinal]] as $card): ?>
        <?php if ($card['data'] !== null): ?>
          <div class="bg-surface-container dark:bg-surface-container-high/10 rounded-xl p-5 flex items-center justify-between">
            <div class="flex flex-col gap-1">
              <span class="font-label-md text-label-md text-on-surface-variant"><?= htmlspecialchars($card['label']) ?></span>
              <span class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode"><?= number_format($card['data']['total_score'], 1) ?> คะแนน</span>
            </div>
            <div class="w-16 h-16 rounded-full bg-status-success/10 flex items-center justify-center shrink-0">
              <span class="font-display-metrics text-display-metrics text-status-success"><?= htmlspecialchars($card['data']['grade'] ?? '-') ?></span>
            </div>
          </div>
        <?php else: ?>
          <div class="bg-surface-container dark:bg-surface-container-high/10 rounded-xl p-5 flex items-center justify-between opacity-70">
            <div class="flex flex-col gap-1">
              <span class="font-label-md text-label-md text-on-surface-variant"><?= htmlspecialchars($card['label']) ?></span>
              <span class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode">รอผลการประเมิน</span>
            </div>
            <div class="w-16 h-16 rounded-full bg-surface-variant flex items-center justify-center shrink-0"><span class="material-symbols-outlined text-on-surface-variant text-[32px]">hourglass_empty</span></div>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="flex flex-col gap-4">
    <h3 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode">ความสำเร็จที่ปลดล็อค</h3>
    <div class="flex overflow-x-auto gap-4 pb-2">
      <?php foreach ($badges as $b): ?>
        <div class="flex flex-col items-center gap-2 min-w-[100px]">
          <div class="w-20 h-20 rounded-full bg-<?= $b['color'] ?> flex items-center justify-center relative <?= $b['unlocked'] ? 'shadow-md' : 'opacity-50' ?>">
            <span class="material-symbols-outlined text-[40px]"><?= $b['icon'] ?></span>
            <?php if (!$b['unlocked']): ?>
              <div class="absolute inset-0 bg-surface/40 rounded-full backdrop-blur-[2px]"></div>
              <span class="material-symbols-outlined absolute text-[24px] text-on-surface">lock</span>
            <?php endif; ?>
          </div>
          <span class="font-label-md text-label-md text-center leading-tight <?= $b['unlocked'] ? 'text-on-surface dark:text-text-dark-mode' : 'text-on-surface-variant' ?>"><?= htmlspecialchars($b['label']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>
