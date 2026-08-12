<?php
/**
 * System settings. Adapted from design-reference/_15 ("การตั้งค่าทั่วไป" / "กฎระเบียบการฝึกงาน"
 * / "สิทธิ์การเข้าถึง"). Field keys match SETTINGS.md §1 exactly. Wired to
 * App\Controllers\SettingsController (Phase 8) — GET/POST /admin/settings, validated per
 * SETTINGS.md §5 (all-or-nothing: any field failing validation rejects the whole submission).
 */
$settings = $settings ?? [
    'default_gps_radius_m' => 100, 'min_hours_before_checkout' => 4.0, 'max_upload_size_kb' => 1024,
    'sick_leave_certificate_min_days' => 3, 'score_visible_to_student' => true,
];
$formError = $formError ?? null;
$formSuccess = $formSuccess ?? false;
?>
<div class="max-w-2xl mx-auto flex flex-col gap-8">
  <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode">ตั้งค่าระบบ</h1>

  <?php if ($formError): ?>
    <div class="bg-error-container text-on-error-container rounded-lg p-4 font-body-md text-body-md" role="alert"><?= htmlspecialchars($formError) ?></div>
  <?php endif; ?>
  <?php if ($formSuccess): ?>
    <div class="bg-status-success/10 text-status-success rounded-lg p-4 font-body-md text-body-md" role="status">บันทึกการตั้งค่าเรียบร้อยแล้ว</div>
  <?php endif; ?>

  <form method="post" action="/admin/settings" class="flex flex-col gap-8">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
    <section class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-6 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex flex-col gap-5">
      <h2 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode">การตั้งค่าทั่วไป (General)</h2>
      <div class="flex flex-col gap-1">
        <label class="font-label-md text-label-md text-on-surface-variant" for="default_gps_radius_m">รัศมี GPS เริ่มต้น (เมตร)</label>
        <input id="default_gps_radius_m" name="default_gps_radius_m" type="number" min="10" max="500" value="<?= $settings['default_gps_radius_m'] ?>"
               class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"/>
      </div>
      <div class="flex flex-col gap-1">
        <label class="font-label-md text-label-md text-on-surface-variant" for="max_upload_size_kb">ขนาดไฟล์แนบสูงสุด (KB)</label>
        <input id="max_upload_size_kb" name="max_upload_size_kb" type="number" min="100" max="10240" value="<?= $settings['max_upload_size_kb'] ?>"
               class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"/>
      </div>
    </section>

    <section class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-6 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex flex-col gap-5">
      <h2 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode">กฎระเบียบการฝึกงาน (Internship Rules)</h2>
      <div class="flex flex-col gap-1">
        <label class="font-label-md text-label-md text-on-surface-variant" for="min_hours_before_checkout">ชม.ขั้นต่ำก่อนลงเวลาออก</label>
        <input id="min_hours_before_checkout" name="min_hours_before_checkout" type="number" step="0.5" min="0" max="12" value="<?= $settings['min_hours_before_checkout'] ?>"
               class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"/>
      </div>
      <div class="flex flex-col gap-1">
        <label class="font-label-md text-label-md text-on-surface-variant" for="sick_leave_certificate_min_days">เกณฑ์บังคับแนบใบรับรองแพทย์ (วัน)</label>
        <input id="sick_leave_certificate_min_days" name="sick_leave_certificate_min_days" type="number" min="1" value="<?= $settings['sick_leave_certificate_min_days'] ?>"
               class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"/>
      </div>
      <div class="flex items-center justify-between">
        <label class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode" for="score_visible_to_student">เปิดให้นักศึกษาเห็นคะแนน</label>
        <input id="score_visible_to_student" name="score_visible_to_student" type="checkbox" <?= $settings['score_visible_to_student'] ? 'checked' : '' ?> class="w-6 h-6 accent-primary"/>
      </div>
    </section>

    <section class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-6 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex flex-col gap-3">
      <h2 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode">สิทธิ์การเข้าถึง (Permissions)</h2>
      <p class="font-body-md text-body-md text-on-surface-variant">แก้ไข RBAC matrix แบบละเอียด (ทำได้เฉพาะ Super Admin)</p>
      <a href="/super-admin/permissions" class="text-primary font-label-md text-label-md w-fit flex items-center gap-1">ไปที่หน้าจัดการสิทธิ์ <span class="material-symbols-outlined text-[18px]">arrow_forward</span></a>
    </section>

    <button type="submit" class="w-full h-touch-target bg-primary text-on-primary rounded-lg font-label-md text-label-md flex items-center justify-center gap-2 hover:bg-primary-container active:scale-[0.97] transition-all">
      <span class="material-symbols-outlined">save</span> บันทึกการตั้งค่า
    </button>
  </form>
</div>
