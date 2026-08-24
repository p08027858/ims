<?php
/**
 * Daily Log Form
 */
$today = date('Y-m-d');
$thaiDate = date('j F Y', strtotime('+543 years'));
?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
  <div class="mb-6">
    <a href="/student/daily-logs" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-700 mb-2">
      <span class="material-symbols-outlined text-[18px] mr-1">arrow_back</span> กลับหน้ารายการ
    </a>
    <h1 class="text-2xl font-bold text-slate-800">บันทึกงานประจำวัน</h1>
    <p class="text-sm text-slate-500 mt-1"><?= htmlspecialchars($thaiDate) ?></p>
  </div>

  <form id="daily-form" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8 space-y-6">
    <div class="flex flex-col gap-2">
      <label for="log_date" class="text-sm font-semibold text-slate-700">วันที่ปฏิบัติงาน <span class="text-rose-500">*</span></label>
      <input type="date" id="log_date" value="<?= htmlspecialchars($today) ?>" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-indigo-500 outline-none">
    </div>

    <div class="flex flex-col gap-2">
      <label for="title" class="text-sm font-semibold text-slate-700">หัวข้องาน / งานที่ได้รับมอบหมาย <span class="text-rose-500">*</span></label>
      <input type="text" id="title" placeholder="เช่น ติดตั้งระบบเครือข่าย, ออกแบบ UI หน้ารายงาน" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-indigo-500 outline-none">
    </div>

    <div class="flex flex-col gap-2">
      <label for="activity_description" class="text-sm font-semibold text-slate-700">รายละเอียดการปฏิบัติงาน <span class="text-rose-500">*</span></label>
      <textarea id="activity_description" rows="4" placeholder="อธิบายขั้นตอนการทำงาน สิ่งที่ทำในวันนี้อย่างละเอียด..." required class="w-full p-4 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
    </div>

    <div class="flex flex-col gap-2">
      <label for="problems_encountered" class="text-sm font-semibold text-slate-700">ปัญหา / อุปสรรคที่พบ (ถ้ามี)</label>
      <textarea id="problems_encountered" rows="3" placeholder="ระบุปัญหาที่พบในการทำงาน หรือข้อผิดพลาด..." class="w-full p-4 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
    </div>

    <div class="flex flex-col gap-2">
      <label for="learning_outcomes" class="text-sm font-semibold text-slate-700">ความรู้ / ทักษะที่ได้รับ</label>
      <textarea id="learning_outcomes" rows="3" placeholder="สิ่งที่ได้เรียนรู้ใหม่ ทักษะที่พัฒนาขึ้น..." class="w-full p-4 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
    </div>

    <div class="flex flex-col gap-2">
      <label class="text-sm font-semibold text-slate-700">แนบไฟล์รูปภาพการปฏิบัติงาน</label>
      <div id="dropzone" onclick="document.getElementById('file-input').click()" class="border-2 border-dashed border-slate-200 hover:border-indigo-500 bg-slate-50 hover:bg-indigo-50/30 rounded-2xl p-6 text-center cursor-pointer transition-colors flex flex-col items-center justify-center gap-2">
        <input type="file" id="file-input" accept="image/*" class="hidden" onchange="processImage(this)">
        
        <div id="upload-placeholder" class="flex flex-col items-center gap-1">
          <div class="w-10 h-10 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center">
            <span class="material-symbols-outlined text-[24px]">add_photo_alternate</span>
          </div>
          <p class="text-sm font-medium text-indigo-600 mt-1">แตะเพื่อเลือกไฟล์ หรือลากไฟล์มาวางที่นี่</p>
          <p class="text-xs text-slate-400">รองรับภาพถ่ายทุกชนิด</p>
        </div>

        <div id="preview-box" class="hidden flex-col items-center gap-2">
          <img id="image-preview" src="" alt="ตัวอย่างรูปภาพ" class="max-h-48 rounded-xl object-contain shadow-sm border border-slate-200">
          <p id="file-name" class="text-xs text-slate-600 font-medium"></p>
          <button type="button" onclick="removeFile(event)" class="text-xs text-rose-600 hover:underline">ลบรูปภาพ</button>
        </div>
      </div>
    </div>

    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
      <a href="/student/daily-logs" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50">ยกเลิก</a>
      <button type="submit" id="submit-btn" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-indigo-200 transition-transform active:scale-[0.98] flex items-center gap-2">
        <span class="material-symbols-outlined text-[18px]">send</span> <span id="btn-text">ส่งบันทึกนี้</span>
      </button>
    </div>
  </form>
</div>

<script>
let photoBase64 = null;
const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('file-input');

['dragenter', 'dragover'].forEach(n => dropzone.addEventListener(n, e => { e.preventDefault(); dropzone.classList.add('border-indigo-600'); }));
['dragleave', 'drop'].forEach(n => dropzone.addEventListener(n, e => { e.preventDefault(); dropzone.classList.remove('border-indigo-600'); }));

dropzone.addEventListener('drop', e => {
  if (e.dataTransfer.files.length > 0) {
    fileInput.files = e.dataTransfer.files;
    processImage(fileInput);
  }
});

function processImage(input) {
  const file = input.files[0];
  if (!file) return;

  document.getElementById('file-name').textContent = file.name;
  const reader = new FileReader();
  reader.onload = function(e) {
    const img = new Image();
    img.onload = function() {
      const canvas = document.createElement('canvas');
      const MAX_WIDTH = 600;
      const scaleSize = MAX_WIDTH / img.width;
      const width = (img.width > MAX_WIDTH) ? MAX_WIDTH : img.width;
      const height = (img.width > MAX_WIDTH) ? (img.height * scaleSize) : img.height;

      canvas.width = width;
      canvas.height = height;
      const ctx = canvas.getContext('2d');
      ctx.drawImage(img, 0, 0, width, height);

      photoBase64 = canvas.toDataURL('image/jpeg', 0.6);
      document.getElementById('image-preview').src = photoBase64;
      document.getElementById('upload-placeholder').classList.add('hidden');
      document.getElementById('preview-box').classList.remove('hidden');
    };
    img.src = e.target.result;
  };
  reader.readAsDataURL(file);
}

function removeFile(e) {
  e.stopPropagation();
  fileInput.value = '';
  photoBase64 = null;
  document.getElementById('image-preview').src = '';
  document.getElementById('preview-box').classList.add('hidden');
  document.getElementById('upload-placeholder').classList.remove('hidden');
}

document.getElementById('daily-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('submit-btn');
  const btnText = document.getElementById('btn-text');

  btn.disabled = true;
  btnText.textContent = 'กำลังบันทึกข้อมูล...';

  const desc = document.getElementById('activity_description').value;
  const title = document.getElementById('title').value;

  const payload = {
    internship_id: 3,
    student_id: 1,
    log_date: document.getElementById('log_date').value,
    title: title,
    tasks_performed: desc,
    activity_description: desc,
    problems_encountered: document.getElementById('problems_encountered').value,
    learning_outcomes: document.getElementById('learning_outcomes').value,
    photo_url: photoBase64,
    status: 'submitted'
  };

  try {
    const res = await fetch('https://klhrxucugkyzjpufdhtj.supabase.co/rest/v1/daily_logs', {
      method: 'POST',
      headers: {
        'apikey': 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImtsaHJ4dWN1Z2t5empwdWZkaHRqIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDAwNDAyNjEsImV4cCI6MjA1NTYxNjI2MX0.g9iK47R5b8wz1pC0cIkmF6R_q0_4aE1x47Yg2P2V6bE',
        'Authorization': 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImtsaHJ4dWN1Z2t5empwdWZkaHRqIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDAwNDAyNjEsImV4cCI6MjA1NTYxNjI2MX0.g9iK47R5b8wz1pC0cIkmF6R_q0_4aE1x47Yg2P2V6bE',
        'Content-Type': 'application/json',
        'Prefer': 'return=minimal'
      },
      body: JSON.stringify(payload)
    });

    if (res.ok) {
      window.location.href = '/student/daily-logs';
    } else {
      const errData = await res.json();
      alert('บันทึกไม่สำเร็จ: ' + (errData.message || JSON.stringify(errData)));
      btn.disabled = false;
      btnText.textContent = 'ส่งบันทึกนี้';
    }
  } catch (err) {
    alert('เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + err.message);
    btn.disabled = false;
    btnText.textContent = 'ส่งบันทึกนี้';
  }
});
</script>