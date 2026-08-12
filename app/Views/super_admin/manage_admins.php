<?php
/**
 * Super Admin: manage admin accounts. Adapted from design-reference/admin_super_admin. Wired to
 * App\Controllers\SuperAdminAdminController (Phase 10). "แก้ไขสิทธิ์" from the original mock was
 * dropped — see SuperAdminPermissionController's docblock for why /super-admin/permissions is a
 * read-only reference page, not a per-admin editor (no such runtime engine exists in this
 * codebase's RBAC architecture).
 */
$admins = $admins ?? [
    ['id' => '1', 'username' => 'admin01', 'scope' => 'Admin', 'status' => 'active', 'isSuperAdmin' => false],
    ['id' => '2', 'username' => 'admin02', 'scope' => 'Admin', 'status' => 'active', 'isSuperAdmin' => false],
];
$formError = $formError ?? null;
$statusLabel = ['active' => 'ใช้งานอยู่', 'suspended' => 'ระงับใช้งาน', 'pending' => 'รออนุมัติ'];
$statusClass = ['active' => 'bg-status-success/10 text-status-success', 'suspended' => 'bg-status-error/10 text-status-error', 'pending' => 'bg-status-warning/10 text-status-warning'];
?>
<div class="flex flex-col gap-6">
  <div class="flex items-center justify-between flex-wrap gap-4">
    <div>
      <h2 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode">จัดการผู้ดูแลระบบ</h2>
      <p class="font-body-md text-body-md text-on-surface-variant mt-1">Super Admin Panel — เพิ่มบัญชี Admin และเปิด/ปิดการใช้งาน (ดูสิทธิ์ทั้งหมดที่ /super-admin/permissions)</p>
    </div>
    <button type="button" id="btn-open-drawer" class="px-5 py-3 rounded-xl bg-primary text-on-primary font-label-md text-label-md flex items-center gap-2 hover:bg-primary-container transition-colors active:scale-95">
      <span class="material-symbols-outlined text-[20px]">add</span> เพิ่ม Admin ใหม่
    </button>
  </div>

  <?php if ($formError): ?>
    <div class="bg-error-container text-on-error-container rounded-lg p-4 font-body-md text-body-md" role="alert"><?= htmlspecialchars($formError) ?></div>
  <?php endif; ?>

  <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl shadow-soft border border-surface-variant dark:border-outline-variant/20 overflow-hidden">
    <table class="w-full text-left border-collapse">
      <thead>
        <tr class="bg-surface-container/50 dark:bg-surface-container-high/10">
          <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant">อีเมล</th>
          <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant">ขอบเขต</th>
          <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant">สถานะ</th>
          <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant text-right">จัดการ</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($admins as $a): ?>
          <tr class="hover:bg-surface-container-low dark:hover:bg-surface-container/10 transition-colors">
            <td class="py-4 px-6 border-b border-outline-variant/20 font-label-md text-label-md text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($a['username']) ?></td>
            <td class="py-4 px-6 border-b border-outline-variant/20 font-body-md text-body-md text-on-surface-variant"><?= htmlspecialchars($a['scope']) ?></td>
            <td class="py-4 px-6 border-b border-outline-variant/20"><span class="px-2.5 py-1 rounded-full font-label-md text-label-md <?= $statusClass[$a['status']] ?? 'bg-surface-variant text-on-surface-variant' ?>"><?= htmlspecialchars($statusLabel[$a['status']] ?? $a['status']) ?></span></td>
            <td class="py-4 px-6 border-b border-outline-variant/20 text-right">
              <?php if (!$a['isSuperAdmin']): ?>
                <form method="post" action="/super-admin/admins/<?= htmlspecialchars((string) $a['id']) ?>/status" class="inline">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
                  <input type="hidden" name="status" value="<?= $a['status'] === 'suspended' ? 'active' : 'suspended' ?>"/>
                  <button type="submit" class="px-3 py-1.5 rounded-lg font-label-md text-label-md <?= $a['status'] === 'suspended' ? 'text-status-success hover:bg-status-success/10' : 'text-error hover:bg-error-container/20' ?>">
                    <?= $a['status'] === 'suspended' ? 'เปิดใช้งาน' : 'ปิดใช้งาน' ?>
                  </button>
                </form>
              <?php else: ?>
                <span class="font-metadata text-metadata text-on-surface-variant">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div id="add-admin-drawer" class="hidden fixed inset-0 z-50">
  <div class="absolute inset-0 bg-surface-dark/40 backdrop-blur-sm" id="drawer-backdrop"></div>
  <div class="absolute right-0 top-0 h-full w-full max-w-md bg-surface-container-lowest dark:bg-surface-dark shadow-2xl flex flex-col">
    <div class="flex items-center justify-between p-6 border-b border-outline-variant/20">
      <h3 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode">เพิ่มผู้ดูแลระบบใหม่</h3>
      <button type="button" id="btn-close-drawer" class="p-2 text-on-surface-variant hover:bg-surface-variant rounded-full"><span class="material-symbols-outlined">close</span></button>
    </div>
    <form method="post" action="/super-admin/admins" class="flex-1 p-6 flex flex-col gap-4">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
      <div class="flex flex-col gap-1"><label class="font-label-md text-label-md text-on-surface-variant" for="new-email">อีเมล</label>
        <input id="new-email" name="email" type="email" required class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"/></div>
      <div class="flex flex-col gap-1"><label class="font-label-md text-label-md text-on-surface-variant" for="new-password">รหัสผ่านเริ่มต้น</label>
        <input id="new-password" name="password" type="password" minlength="8" required class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"/>
        <p class="font-metadata text-metadata text-on-surface-variant mt-0.5">อย่างน้อย 8 ตัวอักษร — บัญชีใหม่จะ active ทันที ไม่ต้องรอใครอนุมัติ</p></div>
      <button type="submit" class="mt-4 w-full h-touch-target bg-primary text-on-primary rounded-lg font-label-md text-label-md">สร้างบัญชี</button>
    </form>
  </div>
</div>
<script>
  const drawer = document.getElementById('add-admin-drawer');
  document.getElementById('btn-open-drawer')?.addEventListener('click', () => drawer.classList.remove('hidden'));
  document.getElementById('btn-close-drawer')?.addEventListener('click', () => drawer.classList.add('hidden'));
  document.getElementById('drawer-backdrop')?.addEventListener('click', () => drawer.classList.add('hidden'));
</script>
