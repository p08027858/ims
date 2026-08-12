<?php
/**
 * Password recovery landing page. Supabase's reset-password email links here with the
 * recovery session in the URL fragment (#access_token=...&type=recovery), which only
 * JavaScript can read (fragments never reach the server) — so this page reads it client-side
 * and drops it into a hidden field before the form posts to POST /auth/password/reset.
 */
$resetError = $resetError ?? null;
$resetSuccess = $resetSuccess ?? false;
?>
<div class="fixed inset-0 bg-bg-light dark:bg-bg-dark -z-10"></div>
<header class="bg-surface-bright/80 dark:bg-bg-dark/80 backdrop-blur-md shadow-sm fixed top-0 w-full z-50 flex justify-between items-center px-margin-mobile md:px-margin-desktop h-16">
  <div class="font-display-metrics text-[24px] md:text-[28px] font-bold text-primary dark:text-primary-fixed-dim">IMS THAI</div>
</header>

<main class="flex-grow flex items-center justify-center px-margin-mobile md:px-margin-desktop pt-20 pb-8 z-10 relative w-full">
  <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-2xl w-full max-w-[420px] p-8 md:p-10 shadow-soft border border-surface-container-low dark:border-outline-variant/20 flex flex-col gap-6">
    <div class="flex flex-col items-center text-center gap-3">
      <span class="material-symbols-outlined text-primary text-4xl">lock_reset</span>
      <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-surface dark:text-text-dark-mode">ตั้งรหัสผ่านใหม่</h1>
    </div>

    <?php if ($resetSuccess): ?>
      <div class="bg-status-success/10 text-status-success rounded-lg p-4 font-body-md text-body-md text-center" role="status">
        ตั้งรหัสผ่านใหม่สำเร็จแล้ว
      </div>
      <a href="/login" class="w-full h-touch-target bg-primary text-on-primary font-label-md text-label-md rounded-lg flex items-center justify-center gap-2 hover:bg-primary-container transition-all">
        ไปหน้าเข้าสู่ระบบ
      </a>
    <?php else: ?>
      <div id="no-token-error" class="hidden bg-error-container text-on-error-container rounded-lg p-4 font-body-md text-body-md text-center" role="alert">
        ลิงก์นี้ไม่ถูกต้องหรือหมดอายุแล้ว กรุณาขอลิงก์ใหม่จากหน้า <a href="/password/forgot" class="underline font-semibold">ลืมรหัสผ่าน</a>
      </div>
      <?php if ($resetError): ?>
        <div class="bg-error-container text-on-error-container rounded-lg p-4 font-body-md text-body-md text-center" role="alert">
          <?= htmlspecialchars($resetError) ?>
        </div>
      <?php endif; ?>
      <form id="reset-form" class="flex flex-col gap-5" method="post" action="/auth/password/reset">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
        <input type="hidden" name="access_token" id="access_token"/>
        <div class="flex flex-col gap-1.5">
          <label class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode" for="password">รหัสผ่านใหม่</label>
          <input class="w-full h-touch-target bg-surface-container-lowest dark:bg-surface-container-high/10 border border-outline-variant rounded-lg px-4 font-body-md text-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"
                 id="password" name="password" placeholder="อย่างน้อย 8 ตัวอักษร" type="password" required/>
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="font-label-md text-label-md text-on-surface dark:text-text-dark-mode" for="password_confirmation">ยืนยันรหัสผ่านใหม่</label>
          <input class="w-full h-touch-target bg-surface-container-lowest dark:bg-surface-container-high/10 border border-outline-variant rounded-lg px-4 font-body-md text-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"
                 id="password_confirmation" name="password_confirmation" placeholder="อย่างน้อย 8 ตัวอักษร" type="password" required/>
        </div>
        <button id="submit-btn" class="w-full h-touch-target bg-primary text-on-primary font-label-md text-label-md rounded-lg flex items-center justify-center gap-2 hover:bg-primary-container active:scale-[0.97] transition-all" type="submit">
          บันทึกรหัสผ่านใหม่
        </button>
      </form>
    <?php endif; ?>
  </div>
</main>
<script>
  // Supabase redirects here with the recovery session in the URL fragment, e.g.
  // #access_token=...&expires_in=3600&refresh_token=...&type=recovery — read it client-side
  // since fragments never reach the server (DATABASE.md §0.1 / API_SPEC.md §1).
  (function () {
    const hash = new URLSearchParams(window.location.hash.replace(/^#/, ''));
    const token = hash.get('access_token');
    const form = document.getElementById('reset-form');
    if (!token) {
      document.getElementById('no-token-error')?.classList.remove('hidden');
      form?.querySelectorAll('input,button').forEach(el => el.disabled = true);
      return;
    }
    document.getElementById('access_token').value = token;
  })();
</script>
