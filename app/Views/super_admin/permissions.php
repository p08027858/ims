<?php
/**
 * Read-only RBAC matrix reference (PERMISSIONS.md §2, DATABASE.md §2.3). New page, not part of
 * the 27 Stitch exports. See App\Controllers\SuperAdminPermissionController's docblock for why
 * this is deliberately not an editor.
 */
$permissions = $permissions ?? [];
$byRole = [];
foreach ($permissions as $p) {
    $byRole[$p['role']][] = $p;
}
$roleLabel = ['student' => 'นักศึกษา', 'company' => 'ผู้ประกอบการ', 'teacher' => 'ครูนิเทศ', 'admin' => 'Admin', 'super_admin' => 'Super Admin'];
?>
<div class="flex flex-col gap-6">
  <div>
    <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode">สิทธิ์การใช้งาน (RBAC Matrix)</h1>
    <p class="font-body-md text-body-md text-on-surface-variant mt-1">ข้อมูลอ้างอิงอย่างเดียว (read-only) — การบังคับสิทธิ์จริงของระบบอยู่ที่ระดับ route ในโค้ด ไม่ได้อ่านค่าจากตารางนี้แบบ real-time การแก้ไขสิทธิ์จริงต้องแก้ที่โค้ด route</p>
  </div>

  <?php if (empty($permissions)): ?>
    <div class="flex flex-col items-center justify-center py-16 text-center">
      <span class="material-symbols-outlined text-5xl text-outline-variant mb-3">rule</span>
      <p class="font-body-md text-body-md text-on-surface-variant">ไม่พบข้อมูลสิทธิ์</p>
    </div>
  <?php endif; ?>

  <?php foreach ($byRole as $role => $rows): ?>
    <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl shadow-soft border border-surface-variant dark:border-outline-variant/20 overflow-hidden">
      <div class="px-6 py-3 bg-surface-container/50 dark:bg-surface-container-high/10">
        <h2 class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($roleLabel[$role] ?? $role) ?></h2>
      </div>
      <table class="w-full text-left border-collapse">
        <thead>
          <tr>
            <th class="py-2 px-6 font-metadata text-metadata text-on-surface-variant">โมดูล</th>
            <th class="py-2 px-6 font-metadata text-metadata text-on-surface-variant">action</th>
            <th class="py-2 px-6 font-metadata text-metadata text-on-surface-variant">อนุญาต</th>
            <th class="py-2 px-6 font-metadata text-metadata text-on-surface-variant">ขอบเขต</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $p): ?>
            <tr class="hover:bg-surface-container-low dark:hover:bg-surface-container/10 transition-colors">
              <td class="py-2 px-6 border-t border-outline-variant/10 font-body-md text-body-md text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($p['module']) ?></td>
              <td class="py-2 px-6 border-t border-outline-variant/10 font-body-md text-body-md text-on-surface-variant"><?= htmlspecialchars($p['action']) ?></td>
              <td class="py-2 px-6 border-t border-outline-variant/10">
                <span class="px-2 py-0.5 rounded-full font-metadata text-metadata <?= $p['allowed'] ? 'bg-status-success/10 text-status-success' : 'bg-status-error/10 text-status-error' ?>"><?= $p['allowed'] ? 'อนุญาต' : 'ไม่อนุญาต' ?></span>
              </td>
              <td class="py-2 px-6 border-t border-outline-variant/10 font-metadata text-metadata text-on-surface-variant"><?= htmlspecialchars($p['scope']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endforeach; ?>
</div>
