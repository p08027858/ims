# IMS THAI — Internship Management System (App Code)

โค้ดเว็บแอปจริงของระบบบริหารจัดการการฝึกงาน สร้างต่อจาก Blueprint เอกสารทั้งหมดที่ [`d:\vscode isms\Internship_Project_Blueprint\`](../vscode%20isms/Internship_Project_Blueprint/) และดีไซน์จาก Google Stitch ที่เก็บถาวรไว้ใน [`design-reference/`](design-reference/)

## สถานะปัจจุบัน: Phase 2 — Authentication & RBAC Middleware (เขียนเสร็จ ยังไม่ได้รันทดสอบจริง)

- ✅ Phase 0: โครงสร้าง MVC, layout กลาง, 33 views ครบทุก role
- ✅ Phase 1: Supabase project live, schema+seed data+Super Admin คนแรก verified จริงแล้ว
- ✅ Phase 2 (เขียนเสร็จ): `POST /auth/register`, `/auth/login`, `GET /logout`, `/auth/password/forgot`, `/auth/password/reset` ทำงานผ่าน Supabase Auth จริง, middleware กันสิทธิ์ตาม role/status ทุก route, custom lockout 5 ครั้ง/15 นาที (RULE-AUTH-03), หน้า login/register ต่อจริงแล้ว
- ⚠️ **เครื่องนี้ยังไม่มี PHP ติดตั้ง จึงยังไม่เคยรันทดสอบโค้ด Phase 2 จริงเลยสักครั้ง** — โค้ดเขียนตามสเปกและตรวจทานเทียบ RULE-AUTH-*/TC-AUTH-* ด้วยมือแล้ว แต่ยังมีความเสี่ยง bug ที่มองไม่เห็นจนกว่าจะรันจริง (ดู ISSUES.md รายการ "Phase 2 — ยังไม่ได้ทดสอบจริง")
- ❌ ยังไม่มี: business data ของ Phase 3+ (ทุกอย่างนอกจาก auth/session ยังเป็น mock data)

ดูแผนงานเต็มที่ [AI_AGENT_PHASES.md](../vscode%20isms/Internship_Project_Blueprint/AI_AGENT_PHASES.md)

## วิธีรันดูหน้าตา (หลังติดตั้ง PHP)

เครื่องนี้ยังไม่มี PHP ติดตั้ง ต้องติดตั้งก่อน (ไม่ต้องมี MySQL/XAMPP MySQL module แล้ว — ฐานข้อมูลอยู่บน Supabase ดู [DEPLOYMENT.md](../vscode%20isms/Internship_Project_Blueprint/DEPLOYMENT.md) §1-3) จากนั้น:

```powershell
# ตัวเลือก A: PHP built-in server (เร็วที่สุดสำหรับดูหน้าตา)
cd D:\ims-app
php -S localhost:8000 -t public

# ตัวเลือก B: XAMPP — วางโฟลเดอร์นี้ทั้งหมดใน C:\xampp\htdocs\ims-app
# แล้วตั้งค่า Document Root ไปที่ C:\xampp\htdocs\ims-app\public
```

เปิดเบราว์เซอร์ที่ `http://localhost:8000/login` แล้วไล่ดูหน้าอื่นได้ตาม path ใน `config/routes.php` เช่น `/student/dashboard`, `/company/dashboard`, `/admin/dashboard`

## โครงสร้างโฟลเดอร์

```
app/
  Controllers/
    AuthController.php    register/login/logout/forgotPassword/resetPassword (Phase 2)
  Models/          ว่าง — เติมใน Phase 3+
  Services/
    SupabaseClient.php     cURL wrapper รอบ Supabase Auth + PostgREST (service_role key)
    AuthService.php        RULE-AUTH-01..05 business logic, ใช้ SupabaseClient
    SupabaseException.php / AuthException.php
  Support/
    Session.php            native PHP session wrapper (cookie ปลอดภัย)
    View.php                render helper ใช้ร่วมกันระหว่าง index.php กับ controller ที่ re-render form พร้อม error
  Middleware/
    AuthGuard.php           requireRole() — ตรวจ session/status/role ก่อน render ทุก route ที่ไม่ใช่ guest
  Views/
    layouts/app.php      shell กลาง (head, tailwind config, sidebar/topbar wrapper)
    partials/             sidebar, topbar, bottom_nav, notification_dropdown, signature_pad
    auth/ student/ company/ teacher/ admin/ super_admin/   หน้าจอตาม role
config/
  routes.php               ตาราง route → view (path ตรงกับ SITEMAP.md) — `role` คือ RBAC requirement จริงตั้งแต่ Phase 2
  actions.php              ตาราง [METHOD, path] → [Controller, method] สำหรับ POST/action (Phase 2)
  supabase.php             ค่าจริงของโปรเจกต์ Supabase (gitignored) — คัดลอกจาก supabase.example.php
database/
  seeds/001_initial_seed.sql   seed data ที่รันแล้วจริงใน Supabase (Phase 1)
public/
  index.php                front controller เดียว (ทุก request ผ่านที่นี่) — มี autoloader ในตัว ไม่ใช้ Composer
  assets/img/               placeholder SVG (โลโก้, ภาพประกอบ) — ต้องเปลี่ยนเป็นของจริงก่อน production
storage/uploads/            โฟลเดอร์อัปโหลดไฟล์ (daily_logs, leave_certificates, signatures, avatars)
design-reference/            ไฟล์ดิบจาก Stitch export (code.html + screen.png ต่อหน้าจอ) เก็บถาวรอ้างอิง
```

## ข้อควรรู้ก่อนพัฒนาต่อ

1. **Tailwind ใช้ CDN อยู่** (`<script src="https://cdn.tailwindcss.com">` ใน `layouts/app.php`) เพราะเครื่องนี้ไม่มี Node/npm — ต้องย้ายไป Tailwind CLI build ก่อนขึ้น production จริง (ดู MASTER_SPEC.md §9.2)
2. **รูปภาพ placeholder** ในหลายหน้า (โลโก้, avatar) เป็น SVG ที่สร้างขึ้นเอง ไม่ใช่รูปจริง ต้องเปลี่ยนก่อน production
3. **ทุก TODO comment ในโค้ด** อ้างอิง section ที่เกี่ยวข้องใน Blueprint (เช่น `API_SPEC.md §8`, `RULE-EVAL-04`) — ตามลิงก์เหล่านั้นเวลาต่อ backend จริง
4. หน้าจอ `_2` และ `_8` ในดีไซน์ต้นฉบับตีความแล้วว่าเป็น notification-panel demo และ company dashboard variant ตามลำดับ — ดู `design-reference/` ถ้าต้องการเทียบภาพต้นฉบับ
5. Field name ในฟอร์มทุกหน้ายึดตาม [DATA_DICTIONARY.md](../vscode%20isms/Internship_Project_Blueprint/DATA_DICTIONARY.md) แล้ว — ตอนเขียน Controller/Model ให้ map ตรงชื่อได้เลย

## ⚠️ สำคัญ: ยังไม่เคยรันทดสอบ Phase 2 จริง

เครื่องพัฒนาไม่มี PHP ติดตั้ง (ผู้ใช้ทราบแล้ว ยืนยันให้เขียนโค้ดไปก่อน) — เมื่อติดตั้ง PHP แล้ว ให้ทดสอบตามลำดับนี้ก่อนเชื่อถือว่า Phase 2 ใช้งานได้จริง:
1. `php -S localhost:8000 -t public` แล้วเปิด `/register` สมัครนักศึกษาใหม่ → เช็คใน Supabase ว่ามีแถวใน `auth.users`+`public.users`+`public.students` ครบ, ถูก redirect ไป `/pending-approval`
2. ใน Supabase SQL editor สั่ง `update public.users set status='active' where email='...';` (จำลอง Admin อนุมัติ เพราะ Phase 8 ยังไม่มี UI จริง) แล้วลอง login ที่ `/login`
3. ลอง login ผิดรหัส 5 ครั้งติด → ครั้งที่ 6 ต้องโดน `ACCOUNT_LOCKED` แม้กรอกรหัสถูก
4. Login สำเร็จแล้วลองเข้า `/admin/dashboard` ต้องโดน 403
5. กด "ออกจากระบบ" แล้วลองเข้า `/student/dashboard` ตรงๆ ต้องถูกเด้งไป `/login`

## ขั้นตอนถัดไป

ทำตาม [AI_AGENT_PHASES.md](../vscode%20isms/Internship_Project_Blueprint/AI_AGENT_PHASES.md) Phase 3 เป็นต้นไป (Organization & Company Management) แล้วค่อยแทนที่ mock data ในแต่ละ view ด้วยข้อมูลจริงจาก Controller ทีละหน้า
