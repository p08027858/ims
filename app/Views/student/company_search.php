<?php
/**
 * Company search/browse. Adapted from design-reference/mobile_2. Wired to
 * App\Controllers\ApplicationController::companySearchData() (Phase 4) — RULE-MATCH-01: only
 * companies.status=approved ever appear here (enforced in ApplicationService, not just hidden
 * client-side). 'positions'/'tags' shown below have no backing DB column yet (ISSUES.md) so
 * real rows always show 0/[] for those — search box submits as a plain GET (?q=) since there's
 * no JS fetch layer yet.
 * pull-to-refresh JS below is a visual simulation only — swap for a real refetch.
 */
$companies = $companies ?? [
    ['id' => 1, 'name' => 'บริษัท เทคโนวา จำกัด', 'industry' => 'เทคโนโลยีและสารสนเทศ', 'province' => 'กรุงเทพมหานคร', 'positions' => 3, 'tags' => ['มีเบี้ยเลี้ยง']],
    ['id' => 2, 'name' => 'บจก. ครีเอทีฟ มายด์เซ็ต', 'industry' => 'การตลาดและโฆษณา', 'province' => 'เชียงใหม่', 'positions' => 1, 'tags' => []],
    ['id' => 3, 'name' => 'Global Logistics Group', 'industry' => 'โลจิสติกส์และการขนส่ง', 'province' => 'ชลบุรี', 'positions' => 5, 'tags' => ['มีที่พัก']],
];
?>
<div class="flex flex-col gap-4">
  <form method="get" action="/student/companies" class="relative w-full">
    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline" aria-hidden="true">search</span>
    <input class="w-full bg-surface-container-low dark:bg-surface-container-high/10 text-on-surface dark:text-text-dark-mode font-body-md py-3 pl-12 pr-4 rounded-full focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all placeholder:text-outline-variant shadow-sm"
           type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="ค้นหาบริษัท หรือ ตำแหน่งงาน..." aria-label="ค้นหาสถานประกอบการ"/>
  </form>
  <div class="flex items-center gap-2 overflow-x-auto pb-1">
    <button type="button" class="shrink-0 flex items-center gap-1 bg-primary text-on-primary px-4 py-1.5 rounded-full font-label-md text-label-md shadow-sm active:scale-95 transition-transform">ทั้งหมด</button>
    <button type="button" class="shrink-0 flex items-center gap-1 bg-surface-container text-on-surface-variant px-4 py-1.5 rounded-full font-label-md text-label-md hover:bg-surface-container-high transition-colors active:scale-95">
      จังหวัด <span class="material-symbols-outlined text-[16px]">arrow_drop_down</span>
    </button>
    <button type="button" class="shrink-0 flex items-center gap-1 bg-surface-container text-on-surface-variant px-4 py-1.5 rounded-full font-label-md text-label-md hover:bg-surface-container-high transition-colors active:scale-95">
      ประเภทอุตสาหกรรม <span class="material-symbols-outlined text-[16px]">arrow_drop_down</span>
    </button>
  </div>
</div>

<div class="flex flex-col gap-4 mt-4" id="company-list">
  <?php if (empty($companies)): ?>
    <div class="flex flex-col items-center justify-center py-12 text-center">
      <div class="w-24 h-24 bg-surface-container rounded-full flex items-center justify-center mb-4"><span class="material-symbols-outlined text-[48px] text-outline-variant">search_off</span></div>
      <h4 class="font-headline-md text-headline-md text-on-surface mb-2">ไม่พบผลลัพธ์</h4>
      <p class="font-body-md text-body-md text-on-surface-variant max-w-[250px]">ลองปรับเปลี่ยนคำค้นหา หรือตัวกรอง เพื่อค้นหาบริษัทที่เหมาะสมกับคุณ</p>
    </div>
  <?php endif; ?>
  <?php foreach ($companies as $c): ?>
    <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-4 shadow-sm border border-surface-variant/50 dark:border-outline-variant/10 flex flex-col gap-3 transition-transform active:scale-[0.98]">
      <div class="flex items-start gap-3">
        <div class="w-14 h-14 rounded-lg bg-primary-container text-on-primary-container flex items-center justify-center shrink-0 shadow-sm font-headline-lg-mobile">
          <?= htmlspecialchars(mb_substr($c['name'], 0, 1)) ?>
        </div>
        <div class="flex flex-col flex-1 min-w-0">
          <h3 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode truncate"><?= htmlspecialchars($c['name']) ?></h3>
          <p class="font-body-md text-metadata text-on-surface-variant truncate"><?= htmlspecialchars($c['industry']) ?></p>
          <div class="flex items-center gap-1 mt-1 text-outline">
            <span class="material-symbols-outlined text-[14px]">location_on</span>
            <span class="font-label-md text-metadata"><?= htmlspecialchars($c['province']) ?></span>
          </div>
        </div>
      </div>
      <div class="flex items-center justify-between mt-2 pt-3 border-t border-surface-variant dark:border-outline-variant/20">
        <div class="flex items-center gap-2 flex-wrap">
          <div class="bg-secondary-container text-on-secondary-container px-2 py-0.5 rounded text-[10px] font-label-md font-bold uppercase tracking-wide">รับ <?= $c['positions'] ?> ตำแหน่ง</div>
          <?php foreach ($c['tags'] as $tag): ?>
            <div class="bg-status-success/10 text-status-success px-2 py-0.5 rounded text-[10px] font-label-md font-bold uppercase tracking-wide"><?= htmlspecialchars($tag) ?></div>
          <?php endforeach; ?>
        </div>
        <a href="/student/companies/<?= $c['id'] ?>" class="bg-primary-container text-on-primary-container px-4 py-1.5 rounded-full font-label-md text-label-md shadow-sm active:scale-95 transition-transform flex items-center gap-1">
          รายละเอียด <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
        </a>
      </div>
    </div>
  <?php endforeach; ?>
</div>
