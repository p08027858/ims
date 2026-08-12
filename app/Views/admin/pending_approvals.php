<?php
/**
 * Pending user/company approval queue. Adapted from design-reference/_13
 * ("รอการอนุมัติ" / empty-state "เย้! คุณจัดการทุกคำขอเสร็จสิ้นแล้ว").
 * Wired to App\Controllers\CompanyController (Phase 3).
 *
 * IMPORTANT: this view's list variable is named `$approvalItems`, NOT `$items` — layouts/app.php
 * includes partials/sidebar.php and partials/notification_dropdown.php (via topbar.php) BEFORE
 * this content view, in the SAME php scope (include doesn't isolate variables). Both of those
 * partials also assign a variable literally named `$items` for their own nav/notification loops,
 * which silently clobbers this view's data before it ever renders if it uses the same name —
 * discovered live 2026-07-30 when the real pending-approval rows were replaced by leftover
 * sidebar/notification data with no error (`$items ?? [...]` never falls back because $items
 * is already non-null, just the wrong shape). See ISSUES.md for the broader landmine this
 * exposes for future view work.
 */
$approvalItems = $approvalItems ?? [
    ['id' => 1, 'type' => 'student', 'name' => 'สมชาย ใจดี', 'meta' => 'นักศึกษา · วิศวกรรมคอมพิวเตอร์'],
    ['id' => 2, 'type' => 'company', 'name' => 'บริษัท เอบีซี จำกัด', 'meta' => 'สถานประกอบการ · เทคโนโลยี'],
];
$flashError = $flashError ?? null;
?>
<div class="flex flex-col gap-6">
  <div class="flex items-start justify-between flex-wrap gap-4">
    <div>
      <h1 class="font-display-metrics text-[32px] text-on-surface dark:text-text-dark-mode mb-2">รอการอนุมัติ</h1>
      <p class="font-body-md text-body-md text-on-surface-variant">จัดการคำขอลงทะเบียนผู้ใช้งานและบริษัทที่รอการตรวจสอบ</p>
    </div>
    <div class="flex gap-2 h-fit">
      <a href="/admin/teachers/new" class="px-4 py-2 rounded-lg border border-primary text-primary font-label-md text-label-md flex items-center gap-1"><span class="material-symbols-outlined text-[18px]">add</span> เพิ่มครูนิเทศ</a>
      <a href="/admin/companies/new" class="px-4 py-2 rounded-lg bg-primary text-on-primary font-label-md text-label-md flex items-center gap-1"><span class="material-symbols-outlined text-[18px]">add</span> เพิ่มสถานประกอบการ</a>
    </div>
  </div>

  <?php if ($flashError): ?>
    <div class="bg-error-container text-on-error-container rounded-lg p-4 font-body-md text-body-md" role="alert"><?= htmlspecialchars($flashError) ?></div>
  <?php endif; ?>

  <?php if (empty($approvalItems)): ?>
    <div class="flex flex-col items-center justify-center py-16 text-center">
      <span class="material-symbols-outlined text-6xl text-status-success mb-4">task_alt</span>
      <h3 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode mb-2">ไม่มีรายการรอดำเนินการ</h3>
      <p class="font-body-md text-body-md text-on-surface-variant max-w-sm">เย้! คุณจัดการทุกคำขอเสร็จสิ้นแล้ว หรือลองเปลี่ยนตัวกรองการค้นหาดู</p>
    </div>
  <?php else: ?>
    <div class="flex flex-col gap-3">
      <?php foreach ($approvalItems as $it): ?>
        <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-5 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex items-center justify-between gap-4 flex-wrap">
          <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-primary text-3xl"><?= $it['type'] === 'company' ? 'apartment' : 'person' ?></span>
            <div><p class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($it['name']) ?></p><p class="font-metadata text-metadata text-on-surface-variant"><?= htmlspecialchars($it['meta']) ?></p></div>
          </div>
          <form method="post" action="/admin/<?= $it['type'] === 'company' ? 'companies' : 'users' ?>/<?= $it['id'] ?>/approve" class="flex gap-2">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
            <button type="submit" name="status" value="rejected" class="px-4 py-2 rounded-lg border border-error text-error hover:bg-error-container/20 font-label-md text-label-md transition-colors active:scale-95">ปฏิเสธ</button>
            <button type="submit" name="status" value="approved" class="px-4 py-2 rounded-lg bg-status-success text-on-error font-label-md text-label-md hover:opacity-90 transition-colors active:scale-95">อนุมัติ</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
