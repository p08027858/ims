<?php
/**
 * Leave request approval queue. Manually composed to match UI_UX.md §5.6 — not present as a
 * distinct Stitch export, so it reuses the card/button language established in
 * company/applications.php and company/daily_log_review.php for visual consistency. Wired to
 * App\Controllers\LeaveController (Phase 6) — shared by both /company/leave-requests and
 * /teacher/leave-requests (PERMISSIONS.md §2.7 grants both roles decide rights).
 */
$leaveRequests = $leaveRequests ?? [
    ['id' => 1, 'student' => 'สมหญิง ดีใจ', 'type' => 'ลาป่วย', 'range' => '25-27 ก.ค. (3 วัน)', 'reason' => 'ป่วยไข้หวัดใหญ่', 'attachment' => 'ใบรับรองแพทย์.pdf'],
];
$flashError = $flashError ?? null;
?>
<div class="flex flex-col gap-6">
  <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode">คำขอลาที่รออนุมัติ</h1>

  <?php if ($flashError): ?>
    <div class="bg-error-container text-on-error-container rounded-lg p-4 font-body-md text-body-md" role="alert"><?= htmlspecialchars($flashError) ?></div>
  <?php endif; ?>

  <?php if (empty($leaveRequests)): ?>
    <div class="flex flex-col items-center justify-center py-12 text-center">
      <span class="material-symbols-outlined text-5xl text-status-success mb-3">task_alt</span>
      <p class="font-body-md text-body-md text-on-surface-variant">เคลียร์หมดแล้ว ทำได้ดีมาก! ✅</p>
    </div>
  <?php endif; ?>

  <?php foreach ($leaveRequests as $lr): ?>
    <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-6 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex flex-col gap-4">
      <div class="flex items-center justify-between flex-wrap gap-2">
        <h3 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($lr['student']) ?></h3>
        <span class="px-3 py-1 rounded-full bg-status-warning/10 text-status-warning font-label-md text-label-md"><?= htmlspecialchars($lr['type']) ?></span>
      </div>
      <p class="font-body-md text-body-md text-on-surface-variant">วันที่: <?= htmlspecialchars($lr['range']) ?></p>
      <p class="font-body-md text-body-md text-on-surface"><?= htmlspecialchars($lr['reason']) ?></p>
      <?php if ($lr['attachment']): ?>
        <div class="flex items-center gap-2 bg-surface-container dark:bg-surface-container-high/10 rounded-lg px-4 py-2 w-fit">
          <span class="material-symbols-outlined text-primary text-[20px]">description</span>
          <span class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($lr['attachment']) ?></span>
        </div>
      <?php endif; ?>
      <form method="post" action="/company/leave-requests/<?= $lr['id'] ?>/decision" class="flex items-center justify-end gap-3 pt-2 border-t border-outline-variant/20">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
        <button type="submit" name="status" value="rejected" class="px-6 py-3 rounded-xl border border-error text-error hover:bg-error-container/20 font-label-md text-label-md transition-colors active:scale-95">ปฏิเสธ</button>
        <button type="submit" name="status" value="approved" class="px-6 py-3 rounded-xl bg-status-success text-on-error hover:opacity-90 font-label-md text-label-md transition-colors active:scale-95">อนุมัติ</button>
      </form>
    </div>
  <?php endforeach; ?>
</div>
