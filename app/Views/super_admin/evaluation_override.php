<?php
/**
 * RULE-EVAL-05 exception path — Super Admin edits scores on an already-submitted evaluation.
 * New page (not part of the 27 Stitch exports). Since Phase 10 the submit button first verifies
 * PIN inline (fetch to /super-admin/verify-pin) and fills the hidden `action_token` field before
 * the form's normal POST — App\Controllers\SuperAdminEvaluationController::override() rejects the
 * request via App\Middleware\ActionTokenGuard if that token is missing/expired/wrong. Every use
 * is written to audit_logs (App\Services\EvaluationService::adminOverrideScores()).
 */
$notFound = $notFound ?? false;
$evaluationId = $evaluationId ?? 0;
$currentTotal = $currentTotal ?? 0;
$status = $status ?? '';
$criteria = $criteria ?? [];
$formError = $formError ?? null;
$maxScore = array_sum(array_column($criteria, 'max'));
?>
<div class="max-w-2xl mx-auto flex flex-col gap-6">
  <?php if ($notFound): ?>
    <div class="flex flex-col items-center justify-center py-16 text-center">
      <span class="material-symbols-outlined text-5xl text-outline-variant mb-3">search_off</span>
      <p class="font-body-md text-body-md text-on-surface-variant">ไม่พบแบบประเมินนี้</p>
    </div>
  <?php else: ?>
    <div>
      <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode">แก้ไขคะแนน (Super Admin)</h1>
      <p class="font-body-md text-body-md text-on-surface-variant">Evaluation #<?= $evaluationId ?> · สถานะปัจจุบัน: <?= htmlspecialchars($status) ?> · คะแนนรวมปัจจุบัน: <?= $currentTotal ?></p>
    </div>

    <div class="bg-status-warning/10 rounded-lg p-4 font-body-md text-body-md text-status-warning" role="alert">
      คำเตือน: การแก้ไขนี้จะถูกบันทึกลง audit log ถาวร (RULE-EVAL-05) — ต้องยืนยันตัวตนด้วย PIN ก่อนบันทึกทุกครั้ง (SECURITY.md §6)
    </div>

    <?php if ($formError): ?>
      <div class="bg-error-container text-on-error-container rounded-lg p-4 font-body-md text-body-md" role="alert"><?= htmlspecialchars($formError) ?></div>
    <?php endif; ?>

    <form method="post" action="/super-admin/evaluations/<?= $evaluationId ?>/override" id="override-form" class="bg-surface-container-lowest dark:bg-surface-dark rounded-[24px] shadow-soft p-8 flex flex-col gap-6 border border-surface-variant dark:border-outline-variant/20">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
      <input type="hidden" name="action_token" id="action_token" value=""/>
      <?php foreach ($criteria as $c): ?>
        <div class="flex items-center justify-between gap-4">
          <label class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode flex-1" for="score-<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></label>
          <div class="flex items-center gap-2 shrink-0">
            <input type="number" id="score-<?= $c['id'] ?>" name="scores[<?= $c['id'] ?>]" min="0" max="<?= $c['max'] ?>" step="0.5" value="<?= $c['current'] ?>" class="w-20 h-10 text-center bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:ring-1 focus:ring-primary"/>
            <span class="font-metadata text-metadata text-on-surface-variant">/<?= $c['max'] ?></span>
          </div>
        </div>
      <?php endforeach; ?>

      <div id="pin-inline" class="hidden flex-col gap-3 bg-surface-container dark:bg-surface-container-high/10 rounded-xl p-4">
        <p class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode">กรอก PIN 6 หลักเพื่อยืนยันการบันทึก</p>
        <div class="flex justify-between gap-2" id="pin-inline-inputs">
          <?php for ($i = 0; $i < 6; $i++): ?>
            <input class="w-11 h-12 bg-surface-container-lowest dark:bg-surface-dark rounded-lg text-center font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:ring-2 focus:ring-error transition-all pin-inline-input" inputmode="numeric" maxlength="1" type="password"/>
          <?php endfor; ?>
        </div>
        <p id="pin-inline-error" class="hidden font-metadata text-metadata text-error"></p>
        <button type="button" id="pin-inline-confirm" class="w-full h-11 bg-error text-on-error rounded-lg font-label-md text-label-md">ยืนยัน PIN และบันทึก</button>
      </div>

      <button type="button" id="open-pin-btn" class="w-full h-14 bg-error text-on-error rounded-xl font-label-md text-body-lg flex items-center justify-center gap-2 shadow-lg transition-all active:scale-[0.97]">
        <span class="material-symbols-outlined">edit</span> บันทึกการแก้ไข
      </button>
    </form>
    <script>
      document.getElementById('open-pin-btn')?.addEventListener('click', (e) => {
        e.target.classList.add('hidden');
        const inline = document.getElementById('pin-inline');
        inline.classList.remove('hidden');
        inline.classList.add('flex');
        document.querySelectorAll('.pin-inline-input')[0]?.focus();
      });
      const inlineInputs = document.querySelectorAll('.pin-inline-input');
      inlineInputs.forEach((input, index) => {
        input.addEventListener('input', (e) => { if (e.target.value.length === 1 && index < inlineInputs.length - 1) inlineInputs[index + 1].focus(); });
        input.addEventListener('keydown', (e) => { if (e.key === 'Backspace' && !e.target.value && index > 0) inlineInputs[index - 1].focus(); });
      });
      document.getElementById('pin-inline-confirm')?.addEventListener('click', async (e) => {
        const btn = e.target;
        const errorEl = document.getElementById('pin-inline-error');
        const pin = Array.from(inlineInputs).map(i => i.value).join('');
        errorEl.classList.add('hidden');
        if (pin.length !== 6) {
          errorEl.textContent = 'กรุณากรอก PIN ให้ครบ 6 หลัก';
          errorEl.classList.remove('hidden');
          return;
        }
        btn.disabled = true;
        try {
          const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
          const res = await fetch('/super-admin/verify-pin', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
            body: JSON.stringify({ pin }),
          });
          const body = await res.json();
          if (!body.success) {
            errorEl.textContent = body.error?.message || 'ยืนยัน PIN ไม่สำเร็จ';
            errorEl.classList.remove('hidden');
            btn.disabled = false;
            return;
          }
          document.getElementById('action_token').value = body.data.action_token;
          document.getElementById('override-form').submit();
        } catch (err) {
          errorEl.textContent = 'เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่';
          errorEl.classList.remove('hidden');
          btn.disabled = false;
        }
      });
    </script>
  <?php endif; ?>
</div>
