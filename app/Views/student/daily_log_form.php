<div class="flex flex-col gap-2">
  <label class="text-sm font-semibold text-slate-700">แนบไฟล์ (สูงสุด 5MB/ไฟล์)</label>
  
  <!-- กล่อง Dropzone -->
  <div id="dropzone" 
       onclick="document.getElementById('file-input').click()"
       class="border-2 border-dashed border-slate-200 hover:border-indigo-500 bg-slate-50 hover:bg-indigo-50/30 rounded-2xl p-6 text-center cursor-pointer transition-colors flex flex-col items-center justify-center gap-2">
    
    <input type="file" id="file-input" name="attachment" accept="image/png, image/jpeg, image/webp, application/pdf" class="hidden" onchange="previewFile(this)">
    
    <div id="upload-placeholder" class="flex flex-col items-center gap-1">
      <div class="w-10 h-10 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center">
        <span class="material-symbols-outlined text-[24px]">upload_file</span>
      </div>
      <p class="text-sm font-medium text-indigo-600 mt-1">แตะเพื่อเลือกไฟล์ หรือลากไฟล์มาวางที่นี่</p>
      <p class="text-xs text-slate-400">รองรับ JPG, PNG, WEBP, PDF</p>
    </div>

    <!-- กล่องแสดงรูปตัวอย่างเมื่อเลือกไฟล์แล้ว -->
    <div id="preview-box" class="hidden flex-col items-center gap-2">
      <img id="image-preview" src="" alt="ตัวอย่างรูปภาพ" class="max-h-48 rounded-xl object-contain shadow-sm border border-slate-200">
      <p id="file-name" class="text-xs text-slate-600 font-medium"></p>
      <button type="button" onclick="removeFile(event)" class="text-xs text-rose-600 hover:underline">ลบรูปภาพ</button>
    </div>
  </div>
</div>

<script>
const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('file-input');

// รองรับการลากไฟล์มาวาง (Drag & Drop)
['dragenter', 'dragover'].forEach(eventName => {
  dropzone.addEventListener(eventName, (e) => {
    e.preventDefault();
    dropzone.classList.add('border-indigo-600', 'bg-indigo-50/50');
  }, false);
});

['dragleave', 'drop'].forEach(eventName => {
  dropzone.addEventListener(eventName, (e) => {
    e.preventDefault();
    dropzone.classList.remove('border-indigo-600', 'bg-indigo-50/50');
  }, false);
});

dropzone.addEventListener('drop', (e) => {
  const dt = e.dataTransfer;
  const files = dt.files;
  if (files.length > 0) {
    fileInput.files = files;
    previewFile(fileInput);
  }
});

function previewFile(input) {
  const file = input.files[0];
  if (file) {
    const previewBox = document.getElementById('preview-box');
    const placeholder = document.getElementById('upload-placeholder');
    const imagePreview = document.getElementById('image-preview');
    const fileName = document.getElementById('file-name');

    fileName.textContent = file.name;

    if (file.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = function(e) {
        imagePreview.src = e.target.result;
        imagePreview.classList.remove('hidden');
      };
      reader.readAsDataURL(file);
    } else {
      imagePreview.classList.add('hidden');
    }

    placeholder.classList.add('hidden');
    previewBox.classList.remove('hidden');
  }
}

function removeFile(e) {
  e.stopPropagation();
  fileInput.value = '';
  document.getElementById('image-preview').src = '';
  document.getElementById('preview-box').classList.add('hidden');
  document.getElementById('upload-placeholder').classList.remove('hidden');
}
</script>