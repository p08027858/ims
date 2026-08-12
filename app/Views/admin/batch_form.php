<?php
/**
 * Open a new internship batch (cycle). New page (not part of the 27 Stitch exports — the mock
 * only had the "current batch" card, not a creation form). Wired to
 * App\Controllers\BatchController::store() (Phase 8) — field names match internship_batches
 * (DATABASE.md §1.3).
 */
$formError = $formError ?? null;
?>
<div class="max-w-xl mx-auto flex flex-col gap-6">
  <div>
    <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode">เปิดรอบฝึกงานใหม่</h1>
  </div>

  <?php if ($formError): ?>
    <div class="bg-error-container text-on-error-container rounded-lg p-4 font-body-md text-body-md" role="alert"><?= htmlspecialchars($formError) ?></div>
  <?php endif; ?>

  <form method="post" action="/admin/batches" class="bg-surface-container-lowest dark:bg-surface-dark rounded-2xl p-6 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex flex-col gap-4">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
    <div class="flex flex-col gap-1">
      <label class="font-label-md text-label-md text-on-surface-variant" for="name">ชื่อรอบ (เช่น 2569/1)</label>
      <input id="name" name="name" required class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
    </div>
    <div class="grid grid-cols-2 gap-4">
      <div class="flex flex-col gap-1">
        <label class="font-label-md text-label-md text-on-surface-variant" for="academic_year">ปีการศึกษา (พ.ศ.)</label>
        <input id="academic_year" name="academic_year" type="number" required class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
      </div>
      <div class="flex flex-col gap-1">
        <label class="font-label-md text-label-md text-on-surface-variant" for="semester">ภาคการศึกษา</label>
        <input id="semester" name="semester" type="number" min="1" max="3" required class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
      </div>
    </div>
    <div class="grid grid-cols-2 gap-4">
      <div class="flex flex-col gap-1">
        <label class="font-label-md text-label-md text-on-surface-variant" for="register_start_date">เริ่มรับสมัคร</label>
        <input id="register_start_date" name="register_start_date" type="date" required class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
      </div>
      <div class="flex flex-col gap-1">
        <label class="font-label-md text-label-md text-on-surface-variant" for="register_end_date">สิ้นสุดรับสมัคร</label>
        <input id="register_end_date" name="register_end_date" type="date" required class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
      </div>
    </div>
    <div class="grid grid-cols-2 gap-4">
      <div class="flex flex-col gap-1">
        <label class="font-label-md text-label-md text-on-surface-variant" for="start_date">เริ่มฝึกงาน</label>
        <input id="start_date" name="start_date" type="date" required class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
      </div>
      <div class="flex flex-col gap-1">
        <label class="font-label-md text-label-md text-on-surface-variant" for="end_date">สิ้นสุดฝึกงาน</label>
        <input id="end_date" name="end_date" type="date" required class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
      </div>
    </div>
    <div class="grid grid-cols-2 gap-4">
      <div class="flex flex-col gap-1">
        <label class="font-label-md text-label-md text-on-surface-variant" for="min_hours_total">ชั่วโมงฝึกงานขั้นต่ำ</label>
        <input id="min_hours_total" name="min_hours_total" type="number" min="1" value="400" required class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
      </div>
      <div class="flex flex-col gap-1">
        <label class="font-label-md text-label-md text-on-surface-variant" for="min_hours_before_checkout">ชม.ขั้นต่ำก่อนลงเวลาออก</label>
        <input id="min_hours_before_checkout" name="min_hours_before_checkout" type="number" step="0.5" min="0.5" max="12" value="4" required class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
      </div>
    </div>
    <button type="submit" class="w-full h-touch-target bg-primary text-on-primary rounded-lg font-label-md text-label-md flex items-center justify-center gap-2 hover:bg-primary-container active:scale-[0.97] transition-all mt-2">
      <span class="material-symbols-outlined">add_circle</span> เปิดรอบฝึกงาน
    </button>
  </form>
</div>
