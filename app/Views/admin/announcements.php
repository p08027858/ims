<?php
/**
 * Manage announcements (SITEMAP.md §5 `/admin/announcements`, Phase 11). New page — the
 * `announcements` table (DATABASE.md §10.3) existed since Phase 1 but had no UI at all until now.
 */
$announcements = $announcements ?? [
    ['id' => 1, 'title' => 'ปิดปรับปรุงระบบ', 'content' => 'ระบบจะปิดปรับปรุงวันที่ 1 ส.ค. 22:00-24:00 น.', 'target_role' => 'all', 'is_pinned' => true, 'expires_at' => null],
];
$formError = $formError ?? null;
$targetLabel = ['all' => 'ทุกคน', 'student' => 'นักศึกษา', 'company' => 'ผู้ประกอบการ', 'teacher' => 'ครูนิเทศ'];
?>
<div class="flex flex-col gap-6">
  <div class="flex items-center justify-between flex-wrap gap-4">
    <div>
      <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode">จัดการประกาศ</h1>
      <p class="font-body-md text-body-md text-on-surface-variant">ประกาศที่แสดงให้ผู้ใช้แต่ละกลุ่มเห็น</p>
    </div>
    <button type="button" id="btn-open-drawer" class="px-5 py-3 rounded-xl bg-primary text-on-primary font-label-md text-label-md flex items-center gap-2 hover:bg-primary-container transition-colors active:scale-95">
      <span class="material-symbols-outlined text-[20px]">add</span> เพิ่มประกาศ
    </button>
  </div>

  <?php if ($formError): ?>
    <div class="bg-error-container text-on-error-container rounded-lg p-4 font-body-md text-body-md" role="alert"><?= htmlspecialchars($formError) ?></div>
  <?php endif; ?>

  <?php if (empty($announcements)): ?>
    <div class="flex flex-col items-center justify-center py-16 text-center">
      <span class="material-symbols-outlined text-5xl text-outline-variant mb-3">campaign</span>
      <p class="font-body-md text-body-md text-on-surface-variant">ยังไม่มีประกาศ</p>
    </div>
  <?php else: ?>
    <div class="flex flex-col gap-3">
      <?php foreach ($announcements as $a): ?>
        <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-5 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex flex-col gap-2">
          <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-2">
              <?php if ($a['is_pinned']): ?><span class="material-symbols-outlined text-primary text-[18px]">push_pin</span><?php endif; ?>
              <h3 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($a['title']) ?></h3>
            </div>
            <form method="post" action="/admin/announcements/<?= $a['id'] ?>/delete" onsubmit="return confirm('ลบประกาศนี้?');">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
              <button type="submit" class="p-2 rounded-lg text-error hover:bg-error-container/20 transition-colors"><span class="material-symbols-outlined text-[20px]">delete</span></button>
            </form>
          </div>
          <p class="font-body-md text-body-md text-on-surface-variant whitespace-pre-line"><?= htmlspecialchars($a['content']) ?></p>
          <div class="flex items-center gap-2 mt-1">
            <span class="px-2 py-0.5 rounded-full bg-secondary-container text-on-secondary-container font-metadata text-metadata"><?= htmlspecialchars($targetLabel[$a['target_role']] ?? $a['target_role']) ?></span>
            <?php if ($a['expires_at']): ?><span class="font-metadata text-metadata text-on-surface-variant">หมดอายุ <?= htmlspecialchars(date('j M Y', strtotime($a['expires_at']))) ?></span><?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div id="add-announcement-drawer" class="hidden fixed inset-0 z-50">
  <div class="absolute inset-0 bg-surface-dark/40 backdrop-blur-sm" id="drawer-backdrop"></div>
  <div class="absolute right-0 top-0 h-full w-full max-w-md bg-surface-container-lowest dark:bg-surface-dark shadow-2xl flex flex-col">
    <div class="flex items-center justify-between p-6 border-b border-outline-variant/20">
      <h3 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode">เพิ่มประกาศ</h3>
      <button type="button" id="btn-close-drawer" class="p-2 text-on-surface-variant hover:bg-surface-variant rounded-full"><span class="material-symbols-outlined">close</span></button>
    </div>
    <form method="post" action="/admin/announcements" class="flex-1 p-6 flex flex-col gap-4">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
      <div class="flex flex-col gap-1"><label class="font-label-md text-label-md text-on-surface-variant" for="new-title">หัวข้อ</label>
        <input id="new-title" name="title" type="text" required class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"/></div>
      <div class="flex flex-col gap-1"><label class="font-label-md text-label-md text-on-surface-variant" for="new-content">เนื้อหา</label>
        <textarea id="new-content" name="content" rows="5" required class="w-full px-4 py-3 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode resize-none focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"></textarea></div>
      <div class="flex flex-col gap-1"><label class="font-label-md text-label-md text-on-surface-variant" for="new-target">กลุ่มเป้าหมาย</label>
        <select id="new-target" name="target_role" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">
          <option value="all">ทุกคน</option>
          <option value="student">นักศึกษา</option>
          <option value="company">ผู้ประกอบการ</option>
          <option value="teacher">ครูนิเทศ</option>
        </select></div>
      <div class="flex flex-col gap-1"><label class="font-label-md text-label-md text-on-surface-variant" for="new-expires">วันหมดอายุ (ถ้ามี)</label>
        <input id="new-expires" name="expires_at" type="date" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"/></div>
      <label class="flex items-center gap-2"><input type="checkbox" name="is_pinned" value="1"/> <span class="font-body-md text-body-md text-on-surface dark:text-text-dark-mode">ปักหมุดไว้บนสุด</span></label>
      <button type="submit" class="mt-4 w-full h-touch-target bg-primary text-on-primary rounded-lg font-label-md text-label-md">บันทึกประกาศ</button>
    </form>
  </div>
</div>
<script>
  const drawer = document.getElementById('add-announcement-drawer');
  document.getElementById('btn-open-drawer')?.addEventListener('click', () => drawer.classList.remove('hidden'));
  document.getElementById('btn-close-drawer')?.addEventListener('click', () => drawer.classList.add('hidden'));
  document.getElementById('drawer-backdrop')?.addEventListener('click', () => drawer.classList.add('hidden'));
</script>
