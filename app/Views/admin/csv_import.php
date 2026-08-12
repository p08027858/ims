<?php
/**
 * CSV bulk import. Adapted from design-reference/csv. Wired to
 * App\Controllers\ImportController (Phase 8) — students only for now (RULE-IMPORT-01/02);
 * bulk company import stays a follow-up (companies already have a working single-entry form).
 */
$importResult = $importResult ?? null; // e.g. ['imported' => 118, 'skipped' => 2, 'errors' => [['row' => 45, 'message' => 'student_code ซ้ำในระบบ']]]
$formError = $formError ?? null;
?>
<div class="max-w-2xl mx-auto flex flex-col gap-6">
  <div>
    <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode">นำเข้าข้อมูลนักศึกษา (CSV)</h1>
    <p class="font-body-md text-body-md text-on-surface-variant">นำเข้ารายชื่อนักศึกษาเป็นชุดจากไฟล์ CSV — คอลัมน์ที่ต้องมี: student_code, prefix, first_name, last_name, email, faculty_code, department_code, year_level</p>
  </div>

  <?php if ($formError): ?>
    <div class="bg-error-container text-on-error-container rounded-lg p-4 font-body-md text-body-md" role="alert"><?= htmlspecialchars($formError) ?></div>
  <?php endif; ?>

  <form method="post" action="/admin/import/students" enctype="multipart/form-data" class="flex flex-col gap-4">
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
