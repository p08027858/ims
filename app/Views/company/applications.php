<?php
/**
 * Incoming internship applications review. Adapted from design-reference/_3.
 * Wired to App\Controllers\ApplicationController (Phase 4) — GET /company/applications loader +
 * POST /company/applications/{id}/decision.
 */
$applications = $applications ?? [
    ['id' => 1, 'name' => 'พงศกร วิเศษกุล', 'position' => 'UI/UX Designer Intern'],
    ['id' => 2, 'name' => 'ณัฐริกา แจ่มใส', 'position' => 'Frontend Dev Intern'],
    ['id' => 3, 'name' => 'ธนภัทร แสงอรุณ', 'position' => 'Data Analyst Intern'],
];
$flashError = $flashError ?? null;
?>
<div class="flex flex-col gap-6">
  <div>
    <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode mb-2">ใบสมัครใหม่</h1>
    <p class="font-body-md text-body-md text-on-surface-variant">คัดเลือกนักศึกษาฝึกงานสำหรับรอบปัจจุบัน</p>
  </div>

  <?php if ($flashError): ?>
    <div class="bg-error-container text-on-error-container rounded-lg p-4 font-body-md text-body-md" role="alert"><?= htmlspecialchars($flashError) ?></div>
  <?php endif; ?>

  <div class="flex flex-col gap-4">
    <?php if (empty($applications)): ?>
      <div class="flex flex-col items-center justify-center py-12 text-center">
        <span class="material-symbols-outlined text-5xl text-outline-variant mb-3">inbox</span>
        <p class="font-body-md text-body-md text-on-surface-variant">ยังไม่มีใบสมัครใหม่ในตอนนี้</p>
      </div>
    <?php endif; ?>
    <?php foreach ($applications as $a): ?>
      <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-5 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-full bg-surface-variant flex items-center justify-center text-on-surface-variant font-headline-md shrink-0"><?= htmlspecialchars(mb_substr($a['name'], 0, 1)) ?></div>
          <div>
            <h3 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode truncate"><?= htmlspecialchars($a['name']) ?></h3>
            <p class="font-label-md text-label-md text-secondary mt-0.5"><?= htmlspecialchars($a['position']) ?></p>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <button type="button" class="px-4 py-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high font-label-md text-label-md transition-colors">ดูประวัติ</button>
          <form method="post" action="/company/applications/<?= $a['id'] ?>/decision" class="flex gap-2">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
            <button type="submit" name="status" value="rejected" class="px-4 py-2 rounded-lg border border-error text-error hover:bg-error-container/20 font-label-md text-label-md transition-colors active:scale-95">ปฏิเสธ</button>
            <button type="submit" name="status" value="accepted" class="px-4 py-2 rounded-lg bg-status-success text-on-error font-label-md text-label-md hover:opacity-90 transition-colors active:scale-95">ตอบรับ</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
