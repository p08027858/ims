<?php
/**
 * PIN confirmation for destructive/critical actions (SECURITY.md §6, API_SPEC.md §13, Phase 10).
 * Adapted from design-reference/pin (auto-advance, shake-on-error, success-overlay JS preserved
 * almost verbatim — it was already excellent). Rendered standalone (role=super_admin, no
 * sidebar/topbar needed for a modal-style page).
 *
 * Driven entirely by query string so any page can link here to confirm one destructive action:
 *   ?actionUrl=/admin/internships/5&actionMethod=DELETE&redirect=/admin/internships&cancel=/admin/internships&desc=...
 * On successful PIN entry this calls POST /super-admin/verify-pin for a real action_token, then
 * replays `actionUrl` with that token in the X-Action-Token header — no more client-side "123456".
 */
$actionUrl = $_GET['actionUrl'] ?? '';
$actionMethod = strtoupper((string) ($_GET['actionMethod'] ?? 'DELETE'));
$redirectUrl = $_GET['redirect'] ?? '/super-admin/dashboard';
$cancelUrl = $_GET['cancel'] ?? $redirectUrl;
$actionDescription = $_GET['desc'] ?? 'คุณกำลังทำรายการที่ทำลาย/แก้ไขข้อมูลสำคัญ การกระทำนี้อาจไม่สามารถย้อนกลับได้';
?>
<div class="flex flex-col w-full h-full items-center justify-center p-4 relative overflow-hidden">
  <div class="absolute inset-0 bg-surface-dark/40 backdrop-blur-md z-0"></div>
  <div class="relative z-10 w-full max-w-sm bg-surface-container-lowest dark:bg-surface-dark rounded-[24px] shadow-2xl flex flex-col p-8 opacity-0 scale-95 transition-all duration-300 ease-out" id="pin-modal">
    <div class="flex justify-center mb-6 relative">
      <div class="absolute inset-0 bg-error/20 blur-xl rounded-full scale-150"></div>
      <div class="w-16 h-16 bg-error-container rounded-full flex items-center justify-center relative z-10 text-on-error-container shadow-sm">
        <span class="material-symbols-outlined text-3xl font-light">lock</span>
      </div>
    </div>
    <div class="text-center mb-6 space-y-2">
      <h2 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode">ยืนยันตัวตนด้วย PIN</h2>
      <p class="font-body-md text-body-md text-on-surface-variant">กรุณากรอกรหัส PIN 6 หลักของคุณ</p>
    </div>
    <div class="bg-error-container/50 rounded-xl p-4 flex items-start gap-3 mb-8">
      <span class="material-symbols-outlined text-error mt-0.5 text-xl">warning</span>
      <div class="flex-1">
        <p class="font-label-md text-label-md text-on-error-container mb-1">การดำเนินการสำคัญ</p>
        <p class="font-metadata text-metadata text-on-error-container/80 leading-relaxed"><?= htmlspecialchars($actionDescription) ?></p>
      </div>
    </div>
    <form id="pin-form" data-action-url="<?= htmlspecialchars($actionUrl) ?>" data-action-method="<?= htmlspecialchars($actionMethod) ?>" data-redirect="<?= htmlspecialchars($redirectUrl) ?>">
      <div class="flex justify-between gap-2 mb-3" id="pin-inputs-container">
        <?php for ($i = 0; $i < 6; $i++): ?>
          <input class="w-12 h-14 bg-surface-container dark:bg-surface-container-high/10 rounded-lg text-center font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode focus:outline-none focus:bg-inverse-on-surface focus:ring-2 focus:ring-error transition-all pin-input" inputmode="numeric" maxlength="1" type="password"/>
        <?php endfor; ?>
      </div>
      <p id="pin-error-message" class="hidden font-metadata text-metadata text-error text-center mb-7" role="alert"></p>
      <div class="flex gap-4 mt-auto">
        <a href="<?= htmlspecialchars($cancelUrl) ?>" type="button" class="flex-1 py-3.5 px-4 rounded-full bg-surface-container dark:bg-surface-container-high/10 hover:bg-surface-container-high transition-all font-label-md text-label-md text-on-surface-variant flex items-center justify-center gap-2">ยกเลิก</a>
        <button class="flex-1 py-3.5 px-4 rounded-full bg-error hover:bg-error/90 active:scale-[0.97] transition-all font-label-md text-label-md text-on-error flex items-center justify-center gap-2 shadow-lg shadow-error/20" id="confirm-btn" type="button">
          <span class="material-symbols-outlined text-xl">check_circle</span> ยืนยัน
        </button>
      </div>
    </form>
    <div class="absolute inset-0 bg-status-success rounded-[24px] flex flex-col items-center justify-center text-on-error opacity-0 pointer-events-none scale-110 transition-all duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)] z-20" id="success-overlay">
      <span class="material-symbols-outlined text-6xl mb-4">task_alt</span>
      <p class="font-headline-md text-headline-md">ยืนยันสำเร็จ</p>
    </div>
  </div>
</div>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('pin-modal');
    const inputs = document.querySelectorAll('.pin-input');
    const confirmBtn = document.getElementById('confirm-btn');
    const inputsContainer = document.getElementById('pin-inputs-container');
    const successOverlay = document.getElementById('success-overlay');
    const errorMessage = document.getElementById('pin-error-message');
    const form = document.getElementById('pin-form');
    const actionUrl = form.dataset.actionUrl;
    const actionMethod = form.dataset.actionMethod;
    const redirectUrl = form.dataset.redirect;

    setTimeout(() => { modal.classList.remove('opacity-0', 'scale-95'); modal.classList.add('opacity-100', 'scale-100'); inputs[0].focus(); }, 50);

    inputs.forEach((input, index) => {
      input.addEventListener('input', (e) => { if (e.target.value.length === 1 && index < inputs.length - 1) inputs[index + 1].focus(); });
      input.addEventListener('keydown', (e) => { if (e.key === 'Backspace' && !e.target.value && index > 0) inputs[index - 1].focus(); });
    });

    function showError(message) {
      errorMessage.textContent = message;
      errorMessage.classList.remove('hidden');
      inputsContainer.classList.add('shake-anim');
      inputs.forEach(i => i.classList.add('ring-2', 'ring-error', 'text-error'));
      setTimeout(() => inputsContainer.classList.remove('shake-anim'), 400);
      setTimeout(() => { inputs.forEach(i => { i.value = ''; i.classList.remove('ring-2', 'ring-error', 'text-error'); }); inputs[0].focus(); }, 800);
    }

    function showSuccess() {
      successOverlay.classList.remove('opacity-0', 'pointer-events-none', 'scale-110');
      successOverlay.classList.add('opacity-100', 'scale-100');
      setTimeout(() => { window.location.href = redirectUrl; }, 900);
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    confirmBtn.addEventListener('click', async () => {
      const pin = Array.from(inputs).map(i => i.value).join('');
      if (pin.length !== 6) {
        showError('กรุณากรอก PIN ให้ครบ 6 หลัก');
        return;
      }
      confirmBtn.disabled = true;
      try {
        const verifyRes = await fetch('/super-admin/verify-pin', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
          body: JSON.stringify({ pin }),
        });
        const verifyBody = await verifyRes.json();
        if (!verifyBody.success) {
          showError(verifyBody.error?.message || 'ยืนยัน PIN ไม่สำเร็จ');
          confirmBtn.disabled = false;
          return;
        }
        const actionToken = verifyBody.data.action_token;

        if (!actionUrl) {
          // No target action attached to this confirmation page — verifying PIN alone is the goal.
          showSuccess();
          return;
        }
        const actionRes = await fetch(actionUrl, {
          method: actionMethod,
          headers: { 'X-Action-Token': actionToken, 'X-CSRF-Token': csrfToken },
        });
        const actionBody = await actionRes.json();
        if (!actionBody.success) {
          showError(actionBody.error?.message || 'ทำรายการไม่สำเร็จ');
          confirmBtn.disabled = false;
          return;
        }
        showSuccess();
      } catch (err) {
        showError('เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่');
        confirmBtn.disabled = false;
      }
    });
  });
</script>
