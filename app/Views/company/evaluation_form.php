<?php
/**
 * Weekly evaluation form for company supervisors.
 */
$notFound = $notFound ?? false;
$internshipId = $internshipId ?? 0;
$student = $student ?? ['name' => 'สมชาย ใจดี'];
$weekNumber = $weekNumber ?? 4;
$formTitle = $formTitle ?? null;
$formError = $formError ?? null;
$criteria = $criteria ?? [
    ['id' => 1, 'name' => 'ความรับผิดชอบต่องาน', 'max' => 5],
    ['id' => 2, 'name' => 'คุณภาพงาน', 'max' => 5],
    ['id' => 3, 'name' => 'การทำงานร่วมกับผู้อื่น', 'max' => 5],
    ['id' => 4, 'name' => 'ทัศนคติและความตรงต่อเวลา', 'max' => 5],
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
      <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($formTitle ?? ('ประเมินรายสัปดาห์ที่ ' . $weekNumber)) ?></h1>
      <p class="font-body-md text-body-md text-on-surface-variant"><?= htmlspecialchars($student['name']) ?></p>
    </div>

    <?php if ($formError): ?>
      <div class="bg-error-container text-on-error-container rounded-lg p-4 font-body-md text-body-md" role="alert"><?= htmlspecialchars($formError) ?></div>
    <?php endif; ?>

    <form method="post" action="/company/evaluations/weekly/<?= $internshipId ?>" class="bg-surface-container-lowest dark:bg-surface-dark rounded-[24px] shadow-soft p-8 flex flex-col gap-8 border border-surface-variant dark:border-outline-variant/20">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
      <input type="hidden" name="week_number" value="<?= (int) $weekNumber ?>"/>
      <div class="flex flex-col gap-6">
        <h2 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode">หัวข้อการประเมิน</h2>
        <?php foreach ($criteria as $c): ?>
          <div class="flex flex-col gap-2" data-criteria-id="<?= $c['id'] ?>" data-max="<?= $c['max'] ?>">
            <div class="flex justify-between items-center">
              <span class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($c['name']) ?></span>
              <span class="font-label-md text-label-md text-on-surface-variant score-display">0/<?= $c['max'] ?></span>
            </div>
            <div class="flex gap-1 star-rating" role="radiogroup" aria-label="<?= htmlspecialchars($c['name']) ?>">
              <?php for ($i = 1; $i <= $c['max']; $i++): ?>
                <button type="button" class="star-btn material-symbols-outlined text-3xl text-outline-variant transition-transform active:scale-90" data-value="<?= $i ?>" aria-label="<?= $i ?> คะแนน">star</button>
              <?php endfor; ?>
            </div>
            <input type="hidden" name="scores[<?= $c['id'] ?>]" class="score-input" value="0"/>
          </div>
        <?php endforeach; ?>
        <div class="flex justify-between items-center pt-4 border-t border-outline-variant/20">
          <span class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode">รวม</span>
          <span class="font-headline-lg text-headline-lg text-primary" id="total-score">0/<?= $maxScore ?></span>
        </div>
      </div>

      <div class="flex flex-col gap-2">
        <h2 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode">ความคิดเห็นและข้อเสนอแนะ</h2>
        <textarea name="overall_comment" rows="4" placeholder="ความคิดเห็นเพิ่มเติม..."
                  class="w-full bg-surface dark:bg-surface-container-high/10 rounded-2xl p-5 font-body-md text-body-md text-on-surface dark:text-text-dark-mode resize-none focus:outline-none focus:ring-2 focus:ring-primary/40 shadow-inner"></textarea>
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
  document.querySelectorAll('[data-criteria-id]').forEach(group => {
    const max = Number(group.dataset.max);
    const stars = group.querySelectorAll('.star-btn');
    const scoreInput = group.querySelector('.score-input');
    const display = group.querySelector('.score-display');
    stars.forEach(star => {
      star.addEventListener('click', () => {
        const val = Number(star.dataset.value);
        scoreInput.value = val;
        display.textContent = `${val}/${max}`;
        stars.forEach(s => {
          const filled = Number(s.dataset.value) <= val;
          s.classList.toggle('text-status-warning', filled);
          s.classList.toggle('text-outline-variant', !filled);
          s.style.fontVariationSettings = filled ? "'FILL' 1" : "'FILL' 0";
        });
        updateTotal();
      });
    });
  });

  function updateTotal() {
    let total = 0;
    document.querySelectorAll('.score-input').forEach(i => total += Number(i.value));
    const max = <?= $maxScore ?>;
    document.getElementById('total-score').textContent = `${total}/${max}`;
  }

  const sigInput = document.querySelector('input[name="signature_image"]');
  const submitBtn = document.getElementById('submit-eval-btn');
  new MutationObserver(() => { submitBtn.disabled = !sigInput.value; }).observe(sigInput, { attributes: true, attributeFilter: ['value'] });
  sigInput?.addEventListener('input', () => { submitBtn.disabled = !sigInput.value; });
  setInterval(() => { submitBtn.disabled = !sigInput.value; }, 500);
</script>