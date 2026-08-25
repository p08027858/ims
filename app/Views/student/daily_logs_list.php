<?php
/**
 * Daily Logs List View
 */
$logs = $dailyLogs ?? $logs ?? $items ?? [];
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
  <div class="flex items-center justify-between mb-8">
    <div>
      <h1 class="text-2xl font-bold text-slate-800">บันทึกงานประจำวัน</h1>
      <p class="text-sm text-slate-500 mt-1">ประวัติบันทึกการปฏิบัติงานทั้งหมด</p>
    </div>
    <a href="/student/daily-logs/new" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-indigo-100 transition-transform active:scale-[0.98]">
      <span class="material-symbols-outlined text-[18px]">add</span> บันทึกวันนี้
    </a>
  </div>

  <?php if (empty($logs)): ?>
    <div class="flex flex-col items-center justify-center py-20 bg-white rounded-2xl border border-slate-100 shadow-sm text-center">
      <div class="w-16 h-16 bg-slate-50 text-slate-400 rounded-full flex items-center justify-center mb-3">
        <span class="material-symbols-outlined text-3xl">description</span>
      </div>
      <p class="text-base font-semibold text-slate-700">ยังไม่มีบันทึกงาน</p>
      <p class="text-xs text-slate-400 mt-1 mb-4">เริ่มต้นเขียนบันทึกการปฏิบัติงานประจำวันของคุณ</p>
      <a href="/student/daily-logs/new" class="px-4 py-2 bg-indigo-50 text-indigo-600 text-xs font-semibold rounded-lg hover:bg-indigo-100">เขียนบันทึกแรก</a>
    </div>
  <?php else: ?>
    <div class="space-y-4">
      <?php foreach ($logs as $log): ?>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 flex flex-col sm:flex-row gap-6 items-start">
          <?php if (!empty($log['photo_url'])): ?>
            <img src="<?= htmlspecialchars($log['photo_url']) ?>" alt="รูปภาพการทำงาน" class="w-full sm:w-36 h-28 object-cover rounded-xl border border-slate-100 shrink-0">
          <?php endif; ?>
          
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2 mb-2">
              <span class="inline-flex items-center text-xs font-medium text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-lg">
                <span class="material-symbols-outlined text-[14px] mr-1">event</span>
                <?= htmlspecialchars(date('d/m/Y', strtotime($log['log_date'] ?? 'now'))) ?>
              </span>
              <span class="text-xs font-medium px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-600">ส่งแล้ว</span>
            </div>
            
            <h3 class="text-base font-bold text-slate-800 mb-1 truncate"><?= htmlspecialchars($log['title'] ?? 'บันทึกงาน') ?></h3>
            <p class="text-sm text-slate-600 leading-relaxed mb-3"><?= nl2br(htmlspecialchars($log['activity_description'] ?? $log['tasks_performed'] ?? '')) ?></p>
            
            <?php if (!empty($log['problems_encountered']) || !empty($log['learning_outcomes'])): ?>
              <div class="pt-3 border-t border-slate-100 flex flex-wrap gap-4 text-xs text-slate-500">
                <?php if (!empty($log['problems_encountered'])): ?>
                  <div><span class="font-semibold text-rose-500">ปัญหา:</span> <?= htmlspecialchars($log['problems_encountered']) ?></div>
                <?php endif; ?>
                <?php if (!empty($log['learning_outcomes'])): ?>
                  <div><span class="font-semibold text-emerald-600">สิ่งที่ได้เรียนรู้:</span> <?= htmlspecialchars($log['learning_outcomes']) ?></div>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>