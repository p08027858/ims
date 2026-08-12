<?php
/**
 * Student's own daily log write form. Adapted (with fields made editable and the
 * supervisor-only review panel removed) from design-reference/_5, which was actually the
 * COMPANY's read-only review of this same document — kept the same card layout/tokens so
 * the two screens feel like one continuous document. See company/daily_log_review.php for
 * the reviewer counterpart. Wired to App\Controllers\DailyLogController (Phase 6) —
 * POST /student/daily-logs (multipart/form-data for attachments) + auto-loads today's
 * existing draft/revision_requested log for editing (RULE-LOG-01: one per day).
 */
$noActiveInternship = $noActiveInternship ?? false;
$locked = $locked ?? false;
$log = $log ?? ['log_date' => date('j F Y', strtotime('+543 years')), 'work_description' => '', 'learning_outcome' => '', 'problem_found' => '', 'status' => null, 'reviewer_comment' => null];
$formError = $formError ?? null;
$editId = $editId ?? null;
$formAction = $editId !== null ? '/student/daily-logs/' . $editId . '/edit' : '/student/daily-logs';
?>
<div class="max-w-3xl mx-auto flex flex-col gap-6">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode">บันทึกงานประจำวัน</h1>
      <p class="font-body-md text-body-md text-on-surface-variant"><?= htmlspecialchars($log['log_date']) ?></p>
    </div>
    <span id="autosave-indicator" class="font-metadata text-metadata text-on-surface-variant flex items-center gap-1 opacity-0 transition-opacity">
      <span class="material-symbols-outlined text-[16px] animate-spin">sync</span> กำลังบันทึก...
    </span>
  </div>

  <?php if ($formError): ?>
    <div class="bg-error-container text-on-error-container rounded-lg p-4 font-body-md text-body-md" role="alert"><?= htmlspecialchars($formError) ?></div>
  <?php endif; ?>

  <?php if ($noActiveInternship): ?>
    <div class="flex flex-col items-center justify-center py-16 text-center bg-surface-container-lowest dark:bg-surface-dark rounded-xl border border-surface-variant dark:border-outline-variant/20">
      <span class="material-symbols-outlined text-5xl text-outline-variant mb-3">work_off</span>
      <p class="font-body-md text-body-md text-on-surface-variant">ไม่พบการฝึกงานที่กำลังดำเนินอยู่ของคุณในขณะนี้</p>
    </div>
  <?php elseif ($locked): ?>
    <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-[24px] shadow-soft p-8 flex flex-col gap-6 border border-surface-variant dark:border-outline-variant/20">
      <div class="flex items-center gap-3">
        <span class="px-3 py-1 rounded-full font-label-md text-label-md <?= $log['status'] === 'reviewed' ? 'bg-status-success/10 text-status-success' : 'bg-status-warning/10 text-status-warning' ?>">
          <?= $log['status'] === 'reviewed' ? 'ตรวจแล้ว' : 'รอการตรวจสอบ' ?>
        </span>
      </div>
      <div class="flex flex-col gap-2">
        <h3 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode">งานที่ทำวันนี้</h3>
        <p class="font-body-md text-body-md text-on-surface-variant whitespace-pre-line"><?= htmlspecialchars($log['work_description']) ?></p>
      </div>
      <?php if ($log['reviewer_comment']): ?>
        <div class="bg-secondary-fixed-dim/20 p-5 rounded-2xl border-l-4 border-secondary">
          <p class="font-body-md text-body-md text-on-surface-variant">ความเห็นผู้ตรวจ: "<?= htmlspecialchars($log['reviewer_comment']) ?>"</p>
        </div>
      <?php endif; ?>
      <p class="font-metadata text-metadata text-on-surface-variant">บันทึกที่ส่งแล้วไม่สามารถแก้ไขได้ (RULE-LOG-02) — รอผู้ตรวจตีกลับเป็น "ขอแก้ไข" หากต้องการแก้</p>
    </div>
  <?php else: ?>
    <form id="daily-log-form" method="post" action="<?= htmlspecialchars($formAction) ?>" enctype="multipart/form-data" class="bg-surface-container-lowest dark:bg-surface-dark rounded-[24px] shadow-soft p-8 flex flex-col gap-8 border border-surface-variant dark:border-outline-variant/20">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
      <?php if ($log['status'] === 'revision_requested'): ?>
        <div class="bg-status-warning/10 rounded-xl p-4 flex items-start gap-3">
          <span class="material-symbols-outlined text-status-warning">edit_note</span>
          <div class="flex flex-col gap-1">
            <span class="font-label-md text-label-md text-status-warning">ผู้ตรวจขอให้แก้ไข</span>
            <?php if ($log['reviewer_comment']): ?><span class="font-body-md text-metadata text-status-warning/80">"<?= htmlspecialchars($log['reviewer_comment']) ?>"</span><?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="flex flex-col gap-3">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-primary-container flex items-center justify-center"><span class="material-symbols-outlined text-on-primary-container">task_alt</span></div>
          <label class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode" for="work_description">งานที่ทำวันนี้ <span class="text-error">*</span></label>
        </div>
        <textarea id="work_description" name="work_description" required rows="5"
                  class="bg-surface dark:bg-surface-container-high/10 rounded-2xl p-5 font-body-md text-body-md text-on-surface dark:text-text-dark-mode resize-none focus:outline-none focus:ring-2 focus:ring-primary/40 shadow-inner"
                  placeholder="อธิบายงานที่ได้รับมอบหมายและลงมือทำในวันนี้..."><?= htmlspecialchars($log['work_description']) ?></textarea>
      </div>

      <div class="flex flex-col gap-3">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-secondary-container flex items-center justify-center"><span class="material-symbols-outlined text-on-secondary-container">lightbulb</span></div>
          <label class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode" for="learning_outcome">สิ่งที่ได้เรียนรู้</label>
        </div>
        <textarea id="learning_outcome" name="learning_outcome" rows="4"
                  class="bg-surface dark:bg-surface-container-high/10 rounded-2xl p-5 font-body-md text-body-md text-on-surface dark:text-text-dark-mode resize-none focus:outline-none focus:ring-2 focus:ring-primary/40 shadow-inner"
                  placeholder="ทักษะ ความรู้ หรือประสบการณ์ใหม่ที่ได้รับวันนี้..."><?= htmlspecialchars($log['learning_outcome']) ?></textarea>
      </div>

      <div class="flex flex-col gap-3">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-tertiary-container/20 flex items-center justify-center"><span class="material-symbols-outlined text-tertiary">report_problem</span></div>
          <label class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode" for="problem_found">ปัญหา/อุปสรรคที่พบ</label>
        </div>
        <textarea id="problem_found" name="problem_found" rows="3"
                  class="bg-surface dark:bg-surface-container-high/10 rounded-2xl p-5 font-body-md text-body-md text-on-surface dark:text-text-dark-mode resize-none focus:outline-none focus:ring-2 focus:ring-primary/40 shadow-inner"
                  placeholder="ปัญหาที่พบระหว่างวัน (ถ้ามี)..."><?= htmlspecialchars($log['problem_found']) ?></textarea>
      </div>

      <div class="flex flex-col gap-3">
        <label class="font-label-md text-label-md text-on-surface-variant">แนบไฟล์ (สูงสุด 1MB/ไฟล์)</label>
        <label for="log-file-upload" class="flex flex-col items-center justify-center p-8 border-2 border-dashed border-outline-variant rounded-xl bg-surface dark:bg-surface-container-high/10 hover:bg-surface-container-low transition-colors cursor-pointer">
          <span class="material-symbols-outlined text-primary text-3xl mb-2">upload_file</span>
          <span class="font-label-md text-label-md text-primary">แตะเพื่อเลือกไฟล์</span>
          <span class="font-metadata text-metadata text-on-surface-variant mt-1">รองรับ JPG, PNG, PDF</span>
          <input id="log-file-upload" name="attachments[]" type="file" accept="image/jpeg,image/png,application/pdf" multiple class="hidden"/>
        </label>
      </div>

      <div class="flex items-center justify-end gap-4">
        <button type="submit" name="action" value="draft" class="px-8 py-4 rounded-2xl bg-surface-container text-on-surface-variant font-headline-md text-label-md hover:bg-surface-container-high transition-colors active:scale-95">
          บันทึกร่าง
        </button>
        <button type="submit" name="action" value="submit" class="px-10 py-4 rounded-2xl bg-primary text-on-primary font-headline-md text-label-md shadow-lg hover:shadow-xl transition-all active:scale-95 flex items-center gap-2">
          <span class="material-symbols-outlined">send</span> ส่งบันทึกนี้
        </button>
      </div>
    </form>
  <?php endif; ?>
</div>
<script>
  // TODO Phase 6 nice-to-have: replace with real autosave PUT request; this only demonstrates
  // the UI state required by MASTER_SPEC.md §9.3 (autosave every 10-15s while typing) — not
  // wired to the backend yet, form still requires an explicit "บันทึกร่าง"/"ส่งบันทึกนี้" click.
  let autosaveTimer;
  document.getElementById('daily-log-form')?.addEventListener('input', () => {
    clearTimeout(autosaveTimer);
    autosaveTimer = setTimeout(() => {
      const el = document.getElementById('autosave-indicator');
      el.classList.remove('opacity-0');
      setTimeout(() => el.classList.add('opacity-0'), 1500);
    }, 1200);
  });
</script>
