<?php
/**
 * Internship batch (cycle) management. Adapted from design-reference/_14
 * ("จัดการรอบฝึกงาน" / "รอบดำเนินการปัจจุบัน" / "ยืนยันการปิดรอบฝึกงาน?"). Wired to
 * App\Controllers\BatchController (Phase 8) — field names match internship_batches
 * (DATA_DICTIONARY.md §3). "รอบดำเนินการปัจจุบัน" = the most recent non-closed batch; the
 * schema has no per-program scoping (batches are university-wide), so that line is generic.
 */
$currentBatch = $currentBatch ?? ['name' => 'ภาคเรียนที่ 2/2566', 'program' => 'ทุกคณะ/สาขา', 'status' => 'เปิดรับสมัคร', 'start_date' => '1 มิ.ย. 2569', 'end_date' => '30 ก.ย. 2569', 'min_hours_total' => 400, 'min_hours_before_checkout' => 4.0];
$pastBatches = $pastBatches ?? [['name' => 'ภาคเรียนที่ 1/2566', 'status' => 'ปิดรอบแล้ว']];
$formError = $formError ?? null;
?>
<div class="flex flex-col gap-8">
  <div>
    <h1 class="font-display-metrics text-[32px] text-on-background dark:text-text-dark-mode">จัดการรอบฝึกงาน</h1>
    <p class="font-body-md text-body-md text-on-surface-variant max-w-2xl mt-1">บริหารจัดการรอบการฝึกงานของนักศึกษาในแต่ละภาคการศึกษา กำหนดระยะเวลา และเงื่อนไขการเก็บชั่วโมง</p>
  </div>

  <?php if ($formError): ?>
    <div class="bg-error-container text-on-error-container rounded-lg p-4 font-body-md text-body-md" role="alert"><?= htmlspecialchars($formError) ?></div>
  <?php endif; ?>

  <div>
    <div class="flex items-center gap-4 mb-3">
      <h2 class="font-headline-lg text-headline-lg text-on-background dark:text-text-dark-mode">รอบดำเนินการปัจจุบัน</h2>
      <div class="flex-1 h-px bg-outline-variant/30"></div>
      <a href="/admin/batches/new" class="px-4 py-2 rounded-lg bg-primary text-on-primary font-label-md text-label-md flex items-center gap-1"><span class="material-symbols-outlined text-[18px]">add</span> เปิดรอบใหม่</a>
    </div>
    <?php if ($currentBatch === null): ?>
      <div class="flex flex-col items-center justify-center py-12 text-center bg-surface-container-lowest dark:bg-surface-dark rounded-2xl border border-surface-variant dark:border-outline-variant/20">
        <span class="material-symbols-outlined text-5xl text-outline-variant mb-3">event_busy</span>
        <p class="font-body-md text-body-md text-on-surface-variant">ยังไม่มีรอบฝึกงานที่เปิดอยู่ — กด "เปิดรอบใหม่" เพื่อเริ่ม</p>
      </div>
    <?php else: ?>
    <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-2xl p-6 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex flex-col gap-4">
      <div class="flex items-start justify-between flex-wrap gap-2">
        <div>
          <h3 class="font-display-metrics text-[28px] text-primary mb-1"><?= htmlspecialchars($currentBatch['name']) ?></h3>
          <p class="font-body-lg text-body-lg text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($currentBatch['program']) ?></p>
        </div>
        <span class="px-3 py-1 rounded-full bg-status-success/10 text-status-success font-label-md text-label-md h-fit"><?= htmlspecialchars($currentBatch['status']) ?></span>
      </div>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-4 border-t border-outline-variant/20">
        <div><p class="font-metadata text-metadata text-on-surface-variant">เริ่ม</p><p class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($currentBatch['start_date']) ?></p></div>
        <div><p class="font-metadata text-metadata text-on-surface-variant">สิ้นสุด</p><p class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($currentBatch['end_date']) ?></p></div>
        <div><p class="font-metadata text-metadata text-on-surface-variant">ชม.ขั้นต่ำ</p><p class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode"><?= $currentBatch['min_hours_total'] ?> ชม.</p></div>
        <div><p class="font-metadata text-metadata text-on-surface-variant">ชม.ขั้นต่ำก่อนออกงาน</p><p class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode"><?= $currentBatch['min_hours_before_checkout'] ?> ชม./วัน</p></div>
      </div>
      <div class="flex justify-end gap-3 pt-2">
        <button type="button" id="close-batch-btn" class="px-5 py-2.5 rounded-lg border border-error text-error hover:bg-error-container/20 font-label-md text-label-md transition-colors">ปิดรอบ</button>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <div>
    <div class="flex items-center gap-4 mb-3">
      <h2 class="font-headline-md text-headline-md text-on-background dark:text-text-dark-mode opacity-80">ประวัติรอบที่ผ่านมา</h2>
      <div class="flex-1 h-px bg-outline-variant/30"></div>
    </div>
    <div class="flex flex-col gap-2">
      <?php foreach ($pastBatches as $b): ?>
        <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-4 shadow-sm border border-surface-variant dark:border-outline-variant/20 flex items-center justify-between">
          <span class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($b['name']) ?></span>
          <span class="px-3 py-1 rounded-full bg-surface-variant text-on-surface-variant font-label-md text-label-md text-sm"><?= htmlspecialchars($b['status']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div id="close-batch-modal" class="hidden fixed inset-0 z-50 bg-surface-dark/40 backdrop-blur-sm items-center justify-center p-4">
  <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-2xl p-8 max-w-sm w-full text-center">
    <h3 class="font-headline-lg text-headline-lg-mobile text-on-background dark:text-text-dark-mode mb-3">ยืนยันการปิดรอบฝึกงาน?</h3>
    <p class="font-body-md text-body-md text-on-surface-variant mb-6">นักศึกษาในรอบนี้จะไม่สามารถลงเวลาหรือส่งบันทึกงานเพิ่มเติมได้อีก</p>
    <div class="flex gap-3">
      <button type="button" id="cancel-close-batch" class="flex-1 py-3 rounded-xl bg-surface-container text-on-surface-variant font-label-md text-label-md">ยกเลิก</button>
      <button type="submit" form="close-batch-form" class="flex-1 py-3 rounded-xl bg-error text-on-error font-label-md text-label-md">ยืนยันปิดรอบ</button>
    </div>
  </div>
</div>
<form id="close-batch-form" method="post" action="/admin/batches/close" class="hidden"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/></form>
<script>
  document.getElementById('close-batch-btn')?.addEventListener('click', () => {
    const m = document.getElementById('close-batch-modal'); m.classList.remove('hidden'); m.classList.add('flex');
  });
  document.getElementById('cancel-close-batch')?.addEventListener('click', () => {
    const m = document.getElementById('close-batch-modal'); m.classList.add('hidden'); m.classList.remove('flex');
  });
</script>
