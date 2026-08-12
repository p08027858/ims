<?php
/**
 * GPS check-in/out. Adapted from design-reference/gps_1. Wired to real
 * App\Controllers\AttendanceController (Phase 5) — this is the app's first page that calls a
 * real JSON API via fetch() instead of a plain form POST, because live GPS coordinates
 * (navigator.geolocation) only ever exist in the browser — see ATTENDANCE_GPS.md §1 sequence
 * diagram and AttendanceController's docblock.
 */
$noActiveInternship = $noActiveInternship ?? false;
$today = $today ?? date('j F Y', strtotime('+543 years')); // TODO: use a proper Thai Buddhist calendar formatter
$companyName = $companyName ?? 'บริษัท โกลบอลเทค จำกัด';
$companyLat = $companyLat ?? 13.736717;
$companyLng = $companyLng ?? 100.523186;
$allowedRadiusM = $allowedRadiusM ?? 100;
$minHoursBeforeCheckout = $minHoursBeforeCheckout ?? 4.0;
$photoRequired = $photoRequired ?? false;
$checkedIn = $checkedIn ?? false;
$checkedOut = $checkedOut ?? false;
$checkInAt = $checkInAt ?? null;
$elapsedHours = $elapsedHours ?? 0;
$canCheckout = $elapsedHours >= $minHoursBeforeCheckout;
?>
<div class="flex flex-col gap-6 max-w-lg mx-auto">
  <div class="flex flex-col gap-1">
    <h1 class="font-headline-lg-mobile lg:font-headline-lg text-headline-lg-mobile lg:text-headline-lg text-on-surface dark:text-text-dark-mode">ลงเวลาประจำวัน</h1>
    <p class="font-body-md text-body-md text-on-surface-variant flex items-center gap-2">
      <span class="material-symbols-outlined text-[18px]">calendar_today</span> <?= htmlspecialchars($today) ?>
    </p>
  </div>

  <?php if ($noActiveInternship): ?>
    <div class="flex flex-col items-center justify-center py-16 text-center bg-surface-container-lowest dark:bg-surface-dark rounded-xl border border-surface-variant dark:border-outline-variant/20">
      <span class="material-symbols-outlined text-5xl text-outline-variant mb-3">work_off</span>
      <p class="font-body-md text-body-md text-on-surface-variant">ไม่พบการฝึกงานที่กำลังดำเนินอยู่ของคุณในขณะนี้</p>
    </div>
  <?php elseif ($checkedOut): ?>
    <div class="flex flex-col items-center justify-center py-16 text-center bg-status-success/10 rounded-xl">
      <span class="material-symbols-outlined text-5xl text-status-success mb-3">task_alt</span>
      <p class="font-body-md text-body-md text-on-surface dark:text-text-dark-mode">วันนี้คุณลงเวลาเข้า-ออกงานครบแล้ว</p>
    </div>
  <?php else: ?>
    <div class="relative w-full h-[320px] rounded-xl overflow-hidden shadow-lg bg-surface-container isolate">
      <div class="absolute inset-0 w-full h-full bg-gradient-to-br from-primary/10 to-secondary/10"></div>
      <div class="absolute inset-x-0 top-0 h-16 bg-gradient-to-b from-surface-dark/40 to-transparent pointer-events-none"></div>

      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-[60%] pointer-events-none flex flex-col items-center justify-center">
        <div class="relative w-32 h-32 bg-primary/10 rounded-full flex items-center justify-center shadow-[inset_0_0_0_1px_rgba(53,37,205,0.3)]">
          <div class="absolute w-8 h-8 bg-surface rounded-full shadow-md flex items-center justify-center">
            <span class="material-symbols-outlined text-primary text-[20px]">business</span>
          </div>
        </div>
      </div>

      <div class="absolute top-4 left-4 bg-surface/90 backdrop-blur-md px-3 py-1.5 rounded-lg shadow-sm flex items-center gap-2">
        <span class="material-symbols-outlined text-primary text-[16px]">location_on</span>
        <span class="font-label-md text-metadata text-on-surface"><?= htmlspecialchars($companyName) ?></span>
      </div>
    </div>

    <div id="gps-status" class="rounded-xl p-4 flex items-start gap-3 relative overflow-hidden bg-surface-container dark:bg-surface-container-high/10" role="status" aria-live="polite">
      <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 bg-outline-variant/20">
        <span class="material-symbols-outlined text-[24px] text-on-surface-variant animate-pulse" id="gps-status-icon">my_location</span>
      </div>
      <div class="flex flex-col gap-1">
        <h3 class="font-label-md text-body-md text-on-surface dark:text-text-dark-mode" id="gps-status-title">กำลังขอตำแหน่ง GPS...</h3>
        <p class="font-body-md text-metadata text-on-surface-variant" id="gps-status-detail">กรุณาอนุญาตการเข้าถึงตำแหน่งของเบราว์เซอร์</p>
      </div>
    </div>

    <div id="api-error" class="hidden bg-error-container text-on-error-container rounded-lg p-4 font-body-md text-body-md" role="alert"></div>

    <?php if ($photoRequired): ?>
      <div class="flex flex-col gap-1">
        <label class="font-label-md text-label-md text-on-surface-variant" for="checkin-photo">ถ่ายภาพยืนยัน (บังคับ)</label>
        <input type="file" accept="image/*" capture="environment" id="checkin-photo" class="w-full text-body-md font-body-md text-on-surface-variant file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary-container file:text-on-primary-container"/>
      </div>
    <?php endif; ?>

    <div class="bg-surface-container dark:bg-surface-container-high/10 rounded-xl p-5 flex flex-col gap-4">
      <div class="flex justify-between items-center">
        <div class="flex flex-col gap-0.5">
          <span class="font-body-md text-metadata text-on-surface-variant">เวลาปัจจุบัน</span>
          <span class="font-display-metrics text-display-metrics text-primary tabular-nums" id="live-time">--:--</span>
        </div>
        <?php if ($checkedIn && $checkInAt): ?>
          <div class="flex flex-col items-end gap-0.5">
            <span class="font-body-md text-metadata text-on-surface-variant">เวลาเข้างาน</span>
            <span class="font-label-md text-body-md text-on-surface dark:text-text-dark-mode"><?= htmlspecialchars($checkInAt) ?> น.</span>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!$checkedIn): ?>
      <button type="button" id="checkin-btn" disabled
              class="w-full h-14 bg-primary text-on-primary rounded-xl font-label-md text-body-lg flex items-center justify-center gap-2 shadow-lg shadow-primary/30 transition-transform active:scale-[0.97] relative overflow-hidden disabled:opacity-60">
        <span class="material-symbols-outlined">where_to_vote</span>
        <span id="checkin-btn-label">ยืนยันลงเวลาเข้างาน</span>
      </button>
    <?php elseif (!$canCheckout): ?>
      <button type="button" disabled class="w-full h-14 bg-surface-variant text-on-surface-variant rounded-xl font-label-md text-body-lg flex items-center justify-center gap-2 cursor-not-allowed">
        <span class="material-symbols-outlined">hourglass_top</span>
        ลงเวลาออกได้ในอีก <?= number_format($minHoursBeforeCheckout - $elapsedHours, 1) ?> ชม.
      </button>
    <?php else: ?>
      <button type="button" id="checkout-btn" disabled
              class="w-full h-14 bg-primary text-on-primary rounded-xl font-label-md text-body-lg flex items-center justify-center gap-2 shadow-lg shadow-primary/30 transition-transform active:scale-[0.97] disabled:opacity-60">
        <span class="material-symbols-outlined">logout</span> ยืนยันลงเวลาออกงาน
      </button>
    <?php endif; ?>
  <?php endif; ?>

  <div id="checkin-success" class="hidden fixed inset-0 z-50 bg-status-success/95 flex flex-col items-center justify-center text-on-error text-center px-8">
    <span class="material-symbols-outlined text-7xl mb-4">task_alt</span>
    <p class="font-headline-lg text-headline-lg mb-2" id="success-title">เยี่ยมมาก!</p>
    <p class="font-body-lg text-body-lg" id="success-detail">ลงเวลาเข้างานสำเร็จ 🎉</p>
  </div>
</div>
<script>
  function tick() {
    const el = document.getElementById('live-time');
    if (el) el.textContent = new Date().toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
  }
  tick(); setInterval(tick, 1000);

  const companyLat = <?= json_encode($companyLat) ?>;
  const companyLng = <?= json_encode($companyLng) ?>;
  const allowedRadiusM = <?= json_encode($allowedRadiusM) ?>;
  let currentPosition = null;

  function haversineMeters(lat1, lng1, lat2, lng2) {
    const R = 6371000, toRad = d => d * Math.PI / 180;
    const dPhi = toRad(lat2 - lat1), dLambda = toRad(lng2 - lng1);
    const a = Math.sin(dPhi / 2) ** 2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLambda / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  }

  function showApiError(message) {
    const box = document.getElementById('api-error');
    if (!box) return;
    box.textContent = message;
    box.classList.remove('hidden');
  }

  function readPhotoAsBase64() {
    return new Promise((resolve) => {
      const input = document.getElementById('checkin-photo');
      if (!input || !input.files || !input.files[0]) { resolve(null); return; }
      const reader = new FileReader();
      reader.onload = () => resolve(reader.result);
      reader.onerror = () => resolve(null);
      reader.readAsDataURL(input.files[0]);
    });
  }

  // RULE-ATT-01: request Geolocation permission — button stays disabled until we have a position.
  if (navigator.geolocation && document.getElementById('gps-status')) {
    navigator.geolocation.getCurrentPosition(
      function (pos) {
        currentPosition = pos.coords;
        const distance = Math.round(haversineMeters(pos.coords.latitude, pos.coords.longitude, companyLat, companyLng));
        const inRange = distance <= allowedRadiusM;
        document.getElementById('gps-status-icon').textContent = inRange ? 'check_circle' : 'error';
        document.getElementById('gps-status-icon').classList.remove('animate-pulse');
        document.getElementById('gps-status-title').textContent = inRange ? 'อยู่ในพื้นที่ที่กำหนด' : 'อยู่นอกพื้นที่ที่กำหนด';
        document.getElementById('gps-status-detail').textContent = `คุณอยู่ห่างจากจุดลงเวลาประมาณ ${distance} เมตร (รัศมีที่อนุญาต ${allowedRadiusM} เมตร)`;
        const checkinBtn = document.getElementById('checkin-btn');
        if (checkinBtn) checkinBtn.disabled = false;
        const checkoutBtn = document.getElementById('checkout-btn');
        if (checkoutBtn) checkoutBtn.disabled = false;
      },
      function () {
        document.getElementById('gps-status-icon').textContent = 'location_off';
        document.getElementById('gps-status-icon').classList.remove('animate-pulse');
        document.getElementById('gps-status-title').textContent = 'ไม่ได้รับอนุญาตให้เข้าถึงตำแหน่ง';
        document.getElementById('gps-status-detail').textContent = 'กรุณาอนุญาตการเข้าถึงตำแหน่ง (Location) ของเบราว์เซอร์แล้วลองใหม่';
      },
      { enableHighAccuracy: true, timeout: 10000 }
    );
  }

  async function submitAttendance(endpoint, includePhoto) {
    if (!currentPosition) { showApiError('ไม่พบพิกัด GPS กรุณาอนุญาตการเข้าถึงตำแหน่ง'); return null; }
    const payload = { lat: currentPosition.latitude, lng: currentPosition.longitude, accuracy_m: currentPosition.accuracy };
    if (includePhoto) payload.photo = await readPhotoAsBase64();
    try {
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
      const resp = await fetch(endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken }, body: JSON.stringify(payload) });
      const json = await resp.json();
      if (!json.success) { showApiError(json.error?.message || 'เกิดข้อผิดพลาด'); return null; }
      return json.data;
    } catch (e) {
      showApiError('เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่ (เช่น เซสชันหมดอายุ กรุณาเข้าสู่ระบบใหม่)');
      return null;
    }
  }

  document.getElementById('checkin-btn')?.addEventListener('click', async function () {
    const label = document.getElementById('checkin-btn-label');
    label.textContent = 'กำลังบันทึก...';
    this.disabled = true;
    const data = await submitAttendance('/attendance/checkin', <?= json_encode($photoRequired) ?>);
    if (!data) { label.textContent = 'ยืนยันลงเวลาเข้างาน'; this.disabled = false; return; }
    document.getElementById('success-detail').textContent = 'ลงเวลาเข้างานสำเร็จ 🎉';
    document.getElementById('checkin-success').classList.remove('hidden');
    setTimeout(() => { window.location.reload(); }, 1200);
  });

  document.getElementById('checkout-btn')?.addEventListener('click', async function () {
    this.disabled = true;
    const data = await submitAttendance('/attendance/checkout', false);
    if (!data) { this.disabled = false; return; }
    document.getElementById('success-title').textContent = 'เยี่ยมมาก!';
    document.getElementById('success-detail').textContent = `ลงเวลาออกงานสำเร็จ — รวม ${data.total_hours} ชั่วโมง 🎉`;
    document.getElementById('checkin-success').classList.remove('hidden');
    setTimeout(() => { window.location.reload(); }, 1500);
  });
</script>
