<?php
/**
 * Company GPS location settings. Adapted from design-reference/gps_2.
 * TODO Phase 3: wire to PUT /companies/{id} (API_SPEC.md §3) — validate radius against
 * settings.max_gps_radius_m client-side too (ATTENDANCE_GPS.md §5).
 */
$company = $company ?? ['name' => 'TechNova Solutions Co., Ltd.', 'latitude' => 13.736717, 'longitude' => 100.523186, 'gps_radius_m' => 100];
$maxRadiusM = $maxRadiusM ?? 500;
$formError = $formError ?? null;
?>
<div class="max-w-2xl mx-auto flex flex-col gap-6">
  <div>
    <h1 class="font-headline-lg text-headline-lg text-on-surface dark:text-text-dark-mode mb-2">ตั้งค่าพื้นที่ลงเวลา</h1>
    <p class="font-body-md text-body-md text-on-surface-variant">กำหนดตำแหน่งและรัศมีที่อนุญาตให้นักศึกษาลงเวลาเข้า-ออกงาน</p>
  </div>

  <?php if ($formError): ?>
    <div class="bg-error-container text-on-error-container rounded-lg p-4 font-body-md text-body-md" role="alert"><?= htmlspecialchars($formError) ?></div>
  <?php endif; ?>

  <form method="post" action="/company/profile" class="flex flex-col gap-5">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
    <div class="relative w-full h-72 rounded-xl overflow-hidden shadow-lg bg-surface-container">
      <div class="absolute inset-0 bg-gradient-to-br from-primary/10 to-secondary/10"></div>
      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 flex flex-col items-center cursor-grab active:cursor-grabbing" id="map-pin" title="ลากเพื่อปรับตำแหน่ง">
        <span class="material-symbols-outlined text-primary text-5xl drop-shadow-lg">location_on</span>
      </div>
      <div id="radius-ring" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-primary/40 bg-primary/5 pointer-events-none" style="width:160px;height:160px;"></div>
      <div class="absolute top-4 left-4 bg-surface/90 backdrop-blur-md px-3 py-1.5 rounded-lg shadow-sm">
        <span class="font-label-md text-metadata text-on-surface"><?= htmlspecialchars($company['name']) ?></span>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
      <div class="flex flex-col gap-1">
        <label class="font-label-md text-label-md text-on-surface-variant" for="latitude">Latitude</label>
        <input id="latitude" name="latitude" type="number" step="0.0000001" value="<?= htmlspecialchars($company['latitude']) ?>"
               class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"/>
      </div>
      <div class="flex flex-col gap-1">
        <label class="font-label-md text-label-md text-on-surface-variant" for="longitude">Longitude</label>
        <input id="longitude" name="longitude" type="number" step="0.0000001" value="<?= htmlspecialchars($company['longitude']) ?>"
               class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"/>
      </div>
    </div>

    <div class="flex flex-col gap-1">
      <label class="font-label-md text-label-md text-on-surface-variant" for="gps_radius_m">รัศมีที่อนุญาต (เมตร) — สูงสุด <?= $maxRadiusM ?> เมตร</label>
      <input id="gps_radius_m" name="gps_radius_m" type="number" min="10" max="<?= $maxRadiusM ?>" value="<?= htmlspecialchars($company['gps_radius_m']) ?>"
             class="w-full h-touch-target px-4 bg-surface-container dark:bg-surface-container-high/10 border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface dark:text-text-dark-mode focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"/>
      <p class="font-metadata text-metadata text-on-surface-variant">ค่าเริ่มต้นระบบ 100 เมตร — ปรับตามขนาดพื้นที่จริงของสถานประกอบการ</p>
    </div>

    <button type="submit" class="w-full h-touch-target bg-primary text-on-primary rounded-lg font-label-md text-label-md flex items-center justify-center gap-2 hover:bg-primary-container active:scale-[0.97] transition-all">
      <span class="material-symbols-outlined">save</span> บันทึกตำแหน่ง
    </button>
  </form>
</div>
<script>
  // Visual-only radius preview; TODO Phase 3 — replace with a real interactive map (e.g. Leaflet)
  // once an API key / self-hosted tiles are decided.
  document.getElementById('gps_radius_m')?.addEventListener('input', function () {
    const px = Math.min(280, Math.max(40, Number(this.value) * 1.2));
    document.getElementById('radius-ring').style.width = px + 'px';
    document.getElementById('radius-ring').style.height = px + 'px';
  });
</script>
