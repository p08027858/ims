<?php
/**
 * Supervisor review of a student's daily log. Adapted from design-reference/_5. Wired to
 * App\Controllers\DailyLogController (Phase 6) — this page always shows the OLDEST still-
 * `submitted` log in the reviewer's scope (company's own internships, or teacher's assigned
 * ones — PERMISSIONS.md §2.6 grants both), one at a time; reviewing it reloads to the next.
 */
$student = $student ?? null;
$log = $log ?? null;
$flashResult = $flashResult ?? null;
?>
<?php if ($flashResult !== null): ?>
  <div class="max-w-[1400px] mx-auto mb-6 rounded-xl p-4 font-body-md text-body-md <?= ($flashResult['type'] ?? '') === 'success' ? 'bg-status-success/10 text-status-success' : 'bg-error-container text-on-error-container' ?>" role="status">
    <?= htmlspecialchars((string) ($flashResult['message'] ?? '')) ?>
  </div>
<?php endif; ?>
<?php if ($student === null || $log === null): ?>
  <div class="flex flex-col items-center justify-center py-16 text-center max-w-lg mx-auto">
    <span class="material-symbols-outlined text-5xl text-status-success mb-3">task_alt</span>
    <p class="font-body-md text-body-md text-on-surface-variant">ไม่มีบันทึกงานที่รอตรวจสอบในขณะนี้</p>
  </div>
<?php else: ?>
<div class="max-w-[1400px] mx-auto flex flex-col gap-8">
  <div class="flex items-end justify-between gap-4 flex-wrap">
    <div class="flex gap-4 items-center">
      <div class="w-16 h-16 rounded-full bg-surface-variant flex items-center justify-center text-on-surface-variant font-headline-lg shrink-0"><?= htmlspecialchars(mb_substr($student['name'], 0, 1)) ?></div>
      <div>
        <div class="flex items-center gap-3 mb-1">
          <div class="px-3 py-1 bg-surface-variant rounded-full flex items-center gap-2"><div class="w-2 h-2 rounded-full bg-status-warning animate-pulse"></div><span class="text-on-surface-variant font-label-md text-label-md">รอการตรวจสอบ</span></div>
          <span class="text-on-surface-variant font-metadata text-metadata">ส่งเมื่อ <?= htmlspecialchars($log['submitted_at']) ?></span>
        </div>
        <h1 class="font-display-metrics text-[32px] leading-tight text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($student['name']) ?></h1>
        <h2 class="font-headline-md text-headline-md text-primary">บันทึกประจำวัน: <?= htmlspecialchars($log['date']) ?></h2>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    <div class="lg:col-span-8 flex flex-col gap-8">
      <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-[24px] shadow-soft p-8 flex flex-col gap-8 border border-surface-variant dark:border-outline-variant/20">
        <div class="flex flex-col gap-4">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-primary-container flex items-center justify-center"><span class="material-symbols-outlined text-on-primary-container text-[24px]">task_alt</span></div>
            <h3 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode">งานที่ทำวันนี้</h3>
          </div>
          <div class="bg-surface dark:bg-surface-container-high/10 rounded-2xl p-6 font-body-lg text-body-lg text-on-surface-variant leading-relaxed whitespace-pre-line"><?= htmlspecialchars($log['work_description']) ?></div>
        </div>
        <div class="flex flex-col gap-4">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-secondary-container flex items-center justify-center"><span class="material-symbols-outlined text-on-secondary-container text-[24px]">lightbulb</span></div>
            <h3 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode">สิ่งที่ได้เรียนรู้</h3>
          </div>
          <div class="bg-secondary-fixed-dim/20 p-6 rounded-2xl border-l-4 border-secondary">
            <p class="font-body-lg text-body-lg text-on-surface-variant leading-relaxed">"<?= htmlspecialchars($log['learning_outcome']) ?>"</p>
          </div>
        </div>
      </div>

      <form method="post" action="/company/daily-logs/review" class="bg-surface-container-high dark:bg-surface-container-high/10 rounded-[24px] p-8 shadow-md flex flex-col gap-6">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
        <input type="hidden" name="daily_log_id" value="<?= (int) $log['id'] ?>"/>
        <h3 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode flex items-center gap-3"><span class="material-symbols-outlined text-primary text-[28px]">rate_review</span> ความเห็นจากผู้ดูแล</h3>
        <textarea name="reviewer_comment" class="w-full h-40 bg-surface-container-lowest dark:bg-surface-dark rounded-2xl p-6 font-body-md text-body-md text-on-surface dark:text-text-dark-mode placeholder:text-outline focus:outline-none shadow-inner resize-none focus:ring-2 focus:ring-primary/40" placeholder="พิมพ์ข้อเสนอแนะ คำชม หรือสิ่งที่ต้องปรับปรุงสำหรับนักศึกษาที่นี่..."></textarea>
        <div class="flex items-center justify-end gap-4">
          <button type="submit" name="status" value="revision_requested" class="px-8 py-4 rounded-2xl bg-status-warning/15 text-[#9a6300] font-headline-md text-label-md flex items-center gap-3 hover:bg-status-warning/25 transition-colors active:scale-95">
            <span class="material-symbols-outlined text-[24px]">edit_note</span> ให้แก้ไขใหม่
          </button>
          <button type="submit" name="status" value="reviewed" id="approve-btn" class="px-10 py-4 rounded-2xl bg-status-success text-surface-container-lowest font-headline-md text-label-md flex items-center gap-3 hover:opacity-90 shadow-lg transition-all active:scale-95">
            <span class="material-symbols-outlined text-[24px]">verified</span> ตรวจแล้ว / ผ่าน
          </button>
        </div>
      </form>
    </div>

    <div class="lg:col-span-4 flex flex-col gap-8">
      <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-[24px] shadow-soft p-6 flex flex-col gap-5">
        <div class="flex items-center justify-between">
          <h4 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode flex items-center gap-2"><span class="material-symbols-outlined text-primary">pin_drop</span> สถานที่ปฏิบัติงาน</h4>
          <div class="px-3 py-1 bg-surface-variant rounded-lg"><span class="font-label-md text-label-md text-on-surface-variant">On-site</span></div>
        </div>
        <div class="w-full h-48 rounded-2xl bg-gradient-to-br from-primary/10 to-secondary/10 relative overflow-hidden">
          <div class="absolute bottom-3 left-3 right-3 bg-surface-container-lowest/95 backdrop-blur-md px-4 py-3 rounded-xl shadow-lg flex items-center justify-between">
            <div class="flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full bg-status-success animate-pulse"></div><span class="font-label-md text-label-md text-on-surface">GPS Verified</span></div>
            <span class="font-metadata text-metadata text-on-surface-variant">ความแม่นยำ: <?= $log['gps_accuracy_m'] ?>m</span>
          </div>
        </div>
        <div class="flex justify-between items-center px-2 py-2 bg-surface dark:bg-surface-container-high/10 rounded-xl">
          <div class="flex flex-col text-center flex-1"><span class="font-metadata text-metadata text-on-surface-variant mb-1 uppercase">Check-in</span><span class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode"><?= $log['check_in'] ?></span></div>
          <div class="w-px h-8 bg-surface-variant"></div>
          <div class="flex flex-col text-center flex-1"><span class="font-metadata text-metadata text-on-surface-variant mb-1 uppercase">Check-out</span><span class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode"><?= $log['check_out'] ?></span></div>
        </div>
      </div>

      <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-[24px] shadow-soft p-6 flex flex-col gap-5">
        <h4 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode flex items-center gap-2"><span class="material-symbols-outlined text-primary">attachment</span> ไฟล์แนบ (<?= count($log['attachments']) ?>)</h4>
        <div class="grid grid-cols-2 gap-4">
          <?php foreach ($log['attachments'] as $file): ?>
            <div class="aspect-square rounded-xl bg-surface-variant flex flex-col items-center justify-center gap-1 shadow-sm">
              <span class="material-symbols-outlined text-on-surface-variant text-[32px]">description</span>
              <span class="font-metadata text-metadata text-on-surface-variant px-2 text-center line-clamp-1"><?= htmlspecialchars($file['name']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  // Small celebratory confetti burst on approve, matching STITCH_DESIGN_PROMPTS.md §9.5.
  document.getElementById('approve-btn')?.addEventListener('click', function (e) {
    const btn = this;
    const colors = ['bg-primary', 'bg-secondary', 'bg-status-success', 'bg-status-warning'];
    for (let i = 0; i < 24; i++) {
      const conf = document.createElement('div');
      conf.className = 'fixed w-2 h-2 rounded-full pointer-events-none z-50 ' + colors[Math.floor(Math.random() * colors.length)];
      const rect = btn.getBoundingClientRect();
      conf.style.left = (rect.left + rect.width / 2) + 'px';
      conf.style.top = (rect.top + rect.height / 2) + 'px';
      document.body.appendChild(conf);
      const angle = Math.random() * Math.PI * 2, v = 50 + Math.random() * 100;
      conf.animate([{ transform: 'translate(0,0)', opacity: 1 }, { transform: `translate(${Math.cos(angle) * v}px, ${Math.sin(angle) * v - 50}px)`, opacity: 0 }],
        { duration: 700, easing: 'cubic-bezier(0.25,1,0.5,1)' }).onfinish = () => conf.remove();
    }
  });
</script>
<?php endif; ?>
