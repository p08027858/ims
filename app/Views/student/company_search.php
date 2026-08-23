<?php
/**
 * Student Company Search / Directory View
 */
$companies = $companies ?? $items ?? [];
$selectedProvince = $selectedProvince ?? '';
$selectedIndustry = $selectedIndustry ?? '';
$q = $q ?? '';

// รายการจังหวัดและประเภทอุตสาหกรรม
$provinces = ['ทั้งหมด', 'แม่ฮ่องสอน', 'เชียงใหม่', 'เชียงราย', 'ตาก', 'สุโขทัย', 'นครปฐม', 'ฉะเชิงเทรา'];
$industries = ['ทั้งหมด', 'เทคโนโลยีสารสนเทศ', 'ช่างยนต์', 'ช่างไฟฟ้ากำลัง', 'การบัญชี', 'ช่างก่อสร้าง'];
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- กล่องค้นหาและตัวกรอง -->
    <form method="GET" action="/student/companies" class="mb-8 space-y-4">
        <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </span>
            <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="ค้นหาบริษัท หรือ ตำแหน่งงาน..." class="w-full pl-10 pr-24 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none text-slate-800 placeholder-slate-400">
            <button type="submit" class="absolute right-2 top-2 bottom-2 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg text-sm transition-colors">
                ค้นหา
            </button>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="/student/companies" class="px-4 py-2 rounded-xl text-sm font-medium transition-colors <?= (empty($selectedProvince) && empty($selectedIndustry)) ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' ?>">
                ทั้งหมด
            </a>

            <!-- ตัวกรองจังหวัด -->
            <div class="relative inline-block text-left" id="province-dropdown-wrapper">
                <button type="button" onclick="toggleDropdown('province-dropdown')" class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-700 hover:bg-slate-50 flex items-center gap-2">
                    <span>จังหวัด: <?= !empty($selectedProvince) ? htmlspecialchars($selectedProvince) : 'ทั้งหมด' ?></span>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div id="province-dropdown" class="hidden absolute left-0 mt-2 w-48 rounded-xl shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-20 py-1 max-h-60 overflow-y-auto">
                    <?php foreach ($provinces as $p): ?>
                        <a href="/student/companies?province=<?= urlencode($p === 'ทั้งหมด' ? '' : $p) ?>&industry=<?= urlencode($selectedIndustry) ?>&q=<?= urlencode($q) ?>" class="block px-4 py-2 text-sm text-slate-700 hover:bg-indigo-50 hover:text-indigo-600">
                            <?= htmlspecialchars($p) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ตัวกรองประเภทอุตสาหกรรม -->
            <div class="relative inline-block text-left" id="industry-dropdown-wrapper">
                <button type="button" onclick="toggleDropdown('industry-dropdown')" class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-700 hover:bg-slate-50 flex items-center gap-2">
                    <span>ประเภทอุตสาหกรรม: <?= !empty($selectedIndustry) ? htmlspecialchars($selectedIndustry) : 'ทั้งหมด' ?></span>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div id="industry-dropdown" class="hidden absolute left-0 mt-2 w-56 rounded-xl shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-20 py-1 max-h-60 overflow-y-auto">
                    <?php foreach ($industries as $ind): ?>
                        <a href="/student/companies?industry=<?= urlencode($ind === 'ทั้งหมด' ? '' : $ind) ?>&province=<?= urlencode($selectedProvince) ?>&q=<?= urlencode($q) ?>" class="block px-4 py-2 text-sm text-slate-700 hover:bg-indigo-50 hover:text-indigo-600">
                            <?= htmlspecialchars($ind) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </form>

    <!-- การ์ดรายการสถานประกอบการ -->
    <?php if (empty($companies)): ?>
        <div class="text-center py-16 bg-white rounded-2xl border border-slate-100 shadow-sm">
            <div class="w-16 h-16 bg-indigo-50 text-indigo-500 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <h3 class="text-lg font-bold text-slate-800">ไม่พบผลลัพธ์</h3>
            <p class="text-slate-500 text-sm mt-1">ลองปรับเปลี่ยนคำค้นหา หรือตัวกรอง เพื่อค้นหาบริษัทที่เหมาะสมกับคุณ</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($companies as $c): ?>
                <?php 
                    $cName = $c['name'] ?? 'ไม่ระบุชื่อบริษัท';
                    $cProvince = $c['province'] ?? 'แม่ฮ่องสอน';
                    $cIndustry = $c['business_type'] ?? $c['industry_type'] ?? $c['industry'] ?? 'ทั่วไป';
                    $cAddress = $c['address'] ?? '';
                    $cPhone = $c['phone'] ?? $c['contact_phone'] ?? '-';
                    $cSlots = $c['slots_available'] ?? 5;
                ?>
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow p-6 flex flex-col justify-between">
                    <div>
                        <div class="flex items-start gap-4 mb-4">
                            <div class="w-12 h-12 rounded-xl bg-indigo-600 text-white font-bold text-xl flex items-center justify-center flex-shrink-0">
                                <?= mb_substr($cName, 0, 1, 'UTF-8') ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-slate-800 text-base line-clamp-1" title="<?= htmlspecialchars($cName) ?>">
                                    <?= htmlspecialchars($cName) ?>
                                </h3>
                                <p class="text-xs text-indigo-600 font-medium mt-0.5"><?= htmlspecialchars($cIndustry) ?></p>
                                <div class="flex items-center gap-1 text-xs text-slate-400 mt-1">
                                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span class="line-clamp-1"><?= htmlspecialchars($cProvince) ?></span>
                                </div>
                            </div>
                        </div>

                        <p class="text-xs text-slate-500 line-clamp-2 mb-4">
                            <?= htmlspecialchars($cAddress ?: 'ไม่มีรายละเอียดที่อยู่') ?>
                        </p>

                        <div class="flex flex-wrap gap-1.5 mb-4">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-emerald-50 text-emerald-700">
                                รับ <?= (int) $cSlots ?> ตำแหน่ง
                            </span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-slate-100 text-slate-600">
                                โทร: <?= htmlspecialchars($cPhone) ?>
                            </span>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100">
                        <a href="/student/companies/<?= (int) ($c['id'] ?? 0) ?>" class="w-full inline-flex justify-center items-center px-4 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 font-medium rounded-xl text-sm transition-colors">
                            ดูรายละเอียดและยื่นสมัคร
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleDropdown(id) {
    const el = document.getElementById(id);
    const allDropdowns = document.querySelectorAll('[id$="-dropdown"]');
    allDropdowns.forEach(d => {
        if (d.id !== id) d.classList.add('hidden');
    });
    el.classList.toggle('hidden');
}

// ปิด Dropdown เมื่อคลิกนอกพื้นที่
document.addEventListener('click', function(event) {
    if (!event.target.closest('#province-dropdown-wrapper') && !event.target.closest('#industry-dropdown-wrapper')) {
        document.querySelectorAll('[id$="-dropdown"]').forEach(d => d.classList.add('hidden'));
    }
});
</script>