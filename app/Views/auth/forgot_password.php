<?php
/**
 * Forgot-password request form. Styled like auth/login.php for visual consistency.
 * TODO Phase 2: wire to POST /auth/password/forgot (API_SPEC.md §1) — done.
 */
$message = $message ?? null;
?>
<div class="fixed inset-0 bg-bg-light dark:bg-bg-dark -z-10"></div>
<header class="bg-surface-bright/80 dark:bg-bg-dark/80 backdrop-blur-md shadow-sm fixed top-0 w-full z-50 flex justify-between items-center px-margin-mobile md:px-margin-desktop h-16">
  <div class="font-display-metrics text-[24px] md:text-[28px] font-bold text-primary dark:text-primary-fixed-dim">IMS THAI</div>
</header>

<main class="flex-grow flex items-center justify-center px-margin-mobile md:px-margin-desktop pt-20 pb-8 z-10 relative w-full">
  <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-2xl w-full max-w-[420px] p-8 md:p-10 shadow-soft border border-surface-container-low dark:border-outline-variant/20 flex flex-col gap-6">
    <div class="flex flex-col items-center text-center gap-3">
      <span class="material-symbols-outlined text-primary text-4xl">mail</span>
      <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-surface dark:text-text-dark-mode">ลืมรหัสผ่าน?</h1>
      <p class="font-body-md text-body-md text-on-surface-variant">กรอกอีเมลที่ใช้สมัครสมาชิก เราจะส่งลิงก์สำหรับตั้งรหัสผ่านใหม่ไปให้</p>
    </div>

    <?php if ($message): ?>
      <div class="bg-status-success/10 text-status-success rounded-lg p-4 font-body-md text-body-md text-center" role="status">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php else: ?>
      <form class="flex flex-col gap-5" method="post" action="/auth/password/forgot">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
        <div class="flex flex-col gap-1.5">
          <label class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode" for="email">อีเมล</label>
          <input class="w-full h-touch-target bg-surface-container-lowest dark:bg-surface-container-high/10 border border-outline-variant rounded-lg px-4 font-body-md text-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"
                 id="email" name="email" placeholder="you@example.com" type="email" required/>
        </div>
        <button class="w-full h-touch-target bg-primary text-on-primary font-label-md text-label-md rounded-lg flex items-center justify-center gap-2 hover:bg-primary-container active:scale-[0.97] transition-all" type="submit">
          ส่งลิงก์รีเซ็ตรหัสผ่าน
        </button>
      </form>
    <?php endif; ?>

    <div class="text-center font-label-md text-label-md">
      <a class="text-primary hover:text-primary-container font-semibold transition-colors hover:underline underline-offset-4" href="/login">กลับไปหน้าเข้าสู่ระบบ</a>
    </div>
  </div>
</main>
