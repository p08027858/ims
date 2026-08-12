<?php
/**
 * Full attendance history + hours summary. Manually composed (not a distinct Stitch export)
 * by extending the "ประวัติการลงเวลาล่าสุด" list pattern seen in design-reference/desktop_home
 * into a full paginated list, to keep the same visual language as the rest of the app.
 * TODO Phase 5: wire to GET /attendance?internship_id=&month= and GET /attendance/summary
 * (API_SPEC.md §5).
 */
$summary = $summary ?? ['total_hours_logged' => 210.5, 'total_required_hours' => 400, 'percent_complete' => 52.6, 'days_present' => 30, 'days_late' => 2, 'days_absent' => 1];
$records = $records ?? [
    ['date' => '22 ก.ค. 2569', 'check_in' => '08:45', 'check_out' => '17:30', 'hours' => 8.75, 'status' => 'present'],
    ['date' => '21 ก.ค. 2569', 'check_in' => '09:15', 'check_out' => '17:20', 'hours' => 8.08, 'status' => 'late'],
    ['date' => '20 ก.ค. 2569', 'check_in' => '-', 'check_out' => '-', 'hours' => 0, 'status' => 'leave'],
];
$statusMeta = [
    'present' => ['label' => 'ปกติ', 'color' => 'status-success'],
    'late' => ['label' => 'มาสาย', 'color' => 'status-warning'],
    'absent' => ['label' => 'ขาด', 'color' => 'status-error'],
    'leave' => ['label' => 'ลา', 'color' => 'status-inactive'],
];
?>
<div class="flex flex-col gap-6">
  <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode">สรุปชั่วโมงและประวัติการลงเวลา</h1>

  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-4 shadow-soft border border-surface-variant dark:border-outline-variant/20">
      <p class="font-label-md text-label-md text-on-surface-variant mb-1">ชั่วโมงสะสม</p>
      <p class="font-headline-lg text-headline-lg text-primary"><?= $summary['total_hours_logged'] ?><span class="text-metadata text-on-surface-variant">/<?= $summary['total_required_hours'] ?></span></p>
    </div>
    <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-4 shadow-soft border border-surface-variant dark:border-outline-variant/20">
      <p class="font-label-md text-label-md text-on-surface-variant mb-1">ความคืบหน้า</p>
      <p class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode"><?= $summary['percent_complete'] ?>%</p>
    </div>
    <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-4 shadow-soft border border-surface-variant dark:border-outline-variant/20">
      <p class="font-label-md text-label-md text-on-surface-variant mb-1">มาสาย</p>
      <p class="font-headline-lg text-headline-lg text-status-warning"><?= $summary['days_late'] ?> วัน</p>
    </div>
    <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-4 shadow-soft border border-surface-variant dark:border-outline-variant/20">
      <p class="font-label-md text-label-md text-on-surface-variant mb-1">ขาดงาน</p>
      <p class="font-headline-lg text-headline-lg text-status-error"><?= $summary['days_absent'] ?> วัน</p>
    </div>
  </div>

  <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl shadow-soft border border-surface-variant dark:border-outline-variant/20 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse min-w-[560px]">
        <thead>
          <tr class="bg-surface-container/50 dark:bg-surface-container-high/10">
            <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant">วันที่</th>
            <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant">เข้างาน</th>
            <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant">ออกงาน</th>
            <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant">ชั่วโมง</th>
            <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant">สถานะ</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($records as $r): $meta = $statusMeta[$r['status']]; ?>
            <tr class="hover:bg-surface-container-low dark:hover:bg-surface-container/10 transition-colors">
              <td class="py-3 px-6 border-b border-outline-variant/20 font-body-md text-body-md text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($r['date']) ?></td>
              <td class="py-3 px-6 border-b border-outline-variant/20 font-body-md text-body-md text-on-surface-variant"><?= htmlspecialchars($r['check_in']) ?></td>
              <td class="py-3 px-6 border-b border-outline-variant/20 font-body-md text-body-md text-on-surface-variant"><?= htmlspecialchars($r['check_out']) ?></td>
              <td class="py-3 px-6 border-b border-outline-variant/20 font-body-md text-body-md text-on-surface-variant"><?= $r['hours'] ?> ชม.</td>
              <td class="py-3 px-6 border-b border-outline-variant/20">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-<?= $meta['color'] ?>/10 text-<?= $meta['color'] ?> font-label-md text-label-md"><?= $meta['label'] ?></span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
