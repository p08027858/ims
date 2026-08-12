<?php
/**
 * Student profile edit. Adapted from design-reference/mobile_4.
 * TODO Phase 2: wire to PUT /me/profile + POST /me/avatar (API_SPEC.md §2). Emergency
 * contact fields are required before internship start per MASTER_SPEC.md workflow notes.
 */
$profile = $profile ?? [
    'student_code' => '6401234567', 'faculty' => 'วิศวกรรมศาสตร์', 'department' => 'วิศวกรรมคอมพิวเตอร์',
    'phone' => '', 'address' => '', 'emergency_name' => '', 'emergency_relation' => '', 'emergency_phone' => '',
];
?>
<div class="max-w-2xl mx-auto flex flex-col gap-6">
  <div class="flex flex-col items-center gap-4 pt-4">
    <div class="relative w-32 h-32 rounded-full overflow-hidden shadow-md bg-primary-container flex items-center justify-center">
      <span class="font-display-metrics text-display-metrics text-on-primary-container"><?= htmlspecialchars(mb_substr($profile['student_code'] ?? 'S', -2)) ?></span>
      <button type="button" aria-label="เปลี่ยนรูปโปรไฟล์" class="absolute bottom-1 right-1 w-10 h-10 bg-primary text-on-primary rounded-full flex items-center justify-center shadow-lg active:scale-90 transition-transform">
        <span class="material-symbols-outlined text-[20px]">edit</span>
      </button>
    </div>
    <div class="text-center">
      <h1 class="font-headline-lg-mobile text-headline-lg-mobile text-on-surface dark:text-text-dark-mode">แก้ไขโปรไฟล์</h1>
      <p class="font-body-md text-body-md text-on-surface-variant mt-1">อัปเดตข้อมูลการติดต่อของคุณ</p>
    </div>
  </div>

  <div class="bg-surface-container-low dark:bg-surface-container-high/10 rounded-xl p-5 shadow-sm space-y-4">
    <div class="flex items-center gap-2 mb-2">
      <span class="material-symbols-outlined text-primary text-[20px]">school</span>
      <h2 class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">ข้อมูลการศึกษา (อ่านอย่างเดียว)</h2>
    </div>
    <div class="flex flex-col gap-1">
      <label class="font-metadata text-metadata text-outline">รหัสนักศึกษา</label>
      <div class="bg-surface-container/50 dark:bg-surface-dark/50 px-4 py-3 rounded-lg text-on-surface-variant font-body-md text-body-md"><?= htmlspecialchars($profile['student_code']) ?></div>
    </div>
    <div class="grid grid-cols-2 gap-4">
      <div class="flex flex-col gap-1"><label class="font-metadata text-metadata text-outline">คณะ</label>
        <div class="bg-surface-container/50 dark:bg-surface-dark/50 px-4 py-3 rounded-lg text-on-surface-variant font-body-md text-body-md truncate"><?= htmlspecialchars($profile['faculty']) ?></div></div>
      <div class="flex flex-col gap-1"><label class="font-metadata text-metadata text-outline">สาขา</label>
        <div class="bg-surface-container/50 dark:bg-surface-dark/50 px-4 py-3 rounded-lg text-on-surface-variant font-body-md text-body-md truncate"><?= htmlspecialchars($profile['department']) ?></div></div>
    </div>
  </div>

  <form id="profileForm" method="post" action="/student/profile" class="flex flex-col gap-6">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
    <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-5 shadow-sm space-y-5">
      <div class="flex items-center gap-2 mb-2"><span class="material-symbols-outlined text-primary text-[20px]">contact_phone</span>
        <h2 class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">ข้อมูลการติดต่อ</h2></div>
      <div class="flex flex-col gap-1">
        <label class="font-metadata text-metadata text-primary" for="phone">เบอร์โทรศัพท์</label>
        <input class="w-full bg-surface-container-lowest dark:bg-surface-container-high/10 outline-none font-body-md text-body-md text-on-surface dark:text-text-dark-mode py-3 px-4 rounded-lg shadow-[inset_0_0_0_1px_theme(colors.outline-variant)] focus:shadow-[inset_0_0_0_2px_theme(colors.primary)]"
               id="phone" name="phone" placeholder="08X-XXX-XXXX" type="tel" value="<?= htmlspecialchars($profile['phone']) ?>"/>
      </div>
      <div class="flex flex-col gap-1">
        <label class="font-metadata text-metadata text-primary" for="address">ที่อยู่ปัจจุบัน</label>
        <textarea class="w-full bg-surface-container-lowest dark:bg-surface-container-high/10 outline-none font-body-md text-body-md text-on-surface dark:text-text-dark-mode py-3 px-4 rounded-lg shadow-[inset_0_0_0_1px_theme(colors.outline-variant)] focus:shadow-[inset_0_0_0_2px_theme(colors.primary)] resize-none"
                  id="address" name="address" rows="3"><?= htmlspecialchars($profile['address']) ?></textarea>
      </div>
    </div>

    <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-5 shadow-sm space-y-5">
      <div class="flex items-center gap-2 mb-2"><span class="material-symbols-outlined text-error text-[20px]">emergency</span>
        <h2 class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">ผู้ติดต่อกรณีฉุกเฉิน <span class="text-error">*</span></h2></div>
      <div class="flex flex-col gap-1">
        <label class="font-metadata text-metadata text-primary" for="emergencyName">ชื่อ-นามสกุล</label>
        <input class="w-full bg-surface-container-lowest dark:bg-surface-container-high/10 outline-none font-body-md text-body-md text-on-surface dark:text-text-dark-mode py-3 px-4 rounded-lg shadow-[inset_0_0_0_1px_theme(colors.outline-variant)] focus:shadow-[inset_0_0_0_2px_theme(colors.primary)]"
               id="emergencyName" name="emergency_contact_name" required type="text" value="<?= htmlspecialchars($profile['emergency_name']) ?>"/>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div class="flex flex-col gap-1">
          <label class="font-metadata text-metadata text-primary" for="emergencyRelation">ความสัมพันธ์</label>
          <input class="w-full bg-surface-container-lowest dark:bg-surface-container-high/10 outline-none font-body-md text-body-md text-on-surface dark:text-text-dark-mode py-3 px-4 rounded-lg shadow-[inset_0_0_0_1px_theme(colors.outline-variant)] focus:shadow-[inset_0_0_0_2px_theme(colors.primary)]"
                 id="emergencyRelation" name="emergency_relation" placeholder="เช่น บิดา, มารดา" type="text" value="<?= htmlspecialchars($profile['emergency_relation']) ?>"/>
        </div>
        <div class="flex flex-col gap-1">
          <label class="font-metadata text-metadata text-primary" for="emergencyPhone">เบอร์โทรศัพท์ฉุกเฉิน</label>
          <input class="w-full bg-surface-container-lowest dark:bg-surface-container-high/10 outline-none font-body-md text-body-md text-on-surface dark:text-text-dark-mode py-3 px-4 rounded-lg shadow-[inset_0_0_0_1px_theme(colors.outline-variant)] focus:shadow-[inset_0_0_0_2px_theme(colors.primary)]"
                 id="emergencyPhone" name="emergency_contact_phone" required type="tel" value="<?= htmlspecialchars($profile['emergency_phone']) ?>"/>
        </div>
      </div>
    </div>

    <button type="submit" class="w-full h-14 bg-primary text-on-primary rounded-xl font-label-md text-label-md flex items-center justify-center gap-2 shadow-md transition-all active:scale-[0.97] hover:opacity-90">
      <span class="material-symbols-outlined text-[20px]">save</span> บันทึกการเปลี่ยนแปลง
    </button>
  </form>
</div>
