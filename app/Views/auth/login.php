<?php
/**
 * Login screen. Adapted from design-reference/light_mode_2 (+ dark_mode variant merged in
 * via Tailwind `dark:` classes so a single file serves both themes).
 * Rendered standalone (role=guest → no sidebar/topbar, see layouts/app.php).
 *
 * TODO Phase 2 (RULE-AUTH-01..05 in RULES.md): wire this <form> to POST /auth/login
 * (API_SPEC.md §1) — on ACCOUNT_LOCKED/INVALID_CREDENTIALS show the inline error state below.
 */
$loginError = $loginError ?? null; // e.g. "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง (เหลืออีก 3 ครั้ง)"
?>
<style>
  .animate-fade-in-up { animation: fadeInUp .8s cubic-bezier(.16,1,.3,1) forwards; opacity:0; transform:translateY(20px) scale(.98); }
  @keyframes fadeInUp { to { opacity:1; transform:translateY(0) scale(1); } }
  .bg-blob { position:absolute; width:600px; height:600px; background:radial-gradient(circle, rgba(79,70,229,.15) 0%, rgba(79,70,229,0) 70%); border-radius:50%; top:50%; left:50%; transform:translate(-50%,-50%); z-index:0; animation:pulse-blob 8s ease-in-out infinite alternate; pointer-events:none; }
  @keyframes pulse-blob { 0%{transform:translate(-50%,-50%) scale(1); opacity:.8;} 100%{transform:translate(-50%,-50%) scale(1.1); opacity:1;} }
</style>
<div class="fixed inset-0 bg-bg-light dark:bg-bg-dark -z-10"></div>
<div class="bg-blob"></div>
<header class="bg-surface-bright/80 dark:bg-bg-dark/80 backdrop-blur-md shadow-sm fixed top-0 w-full z-50 flex justify-between items-center px-margin-mobile md:px-margin-desktop h-16">
  <div class="font-display-metrics text-[24px] md:text-[28px] font-bold text-primary dark:text-primary-fixed-dim">IMS THAI</div>
  <button type="button" id="theme-toggle-btn-standalone" aria-label="สลับโหมดสว่าง/มืด" class="w-10 h-10 flex items-center justify-center rounded-full text-on-surface-variant hover:bg-surface-container-high transition-colors active:scale-95 duration-100">
    <span class="material-symbols-outlined">dark_mode</span>
  </button>
</header>

<main class="flex-grow flex items-center justify-center px-margin-mobile md:px-margin-desktop pt-20 pb-8 z-10 relative w-full">
  <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-2xl w-full max-w-[420px] p-8 md:p-10 shadow-soft border border-surface-container-low dark:border-outline-variant/20 animate-fade-in-up flex flex-col gap-8">
    <div class="flex flex-col items-center text-center gap-4">
      <div class="w-20 h-20 bg-surface-container-high rounded-full flex items-center justify-center overflow-hidden border border-outline-variant/30">
        <img alt="โลโก้มหาวิทยาลัย" class="w-full h-full object-cover" src="/assets/img/logo-placeholder.svg"/>
      </div>
      <div>
        <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-surface dark:text-text-dark-mode">เข้าสู่ระบบ</h1>
        <p class="font-body-md text-body-md text-on-surface-variant mt-2">ระบบบริหารจัดการการฝึกงาน</p>
      </div>
    </div>

    <form class="flex flex-col gap-5" method="post" action="/auth/login">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
      <div class="flex flex-col gap-1.5">
        <label class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode" for="username">รหัสนักศึกษา / อีเมล</label>
        <div class="relative">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant" aria-hidden="true">person</span>
          <input class="w-full h-touch-target bg-surface-container-lowest dark:bg-surface-container-high/10 border border-outline-variant rounded-lg pl-10 pr-4 font-body-md text-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors placeholder-outline"
                 id="username" name="username" placeholder="กรอกรหัสนักศึกษา" type="text" required/>
        </div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode" for="password">รหัสผ่าน</label>
        <div class="relative">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant" aria-hidden="true">lock</span>
          <input class="w-full h-touch-target bg-surface-container-lowest dark:bg-surface-container-high/10 border <?= $loginError ? 'border-error shake-anim' : 'border-outline-variant' ?> rounded-lg pl-10 pr-10 font-body-md text-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors placeholder-outline"
                 id="password" name="password" placeholder="กรอกรหัสผ่าน" type="password" required/>
          <button class="absolute right-1 top-1/2 -translate-y-1/2 w-10 h-10 flex items-center justify-center text-on-surface-variant hover:text-on-surface transition-colors rounded-full focus:outline-none" type="button" id="toggle-password-btn" aria-label="แสดง/ซ่อนรหัสผ่าน">
            <span class="material-symbols-outlined" id="visibility-icon">visibility</span>
          </button>
        </div>
        <?php if ($loginError): ?>
          <p class="font-metadata text-metadata text-error flex items-center gap-1 mt-1" role="alert" aria-live="polite">
            <span class="material-symbols-outlined text-[16px]">error</span> <?= htmlspecialchars($loginError) ?>
          </p>
        <?php endif; ?>
        <div class="flex justify-end mt-1">
          <a class="font-metadata text-metadata text-primary hover:text-primary-container transition-colors hover:underline underline-offset-4" href="/password/forgot">ลืมรหัสผ่าน?</a>
        </div>
      </div>

      <button class="w-full h-touch-target mt-4 bg-primary text-on-primary font-label-md text-label-md rounded-lg flex items-center justify-center gap-2 hover:bg-primary-container active:scale-[0.97] transition-all duration-200" type="submit">
        เข้าสู่ระบบ
        <span class="material-symbols-outlined">login</span>
      </button>
    </form>

    <div class="text-center font-label-md text-label-md mt-2">
      <span class="text-on-surface-variant">ยังไม่มีบัญชีผู้ใช้?</span>
      <a class="text-primary hover:text-primary-container font-semibold transition-colors hover:underline underline-offset-4 ml-1" href="/register">สมัครสมาชิก (นักศึกษา)</a>
    </div>
  </div>
</main>
<script>
  document.getElementById('theme-toggle-btn-standalone')?.addEventListener('click', () => document.documentElement.classList.toggle('dark'));
  document.getElementById('toggle-password-btn')?.addEventListener('click', () => {
    const input = document.getElementById('password');
    const icon = document.getElementById('visibility-icon');
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    icon.textContent = show ? 'visibility_off' : 'visibility';
  });
</script>
