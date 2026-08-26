<?php
/**
 * List of accepted applications not yet turned into a confirmed internship. New page (not part
 * of the 27 Stitch exports - admin/matching.php itself, the per-application form, WAS one of
 * them). Wired to App\Controllers\InternshipController::matchingListData().
 */
$unmatched = $unmatched ?? [];
?>
<div class="flex flex-col gap-6">
  <div>
    <h1 class="font-display-metrics text-[32px] text-on-surface dark:text-text-dark-mode mb-2">Teacher Matching</h1>
    <p class="font-body-md text-body-md text-on-surface-variant">Accepted applications are waiting for Admin to assign a supervisor and create the internship.</p>
  </div>

  <?php if (empty($unmatched)): ?>
    <div class="flex flex-col items-center justify-center py-16 text-center">
      <span class="material-symbols-outlined text-6xl text-status-success mb-4">task_alt</span>
      <h3 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode mb-2">No pending matches</h3>
      <p class="font-body-md text-body-md text-on-surface-variant max-w-sm">Every accepted application has already been matched to a supervisor.</p>
    </div>
  <?php else: ?>
    <div class="flex flex-col gap-3">
      <?php foreach ($unmatched as $m): ?>
        <?php
        $applicationId = (int) ($m['application_id'] ?? 0);
        $studentName = (string) ($m['student_name'] ?? '-');
        $studentCode = (string) ($m['student_code'] ?? '-');
        $departmentName = (string) ($m['department_name'] ?? '-');
        $companyName = (string) ($m['company_name'] ?? '-');
        $positionTitle = (string) ($m['position_title'] ?? '-');
        ?>
        <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-5 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex items-center justify-between gap-4 flex-wrap">
          <div>
            <p class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($studentName) ?> <span class="font-metadata text-metadata text-on-surface-variant">(<?= htmlspecialchars($studentCode) ?>)</span></p>
            <p class="font-metadata text-metadata text-on-surface-variant"><?= htmlspecialchars($departmentName) ?> -> <?= htmlspecialchars($companyName) ?> | <?= htmlspecialchars($positionTitle) ?></p>
          </div>
          <a href="/admin/matching/<?= $applicationId ?>" class="px-4 py-2 rounded-lg bg-primary text-on-primary font-label-md text-label-md flex items-center gap-1">
            <span class="material-symbols-outlined text-[18px]">link</span> Match
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
