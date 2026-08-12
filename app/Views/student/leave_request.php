<?php
/**
 * Leave request form. Adapted from design-reference/mobile_3. Wired to
 * App\Controllers\LeaveController::apply() (Phase 6) — the sick-leave attachment requirement
 * below mirrors settings.sick_leave_certificate_min_days (RULE-LEAVE-01), also re-checked
 * server-side (client-side disable is UX only, never trusted alone).
 */
$sickLeaveCertMinDays = $sickLeaveCertMinDays ?? 3;
$formError = $formError ?? null;
?>
<div class="max-w-lg mx-auto flex flex-col gap-6">
  <div class="flex items-center justify-between">
    <h1 class="font-headline-lg-mobile lg:font-headline-lg text-headline-lg-mobile lg:text-headline-lg text-on-surface dark:text-text-dark-mode">ยื่นใบลา</h1>
  </div>

  <?php if ($formError): ?>
    <div class="bg-error-container text-on-error-container rounded-lg p-4 font-body-md text-body-md" role="alert"><?= htmlspecialchars($formError) ?></div>
  <?php endif; ?>

  <form id="leave-form" method="post" action="/student/leave-requests" enctype="multipart/form-data" class="flex flex-col gap-6">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
    <div class="flex flex-col gap-2">
      <label class="font-label-md text-label-md text-on-surface-variant">ประเภทการลา</label>
      <div class="relative flex w-full bg-surface-container dark:bg-surface-container-high/10 rounded-lg p-1" id="leave-type-container">
        <div class="absolute top-1 bottom-1 left-1 bg-surface dark:bg-surface-dark rounded-md shadow-sm transition-transform duration-300 ease-in-out" id="leave-highlight" style="width:calc(33.333% - 0.5rem);"></div>
        <button type="button" data-value="sick" class="leave-tab flex-1 relative z-10 py-2 text-center font-label-md text-label-md text-primary transition-colors" onclick="selectLeaveType(this,0,'sick')">
          <span class="flex items-center justify-center gap-1"><span class="material-symbols-outlined text-[18px]">sick</span> ลาป่วย</span>
        </button>
        <button type="button" data-value="personal" class="leave-tab flex-1 relative z-10 py-2 text-center font-label-md text-label-md text-on-surface-variant transition-colors" onclick="selectLeaveType(this,1,'personal')">
          <span class="flex items-center justify-center gap-1"><span class="material-symbols-outlined text-[18px]">event</span> ลากิจ</span>
        </button>
        <button type="button" data-value="other" class="leave-tab flex-1 relative z-10 py-2 text-center font-label-md text-label-md text-on-surface-variant transition-colors" onclick="selectLeaveType(this,2,'other')">
          <span class="flex items-center justify-center gap-1"><span class="material-symbols-outlined text-[18px]">more_horiz</span> อื่นๆ</span>
        </button>
      </div>
      <input type="hidden" name="leave_type" id="leave_type" value="sick"/>
    </div>

    <div class="flex flex-col gap-2">
      <div class="flex items-center justify-between">
        <label class="font-label-md text-label-md text-on-surface-variant">วันที่ต้องการลา</label>
        <div class="bg-primary-container/10 px-3 py-1 rounded-full"><span class="font-label-md text-label-md text-primary" id="day-count-chip">จำนวนวัน: 0 วัน</span></div>
      </div>
      <div class="flex gap-4">
        <div class="flex-1 flex flex-col gap-2">
          <label class="font-metadata text-metadata text-on-surface-variant" for="start-date">เริ่มต้น</label>
          <div class="relative bg-surface-container dark:bg-surface-container-high/10 rounded-xl flex items-center px-4 py-3">
            <span class="material-symbols-outlined text-outline mr-2 text-[20px]">calendar_today</span>
            <input class="bg-transparent border-none outline-none w-full font-body-md text-on-surface dark:text-text-dark-mode" id="start-date" name="start_date" onchange="calculateDays()" type="date" required/>
          </div>
        </div>
        <div class="flex flex-col justify-center pt-6"><span class="material-symbols-outlined text-outline">arrow_right_alt</span></div>
        <div class="flex-1 flex flex-col gap-2">
          <label class="font-metadata text-metadata text-on-surface-variant" for="end-date">สิ้นสุด</label>
          <div class="relative bg-surface-container dark:bg-surface-container-high/10 rounded-xl flex items-center px-4 py-3">
            <span class="material-symbols-outlined text-outline mr-2 text-[20px]">calendar_today</span>
            <input class="bg-transparent border-none outline-none w-full font-body-md text-on-surface dark:text-text-dark-mode" id="end-date" name="end_date" onchange="calculateDays()" type="date" required/>
          </div>
        </div>
      </div>
    </div>

    <div class="flex flex-col gap-2">
      <label class="font-label-md text-label-md text-on-surface-variant" for="reason">เหตุผลการลา</label>
      <textarea id="reason" name="reason" required class="bg-surface-container dark:bg-surface-container-high/10 rounded-xl p-4 min-h-[120px] font-body-md text-on-surface dark:text-text-dark-mode resize-none focus:outline-none focus:ring-2 focus:ring-primary/50 placeholder:text-outline" placeholder="ระบุเหตุผลที่ต้องการลาอย่างละเอียด..."></textarea>
    </div>

    <div class="hidden bg-status-warning/10 rounded-xl p-4 flex-col gap-3" id="medical-certificate-warning">
      <div class="flex gap-3">
        <span class="material-symbols-outlined text-status-warning shrink-0">warning</span>
        <div class="flex flex-col gap-1">
          <span class="font-label-md text-label-md text-status-warning">ลาป่วยเกิน <?= $sickLeaveCertMinDays ?> วัน</span>
          <span class="font-body-md text-metadata text-status-warning/80">ระบบกำหนดให้แนบใบรับรองแพทย์สำหรับการลาป่วยติดต่อกัน <?= $sickLeaveCertMinDays ?> วันขึ้นไป (RULE-LEAVE-01)</span>
        </div>
      </div>
      <label class="flex flex-col items-center justify-center p-6 border-2 border-dashed border-status-warning/30 rounded-lg bg-surface dark:bg-surface-container-high/10 hover:bg-status-warning/5 transition-colors cursor-pointer" for="file-upload">
        <span class="material-symbols-outlined text-status-warning mb-2">upload_file</span>
        <span class="font-label-md text-label-md text-status-warning">แตะเพื่ออัปโหลดใบรับรองแพทย์</span>
        <span class="font-metadata text-metadata text-status-warning/60 mt-1">รองรับ JPG, PNG, PDF</span>
        <input accept="image/*,.pdf" class="hidden" id="file-upload" name="attachment" type="file"/>
      </label>
    </div>

    <button type="submit" id="submit-leave-btn" class="w-full h-14 bg-primary rounded-full flex items-center justify-center gap-2 text-on-primary font-headline-md text-label-md shadow-lg shadow-primary/25 hover:shadow-xl active:scale-95 transition-all disabled:opacity-60">
      <span class="material-symbols-outlined">send</span> ส่งคำขอลา
    </button>
  </form>
</div>
<script>
  let currentLeaveType = 'sick';
  const minDays = <?= $sickLeaveCertMinDays ?>;

  function selectLeaveType(btn, index, type) {
    currentLeaveType = type;
    document.getElementById('leave_type').value = type;
    document.getElementById('leave-highlight').style.transform = `translateX(${index * 100}%)`;
    document.querySelectorAll('.leave-tab').forEach(t => t.classList.remove('text-primary'));
    document.querySelectorAll('.leave-tab').forEach(t => t.classList.add('text-on-surface-variant'));
    btn.classList.add('text-primary');
    btn.classList.remove('text-on-surface-variant');
    calculateDays();
  }

  function calculateDays() {
    const start = new Date(document.getElementById('start-date').value);
    const end = new Date(document.getElementById('end-date').value);
    const warning = document.getElementById('medical-certificate-warning');
    const fileInput = document.getElementById('file-upload');
    const submitBtn = document.getElementById('submit-leave-btn');
    let days = 0;
    if (start && end && end >= start && document.getElementById('start-date').value) {
      days = Math.ceil((end - start) / 86400000) + 1;
    }
    document.getElementById('day-count-chip').textContent = `จำนวนวัน: ${days} วัน`;

    const needsCert = currentLeaveType === 'sick' && days >= minDays;
    warning.classList.toggle('hidden', !needsCert);
    warning.classList.toggle('flex', needsCert);
    // RULE-LEAVE-01: block submit until a certificate is attached when required.
    submitBtn.disabled = needsCert && !(fileInput.files && fileInput.files.length);
  }

  document.getElementById('file-upload')?.addEventListener('change', calculateDays);
  calculateDays();
</script>
