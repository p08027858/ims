<?php
/**
 * Company detail page (student browsing before applying). Manually composed — Stitch's 27
 * exports did not include a distinct "company detail" screen, so this reuses the same cards/
 * tokens established by student/company_search.php and company/dashboard.php for consistency.
 * Wired to App\Controllers\ApplicationController (Phase 4) — GET .../{id} + POST /student/applications.
 */
$company = $company ?? [
    'id' => 0, 'name' => 'บริษัท เทคโนวา จำกัด', 'industry' => 'เทคโนโลยีและสารสนเทศ', 'province' => 'กรุงเทพมหานคร',
    'address' => '123 อาคารสาทรสแควร์ ชั้น 20 ถ.สาทรเหนือ บางรัก กทม. 10500', 'positions' => 3,
    'description' => 'บริษัทพัฒนาซอฟต์แวร์และที่ปรึกษาด้านเทคโนโลยีสำหรับองค์กรขนาดกลางถึงใหญ่ เปิดรับนักศึกษาฝึกงานสายพัฒนาโปรแกรมและออกแบบ UX/UI',
    'tags' => ['มีเบี้ยเลี้ยง'],
];
$flashError = $flashError ?? null;
?>
<div class="max-w-3xl mx-auto flex flex-col gap-6">
  <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-6 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex flex-col gap-4">
    <div class="flex items-start gap-4">
      <div class="w-16 h-16 rounded-lg bg-primary-container text-on-primary-container flex items-center justify-center shrink-0 font-headline-lg shadow-sm">
        <?= htmlspecialchars(mb_substr($company['name'], 0, 1)) ?>
      </div>
      <div class="flex-1 min-w-0">
        <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($company['name']) ?></h1>
        <p class="font-body-md text-body-md text-on-surface-variant"><?= htmlspecialchars($company['industry']) ?></p>
        <div class="flex items-center gap-1 mt-1 text-outline">
          <span class="material-symbols-outlined text-[16px]">location_on</span>
          <span class="font-label-md text-metadata"><?= htmlspecialchars($company['province']) ?></span>
        </div>
      </div>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
      <div class="bg-secondary-container text-on-secondary-container px-3 py-1 rounded-full text-xs font-label-md font-bold uppercase tracking-wide">รับ <?= $company['positions'] ?> ตำแหน่ง</div>
      <?php foreach ($company['tags'] as $tag): ?>
        <div class="bg-status-success/10 text-status-success px-3 py-1 rounded-full text-xs font-label-md font-bold uppercase tracking-wide"><?= htmlspecialchars($tag) ?></div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-6 shadow-soft border border-surface-variant dark:border-outline-variant/20">
    <h2 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode mb-3">เกี่ยวกับบริษัท</h2>
    <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed"><?= htmlspecialchars($company['description']) ?></p>
    <div class="flex items-start gap-2 mt-4 text-on-surface-variant">
      <span class="material-symbols-outlined text-[18px] mt-0.5">place</span>
      <p class="font-body-md text-body-md"><?= htmlspecialchars($company['address']) ?></p>
    </div>
  </div>

  <?php if ($flashError): ?>
    <div class="bg-error-container text-on-error-container rounded-lg p-4 font-body-md text-body-md" role="alert"><?= htmlspecialchars($flashError) ?></div>
  <?php endif; ?>

  <form method="post" action="/student/applications" class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-6 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex flex-col gap-4">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
    <input type="hidden" name="company_id" value="<?= (int) $company['id'] ?>"/>
    <h2 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode">สมัครฝึกงานที่นี่</h2>
    <div class="flex flex-col gap-1">
      <label class="font-label-md text-label-md text-on-surface-variant" for="position_title">ตำแหน่งที่สนใจ</label>
      <input id="position_title" name="position_title" type="text" placeholder="เช่น Frontend Developer Intern"
             class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"/>
    </div>
    <div class="flex flex-col gap-1">
      <label class="font-label-md text-label-md text-on-surface-variant" for="note">หมายเหตุ (ถ้ามี)</label>
      <textarea id="note" name="note" rows="3" placeholder="สนใจงานด้าน..."
                class="w-full px-4 py-3 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface dark:text-text-dark-mode resize-none focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"></textarea>
    </div>
    <button type="submit" class="w-full h-touch-target bg-primary text-on-primary rounded-lg font-label-md text-label-md flex items-center justify-center gap-2 hover:bg-primary-container active:scale-[0.97] transition-all">
      <span class="material-symbols-outlined">send</span> ส่งใบสมัคร
    </button>
  </form>
</div>
