<?php
/**
 * Admin creates a teacher (ครูนิเทศ) account — RULE-AUTH-02, same pattern as
 * admin/company_form.php's supervisor creation. New page, not part of the 27 Stitch exports.
 * Wired to App\Controllers\TeacherController::store(). The account still lands in the same
 * pending-approval queue as everyone else (public.users trigger forces status=pending) —
 * approve it from /admin/users afterward.
 */
$faculties = $faculties ?? [];
$formError = $formError ?? null;
$old = $old ?? [];
$v = static fn (string $key, string $default = '') => htmlspecialchars((string) ($old[$key] ?? $default));
?>
<div class="max-w-2xl mx-auto flex flex-col gap-6">
  <div>
    <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode mb-2">เพิ่มครูนิเทศ</h1>
    <p class="font-body-md text-body-md text-on-surface-variant">สร้างบัญชีครูนิเทศ — บัญชีนี้ต้องผ่านการอนุมัติในหน้า "รอการอนุมัติ" อีกครั้งก่อนเข้าใช้งานได้จริง (RULE-AUTH-02)</p>
  </div>

  <?php if ($formError): ?>
    <div class="bg-error-container text-on-error-container rounded-lg p-4 font-body-md text-body-md" role="alert"><?= htmlspecialchars($formError) ?></div>
  <?php endif; ?>

  <form method="post" action="/admin/teachers" class="bg-surface-container-lowest dark:bg-surface-dark rounded-2xl p-6 shadow-soft border border-surface-variant dark:border-outline-variant/20 flex flex-col gap-4">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input name="first_name" placeholder="ชื่อ *" required value="<?= $v('first_name') ?>" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
      <input name="last_name" placeholder="นามสกุล *" required value="<?= $v('last_name') ?>" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <select name="faculty_id" id="teacher_faculty" required class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary">
        <option value="" disabled selected>เลือกคณะ...</option>
        <?php foreach ($faculties as $f): ?><option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option><?php endforeach; ?>
      </select>
      <select name="department_id" id="teacher_department" required class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary">
        <option value="" disabled selected>เลือกคณะก่อน...</option>
      </select>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input name="position" placeholder="ตำแหน่งทางวิชาการ" value="<?= $v('position') ?>" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
      <input name="phone" placeholder="เบอร์โทร" value="<?= $v('phone') ?>" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input name="email" type="email" placeholder="อีเมลเข้าสู่ระบบ *" required value="<?= $v('email') ?>" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
      <input name="password" type="password" placeholder="รหัสผ่านเริ่มต้น * (อย่างน้อย 8 ตัว มีตัวอักษร+ตัวเลข)" required value="<?= $v('password') ?>" class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary"/>
    </div>
    <button type="submit" class="w-full h-touch-target bg-primary text-on-primary rounded-lg font-label-md text-label-md flex items-center justify-center gap-2 hover:bg-primary-container active:scale-[0.97] transition-all">
      <span class="material-symbols-outlined">save</span> บันทึกครูนิเทศ
    </button>
  </form>
</div>
<script>
  const teacherFacultyDepartments = <?= json_encode(array_combine(array_column($faculties, 'id'), array_column($faculties, 'departments')), JSON_UNESCAPED_UNICODE) ?>;
  document.getElementById('teacher_faculty')?.addEventListener('change', function () {
    const deptSelect = document.getElementById('teacher_department');
    let options = '<option value="" disabled selected>เลือกสาขา...</option>';
    (teacherFacultyDepartments[this.value] || []).forEach(d => { options += `<option value="${d.id}">${d.name}</option>`; });
    deptSelect.innerHTML = options;
  });
</script>
