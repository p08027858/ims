<?php
/**
 * Company/supervisor dashboard. Adapted from design-reference/_4 (the most complete of two
 * exported variants — _8 was a duplicate card-list layout, kept only in design-reference/ as
 * a style reference, not converted to its own route).
 * TODO Phase 3: wire to GET /companies/{id}/students (API_SPEC.md §3).
 */
$company = $company ?? ['name' => 'TechNova Solutions Co., Ltd.'];
$stats = $stats ?? ['checked_in_today' => 6, 'total_students' => 8, 'pending_evaluations' => 3, 'pending_leave' => 1];
$students = $students ?? [
    ['name' => 'ณัฐวุฒิ ใจดี', 'position' => 'Software Engineering Intern', 'university' => 'ม.เทคโนโลยีพระจอมเกล้าฯ', 'check_in' => '08:45', 'status' => 'present'],
    ['name' => 'พิมดาว นภา', 'position' => 'UX/UI Design Intern', 'university' => 'จุฬาลงกรณ์มหาวิทยาลัย', 'check_in' => '09:15', 'status' => 'late'],
    ['name' => 'กฤติน ชาญชัย', 'position' => 'Data Analyst Intern', 'university' => 'ม.ธรรมศาสตร์', 'check_in' => '--:--', 'status' => 'absent'],
    ['name' => 'ศิริชัย มั่นคง', 'position' => 'Marketing Intern', 'university' => 'ม.เกษตรศาสตร์', 'check_in' => '08:30', 'status' => 'present'],
];
$statusDot = ['present' => 'status-success', 'late' => 'status-warning', 'absent' => 'status-inactive'];
?>
<div class="flex flex-col gap-6 lg:gap-8">
  <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-surface-container-low dark:bg-surface-container-high/10 p-6 rounded-2xl shadow-sm">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-primary-container/20 flex items-center justify-center"><span class="material-symbols-outlined text-primary text-[24px]">corporate_fare</span></div>
      <div>
        <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($company['name']) ?></h1>
        <p class="font-body-md text-body-md text-on-surface-variant flex items-center gap-2">
          <span>นักศึกษาในความดูแล <?= $stats['total_students'] ?> คน</span>
          <span class="relative flex h-3 w-3"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-status-success opacity-75"></span><span class="relative inline-flex rounded-full h-3 w-3 bg-status-success"></span></span>
          <span class="text-status-success font-label-md text-label-md">Live Status</span>
        </p>
      </div>
    </div>
    <a href="/company/applications" class="flex items-center justify-center gap-2 px-6 py-3 bg-primary text-on-primary rounded-xl font-label-md text-label-md shadow-md hover:shadow-lg transition-all active:scale-95">
      <span class="material-symbols-outlined text-[20px]">assignment_ind</span> ดูใบสมัคร
    </a>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 lg:gap-6">
    <div class="bg-surface-container dark:bg-surface-container-high/10 p-5 rounded-2xl flex items-start gap-4">
      <div class="w-12 h-12 rounded-full bg-primary-container/10 flex items-center justify-center shrink-0"><span class="material-symbols-outlined text-primary text-[28px] fill">how_to_reg</span></div>
      <div class="flex-1">
        <p class="font-label-md text-label-md text-on-surface-variant mb-1">เข้างานวันนี้</p>
        <h3 class="font-display-metrics text-display-metrics text-on-surface dark:text-text-dark-mode"><?= $stats['checked_in_today'] ?><span class="text-on-surface-variant font-headline-md text-headline-md">/<?= $stats['total_students'] ?></span></h3>
      </div>
    </div>
    <div class="bg-surface-container dark:bg-surface-container-high/10 p-5 rounded-2xl flex items-start gap-4">
      <div class="w-12 h-12 rounded-full bg-status-warning/10 flex items-center justify-center shrink-0"><span class="material-symbols-outlined text-status-warning text-[28px] fill">pending_actions</span></div>
      <div class="flex-1">
        <p class="font-label-md text-label-md text-on-surface-variant mb-1">รอประเมินสัปดาห์นี้</p>
        <h3 class="font-display-metrics text-display-metrics text-on-surface dark:text-text-dark-mode"><?= $stats['pending_evaluations'] ?></h3>
      </div>
    </div>
    <div class="bg-surface-container dark:bg-surface-container-high/10 p-5 rounded-2xl flex items-start gap-4">
      <div class="w-12 h-12 rounded-full bg-status-error/10 flex items-center justify-center shrink-0"><span class="material-symbols-outlined text-status-error text-[28px] fill">event_busy</span></div>
      <div class="flex-1">
        <p class="font-label-md text-label-md text-on-surface-variant mb-1">คำขอลาค้างอนุมัติ</p>
        <h3 class="font-display-metrics text-display-metrics text-on-surface dark:text-text-dark-mode"><?= $stats['pending_leave'] ?></h3>
      </div>
    </div>
  </div>

  <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-2xl shadow-sm">
    <div class="p-4 lg:p-6 flex flex-col sm:flex-row justify-between items-center gap-4">
      <h2 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode self-start sm:self-auto">รายชื่อนักศึกษา</h2>
      <div class="relative w-full sm:w-64">
        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px]">search</span>
        <input class="w-full pl-10 pr-4 py-2.5 bg-surface-container dark:bg-surface-container-high/10 rounded-xl font-body-md text-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:ring-2 focus:ring-primary/50" placeholder="ค้นหาชื่อ..." type="text"/>
      </div>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse min-w-[700px]">
        <thead>
          <tr class="bg-surface-container/50 dark:bg-surface-container-high/10">
            <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant w-24">สถานะ</th>
            <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant">นักศึกษา</th>
            <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant">มหาวิทยาลัย</th>
            <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant">เวลาเข้างาน</th>
            <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant text-right">จัดการ</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $s): $dot = $statusDot[$s['status']]; ?>
            <tr class="hover:bg-surface-container-low dark:hover:bg-surface-container/10 transition-colors">
              <td class="py-4 px-6 border-b border-outline-variant/20">
                <div class="flex justify-center items-center w-8 h-8 rounded-full bg-<?= $dot ?>/10">
                  <span class="relative inline-flex rounded-full h-3 w-3 bg-<?= $dot ?>"></span>
                </div>
              </td>
              <td class="py-4 px-6 border-b border-outline-variant/20">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-surface-variant flex items-center justify-center text-on-surface-variant font-headline-md shrink-0"><?= htmlspecialchars(mb_substr($s['name'], 0, 1)) ?></div>
                  <div><p class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($s['name']) ?></p><p class="font-metadata text-metadata text-on-surface-variant"><?= htmlspecialchars($s['position']) ?></p></div>
                </div>
              </td>
              <td class="py-4 px-6 border-b border-outline-variant/20"><p class="font-body-md text-body-md text-on-surface-variant truncate"><?= htmlspecialchars($s['university']) ?></p></td>
              <td class="py-4 px-6 border-b border-outline-variant/20">
                <div class="inline-flex items-center px-2.5 py-1 rounded-full bg-<?= $dot ?>/10 text-<?= $dot ?> font-label-md text-label-md"><?= htmlspecialchars($s['check_in']) ?></div>
              </td>
              <td class="py-4 px-6 border-b border-outline-variant/20 text-right">
                <a class="inline-flex items-center justify-center gap-1 px-4 py-2 rounded-lg text-primary hover:bg-primary/10 font-label-md text-label-md transition-colors" href="#">ดูโปรไฟล์ <span class="material-symbols-outlined text-[18px]">chevron_right</span></a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
