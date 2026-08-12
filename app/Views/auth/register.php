<?php
/**
 * Student self-registration. Adapted from design-reference/light_mode_1.
 * Wired to POST /auth/register (Phase 2, AuthController::register) — form fields match
 * students table in DATA_DICTIONARY.md §8.
 * TODO: faculty/department options below are STILL mock data with hardcoded ids (1-6) that
 * happen to match the current seed order (001_initial_seed.sql) — fragile if faculties are
 * ever reseeded/reordered. Replace with a real GET /faculties + /departments?faculty_id=
 * endpoint (not built yet) instead of the client-side JS mock further down this file.
 */
$registerError = $registerError ?? null; // set by AuthController::register() on validation/API failure
$faculties = $faculties ?? [
    ['id' => 1, 'name' => 'วิศวกรรมศาสตร์', 'departments' => [
        ['id' => 1, 'name' => 'วิศวกรรมคอมพิวเตอร์'], ['id' => 2, 'name' => 'วิศวกรรมไฟฟ้า'],
    ]],
    ['id' => 2, 'name' => 'วิทยาศาสตร์', 'departments' => [
        ['id' => 3, 'name' => 'วิทยาการคอมพิวเตอร์'], ['id' => 4, 'name' => 'เทคโนโลยีสารสนเทศ'],
    ]],
    ['id' => 3, 'name' => 'บริหารธุรกิจ', 'departments' => [
        ['id' => 5, 'name' => 'การตลาด'], ['id' => 6, 'name' => 'การเงิน'],
    ]],
];
?>
<style>
  .blob-bg { position:fixed; top:-10%; left:-10%; width:120%; height:120%; background:radial-gradient(circle at 30% 20%, rgba(79,70,229,.15) 0%, transparent 40%), radial-gradient(circle at 80% 80%, rgba(96,99,238,.1) 0%, transparent 40%); z-index:-1; filter:blur(60px); animation:breathe 20s ease-in-out infinite alternate; }
  @keyframes breathe { 0%{transform:scale(1);} 100%{transform:scale(1.05);} }
  .glass-card { background:rgba(255,255,255,.95); backdrop-filter:blur(10px); box-shadow:0 10px 40px -10px rgba(0,0,0,.08); border:1px solid rgba(255,255,255,.5); }
  .dark .glass-card { background:rgba(30,41,59,.9); border-color:rgba(255,255,255,.05); }
  .floating-input { transition:border-color .2s, box-shadow .2s; }
  .floating-input:focus { border-color:#3525cd; box-shadow:0 0 0 2px rgba(53,37,205,.1); }
  .strength-bar { transition:width .3s ease-in-out, background-color .3s ease-in-out; }
  .shimmer { background:linear-gradient(90deg,#f0ecf9 0%,#ffffff 50%,#f0ecf9 100%); background-size:200% 100%; animation:shimmer 1.5s infinite; }
  @keyframes shimmer { 0%{background-position:200% 0;} 100%{background-position:-200% 0;} }
</style>
<div class="blob-bg"></div>
<div class="w-full max-w-[500px] z-10 mx-auto">
  <div class="glass-card rounded-[24px] p-6 md:p-10 relative overflow-hidden">
    <div class="text-center mb-8">
      <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-primary-container text-on-primary-container mb-4 shadow-sm">
        <span class="material-symbols-outlined text-3xl fill">school</span>
      </div>
      <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-primary mb-2">สมัครสมาชิกนักศึกษา</h1>
      <p class="font-body-md text-body-md text-on-surface-variant">เริ่มต้นการฝึกงานของคุณได้ในไม่กี่ขั้นตอน!</p>
    </div>

    <?php if ($registerError): ?>
      <div class="bg-error-container text-on-error-container rounded-lg p-4 font-body-md text-body-md text-center mb-5 flex items-center gap-2 justify-center" role="alert">
        <span class="material-symbols-outlined text-[20px]">error</span> <?= htmlspecialchars($registerError) ?>
      </div>
    <?php endif; ?>

    <form class="space-y-5" id="registrationForm" method="post" action="/auth/register">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
          <label class="block font-label-md text-label-md text-on-surface mb-1" for="studentId">รหัสนักศึกษา</label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline" aria-hidden="true">badge</span>
            <input class="w-full h-touch-target pl-10 pr-4 bg-surface-container-lowest border border-outline-variant rounded-xl floating-input font-body-md text-body-md text-on-surface placeholder:text-outline/60 focus:outline-none" id="studentId" name="student_code" placeholder="เช่น 6512345678" required type="text"/>
          </div>
        </div>
        <div>
          <label class="block font-label-md text-label-md text-on-surface mb-1" for="fullName">ชื่อ-นามสกุล</label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline" aria-hidden="true">person</span>
            <input class="w-full h-touch-target pl-10 pr-4 bg-surface-container-lowest border border-outline-variant rounded-xl floating-input font-body-md text-body-md text-on-surface placeholder:text-outline/60 focus:outline-none" id="fullName" name="full_name" placeholder="ชื่อจริง นามสกุล" required type="text"/>
          </div>
        </div>
      </div>

      <div>
        <label class="block font-label-md text-label-md text-on-surface mb-1" for="citizenId">เลขบัตรประชาชน</label>
        <div class="relative">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline" aria-hidden="true">badge</span>
          <input class="w-full h-touch-target pl-10 pr-4 bg-surface-container-lowest border border-outline-variant rounded-xl floating-input font-body-md text-body-md text-on-surface placeholder:text-outline/60 focus:outline-none tracking-widest" id="citizenId" name="citizen_id" placeholder="13 หลัก ไม่ต้องเว้นวรรค" required type="text" inputmode="numeric" maxlength="13" pattern="\d{13}" title="เลขบัตรประชาชน 13 หลัก"/>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
          <label class="block font-label-md text-label-md text-on-surface mb-1" for="faculty">คณะ</label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline" aria-hidden="true">account_balance</span>
            <select class="w-full h-touch-target pl-10 pr-10 bg-surface-container-lowest border border-outline-variant rounded-xl floating-input font-body-md text-body-md text-on-surface appearance-none focus:outline-none" id="faculty" name="faculty_id" required>
              <option disabled selected value="">เลือกคณะ...</option>
              <?php foreach ($faculties as $f): ?>
                <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline pointer-events-none" aria-hidden="true">expand_more</span>
          </div>
        </div>
        <div>
          <label class="block font-label-md text-label-md text-on-surface mb-1" for="department">สาขา</label>
          <div class="relative" id="departmentContainer">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline z-10" aria-hidden="true">category</span>
            <select class="w-full h-touch-target pl-10 pr-10 bg-surface-variant/30 border border-outline-variant/50 text-on-surface-variant rounded-xl font-body-md appearance-none" disabled id="department" name="department_id">
              <option value="">กรุณาเลือกคณะก่อน</option>
            </select>
            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline/50 pointer-events-none z-10" aria-hidden="true">expand_more</span>
            <div class="absolute inset-0 rounded-xl shimmer hidden z-20 border border-outline-variant/30" id="departmentLoader"></div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="md:col-span-1">
          <label class="block font-label-md text-label-md text-on-surface mb-1" for="year">ชั้นปี</label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline" aria-hidden="true">format_list_numbered</span>
            <select class="w-full h-touch-target pl-10 pr-8 bg-surface-container-lowest border border-outline-variant rounded-xl floating-input font-body-md text-body-md text-on-surface appearance-none focus:outline-none" id="year" name="year_level" required>
              <option value="3">ปี 3</option>
              <option selected value="4">ปี 4</option>
            </select>
            <span class="material-symbols-outlined absolute right-2 top-1/2 -translate-y-1/2 text-outline pointer-events-none" aria-hidden="true">expand_more</span>
          </div>
        </div>
        <div class="md:col-span-2">
          <label class="block font-label-md text-label-md text-on-surface mb-1" for="email">อีเมล</label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline" aria-hidden="true">mail</span>
            <input class="w-full h-touch-target pl-10 pr-4 bg-surface-container-lowest border border-outline-variant rounded-xl floating-input font-body-md text-body-md text-on-surface placeholder:text-outline/60 focus:outline-none" id="email" name="email" placeholder="student@university.ac.th" required type="email"/>
          </div>
        </div>
      </div>

      <div>
        <label class="block font-label-md text-label-md text-on-surface mb-1" for="password">รหัสผ่าน</label>
        <div class="relative">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline" aria-hidden="true">lock</span>
          <input class="w-full h-touch-target pl-10 pr-10 bg-surface-container-lowest border border-outline-variant rounded-xl floating-input font-body-md text-body-md text-on-surface focus:outline-none tracking-widest" id="password" name="password" oninput="checkPasswordStrength()" placeholder="อย่างน้อย 8 ตัวอักษร" required type="password"/>
          <button class="absolute right-3 top-1/2 -translate-y-1/2 text-outline hover:text-primary transition-colors focus:outline-none" onclick="togglePassword('password','toggleIcon1')" type="button" aria-label="แสดง/ซ่อนรหัสผ่าน">
            <span class="material-symbols-outlined" id="toggleIcon1">visibility</span>
          </button>
        </div>
        <div class="mt-2">
          <span class="font-metadata text-metadata text-outline" id="strengthText">ความปลอดภัยของรหัสผ่าน</span>
          <div class="h-1.5 w-full bg-surface-variant rounded-full overflow-hidden mt-1">
            <div class="h-full strength-bar w-0 bg-status-error rounded-full" id="strengthBar"></div>
          </div>
        </div>
      </div>

      <div>
        <label class="block font-label-md text-label-md text-on-surface mb-1" for="confirmPassword">ยืนยันรหัสผ่าน</label>
        <div class="relative">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline" aria-hidden="true">lock_reset</span>
          <input class="w-full h-touch-target pl-10 pr-10 bg-surface-container-lowest border border-outline-variant rounded-xl floating-input font-body-md text-body-md text-on-surface focus:outline-none tracking-widest" id="confirmPassword" name="password_confirmation" placeholder="อย่างน้อย 8 ตัวอักษร" required type="password"/>
        </div>
      </div>

      <div class="flex items-start mt-4 pt-2">
        <input class="w-5 h-5 border-outline-variant rounded bg-surface-container-lowest text-primary focus:ring-primary/20 focus:ring-2 cursor-pointer" id="terms" name="accept_terms" required type="checkbox"/>
        <label class="ml-3 font-metadata text-metadata text-on-surface-variant cursor-pointer" for="terms">
          ข้าพเจ้ายอมรับ <a class="text-primary font-medium hover:underline" href="#">ข้อตกลงการใช้งาน</a> และ <a class="text-primary font-medium hover:underline" href="#">นโยบายความเป็นส่วนตัว</a>
        </label>
      </div>

      <div class="pt-4">
        <button class="w-full h-[52px] bg-gradient-to-r from-primary to-surface-tint text-on-primary rounded-xl font-headline-md shadow-[0_4px_14px_0_rgba(53,37,205,0.39)] hover:shadow-[0_6px_20px_rgba(53,37,205,0.23)] hover:-translate-y-0.5 active:scale-[0.98] transition-all duration-200 relative overflow-hidden group" id="submitBtn" type="submit">
          <div class="flex items-center justify-center gap-2" id="btnContent">
            <span>สมัครสมาชิก</span>
            <span class="material-symbols-outlined transition-transform group-hover:translate-x-1">arrow_forward</span>
          </div>
        </button>
      </div>
      <div class="text-center mt-6">
        <p class="font-metadata text-metadata text-on-surface-variant">มีบัญชีอยู่แล้ว? <a class="text-primary font-medium hover:underline" href="/login">เข้าสู่ระบบ</a></p>
      </div>
    </form>
  </div>
</div>
<script>
  const facultyDepartments = <?= json_encode(array_combine(array_column($faculties, 'id'), array_column($faculties, 'departments')), JSON_UNESCAPED_UNICODE) ?>;

  function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    icon.textContent = show ? 'visibility_off' : 'visibility';
  }

  function checkPasswordStrength() {
    const pwd = document.getElementById('password').value;
    const bar = document.getElementById('strengthBar');
    const text = document.getElementById('strengthText');
    let strength = 0;
    if (pwd.length > 0) strength++;
    if (pwd.length > 5) strength++;
    if (pwd.length > 7 && /[A-Z]/.test(pwd) && /[0-9]/.test(pwd)) strength++;
    bar.className = 'h-full strength-bar rounded-full';
    const map = {
      0: ['0%', 'ความปลอดภัยของรหัสผ่าน', ''],
      1: ['33%', 'อ่อน (ควรเพิ่มความยาว)', 'bg-status-error'],
      2: ['66%', 'ปานกลาง', 'bg-status-warning'],
      3: ['100%', 'แข็งแรง', 'bg-status-success'],
    };
    const [width, label, cls] = map[strength];
    bar.style.width = width;
    text.textContent = label;
    if (cls) bar.classList.add(cls);
  }

  // TODO Phase 1: replace this client-side mock with GET /departments?faculty_id= (API_SPEC.md)
  document.getElementById('faculty').addEventListener('change', function () {
    const facultyId = this.value;
    const deptSelect = document.getElementById('department');
    const loader = document.getElementById('departmentLoader');
    if (!facultyId) return;
    loader.classList.remove('hidden');
    deptSelect.disabled = true;
    deptSelect.innerHTML = '<option value="">กำลังโหลด...</option>';
    setTimeout(() => {
      loader.classList.add('hidden');
      deptSelect.disabled = false;
      deptSelect.classList.remove('bg-surface-variant/30', 'text-on-surface-variant');
      deptSelect.classList.add('bg-surface-container-lowest', 'floating-input', 'text-on-surface');
      let options = '<option value="" disabled selected>เลือกสาขา...</option>';
      (facultyDepartments[facultyId] || []).forEach(d => {
        options += `<option value="${d.id}">${d.name}</option>`;
      });
      deptSelect.innerHTML = options;
    }, 500);
  });

  document.getElementById('registrationForm').addEventListener('submit', function (e) {
    const pwd = document.getElementById('password').value;
    const confirm = document.getElementById('confirmPassword').value;
    if (pwd !== confirm) {
      e.preventDefault();
      document.getElementById('confirmPassword').classList.add('border-status-error', 'shake-anim');
      setTimeout(() => document.getElementById('confirmPassword').classList.remove('border-status-error', 'shake-anim'), 500);
    }
    // TODO Phase 2: submit via fetch() to POST /auth/register instead of full page post,
    // then redirect to /pending-approval on 201 (API_SPEC.md §1).
  });
</script>
