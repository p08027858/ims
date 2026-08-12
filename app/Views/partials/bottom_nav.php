<?php
/**
 * Native-app-style bottom navigation bar (mobile only, hidden on lg+).
 * Reuses $items/$activeNav already computed by sidebar.php (both partials are included
 * from the same scope in layouts/app.php, so no need to recompute the nav config here).
 * Takes at most the first 5 items — bottom bars stay cramped beyond that.
 */
$bottomItems = array_slice($items ?? [], 0, 5);
?>
<nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-surface-bright/95 dark:bg-bg-dark/95 backdrop-blur-md border-t border-outline-variant dark:border-surface-variant h-bottom-nav-height pb-[env(safe-area-inset-bottom)]">
  <div class="flex items-stretch justify-around h-bottom-nav-height">
    <?php foreach ($bottomItems as $item): $isActive = $activeNav === $item['key']; ?>
      <a href="<?= htmlspecialchars($item['href']) ?>"
         class="flex-1 flex flex-col items-center justify-center gap-0.5 relative <?= $isActive ? 'text-primary' : 'text-on-surface-variant' ?>">
        <?php if ($isActive): ?>
          <span class="absolute top-1 w-1.5 h-1.5 rounded-full bg-primary"></span>
        <?php endif; ?>
        <span class="material-symbols-outlined<?= $isActive ? ' fill' : '' ?> text-[22px]"><?= $item['icon'] ?></span>
        <span class="font-metadata text-[10px] leading-none"><?= htmlspecialchars($item['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</nav>
