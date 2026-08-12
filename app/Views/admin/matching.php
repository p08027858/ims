<?php
/**
 * Student-company-teacher matching. Adapted from design-reference/_16
 * ("จับคู่ครูนิเทศ" / success: "จับคู่สำเร็จ!"). Wired to
 * App\Controllers\InternshipController (Phase 4) — GET/POST /admin/matching/{applicationId}.
 */
$applicationId = $applicationId ?? 0;
$student = $student ?? ['name' => 'นายสมชาย ใจดี', 'code' => '64010123', 'department' => 'วิศวกรรมคอมพิวเตอร์'];
$company = $company ?? ['name' => 'บริษัท เทคอินโนเวชั่น จำกัด', 'address' => '123 อาคารสาทรสแควร์ ชั้น 20 ถ.สาทรเหนือ บางรัก กทม. 10500'];
$teachers = $teachers ?? [['id' => 1, 'name' => 'อ.สมศรี รักศิษย์'], ['id' => 2, 'name' => 'อ.วิชัย ตั้งใจ']];
$formError = $formError ?? null;
?>
<div class="max-w-2xl mx-auto flex flex-col gap-6">
  <div>
    <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode mb-1">จับคู่ครูนิเทศ</h1>
    <p class="font-body-md text-body-md text-on-surface-variant">กำหนดครูนิเทศสำหรับนักศึกษาที่เลือกและสถานประกอบการ</p>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-5 shadow-soft border border-surface-variant dark:border-outline-variant/20">
      <p class="font-metadata text-metadata text-on-surface-variant mb-1">นักศึกษา</p>
      <h2 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($student['name']) ?></h2>
      <p class="font-label-md text-label-md text-primary"><?= htmlspecialchars($student['code']) ?> · <?= htmlspecialchars($student['department']) ?></p>
    </div>
    <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-5 shadow-soft border border-surface-variant dark:border-outline-variant/20">
      <p class="font-metadata text-metadata text-on-surface-variant mb-1">สถานประกอบการ</p>
      <h3 class="font-headline-md text-[18px] text-on-surface dark:text-text-dark-mode mb-1"><?= htmlspecialchars($company['name']) ?></h3>
      <p class="font-body-md text-metadata text-on-surface-variant line-clamp-2"><?= htmlspecialchars($company['address']) ?></p>
    </div>
  </div>

  <?php if ($formError): ?>
    <div class="bg-error-container text-on-error-container rounded-lg p-4 font-body-md text-body-md" role="alert"><?= htmlspecialchars($formError) ?></div>
  <?php endif; ?>

  <form method="post" action="/admin/matching/<?= $applicationId ?>" id="matching-form" class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-6 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex flex-col gap-5">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
    <h2 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode flex items-center gap-3"><span class="material-symbols-outlined text-primary text-[28px] bg-primary/10 p-2 rounded-xl">supervisor_account</span> เลือกครูนิเทศ</h2>
    <div class="flex flex-col gap-1">
      <label class="font-label-md text-label-md text-on-surface-variant" for="teacher_id">ครูนิเทศ</label>
      <select id="teacher_id" name="teacher_id" required class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">
        <option value="" disabled selected>เลือกครูนิเทศ...</option>
        <?php foreach ($teachers as $t): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="grid grid-cols-2 gap-4">
      <div class="flex flex-col gap-1"><label class="font-label-md text-label-md text-on-surface-variant" for="start_date">วันที่เริ่ม</label>
        <input id="start_date" name="start_date" type="date" required class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"/></div>
      <div class="flex flex-col gap-1"><label class="font-label-md text-label-md text-on-surface-variant" for="end_date">วันที่สิ้นสุด</label>
        <input id="end_date" name="end_date" type="date" required class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"/></div>
    </div>
    <div class="flex flex-col gap-1"><label class="font-label-md text-label-md text-on-surface-variant" for="total_required_hours">ชั่วโมงฝึกงานที่ต้องสะสม</label>
      <input id="total_required_hours" name="total_required_hours" type="number" min="1" value="400" required class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"/></div>
    <button type="submit" id="confirm-match-btn" class="w-full h-touch-target bg-primary text-on-primary rounded-lg font-label-md text-label-md flex items-center justify-center gap-2 hover:bg-primary-container active:scale-[0.97] transition-all">
      <span class="material-symbols-outlined">link</span> ยืนยันจับคู่
    </button>
  </form>
</div>
