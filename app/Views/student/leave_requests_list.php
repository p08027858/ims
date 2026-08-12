<?php
/**
 * Student's own leave request history (SITEMAP.md §2 `/student/leave-requests`, Phase 11). New
 * page — Phase 6 only ever built the "new" form. Wired to LeaveController::myListData().
 */
$noActiveInternship = $noActiveInternship ?? false;
$leaveRequests = $leaveRequests ?? [
    ['id' => 1, 'type' => 'ลาป่วย', 'range' => '2026-07-25 ถึง 2026-07-27 (3 วัน)', 'reason' => 'ป่วยไข้หวัดใหญ่', 'status' => 'approved', 'cancellable' => false],
];
$statusLabel = ['pending' => 'รออนุมัติ', 'approved' => 'อนุมัติแล้ว', 'rejected' => 'ปฏิเสธ', 'cancelled' => 'ยกเลิกแล้ว'];
$statusClass = [
    'pending' => 'bg-status-warning/10 text-status-warning',
    'approved' => 'bg-status-success/10 text-status-success',
    'rejected' => 'bg-status-error/10 text-status-error',
    'cancelled' => 'bg-surface-variant text-on-surface-variant',
];
?>
<div class="flex flex-col gap-6">
  <div class="flex items-center justify-between flex-wrap gap-3">
    <div>
      <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode">คำขอลาของฉัน</h1>
      <p class="font-body-md text-body-md text-on-surface-variant">ประวัติคำขอลาทั้งหมด</p>
    </div>
    <a href="/student/leave-requests/new" class="px-5 py-2.5 rounded-xl bg-primary text-on-primary font-label-md text-label-md flex items-center gap-2">
      <span class="material-symbols-outlined text-[20px]">add</span> ยื่นคำขอลา
    </a>
  </div>

  <?php if ($noActiveInternship): ?>
    <div class="flex flex-col items-center justify-center py-16 text-center bg-surface-container-lowest dark:bg-surface-dark rounded-xl border border-surface-variant dark:border-outline-variant/20">
      <span class="material-symbols-outlined text-5xl text-outline-variant mb-3">work_off</span>
      <p class="font-body-md text-body-md text-on-surface-variant">ไม่พบการฝึกงานที่กำลังดำเนินอยู่ของคุณในขณะนี้</p>
    </div>
  <?php elseif (empty($leaveRequests)): ?>
    <div class="flex flex-col items-center justify-center py-16 text-center">
      <span class="material-symbols-outlined text-5xl text-outline-variant mb-3">event_busy</span>
      <p class="font-body-md text-body-md text-on-surface-variant">ยังไม่มีคำขอลา</p>
    </div>
  <?php else: ?>
    <div class="flex flex-col gap-3">
      <?php foreach ($leaveRequests as $lr): ?>
        <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-4 shadow-sm border border-surface-variant/50 dark:border-outline-variant/10 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 sm:justify-between">
          <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2 mb-1">
              <p class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($lr['type']) ?></p>
              <span class="px-2 py-0.5 rounded-full font-metadata text-metadata <?= $statusClass[$lr['status']] ?? 'bg-surface-variant text-on-surface-variant' ?>"><?= htmlspecialchars($statusLabel[$lr['status']] ?? $lr['status']) ?></span>
            </div>
            <p class="font-metadata text-metadata text-on-surface-variant"><?= htmlspecialchars($lr['range']) ?></p>
            <p class="font-metadata text-metadata text-on-surface-variant truncate"><?= htmlspecialchars($lr['reason']) ?></p>
          </div>
          <?php if ($lr['cancellable']): ?>
            <form method="post" action="/student/leave-requests/<?= $lr['id'] ?>/cancel" class="shrink-0" onsubmit="return confirm('ยกเลิกคำขอลานี้?');">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
              <button type="submit" class="px-4 py-2 rounded-lg border border-error text-error font-label-md text-label-md hover:bg-error-container/20 transition-colors">ยกเลิก</button>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
