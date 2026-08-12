<?php
/**
 * Final evaluation form — used for BOTH teacher_final (max 100, Template C) and company_final
 * (max 100, Template B), reused across /teacher/evaluations/final/{id} and
 * /company/evaluations/final/{id} the same way company/daily_log_review.php is shared across
 * company/teacher (Phase 6 precedent) — differs only by $formTitle/$formAction/$criteria passed
 * in. Uses numeric score inputs instead of stars since max scores vary per criterion
 * (LEAVE_EVALUATION_SIGNATURE.md §2.2). Wired to App\Controllers\EvaluationController (Phase 7).
 */
$notFound = $notFound ?? false;
$internshipId = $internshipId ?? 0;
$student = $student ?? ['name' => 'สมชาย ใจดี'];
$formTitle = $formTitle ?? 'ประเมินปลายภาค (ครูนิเทศ)';
$formAction = $formAction ?? ('/teacher/evaluations/final/' . $internshipId);
$formError = $formError ?? null;
$criteria = $criteria ?? [
    ['id' => 1, 'name' => 'ความสม่ำเสมอในการบันทึกงาน/รายงาน', 'max' => 20],
    ['id' => 2, 'name' => 'ความสอดคล้องของงานกับสาขาวิชา', 'max' => 25],
    ['id' => 3, 'name' => 'พัฒนาการระหว่างการฝึกงาน', 'max' => 20],
    ['id' => 4, 'name' => 'การนำเสนอผลสรุปการฝึกงาน', 'max' => 20],
    ['id' => 5, 'name' => 'คุณธรรม จริยธรรม และการอุทิศตน', 'max' => 15],
];
$maxScore = array_sum(array_column($criteria, 'max'));
?>
<div class="max-w-2xl mx-auto flex flex-col gap-6">
  <?php if ($notFound): ?>
    <div class="flex flex-col items-center justify-center py-16 text-center">
      <span class="material-symbols-outlined text-5xl text-outline-variant mb-3">search_off</span>
      <p class="font-body-md text-body-md text-on-surface-variant">ไม่พบการฝึกงานนี้ หรือยังไม่มีแบบประเมินที่รองรับ</p>
    </div>
  <?php else: ?>
    <div>
      <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($formTitle) ?></h1>
      <p class="font-body-md text-body-md text-on-surface-variant"><?= htmlspecialchars($student['name']) ?></p>
    </div>

    <?php if ($formError): ?>
      <div class="bg-error-container text-on-error-container rounded-lg p-4 font-body-md text-body-md" role="alert"><?= htmlspecialchars($formError) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= htmlspecialchars($formAction) ?>" class="bg-surface-container-lowest dark:bg-surface-dark rounded-[24px] shadow-soft p-8 flex flex-col gap-8 border border-surface-variant dark:border-outline-variant/20">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
      <div class="flex flex-col gap-5">
        <h2 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode">หัวข้อการประเมิน</h2>
        <?php foreach ($criteria as $c): ?>
          <div class="flex items-center justify-between gap-4">
            <label class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode flex-1" for="score-<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></label>
            <div class="flex items-center gap-2 shrink-0">
              <input type="number" id="score-<?= $c['id'] ?>" name="scores[<?= $c['id'] ?>]" min="0" max="<?= $c['max'] ?>" step="0.5" value="0" class="score-input w-20 h-10 text-center bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:ring-1 focus:ring-primary" data-max="<?= $c['max'] ?>"/>
              <span class="font-metadata text-metadata text-on-surface-variant">/<?= $c['max'] ?></span>
            </div>
          </div>
        <?php endforeach; ?>
        <div class="flex justify-between items-center pt-4 border-t border-outline-variant/20">
          <span class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode">รวม</span>
          <span class="font-headline-lg text-headline-lg text-primary" id="total-score">0/<?= $maxScore ?></span>
        </div>
      </div>

      <div class="flex flex-col gap-2">
        <label class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode" for="overall_comment">ความเห็นภาพรวม</label>
        <textarea id="overall_comment" name="overall_comment" rows="4" class="w-full bg-surface dark:bg-surface-container-high/10 rounded-2xl p-5 font-body-md text-body-md text-on-surface dark:text-text-dark-mode resize-none focus:outline-none focus:ring-2 focus:ring-primary/40 shadow-inner"></textarea>
      </div>

      <?php include __DIR__ . '/../partials/signature_pad.php'; ?>

      <button type="submit" id="submit-eval-btn" disabled
              class="w-full h-14 bg-primary text-on-primary rounded-xl font-label-md text-body-lg flex items-center justify-center gap-2 shadow-lg shadow-primary/30 transition-all active:scale-[0.97] disabled:opacity-50 disabled:cursor-not-allowed">
        <span class="material-symbols-outlined">send</span> ส่งแบบประเมิน
      </button>
    </form>
  <?php endif; ?>
</div>
<script>
  function updateTotal() {
    let total = 0;
    document.querySelectorAll('.score-input').forEach(i => {
      const max = Number(i.dataset.max);
      let v = Math.max(0, Math.min(max, Number(i.value) || 0));
      i.value = v;
      total += v;
    });
    document.getElementById('total-score').textContent = `${total}/<?= $maxScore ?>`;
  }
  document.querySelectorAll('.score-input').forEach(i => i.addEventListener('input', updateTotal));

  const sigInput = document.querySelector('input[name="signature_image"]');
  const submitBtn = document.getElementById('submit-eval-btn');
  setInterval(() => { submitBtn.disabled = !sigInput.value; }, 500);
</script>
