<?php
/** Shown right after registration while users.status = 'pending' (RULE-AUTH-01). Adapted from design-reference/_1. */
?>
<div class="w-full max-w-sm bg-surface-container-lowest dark:bg-surface-dark rounded-2xl p-8 flex flex-col items-center text-center shadow-xl mx-auto">
  <div class="w-40 h-40 mb-6">
    <img class="w-full h-full object-contain" alt="ภาพประกอบรอการอนุมัติ" src="/assets/img/illustration-pending.svg"/>
  </div>
  <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode mb-3 tracking-tight">
    บัญชีของคุณอยู่ระหว่างการอนุมัติ <span class="inline-block animate-bounce" style="animation-duration:2s;">⏳</span>
  </h1>
  <p class="font-body-md text-body-md text-on-surface-variant mb-8 leading-relaxed max-w-[280px]">
    เจ้าหน้าที่กำลังตรวจสอบข้อมูลของคุณ ปกติใช้เวลาไม่เกิน 1-2 วันทำการ เราจะแจ้งเตือนทันทีที่พร้อมใช้งาน
  </p>
  <a href="/login" class="w-full flex items-center justify-center gap-2 bg-transparent text-primary font-label-md text-label-md px-6 py-4 rounded-xl transition-all duration-300 hover:bg-surface-container active:scale-95 group">
    <span class="material-symbols-outlined text-[20px] transition-transform group-hover:-translate-x-1">arrow_back</span>
    <span>กลับไปหน้าเข้าสู่ระบบ</span>
  </a>
</div>
