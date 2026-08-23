<?php
/**
 * Student Applications View
 */
$list = $applications ?? $items ?? $myApplications ?? [];
?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">ใบสมัครของฉัน</h1>
            <p class="text-slate-500 text-sm mt-1">สถานะใบสมัครฝึกงานทั้งหมดที่เคยยื่น</p>
        </div>
        <a href="/student/companies" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-xl transition-colors">
            ค้นหาสถานประกอบการเพิ่ม
        </a>
    </div>

    <?php if (empty($list)): ?>
        <div class="text-center py-16 bg-white rounded-2xl border border-slate-100 shadow-sm">
            <div class="w-16 h-16 bg-indigo-50 text-indigo-500 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <h3 class="text-lg font-bold text-slate-800">ยังไม่มีใบสมัคร</h3>
            <div class="mt-4">
                <a href="/student/companies" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-xl transition-colors inline-block">
                    ค้นหาสถานประกอบการ
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($list as $app): ?>
                <?php 
                    $compName = $app['company']['name'] ?? $app['company_name'] ?? 'สถานประกอบการ';
                    $pos = $app['position'] ?? 'นักศึกษาฝึกงาน';
                    $status = $app['status'] ?? 'pending';
                    $date = !empty($app['created_at']) ? date('d/m/Y H:i', strtotime($app['created_at'])) : '-';
                    $notes = $app['cover_letter'] ?? $app['notes'] ?? '-';
                ?>
                <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3">
                            <h3 class="font-bold text-slate-800 text-lg"><?= htmlspecialchars($compName) ?></h3>
                            <?php if ($status === 'approved'): ?>
                                <span class="px-3 py-1 bg-emerald-50 text-emerald-700 text-xs font-semibold rounded-full">อนุมัติแล้ว</span>
                            <?php elseif ($status === 'rejected'): ?>
                                <span class="px-3 py-1 bg-rose-50 text-rose-700 text-xs font-semibold rounded-full">ปฏิเสธ</span>
                            <?php else: ?>
                                <span class="px-3 py-1 bg-amber-50 text-amber-700 text-xs font-semibold rounded-full">รอพิจารณา</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-sm text-indigo-600 font-medium mt-1">ตำแหน่ง: <?= htmlspecialchars($pos) ?></p>
                        <p class="text-xs text-slate-500 mt-2">ข้อความถึงสถานประกอบการ: <?= htmlspecialchars($notes) ?></p>
                        <p class="text-xs text-slate-400 mt-1">วันที่ยื่น: <?= htmlspecialchars($date) ?></p>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-400">สถานะ: รอการติดต่อกลับ</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>