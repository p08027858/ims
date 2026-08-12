<?php
/**
 * Audit log table. Adapted from design-reference/audit_log. Wired to
 * App\Controllers\AuditLogController::listData() (Phase 9) — this list stays read-only
 * everywhere (audit_logs is immutable, SECURITY.md §5; no update/delete method exists at all).
 * Filters below (module/user/date) remain decorative — not wired, not part of Phase 9's DoD.
 */
$logs = $logs ?? [
    ['time' => '22/07/26 10:15', 'user' => 'admin01', 'action' => 'update', 'entity' => 'internships#500', 'diff' => 'status: approved → active'],
    ['time' => '22/07/26 09:40', 'user' => 'company03', 'action' => 'create', 'entity' => 'evaluations#88', 'diff' => 'total_score: null → 18.0'],
];
?>
<div class="flex flex-col gap-6">
  <div>
    <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode">Audit Log</h1>
    <p class="font-body-md text-body-md text-on-surface-variant">บันทึกการกระทำทั้งหมดในระบบ (อ่านอย่างเดียว)</p>
  </div>

  <div class="flex flex-wrap gap-2">
    <select class="px-4 py-2 bg-surface-container dark:bg-surface-container-high/10 rounded-xl font-label-md text-label-md text-on-surface-variant"><option>โมดูลทั้งหมด</option></select>
    <select class="px-4 py-2 bg-surface-container dark:bg-surface-container-high/10 rounded-xl font-label-md text-label-md text-on-surface-variant"><option>ผู้ใช้ทั้งหมด</option></select>
    <input type="date" class="px-4 py-2 bg-surface-container dark:bg-surface-container-high/10 rounded-xl font-label-md text-label-md text-on-surface-variant"/>
  </div>

  <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl shadow-soft border border-surface-variant dark:border-outline-variant/20 divide-y divide-outline-variant/20">
    <?php foreach ($logs as $log): ?>
      <div class="p-4 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-6">
        <span class="font-metadata text-metadata text-on-surface-variant w-36 shrink-0"><?= htmlspecialchars($log['time']) ?></span>
        <span class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode w-28 shrink-0"><?= htmlspecialchars($log['user']) ?></span>
        <span class="font-label-md text-label-md text-primary w-20 shrink-0"><?= htmlspecialchars($log['action']) ?></span>
        <span class="font-metadata text-metadata text-on-surface-variant w-36 shrink-0"><?= htmlspecialchars($log['entity']) ?></span>
        <code class="font-mono text-xs text-on-surface-variant bg-surface-container dark:bg-surface-container-high/10 px-2 py-1 rounded"><?= htmlspecialchars($log['diff']) ?></code>
      </div>
    <?php endforeach; ?>
  </div>
</div>
