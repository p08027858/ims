<?php
/**
 * Confirmed internships awaiting Admin approval, plus already-approved/active ones that can be
 * terminated. New page (not part of the 27 Stitch exports). Wired to
 * App\Controllers\InternshipController (Phase 4 items 3-4).
 *
 * Phase 10: a real permanent-delete option is added, visible only to role=super_admin — RULE-SEC-01
 * requires Super Admin + PIN for any hard DELETE, and an ordinary admin viewing this same page
 * (role gate is now 'admin|super_admin') has no path to it at all, matching ROLES.md §4 ("Admin...
 * ทำเองไม่ได้ ต้องส่งคำขอให้ Super Admin").
 */
$internships = $internships ?? [
    ['id' => 1, 'student_name' => 'สมชาย ใจดี', 'company_name' => 'บริษัท เทคโนวา จำกัด', 'start_date' => '2026-09-01', 'end_date' => '2026-12-31', 'status' => 'pending_approval'],
];
$flashError = $flashError ?? null;
$statusLabel = ['pending_approval' => 'รออนุมัติ', 'approved' => 'อนุมัติแล้ว', 'active' => 'กำลังฝึกงาน'];
$statusClass = ['pending_approval' => 'bg-status-warning/10 text-status-warning', 'approved' => 'bg-status-success/10 text-status-success', 'active' => 'bg-primary/10 text-primary'];
?>
<div class="flex flex-col gap-6">
  <div>
    <h1 class="font-display-metrics text-[32px] text-on-surface dark:text-text-dark-mode mb-2">การฝึกงานที่ยืนยันแล้ว</h1>
    <p class="font-body-md text-body-md text-on-surface-variant">อนุมัติการฝึกงานที่จับคู่ครูนิเทศแล้ว หรือยุติการฝึกงานที่กำลังดำเนินอยู่</p>
  </div>

  <?php if ($flashError): ?>
    <div class="bg-error-container text-on-error-container rounded-lg p-4 font-body-md text-body-md" role="alert"><?= htmlspecialchars($flashError) ?></div>
  <?php endif; ?>

  <?php if (empty($internships)): ?>
    <div class="flex flex-col items-center justify-center py-16 text-center">
      <span class="material-symbols-outlined text-6xl text-status-success mb-4">task_alt</span>
      <h3 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode mb-2">ไม่มีรายการ</h3>
    </div>
  <?php else: ?>
    <div class="flex flex-col gap-3">
      <?php foreach ($internships as $i): ?>
        <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-5 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex items-center justify-between gap-4 flex-wrap">
          <div>
            <div class="flex items-center gap-2 mb-1">
              <p class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars((string) ($i['student_name'] ?? '')) ?></p>
              <span class="px-2 py-0.5 rounded-full font-metadata text-metadata <?= $statusClass[$i['status']] ?? 'bg-surface-variant text-on-surface-variant' ?>"><?= $statusLabel[$i['status']] ?? $i['status'] ?></span>
            </div>
            <p class="font-metadata text-metadata text-on-surface-variant"><?= htmlspecialchars((string) ($i['company_name'] ?? '')) ?> · <?= htmlspecialchars((string) ($i['start_date'] ?? '')) ?> - <?= htmlspecialchars((string) ($i['end_date'] ?? '')) ?></p>
          </div>
          <div class="flex items-center gap-2">
            <?php if ($i['status'] === 'pending_approval'): ?>
              <form method="post" action="/admin/internships/<?= $i['id'] ?>/approve">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
                <button type="submit" class="px-4 py-2 rounded-lg bg-status-success text-on-error font-label-md text-label-md hover:opacity-90 transition-colors active:scale-95">อนุมัติ</button>
              </form>
            <?php else: ?>
              <button type="button" onclick="document.getElementById('terminate-modal-<?= $i['id'] ?>').classList.remove('hidden')" class="px-4 py-2 rounded-lg border border-error text-error hover:bg-error-container/20 font-label-md text-label-md transition-colors">ยุติการฝึกงาน</button>
              <div id="terminate-modal-<?= $i['id'] ?>" class="hidden fixed inset-0 z-50 bg-surface-dark/40 backdrop-blur-sm items-center justify-center p-4">
                <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-2xl p-6 max-w-sm w-full">
                  <h3 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode mb-3">ยุติการฝึกงานของ <?= htmlspecialchars((string) ($i['student_name'] ?? '')) ?>?</h3>
                  <form method="post" action="/admin/internships/<?= $i['id'] ?>/terminate" class="flex flex-col gap-3">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
                    <textarea name="termination_reason" rows="3" required placeholder="ระบุเหตุผล..." class="w-full px-4 py-3 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface dark:text-text-dark-mode resize-none focus:outline-none focus:border-primary"></textarea>
                    <div class="flex gap-3">
                      <button type="button" onclick="document.getElementById('terminate-modal-<?= $i['id'] ?>').classList.add('hidden')" class="flex-1 py-2.5 rounded-xl bg-surface-container text-on-surface-variant font-label-md text-label-md">ยกเลิก</button>
                      <button type="submit" class="flex-1 py-2.5 rounded-xl bg-error text-on-error font-label-md text-label-md">ยืนยันยุติ</button>
                    </div>
                  </form>
                </div>
              </div>
            <?php endif; ?>
            <?php if (($user['role'] ?? null) === 'super_admin'): ?>
              <a href="/super-admin/critical-actions?actionUrl=<?= urlencode('/admin/internships/' . $i['id']) ?>&actionMethod=DELETE&redirect=<?= urlencode('/admin/internships') ?>&cancel=<?= urlencode('/admin/internships') ?>&desc=<?= urlencode('ลบข้อมูลการฝึกงานของ ' . $i['student_name'] . ' (#' . $i['id'] . ') ถาวร ข้อมูลการเข้างาน/บันทึกงาน/คำขอลา/ผลประเมินที่เกี่ยวข้องทั้งหมดจะถูกลบไปด้วย และไม่สามารถย้อนกลับได้') ?>"
                 class="px-3 py-2 rounded-lg border border-error text-error hover:bg-error-container/20 font-label-md text-label-md transition-colors flex items-center gap-1">
                <span class="material-symbols-outlined text-[18px]">delete_forever</span> ลบถาวร
              </a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
