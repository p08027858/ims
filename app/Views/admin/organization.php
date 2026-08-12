<?php
/**
 * Faculties & departments CRUD (AI_AGENT_PHASES.md Phase 3 item 1) — new page, not part of the
 * original 27 Stitch exports (styled to match admin/batch_management.php + pending_approvals.php).
 * Wired to App\Controllers\OrgController via config/actions.php + config/view_data.php.
 */
$faculties = $faculties ?? [
    ['id' => 1, 'code' => 'ENG', 'name' => 'วิศวกรรมศาสตร์', 'status' => 'active', 'departments' => [
        ['id' => 1, 'code' => 'CPE', 'name' => 'วิศวกรรมคอมพิวเตอร์', 'status' => 'active'],
    ]],
];
$flashError = $flashError ?? null;
?>
<div class="flex flex-col gap-8">
  <div>
    <h1 class="font-display-metrics text-[32px] text-on-background dark:text-text-dark-mode">จัดการคณะ/สาขา</h1>
    <p class="font-body-md text-body-md text-on-surface-variant max-w-2xl mt-1">เพิ่ม/ปิดการใช้งานคณะและสาขาวิชา รองรับหลายคณะหลายสาขาในสถาบันเดียว (MASTER_SPEC.md §2)</p>
  </div>

  <?php if ($flashError): ?>
    <div class="bg-error-container text-on-error-container rounded-lg p-4 font-body-md text-body-md" role="alert"><?= htmlspecialchars($flashError) ?></div>
  <?php endif; ?>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <form method="post" action="/admin/faculties" class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-5 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex flex-col gap-3">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
      <h3 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode">เพิ่มคณะใหม่</h3>
      <input name="code" placeholder="รหัสคณะ เช่น ENG" required class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
      <input name="name_th" placeholder="ชื่อคณะ (ไทย)" required class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
      <input name="name_en" placeholder="ชื่อคณะ (อังกฤษ) — ไม่บังคับ" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
      <button type="submit" class="w-full h-touch-target bg-primary text-on-primary rounded-lg font-label-md text-label-md">เพิ่มคณะ</button>
    </form>

    <form method="post" action="/admin/departments" class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-5 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex flex-col gap-3">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
      <h3 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode">เพิ่มสาขาใหม่</h3>
      <select name="faculty_id" required class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary">
        <option value="" disabled selected>เลือกคณะ...</option>
        <?php foreach ($faculties as $f): ?>
          <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <input name="code" placeholder="รหัสสาขา เช่น CPE" required class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
      <input name="name_th" placeholder="ชื่อสาขา (ไทย)" required class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
      <input name="name_en" placeholder="ชื่อสาขา (อังกฤษ) — ไม่บังคับ" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
      <button type="submit" class="w-full h-touch-target bg-primary text-on-primary rounded-lg font-label-md text-label-md">เพิ่มสาขา</button>
    </form>
  </div>

  <div class="flex flex-col gap-4">
    <?php foreach ($faculties as $f): ?>
      <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-2xl p-6 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex flex-col gap-4">
        <div class="flex items-center justify-between flex-wrap gap-2">
          <div>
            <h3 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($f['name']) ?> <span class="font-metadata text-metadata text-on-surface-variant">(<?= htmlspecialchars($f['code']) ?>)</span></h3>
          </div>
          <div class="flex items-center gap-2">
            <span class="px-3 py-1 rounded-full font-label-md text-label-md <?= $f['status'] === 'active' ? 'bg-status-success/10 text-status-success' : 'bg-surface-variant text-on-surface-variant' ?>"><?= $f['status'] === 'active' ? 'ใช้งานอยู่' : 'ปิดใช้งาน' ?></span>
            <form method="post" action="/admin/faculties/<?= $f['id'] ?>/status">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
              <input type="hidden" name="status" value="<?= $f['status'] === 'active' ? 'inactive' : 'active' ?>"/>
              <button type="submit" class="px-3 py-1.5 rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container-high font-label-md text-label-md transition-colors"><?= $f['status'] === 'active' ? 'ปิดใช้งาน' : 'เปิดใช้งาน' ?></button>
            </form>
          </div>
        </div>
        <?php if (empty($f['departments'])): ?>
          <p class="font-body-md text-body-md text-on-surface-variant">ยังไม่มีสาขาในคณะนี้</p>
        <?php else: ?>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <?php foreach ($f['departments'] as $d): ?>
              <div class="flex items-center justify-between gap-2 bg-surface-container dark:bg-surface-container-high/10 rounded-lg px-4 py-2.5">
                <span class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($d['name']) ?> <span class="font-metadata text-metadata text-on-surface-variant">(<?= htmlspecialchars($d['code']) ?>)</span></span>
                <div class="flex items-center gap-2">
                  <span class="px-2 py-0.5 rounded-full font-metadata text-metadata <?= $d['status'] === 'active' ? 'bg-status-success/10 text-status-success' : 'bg-surface-variant text-on-surface-variant' ?>"><?= $d['status'] === 'active' ? 'ใช้งาน' : 'ปิด' ?></span>
                  <form method="post" action="/admin/departments/<?= $d['id'] ?>/status">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
                    <input type="hidden" name="status" value="<?= $d['status'] === 'active' ? 'inactive' : 'active' ?>"/>
                    <button type="submit" class="text-on-surface-variant hover:text-primary transition-colors" title="สลับสถานะ"><span class="material-symbols-outlined text-[20px]">sync</span></button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
