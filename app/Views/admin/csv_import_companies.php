<?php
/**
 * CSV bulk import for companies (SITEMAP.md §5 `/admin/import/companies`, Phase 11) — closes the
 * follow-up noted in ISSUES.md since Phase 8 (companies only ever had the single-entry form).
 * Same shape as admin/csv_import.php (students). Wired to
 * App\Controllers\ImportController::companiesPageData()/importCompanies().
 */
$importResult = $importResult ?? null;
$formError = $formError ?? null;
?>
<div class="max-w-2xl mx-auto flex flex-col gap-6">
  <div>
    <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode">นำเข้าข้อมูลสถานประกอบการ (CSV)</h1>
    <p class="font-body-md text-body-md text-on-surface-variant">นำเข้ารายชื่อสถานประกอบการเป็นชุด พร้อมสร้างบัญชีผู้ติดต่อหลักให้อัตโนมัติ — คอลัมน์ที่ต้องมี: name, tax_id, address, province, latitude, longitude, supervisor_email, supervisor_first_name, supervisor_last_name (คอลัมน์เสริม: subdistrict, district, postcode, gps_radius_m, phone, email, website, industry_type, supervisor_position, supervisor_phone)</p>
  </div>

  <?php if ($formError): ?>
    <div class="bg-error-container text-on-error-container rounded-lg p-4 font-body-md text-body-md" role="alert"><?= htmlspecialchars($formError) ?></div>
  <?php endif; ?>

  <form method="post" action="/admin/import/companies" enctype="multipart/form-data" class="flex flex-col gap-4">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
    <label for="csv-file" id="csv-dropzone" class="flex flex-col items-center justify-center p-10 border-2 border-dashed border-outline-variant rounded-xl bg-surface-container dark:bg-surface-container-high/10 hover:bg-surface-container-low transition-colors cursor-pointer">
      <span class="material-symbols-outlined text-primary text-4xl mb-2">upload_file</span>
      <span class="font-label-md text-label-md text-primary" id="csv-file-label">แตะหรือลากไฟล์ CSV มาวางที่นี่</span>
      <span class="font-metadata text-metadata text-on-surface-variant mt-1">รองรับไฟล์ .csv เท่านั้น</span>
      <input id="csv-file" name="csv_file" type="file" accept=".csv" class="hidden" required/>
    </label>
    <button type="submit" class="w-full h-touch-target bg-primary text-on-primary rounded-lg font-label-md text-label-md flex items-center justify-center gap-2 hover:bg-primary-container active:scale-[0.97] transition-all">
      <span class="material-symbols-outlined">publish</span> นำเข้าข้อมูล
    </button>
  </form>

  <?php if ($importResult): ?>
    <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-5 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex flex-col gap-3">
      <p class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode">
        <span class="text-status-success">นำเข้าสำเร็จ <?= $importResult['imported'] ?> รายการ</span> /
        <span class="text-error">ผิดพลาด <?= count($importResult['errors']) ?> รายการ</span>
      </p>
      <?php if ($importResult['errors']): ?>
        <ul class="flex flex-col gap-1">
          <?php foreach ($importResult['errors'] as $err): ?>
            <li class="font-metadata text-metadata text-error">แถวที่ <?= $err['row'] ?>: <?= htmlspecialchars($err['message']) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
<script>
  document.getElementById('csv-file')?.addEventListener('change', function () {
    if (this.files[0]) document.getElementById('csv-file-label').textContent = this.files[0].name;
  });
</script>
