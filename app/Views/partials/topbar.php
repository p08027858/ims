<?php
/**
 * Fixed top app bar: mobile brand mark, theme toggle, notification bell + dropdown, avatar menu.
 * $unreadNotifications / $notifications should be set by the controller; sane defaults below
 * so every view still renders standalone during Phase 0.
 */
$unreadNotifications = $unreadNotifications ?? 0;
$userInitial = $user['avatar_initial'] ?? mb_substr($user['first_name'] ?? 'U', 0, 1);
?>
<header class="fixed top-0 w-full lg:w-[calc(100%-280px)] z-30 bg-surface-bright/80 dark:bg-bg-dark/80 backdrop-blur-md shadow-sm">
  <div class="flex justify-between items-center px-margin-mobile md:px-margin-desktop h-16 w-full">
    <div class="font-display-metrics text-display-metrics font-bold text-primary dark:text-primary-fixed-dim lg:hidden text-2xl">
      IMS THAI
    </div>
    <div class="hidden lg:block flex-1"></div>
    <div class="flex items-center gap-4 relative">
      <button type="button" id="theme-toggle-btn" aria-label="สลับโหมดสว่าง/มืด"
              class="w-touch-target h-touch-target flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high transition-colors rounded-full active:scale-95 duration-100">
        <span class="material-symbols-outlined">dark_mode</span>
      </button>

      <div class="relative">
        <button type="button" id="notification-bell-btn" aria-label="การแจ้งเตือน" aria-haspopup="true" aria-expanded="false"
                class="w-touch-target h-touch-target flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high transition-colors rounded-full active:scale-95 duration-100 relative">
          <span class="material-symbols-outlined">notifications</span>
          <?php if ($unreadNotifications > 0): ?>
            <span class="absolute top-2 right-2 w-2.5 h-2.5 bg-status-error rounded-full ring-2 ring-surface dark:ring-bg-dark animate-pulse"></span>
          <?php endif; ?>
        </button>
        <?php include __DIR__ . '/notification_dropdown.php'; ?>
      </div>

      <button type="button" aria-label="เมนูผู้ใช้งาน"
              class="w-9 h-9 rounded-full overflow-hidden hover:opacity-90 active:scale-95 duration-100 border-2 border-primary-container bg-primary text-on-primary flex items-center justify-center font-label-md text-label-md">
        <?= htmlspecialchars($userInitial) ?>
      </button>
    </div>
  </div>
</header>
<script>
  // TODO Phase 2: persist theme preference (localStorage) + respect prefers-color-scheme.
  document.getElementById('theme-toggle-btn')?.addEventListener('click', () => {
    document.documentElement.classList.toggle('dark');
  });
  const bellBtn = document.getElementById('notification-bell-btn');
  const notifPanel = document.getElementById('notification-panel');
  bellBtn?.addEventListener('click', () => {
    const isOpen = notifPanel.classList.toggle('hidden');
    bellBtn.setAttribute('aria-expanded', String(!isOpen));
  });
  document.addEventListener('click', (e) => {
    if (notifPanel && !notifPanel.contains(e.target) && !bellBtn.contains(e.target)) {
      notifPanel.classList.add('hidden');
      bellBtn?.setAttribute('aria-expanded', 'false');
    }
  });
</script>
