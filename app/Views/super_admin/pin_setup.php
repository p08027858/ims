<?php
/**
 * First-time PIN setup (SECURITY.md §6 — "ตั้งครั้งแรกตอนสร้างบัญชี super_admin"). New page, not
 * part of the 27 Stitch exports. Wired to App\Controllers\SuperAdminPinController (Phase 10).
 * No reset path yet if a PIN already exists — see ISSUES.md.
 */
$alreadySet = $alreadySet ?? false;
$formError = $formError ?? null;
?>
<div class="max-w-md mx-auto flex flex-col gap-6">
  <div>
    <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode">ตั้งค่า PIN ยืนยันตัวตน</h1>
    <p class="font-body-md text-body-md text-on-surface-variant mt-1">PIN 6 หลักนี้แยกจากรหัสผ่านล็อกอินโดยสิ้นเชิง ใช้ยืนยันก่อนทำรายการที่ทำลาย/แก้ไขข้อมูลสำคัญเท่านั้น (SECURITY.md §6)</p>
  </div>

  <?php if ($alreadySet): ?>
    <div class="bg-status-success/10 text-status-success rounded-lg p-4 font-body-md text-body-md flex items-center gap-2" role="status">
      <span class="material-symbols-outlined">check_circle</span> บัญชีนี้ตั้งค่า PIN ไว้แล้ว — ยังไม่มีหน้ารีเซ็ต PIN ในเวอร์ชันนี้
    </div>
  <?php else: ?>
    <?php if ($formError): ?>
      <div class="bg-error-container text-on-error-container rounded-lg p-4 font-body-md text-body-md" role="alert"><?= htmlspecialchars($formError) ?></div>
    <?php endif; ?>
    <form method="post" action="/super-admin/pin/setup" class="bg-surface-container-lowest dark:bg-surface-dark rounded-[24px] shadow-soft p-8 flex flex-col gap-5 border border-surface-variant dark:border-outline-variant/20">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
      <div class="flex flex-col gap-1.5">
        <label class="font-label-md text-label-md text-on-surface-variant" for="pin">PIN 6 หลัก</label>
        <input id="pin" name="pin" type="password" inputmode="numeric" pattern="\d{6}" maxlength="6" required class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary tracking-[0.5em]"/>
      </div>
      <div class="flex flex-col gap-1.5">
        <label class="font-label-md text-label-md text-on-surface-variant" for="pin_confirm">ยืนยัน PIN อีกครั้ง</label>
        <input id="pin_confirm" name="pin_confirm" type="password" inputmode="numeric" pattern="\d{6}" maxlength="6" required class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary tracking-[0.5em]"/>
      </div>
      <button type="submit" class="mt-2 w-full h-touch-target bg-primary text-on-primary rounded-lg font-label-md text-label-md">บันทึก PIN</button>
    </form>
  <?php endif; ?>
</div>
