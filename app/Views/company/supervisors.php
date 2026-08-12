<?php
/**
 * Manage company_supervisors accounts at this company (SITEMAP.md §3 `/company/supervisors`,
 * Phase 11). New page. Only the primary contact (is_primary=true, ROLES.md §2) can add new
 * secondary supervisor accounts — `$canAdd` gates the form below.
 */
$supervisors = $supervisors ?? [
    ['id' => '1', 'name' => 'สมศักดิ์ ดูแลดี', 'email' => 'somsak@abc.co.th', 'position' => 'ผู้จัดการฝ่ายบุคคล', 'is_primary' => true, 'status' => 'active'],
];
$canAdd = $canAdd ?? false;
$formError = $formError ?? null;
$statusLabel = ['active' => 'ใช้งานอยู่', 'pending' => 'รออนุมัติ', 'suspended' => 'ระงับใช้งาน'];
?>
<div class="flex flex-col gap-6">
  <div class="flex items-center justify-between flex-wrap gap-4">
    <div>
      <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode">ผู้ติดต่อ/พี่เลี้ยงของบริษัท</h1>
      <p class="font-body-md text-body-md text-on-surface-variant">บัญชีผู้ติดต่อหลักและผู้ติดต่อรองที่เข้าถึงระบบนี้ได้</p>
    </div>
    <?php if ($canAdd): ?>
      <button type="button" id="btn-open-drawer" class="px-5 py-3 rounded-xl bg-primary text-on-primary font-label-md text-label-md flex items-center gap-2 hover:bg-primary-container transition-colors active:scale-95">
        <span class="material-symbols-outlined text-[20px]">add</span> เพิ่มผู้ติดต่อ
      </button>
    <?php endif; ?>
  </div>

  <?php if ($formError): ?>
    <div class="bg-error-container text-on-error-container rounded-lg p-4 font-body-md text-body-md" role="alert"><?= htmlspecialchars($formError) ?></div>
  <?php endif; ?>

  <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl shadow-soft border border-surface-variant dark:border-outline-variant/20 overflow-hidden">
    <table class="w-full text-left border-collapse">
      <thead>
        <tr class="bg-surface-container/50 dark:bg-surface-container-high/10">
          <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant">ชื่อ</th>
          <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant">อีเมล</th>
          <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant">ตำแหน่ง</th>
          <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant">บทบาท</th>
          <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant">สถานะ</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($supervisors as $s): ?>
          <tr class="hover:bg-surface-container-low dark:hover:bg-surface-container/10 transition-colors">
            <td class="py-4 px-6 border-b border-outline-variant/20 font-label-md text-label-md text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($s['name']) ?></td>
            <td class="py-4 px-6 border-b border-outline-variant/20 font-body-md text-body-md text-on-surface-variant"><?= htmlspecialchars($s['email']) ?></td>
            <td class="py-4 px-6 border-b border-outline-variant/20 font-body-md text-body-md text-on-surface-variant"><?= htmlspecialchars($s['position']) ?></td>
            <td class="py-4 px-6 border-b border-outline-variant/20 font-body-md text-body-md text-on-surface-variant"><?= $s['is_primary'] ? 'ผู้ติดต่อหลัก' : 'ผู้ติดต่อรอง' ?></td>
            <td class="py-4 px-6 border-b border-outline-variant/20"><span class="px-2.5 py-1 rounded-full bg-status-success/10 text-status-success font-label-md text-label-md"><?= htmlspecialchars($statusLabel[$s['status']] ?? $s['status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($canAdd): ?>
<div id="add-supervisor-drawer" class="hidden fixed inset-0 z-50">
  <div class="absolute inset-0 bg-surface-dark/40 backdrop-blur-sm" id="drawer-backdrop"></div>
  <div class="absolute right-0 top-0 h-full w-full max-w-md bg-surface-container-lowest dark:bg-surface-dark shadow-2xl flex flex-col">
    <div class="flex items-center justify-between p-6 border-b border-outline-variant/20">
      <h3 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode">เพิ่มผู้ติดต่อรอง</h3>
      <button type="button" id="btn-close-drawer" class="p-2 text-on-surface-variant hover:bg-surface-variant rounded-full"><span class="material-symbols-outlined">close</span></button>
    </div>
    <form method="post" action="/company/supervisors" class="flex-1 p-6 flex flex-col gap-4">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
      <div class="flex flex-col gap-1"><label class="font-label-md text-label-md text-on-surface-variant" for="new-first-name">ชื่อ</label>
        <input id="new-first-name" name="first_name" type="text" required class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"/></div>
      <div class="flex flex-col gap-1"><label class="font-label-md text-label-md text-on-surface-variant" for="new-last-name">นามสกุล</label>
        <input id="new-last-name" name="last_name" type="text" required class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"/></div>
      <div class="flex flex-col gap-1"><label class="font-label-md text-label-md text-on-surface-variant" for="new-position">ตำแหน่ง</label>
        <input id="new-position" name="position" type="text" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"/></div>
      <div class="flex flex-col gap-1"><label class="font-label-md text-label-md text-on-surface-variant" for="new-email">อีเมล</label>
        <input id="new-email" name="email" type="email" required class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"/></div>
      <div class="flex flex-col gap-1"><label class="font-label-md text-label-md text-on-surface-variant" for="new-password">รหัสผ่านเริ่มต้น</label>
        <input id="new-password" name="password" type="password" minlength="8" required class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"/></div>
      <button type="submit" class="mt-4 w-full h-touch-target bg-primary text-on-primary rounded-lg font-label-md text-label-md">สร้างบัญชี</button>
    </form>
  </div>
</div>
<script>
  const drawer = document.getElementById('add-supervisor-drawer');
  document.getElementById('btn-open-drawer')?.addEventListener('click', () => drawer.classList.remove('hidden'));
  document.getElementById('btn-close-drawer')?.addEventListener('click', () => drawer.classList.add('hidden'));
  document.getElementById('drawer-backdrop')?.addEventListener('click', () => drawer.classList.add('hidden'));
</script>
<?php endif; ?>
