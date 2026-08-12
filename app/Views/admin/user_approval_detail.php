<?php
/**
 * Single user approval detail. Adapted from design-reference/_7 ("กมลวรรณ ศรีสุข" /
 * "อนุมัติเรียบร้อย"). Wired to CompanyController::userApprovalDetailData() (GET) and
 * ::approveUserFromDetail() (POST /admin/users/approve, id carried via hidden field since the
 * URL itself has no id segment for this route — see config/actions.php).
 */
$user = $user ?? ['id' => '', 'name' => 'กมลวรรณ ศรีสุข', 'faculty' => 'คณะวิศวกรรมศาสตร์', 'student_code' => '6512345678'];
?>
<div class="max-w-lg mx-auto flex flex-col gap-6">
  <div class="bg-surface-container-lowest dark:bg-surface-dark rounded-xl p-8 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex flex-col items-center text-center gap-3">
    <div class="w-20 h-20 rounded-full bg-primary-container text-on-primary-container flex items-center justify-center font-headline-lg"><?= htmlspecialchars(mb_substr($user['name'], 0, 1)) ?></div>
    <h2 class="font-headline-md text-headline-md text-on-surface dark:text-text-dark-mode mb-1"><?= htmlspecialchars($user['name']) ?></h2>
    <p class="font-body-md text-body-md text-on-surface-variant mb-4"><?= htmlspecialchars($user['faculty']) ?></p>

    <form method="post" action="/admin/users/approve" class="w-full flex gap-3">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
      <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>"/>
      <button type="submit" name="status" value="rejected" class="flex-1 px-6 py-3 rounded-xl border border-error text-error hover:bg-error-container/20 font-label-md text-label-md transition-colors active:scale-95">ปฏิเสธ</button>
      <button type="submit" name="status" value="approved" class="flex-1 px-6 py-3 rounded-xl bg-status-success text-on-error font-label-md text-label-md hover:opacity-90 transition-colors active:scale-95">อนุมัติ</button>
    </form>
  </div>
</div>
