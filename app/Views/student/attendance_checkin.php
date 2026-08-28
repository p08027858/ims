<?php
/**
 * GPS check-in/out. Wired to App\Controllers\AttendanceController
 */
$hasInternship = !empty($internship) || !empty($currentInternship);
$noActiveInternship = ($noActiveInternship ?? false) && !$hasInternship;
$today = $today ?? date('j F Y', strtotime('+543 years'));
$companyName = $companyName ?? ($company['name'] ?? 'บริษัท โกลบอลเทค จำกัด');
$companyLat = $companyLat ?? ($company['latitude'] ?? 18.163351);
$companyLng = $companyLng ?? ($company['longitude'] ?? 97.933800);
$minHoursBeforeCheckout = $minHoursBeforeCheckout ?? 4.0;
$photoRequired = $photoRequired ?? false;
$allowedRadiusM = (float) ($company['gps_radius_m'] ?? 100);
$checkedIn = !empty($todayAttendance['check_in_time']) || ($checkedIn ?? false);
$checkedOut = !empty($todayAttendance['check_out_time']) || ($checkedOut ?? false);
$checkInAt = !empty($todayAttendance['check_in_time']) ? date('H:i', strtotime($todayAttendance['check_in_time'])) : ($checkInAt ?? null);
$elapsedHours = $elapsedHours ?? 0;
$canCheckout = $elapsedHours >= $minHoursBeforeCheckout;
?>
<div class="flex flex-col gap-6 max-w-lg mx-auto">
  <div class="flex flex-col gap-1">
    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">ลงเวลาประจำวัน</h1>
    <p class="text-sm text-slate-500 flex items-center gap-2">
      <span class="material-symbols-outlined text-[18px]">calendar_today</span> <?= htmlspecialchars($today) ?>
    </p>
  </div>

  <?php if ($noActiveInternship): ?>
    <div class="flex flex-col items-center justify-center py-16 text-center bg-white rounded-xl border border-slate-100 shadow-sm">
      <span class="material-symbols-outlined text-5xl text-slate-300 mb-3">work_off</span>
      <p class="text-sm text-slate-500">ไม่พบการฝึกงานที่กำลังดำเนินอยู่ของคุณในขณะนี้</p>
    </div>
  <?php elseif ($checkedOut): ?>
    <div class="flex flex-col items-center justify-center py-16 text-center bg-emerald-50 rounded-xl border border-emerald-100">
      <span class="material-symbols-outlined text-5xl text-emerald-600 mb-3">task_alt</span>
      <p class="text-base font-semibold text-slate-800">วันนี้คุณลงเวลาเข้า-ออกงานครบแล้ว</p>
    </div>
  <?php else: ?>
    <div class="relative w-full h-[280px] rounded-2xl overflow-hidden shadow-sm bg-slate-100 isolate border border-slate-200">
      <div class="absolute inset-0 w-full h-full bg-gradient-to-br from-indigo-50 to-blue-50"></div>

      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 flex flex-col items-center justify-center">
        <div class="relative w-28 h-28 bg-indigo-100/60 rounded-full flex items-center justify-center animate-pulse">
          <div class="w-12 h-12 bg-indigo-600 text-white rounded-full shadow-md flex items-center justify-center">
            <span class="material-symbols-outlined text-[24px]">business</span>
          </div>
        </div>
      </div>

      <div class="absolute top-4 left-4 bg-white/90 backdrop-blur-md px-3.5 py-1.5 rounded-xl shadow-sm flex items-center gap-2 border border-slate-100">
        <span class="material-symbols-outlined text-indigo-600 text-[18px]">location_on</span>
        <span class="text-xs font-semibold text-slate-700"><?= htmlspecialchars($companyName) ?></span>
      </div>
    </div>

    <div id="gps-status" class="rounded-xl p-4 flex items-start gap-3 bg-white border border-slate-100 shadow-sm" role="status">
      <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 bg-emerald-50 text-emerald-600">
        <span class="material-symbols-outlined text-[24px]" id="gps-status-icon">check_circle</span>
      </div>
      <div class="flex flex-col gap-0.5">
        <h3 class="text-sm font-semibold text-slate-800" id="gps-status-title">อยู่ในพื้นที่ที่กำหนด</h3>
        <p class="text-xs text-slate-500" id="gps-status-detail">พิกัด GPS พร้อมสำหรับการลงเวลา</p>
      </div>
    </div>

    <div id="api-error" class="hidden bg-rose-50 border border-rose-100 text-rose-700 rounded-xl p-4 text-xs" role="alert"></div>

    <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm flex justify-between items-center">
      <div class="flex flex-col">
        <span class="text-xs text-slate-400">เวลาปัจจุบัน</span>
        <span class="text-3xl font-bold text-indigo-600 tracking-tight" id="live-time">--:--</span>
      </div>
      <?php if ($checkedIn && $checkInAt): ?>
        <div class="flex flex-col items-end">
          <span class="text-xs text-slate-400">เวลาเข้างาน</span>
          <span class="text-base font-semibold text-slate-700"><?= htmlspecialchars($checkInAt) ?> น.</span>
        </div>
      <?php endif; ?>
    </div>

    <?php if (!$checkedIn): ?>
      <button type="button" id="checkin-btn"
              class="w-full h-14 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl font-semibold text-base flex items-center justify-center gap-2 shadow-md shadow-indigo-200 transition-transform active:scale-[0.98]">
        <span class="material-symbols-outlined">where_to_vote</span>
        <span id="checkin-btn-label">ยืนยันลงเวลาเข้างาน</span>
      </button>
    <?php else: ?>
      <button type="button" id="checkout-btn"
              class="w-full h-14 bg-rose-600 hover:bg-rose-700 text-white rounded-2xl font-semibold text-base flex items-center justify-center gap-2 shadow-md shadow-rose-200 transition-transform active:scale-[0.98]">
        <span class="material-symbols-outlined">logout</span>
        <span id="checkout-btn-label">ยืนยันลงเวลาออกงาน</span>
      </button>
    <?php endif; ?>
  <?php endif; ?>

  <div id="checkin-success" class="hidden fixed inset-0 z-50 bg-indigo-900/90 backdrop-blur-sm flex flex-col items-center justify-center text-white text-center px-8">
    <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mb-4">
      <span class="material-symbols-outlined text-5xl text-emerald-400">task_alt</span>
    </div>
    <p class="text-2xl font-bold mb-2" id="success-title">เรียบร้อย!</p>
    <p class="text-base text-indigo-200" id="success-detail">ลงเวลาสำเร็จ 🎉</p>
  </div>
</div>

<script>
  function tick() {
    const el = document.getElementById('live-time');
    if (el) el.textContent = new Date().toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
  }
  tick(); setInterval(tick, 1000);

  let currentPosition = null;

  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      function (pos) {
        currentPosition = pos.coords;
      },
      function () {
        showApiError('Unable to read GPS location. Please allow location access and try again.');
      },
      { enableHighAccuracy: true, timeout: 5000 }
    );
  }

  function showApiError(message) {
    const box = document.getElementById('api-error');
    if (!box) return;
    box.textContent = message;
    box.classList.remove('hidden');
  }

  async function submitAttendance(type) {
    const payload = {
      latitude: currentPosition?.latitude,
      longitude: currentPosition?.longitude,
      type: type
    };
    if (!currentPosition) {
      showApiError('Waiting for GPS location. Please try again.');
      return false;
    }
    try {
      const resp = await fetch('/student/attendance', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': <?= json_encode(\App\Support\Session::csrfToken()) ?>
        },
        body: JSON.stringify(payload)
      });
      const result = await resp.json().catch(() => ({}));
      if (!resp.ok || result.success !== true) {
        showApiError(result?.error?.message || 'Unable to save attendance.');
        return false;
      }
      return true;
    } catch (e) {
      showApiError('Unable to connect to the server. Please try again.');
      return false;
    }
  }

  document.getElementById('checkin-btn')?.addEventListener('click', async function () {
    const label = document.getElementById('checkin-btn-label');
    if (label) label.textContent = 'กำลังบันทึก...';
    this.disabled = true;
    if (!await submitAttendance('in')) { this.disabled = false; return; }
    document.getElementById('success-detail').textContent = 'ลงเวลาเข้างานสำเร็จ 🎉';
    document.getElementById('checkin-success').classList.remove('hidden');
    setTimeout(() => { window.location.reload(); }, 1200);
  });

  document.getElementById('checkout-btn')?.addEventListener('click', async function () {
    const label = document.getElementById('checkout-btn-label');
    if (label) label.textContent = 'กำลังบันทึก...';
    this.disabled = true;
    if (!await submitAttendance('out')) { this.disabled = false; return; }
    document.getElementById('success-title').textContent = 'เยี่ยมมาก!';
    document.getElementById('success-detail').textContent = 'ลงเวลาออกงานสำเร็จ 🎉';
    document.getElementById('checkin-success').classList.remove('hidden');
    setTimeout(() => { window.location.reload(); }, 1200);
  });
</script>
