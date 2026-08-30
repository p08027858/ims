<?php
/**
 * Full attendance history + hours summary.
 */
$summary = $summary ?? [
    'total_hours_logged' => 210.5,
    'total_required_hours' => 400,
    'percent_complete' => 52.6,
    'days_present' => 30,
    'days_late' => 2,
    'days_absent' => 1,
];
$records = $records ?? [
    ['date' => '2026-07-22', 'check_in' => '08:45', 'check_out' => '17:30', 'hours' => 8.75, 'status' => 'present'],
    ['date' => '2026-07-21', 'check_in' => '09:15', 'check_out' => '17:20', 'hours' => 8.08, 'status' => 'late'],
    ['date' => '2026-07-20', 'check_in' => '-', 'check_out' => '-', 'hours' => 0, 'status' => 'leave'],
];
$statusMeta = [
    'present' => ['label' => 'Present', 'color' => 'status-success'],
    'late' => ['label' => 'Late', 'color' => 'status-warning'],
    'absent' => ['label' => 'Absent', 'color' => 'status-error'],
    'leave' => ['label' => 'Leave', 'color' => 'status-inactive'],
    'out_of_range' => ['label' => 'Out of range', 'color' => 'status-warning'],
];
?>
<div class="flex flex-col gap-6">
  <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode">Attendance Summary</h1>

  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-4 shadow-soft border border-surface-variant dark:border-outline-variant/20">
      <p class="font-label-md text-label-md text-on-surface-variant mb-1">Hours logged</p>
      <p class="font-headline-lg text-headline-lg text-primary"><?= htmlspecialchars((string) $summary['total_hours_logged']) ?><span class="text-metadata text-on-surface-variant">/<?= htmlspecialchars((string) $summary['total_required_hours']) ?></span></p>
    </div>
    <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-4 shadow-soft border border-surface-variant dark:border-outline-variant/20">
      <p class="font-label-md text-label-md text-on-surface-variant mb-1">Progress</p>
      <p class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars((string) $summary['percent_complete']) ?>%</p>
    </div>
    <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-4 shadow-soft border border-surface-variant dark:border-outline-variant/20">
      <p class="font-label-md text-label-md text-on-surface-variant mb-1">Late days</p>
      <p class="font-headline-lg text-headline-lg text-status-warning"><?= htmlspecialchars((string) $summary['days_late']) ?> days</p>
    </div>
    <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-4 shadow-soft border border-surface-variant dark:border-outline-variant/20">
      <p class="font-label-md text-label-md text-on-surface-variant mb-1">Absent days</p>
      <p class="font-headline-lg text-headline-lg text-status-error"><?= htmlspecialchars((string) $summary['days_absent']) ?> days</p>
    </div>
  </div>

  <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl shadow-soft border border-surface-variant dark:border-outline-variant/20 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse min-w-[560px]">
        <thead>
          <tr class="bg-surface-container/50 dark:bg-surface-container-high/10">
            <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant">Date</th>
            <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant">Check in</th>
            <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant">Check out</th>
            <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant">Hours</th>
            <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($records as $r): ?>
            <?php $meta = $statusMeta[$r['status']] ?? ['label' => (string) $r['status'], 'color' => 'status-inactive']; ?>
            <tr class="hover:bg-surface-container-low dark:hover:bg-surface-container/10 transition-colors">
              <td class="py-3 px-6 border-b border-outline-variant/20 font-body-md text-body-md text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars((string) $r['date']) ?></td>
              <td class="py-3 px-6 border-b border-outline-variant/20 font-body-md text-body-md text-on-surface-variant"><?= htmlspecialchars((string) $r['check_in']) ?></td>
              <td class="py-3 px-6 border-b border-outline-variant/20 font-body-md text-body-md text-on-surface-variant"><?= htmlspecialchars((string) $r['check_out']) ?></td>
              <td class="py-3 px-6 border-b border-outline-variant/20 font-body-md text-body-md text-on-surface-variant"><?= htmlspecialchars((string) $r['hours']) ?> hrs</td>
              <td class="py-3 px-6 border-b border-outline-variant/20">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-<?= htmlspecialchars($meta['color']) ?>/10 text-<?= htmlspecialchars($meta['color']) ?> font-label-md text-label-md"><?= htmlspecialchars($meta['label']) ?></span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
