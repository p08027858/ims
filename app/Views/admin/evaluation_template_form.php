<?php
/**
 * Edit one evaluation template's criteria (Phase 8 item 5, RULE-EVAL-03).
 */
$notFound = $notFound ?? false;
$templateId = $templateId ?? 0;
$templateName = $templateName ?? '';
$maxScore = $maxScore ?? 0;
$criteria = $criteria ?? [];
$formError = $formError ?? null;
?>
<div class="max-w-2xl mx-auto flex flex-col gap-6">
  <?php if ($notFound): ?>
    <div class="flex flex-col items-center justify-center py-16 text-center">
      <span class="material-symbols-outlined text-5xl text-outline-variant mb-3">search_off</span>
      <p class="font-body-md text-body-md text-on-surface-variant">ไม่พบแบบประเมินนี้</p>
    </div>
  <?php else: ?>
    <div>
      <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($templateName) ?></h1>
      <p class="font-body-md text-body-md text-on-surface-variant">ผลรวมหัวข้อย่อยต้องเท่ากับคะแนนเต็มของแบบประเมินตาม RULE-EVAL-03</p>
    </div>

    <?php if ($formError): ?>
      <div class="bg-error-container text-on-error-container rounded-lg p-4 font-body-md text-body-md" role="alert"><?= htmlspecialchars($formError) ?></div>
    <?php endif; ?>

    <form method="post" action="/admin/evaluation-templates/<?= $templateId ?>" class="bg-surface-container-lowest dark:bg-surface-dark rounded-2xl p-6 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex flex-col gap-4">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
      <?php foreach ($criteria as $c): ?>
        <div class="flex items-center gap-3">
          <input type="text" name="criteria_name[<?= $c['id'] ?>]" value="<?= htmlspecialchars($c['criteria_name']) ?>" class="criteria-name flex-1 h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
          <input type="number" name="criteria_max[<?= $c['id'] ?>]" value="<?= $c['max_score'] ?>" step="0.5" min="0" class="criteria-score w-24 h-touch-target px-3 text-center bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
        </div>
      <?php endforeach; ?>

      <div class="flex items-center justify-between pt-4 border-t border-outline-variant/20">
        <span class="font-label-md text-label-md text-on-surface-variant">ผลรวมหัวข้อย่อย</span>
        <span id="criteria-sum" class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode">0</span>
      </div>
      <div class="flex items-center justify-between">
        <label class="font-label-md text-label-md text-on-surface-variant" for="max_score">คะแนนเต็มของแบบประเมิน</label>
        <input type="number" id="max_score" name="max_score" value="<?= $maxScore ?>" step="0.5" min="0" class="w-24 h-touch-target px-3 text-center bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
      </div>
      <p id="sum-mismatch-warning" class="hidden font-metadata text-metadata text-error">ผลรวมหัวข้อย่อยต้องเท่ากับคะแนนเต็มของแบบประเมิน</p>

      <button type="submit" class="w-full h-touch-target bg-primary text-on-primary rounded-lg font-label-md text-label-md flex items-center justify-center gap-2 hover:bg-primary-container active:scale-[0.97] transition-all">
        <span class="material-symbols-outlined">save</span> บันทึก
      </button>
    </form>
  <?php endif; ?>
</div>
<script>
  function recompute() {
    let sum = 0;
    document.querySelectorAll('.criteria-score').forEach((input) => {
      sum += Number(input.value) || 0;
    });
    document.getElementById('criteria-sum').textContent = sum;
    const max = Number(document.getElementById('max_score').value) || 0;
    document.getElementById('sum-mismatch-warning').classList.toggle('hidden', Math.abs(sum - max) < 0.01);
  }

  document.querySelectorAll('.criteria-score, #max_score').forEach((input) => {
    input.addEventListener('input', recompute);
  });

  recompute();
</script>