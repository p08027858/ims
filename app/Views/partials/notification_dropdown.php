<?php
/**
 * Notification center panel, toggled by the bell button in topbar.php.
 * Based on design-reference/_2 (student dashboard notification-open state).
 * $notifications should be provided by the controller — falls back to sample data
 * so the panel still looks right during Phase 0 (no backend yet).
 */
$notifications = $notifications ?? [
    ['group' => 'วันนี้', 'icon' => 'location_on', 'color' => 'primary', 'title' => 'ลงเวลาเข้างานสำเร็จแล้ว! 📍', 'desc' => 'ระบบบันทึกเวลาเข้างานของคุณเวลา 08:45 น.', 'time' => '5 นาทีที่แล้ว', 'unread' => true],
    ['group' => 'วันนี้', 'icon' => 'star', 'color' => 'status-success', 'title' => 'คุณมีผลประเมินใหม่จากบริษัท 🌟', 'desc' => 'พี่เลี้ยงได้ประเมินผลการทำงานสัปดาห์ที่ 3', 'time' => '2 ชั่วโมงที่แล้ว', 'unread' => true],
    ['group' => 'ก่อนหน้านี้', 'icon' => 'event_available', 'color' => 'status-warning', 'title' => 'คำขอลาป่วยได้รับการอนุมัติ', 'desc' => 'วันที่ 15 ต.ค. 2566', 'time' => 'เมื่อวานนี้', 'unread' => false],
];
$grouped = [];
foreach ($notifications as $n) { $grouped[$n['group']][] = $n; }
?>
<div id="notification-panel" class="hidden absolute right-0 top-full mt-2 w-[360px] max-w-[90vw] bg-surface-container-lowest dark:bg-surface-dark rounded-xl shadow-2xl border border-surface-variant dark:border-outline-variant/30 overflow-hidden z-50">
  <div class="flex items-center justify-between px-5 py-4 border-b border-surface-variant dark:border-outline-variant/20">
    <h2 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode">การแจ้งเตือน</h2>
    <?php $unread = count(array_filter($notifications, fn($n) => $n['unread'])); ?>
    <?php if ($unread > 0): ?>
      <span class="bg-error text-on-error text-metadata font-metadata px-2 py-0.5 rounded-full"><?= $unread ?> ใหม่</span>
    <?php endif; ?>
  </div>
  <div class="max-h-[420px] overflow-y-auto">
    <?php foreach ($grouped as $groupName => $groupItems): ?>
      <p class="px-5 pt-3 pb-1 font-metadata text-metadata text-on-surface-variant"><?= htmlspecialchars($groupName) ?></p>
      <?php foreach ($groupItems as $n): ?>
        <a href="#" class="flex items-start gap-3 px-5 py-3 hover:bg-surface-container-low dark:hover:bg-surface-container/10 transition-colors <?= $n['unread'] ? 'border-l-2 border-primary' : 'border-l-2 border-transparent' ?>">
          <div class="w-9 h-9 rounded-full bg-<?= $n['color'] ?>/10 flex items-center justify-center text-<?= $n['color'] ?> shrink-0">
            <span class="material-symbols-outlined text-[20px]"><?= $n['icon'] ?></span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($n['title']) ?></p>
            <p class="font-metadata text-metadata text-on-surface-variant line-clamp-2"><?= htmlspecialchars($n['desc']) ?></p>
            <p class="font-metadata text-metadata text-primary mt-0.5"><?= htmlspecialchars($n['time']) ?></p>
          </div>
        </a>
      <?php endforeach; ?>
    <?php endforeach; ?>
    <?php if (empty($notifications)): ?>
      <div class="flex flex-col items-center gap-2 py-10 px-5 text-center">
        <span class="material-symbols-outlined text-4xl text-outline-variant">notifications_off</span>
        <p class="font-body-md text-body-md text-on-surface-variant">ยังไม่มีการแจ้งเตือนตอนนี้</p>
      </div>
    <?php endif; ?>
  </div>
  <a href="/<?= htmlspecialchars($role) ?>/notifications" class="block text-center py-3 font-label-md text-label-md text-primary hover:bg-surface-container-low dark:hover:bg-surface-container/10 border-t border-surface-variant dark:border-outline-variant/20">
    ดูทั้งหมด →
  </a>
</div>
