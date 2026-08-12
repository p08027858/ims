<?php
/**
 * "View all" notifications page — new page (not part of the 27 Stitch exports, which only had
 * the topbar dropdown, partials/notification_dropdown.php). Shared across every role the same
 * way company/daily_log_review.php is shared between company/teacher (Phase 6 precedent) —
 * physically lives under student/ but routes.php points every role's /{role}/notifications at
 * this same file. Wired to App\Controllers\NotificationController (Phase 9).
 */
$allNotifications = $allNotifications ?? [
    ['id' => 1, 'group' => 'วันนี้', 'icon' => 'location_on', 'color' => 'primary', 'title' => 'ลงเวลาเข้างานสำเร็จแล้ว!', 'desc' => 'ระบบบันทึกเวลาเข้างานของคุณเวลา 08:45 น.', 'time' => '5 นาทีที่แล้ว', 'unread' => true, 'link_url' => null],
];
$grouped = [];
foreach ($allNotifications as $n) {
    $grouped[$n['group']][] = $n;
}
$hasUnread = !empty(array_filter($allNotifications, static fn (array $n) => $n['unread']));
?>
<div class="flex flex-col gap-6 max-w-2xl mx-auto">
  <div class="flex items-center justify-between flex-wrap gap-3">
    <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode">การแจ้งเตือน</h1>
    <?php if ($hasUnread): ?>
      <form method="post" action="/notifications/read-all">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
        <button type="submit" class="px-4 py-2 rounded-lg text-primary hover:bg-primary/10 font-label-md text-label-md transition-colors">ทำเครื่องหมายว่าอ่านแล้วทั้งหมด</button>
      </form>
    <?php endif; ?>
  </div>

  <?php if (empty($allNotifications)): ?>
    <div class="flex flex-col items-center justify-center py-16 text-center">
      <span class="material-symbols-outlined text-6xl text-outline-variant mb-3">notifications_off</span>
      <p class="font-body-md text-body-md text-on-surface-variant">ยังไม่มีการแจ้งเตือน</p>
    </div>
  <?php else: ?>
    <?php foreach ($grouped as $groupName => $groupItems): ?>
      <div class="flex flex-col gap-2">
        <p class="font-metadata text-metadata text-on-surface-variant px-1"><?= htmlspecialchars($groupName) ?></p>
        <?php foreach ($groupItems as $n): ?>
          <a href="/notifications/<?= $n['id'] ?>/read" class="flex items-start gap-3 p-4 rounded-xl bg-surface-container-lowest dark:bg-surface-dark shadow-soft border <?= $n['unread'] ? 'border-primary/30' : 'border-surface-variant dark:border-outline-variant/20' ?> hover:bg-surface-container-low dark:hover:bg-surface-container/10 transition-colors">
            <div class="w-10 h-10 rounded-full bg-<?= $n['color'] ?>/10 flex items-center justify-center text-<?= $n['color'] ?> shrink-0">
              <span class="material-symbols-outlined text-[22px]"><?= htmlspecialchars($n['icon']) ?></span>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <p class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($n['title']) ?></p>
                <?php if ($n['unread']): ?><span class="w-2 h-2 rounded-full bg-primary shrink-0"></span><?php endif; ?>
              </div>
              <p class="font-body-md text-metadata text-on-surface-variant"><?= htmlspecialchars($n['desc']) ?></p>
              <p class="font-metadata text-metadata text-primary mt-0.5"><?= htmlspecialchars($n['time']) ?></p>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
