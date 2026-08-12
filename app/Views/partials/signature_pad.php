<?php
/**
 * Reusable digital signature pad (canvas). Include this inside any evaluation/approval form.
 * On submit, the hidden input named by $signatureFieldName carries a base64 PNG matching the
 * shape expected by POST /signatures in Internship_Project_Blueprint/API_SPEC.md §9:
 *   "data:image/png;base64,...."
 * The containing <form> is responsible for actually submitting it (Phase 2+ — no real API call yet).
 */
$signatureFieldName = $signatureFieldName ?? 'signature_image';
$padId = $padId ?? 'sig-' . substr(md5($signatureFieldName), 0, 8);
?>
<div class="flex flex-col gap-2">
  <h3 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode">ลายเซ็นดิจิทัล</h3>
  <p class="font-body-md text-metadata text-on-surface-variant">กรุณาลงลายเซ็นในกรอบด้านล่างก่อนส่งแบบประเมิน</p>
  <div class="relative bg-surface-container dark:bg-surface-container-high/10 rounded-[4px] border-2 border-dashed border-outline-variant overflow-hidden" style="touch-action:none;">
    <span id="<?= $padId ?>-placeholder" class="absolute inset-0 flex items-center justify-center font-headline-lg text-headline-lg text-on-surface-variant/20 select-none pointer-events-none">พื้นที่วาดลายเซ็น</span>
    <canvas id="<?= $padId ?>-canvas" class="w-full h-40 touch-none cursor-crosshair"></canvas>
  </div>
  <input type="hidden" name="<?= htmlspecialchars($signatureFieldName) ?>" id="<?= $padId ?>-input" required/>
  <div class="flex justify-end">
    <button type="button" id="<?= $padId ?>-clear" class="text-sm font-label-md text-label-md text-on-surface-variant hover:text-error transition-colors px-3 py-1.5 rounded-lg hover:bg-error-container/20">
      ล้าง
    </button>
  </div>
</div>
<script>
(function () {
  const canvas = document.getElementById('<?= $padId ?>-canvas');
  const placeholder = document.getElementById('<?= $padId ?>-placeholder');
  const hiddenInput = document.getElementById('<?= $padId ?>-input');
  const clearBtn = document.getElementById('<?= $padId ?>-clear');
  const ctx = canvas.getContext('2d');
  let drawing = false, hasDrawn = false;

  function resize() {
    const ratio = window.devicePixelRatio || 1;
    canvas.width = canvas.clientWidth * ratio;
    canvas.height = canvas.clientHeight * ratio;
    ctx.scale(ratio, ratio);
    ctx.lineWidth = 2.5;
    ctx.lineCap = 'round';
    ctx.strokeStyle = '#1b1b24';
  }
  window.addEventListener('resize', resize);
  resize();

  function pos(e) {
    const rect = canvas.getBoundingClientRect();
    const point = e.touches ? e.touches[0] : e;
    return { x: point.clientX - rect.left, y: point.clientY - rect.top };
  }
  function start(e) { drawing = true; hasDrawn = true; placeholder.classList.add('hidden'); const p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); }
  function move(e) { if (!drawing) return; e.preventDefault(); const p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); }
  function end() {
    if (!drawing) return;
    drawing = false;
    hiddenInput.value = canvas.toDataURL('image/png');
  }
  canvas.addEventListener('mousedown', start);
  canvas.addEventListener('mousemove', move);
  window.addEventListener('mouseup', end);
  canvas.addEventListener('touchstart', start, { passive: true });
  canvas.addEventListener('touchmove', move, { passive: false });
  canvas.addEventListener('touchend', end);

  clearBtn.addEventListener('click', () => {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    hiddenInput.value = '';
    hasDrawn = false;
    placeholder.classList.remove('hidden');
  });
})();
</script>
