<?php
/**
 * Reports summary for teacher's advisees. Adapted from design-reference/_11. Wired to
 * App\Controllers\ReportController::teacherReportData() (Phase 11) — never had real data before.
 * Export PDF opens a print-optimized page (Ctrl+P → Save as PDF); Export Excel downloads a CSV
 * (Excel opens it natively) — see ReportController's docblock for why there's no PDF/XLSX library.
 */
$rows = $rows ?? [
    ['name' => 'สมชาย ใจดี', 'company' => 'บ.เอบีซี จำกัด', 'hours' => 210, 'avg_score' => 18.5, 'status' => 'active'],
    ['name' => 'สมหญิง ดีใจ', 'company' => 'บ.ดีอีเอฟ จำกัด', 'hours' => 180, 'avg_score' => 17.0, 'status' => 'active'],
];
$statusLabel = ['active' => ['ฝึกงานอยู่', 'primary'], 'completed' => ['เสร็จสิ้น', 'status-success'], 'terminated' => ['ยุติ', 'status-error']];
?>
<div class="flex flex-col gap-6">
  <div class="flex items-start justify-between flex-wrap gap-4">
    <div>
      <h1 class="font-display-metrics text-[32px] text-on-surface dark:text-text-dark-mode">รายงานสรุปผลการฝึกงาน</h1>
      <p class="font-body-lg text-body-lg text-on-surface-variant max-w-2xl mt-1">ภาพรวมผลการปฏิบัติงานของนักศึกษาในความดูแล สามารถส่งออกข้อมูลเพื่อนำไปประมวลผลต่อได้</p>
    </div>
    <div class="flex gap-3">
      <a href="/teacher/reports/export-pdf" target="_blank" class="px-5 py-3 rounded-xl bg-surface-container dark:bg-surface-container-high/10 text-on-surface-variant font-label-md text-label-md flex items-center gap-2 hover:bg-surface-container-high transition-colors">
        <span class="material-symbols-outlined text-[20px]">picture_as_pdf</span> Export PDF
      </a>
      <a href="/teacher/reports/export-csv" class="px-5 py-3 rounded-xl bg-surface-container dark:bg-surface-container-high/10 text-on-surface-variant font-label-md text-label-md flex items-center gap-2 hover:bg-surface-container-high transition-colors">
        <span class="material-symbols-outlined text-[20px]">grid_on</span> Export Excel
      </a>
    </div>
  </div>

  <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl shadow-soft border border-surface-variant dark:border-outline-variant/20 overflow-x-auto">
    <table class="w-full text-left border-collapse min-w-[600px]">
      <thead>
        <tr class="bg-surface-container/50 dark:bg-surface-container-high/10">
          <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant">ชื่อ</th>
          <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant">บริษัท</th>
          <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant">ชม.สะสม</th>
          <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant">คะแนนเฉลี่ย</th>
          <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant">สถานะ</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): [$label, $color] = $statusLabel[$r['status']]; ?>
          <tr class="hover:bg-surface-container-low dark:hover:bg-surface-container/10 transition-colors">
            <td class="py-3 px-6 border-b border-outline-variant/20 font-body-md text-body-md text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($r['name']) ?></td>
            <td class="py-3 px-6 border-b border-outline-variant/20 font-body-md text-body-md text-on-surface-variant"><?= htmlspecialchars($r['company']) ?></td>
            <td class="py-3 px-6 border-b border-outline-variant/20 font-body-md text-body-md text-on-surface-variant"><?= $r['hours'] ?> ชม.</td>
            <td class="py-3 px-6 border-b border-outline-variant/20 font-body-md text-body-md text-on-surface-variant"><?= $r['avg_score'] ?>/20</td>
            <td class="py-3 px-6 border-b border-outline-variant/20"><span class="inline-flex px-2.5 py-1 rounded-full bg-<?= $color ?>/10 text-<?= $color ?> font-label-md text-label-md"><?= $label ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
