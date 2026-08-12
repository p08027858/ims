<?php
/**
 * Admin creates a company + its primary contact (supervisor) account together
 * (WORKFLOW.md §3: "Admin สร้างบัญชีบริษัท + ผู้ติดต่อหลัก", RULE-AUTH-02). New page, not part
 * of the original 27 Stitch exports. Wired to App\Controllers\CompanyController::store().
 */
$maxRadiusM = $maxRadiusM ?? 500;
$formError = $formError ?? null;
$old = $old ?? [];
$v = static fn (string $key, string $default = '') => htmlspecialchars((string) ($old[$key] ?? $default));
?>
<div class="max-w-3xl mx-auto flex flex-col gap-6">
  <div>
    <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode mb-2">เพิ่มสถานประกอบการ</h1>
    <p class="font-body-md text-body-md text-on-surface-variant">สร้างข้อมูลบริษัทพร้อมบัญชีผู้ติดต่อหลัก (ผู้ประกอบการ) — บัญชีนี้ต้องผ่านการอนุมัติในหน้า "รอการอนุมัติ" อีกครั้งก่อนเข้าใช้งานได้จริง</p>
  </div>

  <?php if ($formError): ?>
    <div class="bg-error-container text-on-error-container rounded-lg p-4 font-body-md text-body-md" role="alert"><?= htmlspecialchars($formError) ?></div>
  <?php endif; ?>

  <form method="post" action="/admin/companies" class="flex flex-col gap-6">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
    <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-2xl p-6 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex flex-col gap-4">
      <h2 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode">ข้อมูลบริษัท</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input name="name" placeholder="ชื่อบริษัท *" required value="<?= $v('name') ?>" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
        <input name="tax_id" placeholder="เลขประจำตัวผู้เสียภาษี" value="<?= $v('tax_id') ?>" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
      </div>
      <input name="address" placeholder="ที่อยู่ *" required value="<?= $v('address') ?>" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <input name="subdistrict" placeholder="ตำบล/แขวง" value="<?= $v('subdistrict') ?>" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
        <input name="district" placeholder="อำเภอ/เขต" value="<?= $v('district') ?>" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
        <input name="province" placeholder="จังหวัด" value="<?= $v('province') ?>" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
        <input name="postcode" placeholder="รหัสไปรษณีย์" value="<?= $v('postcode') ?>" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
      </div>
      <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <input name="latitude" type="number" step="0.0000001" placeholder="Latitude *" required value="<?= $v('latitude') ?>" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
        <input name="longitude" type="number" step="0.0000001" placeholder="Longitude *" required value="<?= $v('longitude') ?>" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
        <input name="gps_radius_m" type="number" min="10" max="<?= $maxRadiusM ?>" placeholder="รัศมี (ม.)" value="<?= $v('gps_radius_m', '100') ?>" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input name="phone" placeholder="เบอร์โทรบริษัท" value="<?= $v('phone') ?>" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
        <input name="company_email" type="email" placeholder="อีเมลบริษัท" value="<?= $v('company_email') ?>" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
        <input name="industry_type" placeholder="ประเภทธุรกิจ" value="<?= $v('industry_type') ?>" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
      </div>
      <input name="website" placeholder="เว็บไซต์" value="<?= $v('website') ?>" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
    </div>

    <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-2xl p-6 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex flex-col gap-4">
      <h2 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode">บัญชีผู้ติดต่อหลัก (ผู้ประกอบการ)</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input name="supervisor_first_name" placeholder="ชื่อ *" required value="<?= $v('supervisor_first_name') ?>" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
        <input name="supervisor_last_name" placeholder="นามสกุล *" required value="<?= $v('supervisor_last_name') ?>" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input name="supervisor_position" placeholder="ตำแหน่ง" value="<?= $v('supervisor_position') ?>" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
        <input name="supervisor_phone" placeholder="เบอร์โทร" value="<?= $v('supervisor_phone') ?>" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input name="supervisor_email" type="email" placeholder="อีเมลเข้าสู่ระบบ *" required value="<?= $v('supervisor_email') ?>" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
        <input name="supervisor_password" type="password" placeholder="รหัสผ่านเริ่มต้น * (อย่างน้อย 8 ตัว มีตัวอักษร+ตัวเลข)" required value="<?= $v('supervisor_password') ?>" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
      </div>
    </div>

    <button type="submit" class="w-full h-touch-target bg-primary text-on-primary rounded-lg font-label-md text-label-md flex items-center justify-center gap-2 hover:bg-primary-container active:scale-[0.97] transition-all">
      <span class="material-symbols-outlined">save</span> บันทึกสถานประกอบการ
    </button>
  </form>
</div>
