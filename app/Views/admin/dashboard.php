<?php
/**
 * Admin dashboard. Adapted from design-reference/_12 ("สถานะนักศึกษาฝึกงาน").
 * TODO Phase 8: wire to GET /admin/reports/summary (API_SPEC.md §11).
 */
$faculty = $faculty ?? 'คณะวิศวกรรมศาสตร์';
$pending = $pending ?? ['users' => 12, 'companies' => 3, 'matching' => 8];
$batchSummary = $batchSummary ?? ['active' => 96, 'completed' => 8, 'terminated' => 2, 'withdrawn' => 4];
$total = array_sum($batchSummary);
?>
<div class="flex flex-col gap-6">
  <div>
    <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode">แดชบอร์ดผู้ดูแลระบบ</h1>
    <p class="font-body-md text-body-md text-on-surface-variant"><?= htmlspecialchars($faculty) ?></p>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <a href="/admin/users" class="bg-status-warning/10 rounded-2xl p-5 flex items-center justify-between hover:bg-status-warning/20 transition-colors">
      <div><p class="font-label-md text-label-md text-on-surface-variant mb-1">รออนุมัติบัญชี</p><h3 class="font-display-metrics text-display-metrics text-status-warning"><?= $pending['users'] ?></h3></div>
      <span class="material-symbols-outlined text-status-warning text-4xl">how_to_reg</span>
    </a>
    <a href="/admin/companies" class="bg-status-warning/10 rounded-2xl p-5 flex items-center justify-between hover:bg-status-warning/20 transition-colors">
      <div><p class="font-label-md text-label-md text-on-surface-variant mb-1">รออนุมัติบริษัท</p><h3 class="font-display-metrics text-display-metrics text-status-warning"><?= $pending['companies'] ?></h3></div>
      <span class="material-symbols-outlined text-status-warning text-4xl">apartment</span>
    </a>
    <a href="/admin/matching" class="bg-primary-container/10 rounded-2xl p-5 flex items-center justify-between hover:bg-primary-container/20 transition-colors">
      <div><p class="font-label-md text-label-md text-on-surface-variant mb-1">รอจับคู่ครูนิเทศ</p><h3 class="font-display-metrics text-display-metrics text-primary"><?= $pending['matching'] ?></h3></div>
      <span class="material-symbols-outlined text-primary text-4xl">link</span>
    </a>
  </div>

  <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-2xl shadow-soft p-6 border border-surface-variant dark:border-outline-variant/20">
    <h2 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode mb-4">สถานะนักศึกษาฝึกงาน (รอบปัจจุบัน)</h2>
    <div class="flex flex-col gap-3">
      <?php
      $meta = ['active' => ['กำลังฝึกงาน', 'primary'], 'completed' => ['เสร็จสิ้น', 'status-success'], 'terminated' => ['ยุติ', 'status-error'], 'withdrawn' => ['ถอนตัว', 'status-inactive']];
      foreach ($batchSummary as $key => $count): [$label, $color] = $meta[$key]; $pct = $total ? round($count / $total * 100) : 0; ?>
        <div class="flex items-center gap-4">
          <span class="w-28 font-label-md text-label-md text-on-surface-variant shrink-0"><?= $label ?></span>
          <div class="flex-1 bg-surface-variant rounded-full h-3 overflow-hidden"><div class="bg-<?= $color ?> h-full rounded-full" style="width:<?= $pct ?>%"></div></div>
          <span class="w-16 text-right font-label-md text-label-md text-on-surface dark:text-text-dark-mode"><?= $count ?> คน</span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
