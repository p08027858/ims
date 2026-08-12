<?php
/**
 * Student dashboard. Merges design-reference/desktop_home (desktop layout) and
 * design-reference/mobile_1 (richer mobile content: journey stepper, streak, FAB) into one
 * responsive view — desktop-only elements use `hidden lg:flex`, mobile-only use `lg:hidden`.
 * TODO Phase 1-2: replace $student/$stats mock data with real values from
 * GET /attendance/summary + GET /evaluations (API_SPEC.md §5, §8).
 */
$student = $student ?? ['first_name' => 'สมชาย', 'company_name' => 'TechNova Solutions Co., Ltd.', 'week_number' => 4, 'total_weeks' => 16];
$stats = $stats ?? ['hours_logged' => 210.5, 'hours_required' => 400, 'percent_complete' => 52, 'days_present' => 30, 'last_score' => 18, 'last_score_max' => 20];
$hasCheckedInToday = $hasCheckedInToday ?? false;
$journeyStage = $journeyStage ?? 'internship'; // application | approved | internship | evaluation | done
$streakDays = $streakDays ?? 7;
$pendingLog = $pendingLog ?? ['week' => 3];
?>
<!-- Mobile hero + journey stepper + gamified stats (design-reference/mobile_1) -->
<div class="lg:hidden flex flex-col gap-6 -mx-margin-mobile px-margin-mobile">
  <div class="bg-primary text-on-primary rounded-xl p-5 shadow-lg relative overflow-hidden">
    <div class="relative z-10 flex flex-col gap-1">
      <h1 class="font-headline-lg-mobile text-headline-lg-mobile">สวัสดี <?= htmlspecialchars($student['first_name']) ?> 👋</h1>
      <p class="font-body-md text-body-md text-on-primary/90 mt-1">
        <?= $hasCheckedInToday ? 'วันนี้คุณลงเวลาเข้างานแล้ว เยี่ยมมาก!' : 'วันนี้คุณยังไม่ได้ลงเวลาฝึกงานเลยนะ 😊' ?>
      </p>
      <div class="flex items-center gap-3 mt-4">
        <div class="bg-on-primary/10 rounded-lg p-2 flex items-center justify-center backdrop-blur-sm">
          <span class="material-symbols-outlined text-[20px]">business</span>
        </div>
        <div class="flex flex-col">
          <span class="font-label-md text-label-md opacity-80"><?= htmlspecialchars($student['company_name']) ?></span>
          <span class="font-label-md text-label-md font-bold">สัปดาห์ที่ <?= $student['week_number'] ?> / <?= $student['total_weeks'] ?></span>
        </div>
      </div>
    </div>
  </div>

  <?php
  $stages = ['application' => ['สมัคร', 'assignment_ind'], 'approved' => ['อนุมัติ', 'check'], 'internship' => ['ฝึกงาน', 'work'], 'evaluation' => ['ประเมิน', 'assignment'], 'done' => ['เสร็จ', 'flag']];
  $stageKeys = array_keys($stages);
  $currentIndex = array_search($journeyStage, $stageKeys);
  ?>
  <div class="bg-surface-container dark:bg-surface-container-high/10 rounded-xl p-4 shadow-sm flex flex-col gap-3">
    <h2 class="font-label-md text-label-md text-on-surface-variant">สถานะการฝึกงาน</h2>
    <div class="flex items-center justify-between relative">
      <div class="absolute top-1/2 left-4 right-4 h-0.5 bg-surface-variant -translate-y-1/2 z-0"></div>
      <div class="absolute top-1/2 left-4 h-0.5 bg-primary -translate-y-1/2 z-0" style="width:<?= $currentIndex / (count($stageKeys) - 1) * 100 ?>%"></div>
      <?php foreach ($stageKeys as $i => $key): [$label, $icon] = $stages[$key]; $done = $i <= $currentIndex; $current = $i === $currentIndex; ?>
        <div class="flex flex-col items-center gap-1 relative z-10">
          <div class="w-8 h-8 rounded-full flex items-center justify-center shadow-sm <?= $done ? 'bg-primary text-on-primary' : 'bg-surface-variant text-on-surface-variant' ?> <?= $current ? 'shadow-md ring-4 ring-primary/20 animate-pulse' : '' ?>">
            <span class="material-symbols-outlined text-[16px]"><?= $icon ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="flex justify-between w-full mt-1">
      <?php foreach ($stageKeys as $i => $key): ?>
        <span class="font-metadata text-metadata text-center w-8 <?= $i === $currentIndex ? 'text-primary font-bold' : 'text-on-surface-variant' ?>"><?= $stages[$key][0] ?></span>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="grid grid-cols-2 gap-4">
    <div class="col-span-2 bg-surface-container dark:bg-surface-container-high/10 rounded-xl p-5 shadow-sm flex items-center gap-5 relative overflow-hidden">
      <div class="relative w-20 h-20 shrink-0">
        <svg class="w-full h-full -rotate-90" viewBox="0 0 100 100">
          <circle class="text-surface-variant" cx="50" cy="50" fill="none" r="45" stroke="currentColor" stroke-width="8"/>
          <circle class="text-primary" cx="50" cy="50" fill="none" r="45" stroke="currentColor"
                  stroke-dasharray="282.7" stroke-dashoffset="<?= 282.7 * (1 - $stats['percent_complete'] / 100) ?>" stroke-linecap="round" stroke-width="8"/>
        </svg>
        <div class="absolute inset-0 flex flex-col items-center justify-center text-primary font-headline-md text-headline-md"><?= $stats['percent_complete'] ?><span class="text-metadata font-metadata">%</span></div>
      </div>
      <div class="flex flex-col z-10">
        <span class="font-label-md text-label-md text-on-surface-variant">ชั่วโมงสะสม</span>
        <div class="flex items-baseline gap-1">
          <span class="font-headline-lg-mobile text-headline-lg-mobile text-on-surface"><?= $stats['hours_logged'] ?></span>
          <span class="font-body-md text-body-md text-on-surface-variant">/ <?= $stats['hours_required'] ?> ชม.</span>
        </div>
        <span class="text-metadata font-metadata text-status-success mt-1 flex items-center gap-1">
          <span class="material-symbols-outlined text-[14px]">trending_up</span> ทำได้ดีมาก!
        </span>
      </div>
    </div>
    <div class="bg-surface-container dark:bg-surface-container-high/10 rounded-xl p-4 shadow-sm flex flex-col justify-between">
      <div class="w-10 h-10 rounded-full bg-secondary-container/20 text-secondary flex items-center justify-center mb-3"><span class="material-symbols-outlined">calendar_month</span></div>
      <span class="font-label-md text-label-md text-on-surface-variant">เข้างานแล้ว</span>
      <span class="font-headline-md text-headline-md text-on-surface mt-1"><?= $stats['days_present'] ?> <span class="font-body-md text-body-md text-on-surface-variant">วัน</span></span>
    </div>
    <div class="bg-surface-container dark:bg-surface-container-high/10 rounded-xl p-4 shadow-sm flex flex-col justify-between">
      <div class="w-10 h-10 rounded-full bg-tertiary-container/20 text-tertiary flex items-center justify-center mb-3"><span class="material-symbols-outlined">star</span></div>
      <span class="font-label-md text-label-md text-on-surface-variant">คะแนนประเมินล่าสุด</span>
      <span class="font-headline-md text-headline-md text-on-surface mt-1"><?= $stats['last_score'] ?><span class="font-body-md text-body-md text-on-surface-variant">/<?= $stats['last_score_max'] ?></span></span>
    </div>
  </div>

  <?php if ($streakDays > 0): ?>
    <div class="bg-gradient-to-r from-tertiary-container/10 to-transparent rounded-xl p-4 shadow-sm border-l-4 border-tertiary flex items-center gap-4">
      <div class="text-4xl animate-bounce">🔥</div>
      <div class="flex flex-col">
        <span class="font-headline-md text-headline-md text-on-surface">สุดยอดมาก!</span>
        <span class="font-body-md text-body-md text-on-surface-variant">บันทึกงานครบ <?= $streakDays ?> วันติดต่อกัน</span>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($pendingLog): ?>
    <div class="bg-error-container text-on-error-container rounded-xl p-4 shadow-sm flex items-start gap-4">
      <span class="material-symbols-outlined text-error mt-0.5">warning</span>
      <div class="flex flex-col gap-1 w-full">
        <span class="font-label-md text-label-md font-bold">งานค้างส่ง (1)</span>
        <span class="font-body-md text-body-md text-on-error-container/80">บันทึกประจำสัปดาห์ที่ <?= $pendingLog['week'] ?> ยังไม่ได้ส่ง</span>
        <a href="/student/daily-logs/new" class="self-start mt-2 px-4 py-2 bg-error text-on-error rounded-full font-label-md text-label-md shadow-sm active:scale-95 transition-transform">ดำเนินการตอนนี้</a>
      </div>
    </div>
  <?php endif; ?>

  <a href="/student/attendance" class="fixed bottom-[88px] right-4 bg-primary text-on-primary px-6 py-4 rounded-full shadow-xl flex items-center gap-2 font-label-md text-label-md z-40 active:scale-95 transition-transform hover:shadow-2xl">
    <span class="material-symbols-outlined">location_on</span>
    <?= $hasCheckedInToday ? 'ลงเวลาออกงาน' : 'ลงเวลาเข้างาน' ?>
    <?php if (!$hasCheckedInToday): ?>
      <span class="absolute top-0 right-0 w-3 h-3 bg-error rounded-full ring-2 ring-surface animate-ping"></span>
      <span class="absolute top-0 right-0 w-3 h-3 bg-error rounded-full ring-2 ring-surface"></span>
    <?php endif; ?>
  </a>
</div>

<!-- Desktop layout (design-reference/desktop_home) -->
<div class="hidden lg:block">
  <div class="bg-surface-container-lowest rounded-xl p-6 shadow-soft mb-8 flex flex-col md:flex-row items-center justify-between border border-surface-variant dark:border-outline-variant/20">
    <div>
      <h2 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode mb-2">สวัสดี <?= htmlspecialchars($student['first_name']) ?> 👋</h2>
      <p class="font-body-lg text-body-lg text-on-surface-variant">วันนี้คุณพร้อมฝึกงานหรือยัง?</p>
    </div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2 flex flex-col gap-6">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-surface-container-lowest rounded-xl p-4 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex flex-col items-center justify-center text-center">
          <div class="relative w-16 h-16 mb-2">
            <svg class="w-full h-full" viewBox="0 0 36 36">
              <path class="text-surface-variant" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="3"/>
              <path class="text-primary" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-dasharray="<?= $stats['percent_complete'] ?>, 100" stroke-width="3"/>
            </svg>
            <div class="absolute inset-0 flex items-center justify-center font-label-md text-label-md font-bold text-primary"><?= $stats['percent_complete'] ?>%</div>
          </div>
          <span class="font-metadata text-metadata text-on-surface-variant">ชั่วโมงรวม</span>
        </div>
        <div class="bg-surface-container-lowest rounded-xl p-4 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex flex-col items-center justify-center text-center">
          <span class="material-symbols-outlined text-4xl text-status-success mb-2">check_circle</span>
          <div class="font-headline-md text-headline-md text-on-surface font-bold"><?= $stats['days_present'] ?></div>
          <span class="font-metadata text-metadata text-on-surface-variant">เข้างาน (วัน)</span>
        </div>
        <div class="bg-surface-container-lowest rounded-xl p-4 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex flex-col items-center justify-center text-center">
          <span class="material-symbols-outlined text-4xl text-status-warning mb-2">star</span>
          <div class="font-headline-md text-headline-md text-on-surface font-bold"><?= $stats['last_score'] ?>/<?= $stats['last_score_max'] ?></div>
          <span class="font-metadata text-metadata text-on-surface-variant">คะแนนประเมิน</span>
        </div>
        <div class="bg-surface-container-lowest rounded-xl p-4 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex flex-col items-center justify-center text-center">
          <span class="material-symbols-outlined text-4xl text-tertiary-container mb-2">local_fire_department</span>
          <div class="font-headline-md text-headline-md text-on-surface font-bold"><?= $streakDays ?></div>
          <span class="font-metadata text-metadata text-on-surface-variant">Streak (วัน)</span>
        </div>
      </div>

      <div class="bg-surface-container-lowest rounded-xl p-6 shadow-soft border border-surface-variant dark:border-outline-variant/20">
        <h3 class="font-headline-md text-headline-md text-on-surface mb-6">เส้นทางการฝึกงาน</h3>
        <div class="relative flex justify-between items-center w-full">
          <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-surface-variant -z-10"></div>
          <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-primary -z-10" style="width:<?= $currentIndex / (count($stageKeys) - 1) * 100 ?>%"></div>
          <?php foreach ($stageKeys as $i => $key): $done = $i <= $currentIndex; ?>
            <div class="flex flex-col items-center gap-2">
              <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold <?= $done ? 'bg-primary text-on-primary' : 'bg-surface-variant text-on-surface-variant border-2 border-surface-container-lowest' ?>"><?= $i + 1 ?></div>
              <span class="font-metadata text-metadata text-on-surface-variant hidden md:block"><?= $stages[$key][0] ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="flex flex-col gap-6">
      <div class="bg-surface-container-lowest rounded-xl p-6 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex flex-col">
        <h3 class="font-headline-md text-headline-md text-on-surface mb-4">บันทึกงานประจำวัน</h3>
        <p class="font-body-md text-body-md text-on-surface-variant mb-6 text-sm">วันนี้คุณได้เรียนรู้อะไรบ้าง? บันทึกประสบการณ์ของคุณเพื่อเก็บเป็น Portfolio</p>
        <a href="/student/daily-logs/new" class="mt-auto w-full flex items-center justify-center gap-2 bg-secondary-container text-on-secondary-container rounded-xl px-4 py-3 font-label-md text-label-md hover:opacity-90 transition-colors active:scale-[0.97]">
          <span class="material-symbols-outlined">edit_document</span> เขียนบันทึกวันนี้
        </a>
      </div>
      <div class="bg-surface-container-lowest rounded-xl p-6 shadow-soft border border-surface-variant dark:border-outline-variant/20">
        <div class="flex justify-between items-center mb-4">
          <h3 class="font-headline-md text-headline-md text-on-surface">ประวัติการลงเวลาล่าสุด</h3>
          <a href="/student/attendance/history" class="text-primary text-sm font-label-md">ดูทั้งหมด</a>
        </div>
        <div class="flex flex-col gap-3">
          <div class="flex items-center gap-4 p-3 rounded-lg hover:bg-surface-container-low transition-colors border border-transparent hover:border-surface-variant">
            <div class="w-10 h-10 rounded-full bg-status-success/10 flex items-center justify-center text-status-success"><span class="material-symbols-outlined">login</span></div>
            <div class="flex-1"><div class="font-label-md text-label-md text-on-surface">เข้างาน</div><div class="font-metadata text-metadata text-on-surface-variant">วันนี้, 08:45 น.</div></div>
            <span class="px-2 py-1 rounded-full bg-status-success/10 text-status-success text-xs font-bold">สำเร็จ</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
