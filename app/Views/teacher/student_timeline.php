<?php
/**
 * Individual student detail/timeline — shared by teacher (`/teacher/students/{id}`) and company
 * (`/company/students/{id}`) roles (SITEMAP.md §3/§4). Adapted from design-reference/_9
 * ("ไทม์ไลน์กิจกรรมล่าสุด"). Wired to App\Controllers\StudentTimelineController (Phase 11) — never
 * actually connected to real data before this, despite the route existing since Phase 4.
 */
$notFound = $notFound ?? false;
$student = $student ?? ['name' => 'สมชาย ใจดี', 'company' => 'บริษัท เอบีซี จำกัด'];
$activeTab = $activeTab ?? 'overview';
$timeline = $timeline ?? [
    ['date' => '22 ก.ค.', 'items' => [['icon' => 'check_circle', 'color' => 'status-success', 'text' => 'เข้างาน 08:02'], ['icon' => 'check_circle', 'color' => 'status-success', 'text' => 'บันทึกงานแล้ว']]],
    ['date' => '21 ก.ค.', 'items' => [['icon' => 'check_circle', 'color' => 'status-success', 'text' => 'เข้างาน 08:05'], ['icon' => 'warning', 'color' => 'status-warning', 'text' => 'ยังไม่บันทึกงาน']]],
    ['date' => '20 ก.ค.', 'items' => [['icon' => 'event_available', 'color' => 'status-inactive', 'text' => 'ลา (อนุมัติแล้ว)']]],
];
$tabs = ['overview' => 'ภาพรวม', 'attendance' => 'การเข้างาน', 'daily_logs' => 'บันทึกงาน', 'leave' => 'การลา', 'evaluation' => 'ประเมินผล'];
?>
<?php if ($notFound): ?>
  <div class="flex flex-col items-center justify-center py-16 text-center">
    <span class="material-symbols-outlined text-5xl text-outline-variant mb-3">search_off</span>
    <p class="font-body-md text-body-md text-on-surface-variant">ไม่พบข้อมูลนักศึกษานี้ หรือคุณไม่มีสิทธิ์เข้าถึง</p>
  </div>
<?php else: ?>
<div class="flex flex-col gap-6">
  <div>
    <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($student['name']) ?></h1>
    <p class="font-body-md text-body-md text-on-surface-variant"><?= htmlspecialchars($student['company']) ?></p>
  </div>

  <div class="flex gap-1 border-b border-outline-variant/20 overflow-x-auto">
    <?php foreach ($tabs as $key => $label): $isActive = $activeTab === $key; ?>
      <a href="?tab=<?= $key ?>" class="px-4 py-3 font-label-md text-label-md whitespace-nowrap border-b-2 transition-colors <?= $isActive ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface' ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </div>

  <div class="flex flex-col gap-6">
    <?php if (empty($timeline)): ?>
      <p class="font-body-md text-body-md text-on-surface-variant text-center py-8">ยังไม่มีข้อมูลในหมวดนี้</p>
    <?php endif; ?>
    <?php foreach ($timeline as $day): ?>
      <div class="flex gap-4">
        <div class="w-16 shrink-0 text-right font-label-md text-label-md text-on-surface-variant pt-1"><?= htmlspecialchars($day['date']) ?></div>
        <div class="flex-1 flex flex-col gap-2 border-l-2 border-outline-variant/20 pl-4">
          <?php foreach ($day['items'] as $item): ?>
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-<?= $item['color'] ?> text-[18px]"><?= $item['icon'] ?></span>
              <span class="font-body-md text-body-md text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($item['text']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
