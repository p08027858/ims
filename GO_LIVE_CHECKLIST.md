# Go-Live Checklist

Checklist นี้ใช้ก่อนส่งลิงก์ระบบให้เพื่อนหรือผู้ใช้จริงทดลองใช้งานบน production

## เป้าหมาย

- ยืนยันว่าเว็บ production เปิดใช้งานได้จริง
- ยืนยันว่า Railway ชี้ไป Supabase production ถูกตัว
- ยืนยันว่า flow สมัคร, login, และใช้งานหลักไม่พัง
- ลดความเสี่ยงเรื่องข้อมูลจริงเสียหายหรือหลุดไป dev environment

## ข้อมูลที่ควรเตรียมก่อนตรวจ

- Production URL ของระบบ
- Railway project ที่ใช้ deploy
- Supabase project ที่เป็น production
- GitHub branch ที่ Railway ดึงขึ้น deploy
- บัญชี admin สำหรับตรวจสอบข้อมูล
- บัญชีทดสอบใหม่ 1 บัญชีสำหรับสมัครใช้งานจริง

## 1. ตรวจสภาพแวดล้อม Production

1. เปิด production URL จาก browser ปกติ
2. ยืนยันว่าโหลดหน้าแรกได้ ไม่มี `500`, `502`, `503`
3. ตรวจว่า Railway service ตัวที่รันอยู่คือ service ที่ถูกต้อง
4. ตรวจว่า branch ที่ deploy ล่าสุดเป็น branch ที่ต้องการ
5. ตรวจวันเวลา deploy ล่าสุดใน Railway
6. ตรวจว่า environment variables บน Railway ครบ
7. ยืนยันว่า config ที่ใช้ชี้ไป Supabase production ไม่ใช่ dev

Expected:

- เว็บเปิดได้จริง
- deploy ล่าสุดเป็นโค้ดที่ต้องการ
- ไม่มีการชี้ database ผิด environment

## 2. ตรวจการเชื่อมต่อ Supabase

1. เปิด Supabase production dashboard
2. ตรวจว่าตารางหลักเปิดได้ เช่น `users`, `students`, `internships`, `attendance`
3. ยืนยันว่ามีข้อมูลตั้งต้นที่ระบบต้องใช้
4. ตรวจว่าบัญชี service role และ REST API ใช้งานได้ตามปกติ
5. ถ้ามี recent error ใน logs ให้ดูว่ามี query ล้มเหลวหรือไม่

Expected:

- production schema พร้อมใช้งาน
- ไม่มี error สำคัญค้างในช่วงล่าสุด

## 3. ตรวจข้อมูลตั้งต้นที่จำเป็น

1. ตรวจว่ามี `faculties`
2. ตรวจว่ามี `departments`
3. ตรวจว่ามี `internship_batches`
4. ตรวจว่ามี `companies` ที่พร้อมใช้งาน ถ้าระบบต้องใช้
5. ตรวจค่า settings สำคัญ
6. ยืนยันว่ามีค่าพื้นฐานเช่น GPS radius และ minimum checkout hours

Expected:

- ผู้ใช้ใหม่สมัครแล้วไม่ติดเพราะ master data ว่าง

## 4. ทดสอบสมัครสมาชิกจริง

1. เปิดหน้าสมัครสมาชิกจาก production URL
2. สมัครด้วยอีเมลใหม่ที่ไม่เคยใช้
3. กรอกข้อมูลให้ครบ
4. ส่งฟอร์มสมัคร
5. ตรวจหน้าเว็บว่าไม่ error
6. ตรวจใน Supabase ว่ามี user record ใหม่
7. ตรวจ role และ status หลังสมัคร

Expected:

- สมัครสำเร็จ
- มีข้อมูลใหม่ใน production database
- ไม่มีการเขียนข้อมูลลง dev project

## 5. ทดสอบ Login และ Session

1. login ด้วยบัญชีที่เพิ่งสมัคร
2. ยืนยันว่าเข้า dashboard ได้
3. รีเฟรชหน้า 1-2 ครั้ง
4. logout
5. login ใหม่อีกครั้ง

Expected:

- session ทำงานปกติ
- logout และ login กลับเข้าได้
- ไม่เด้ง error จาก token หรือ session

## 6. ทดสอบ Flow หลักของผู้ใช้

เลือกเฉพาะ flow ที่เพื่อนจะใช้จริงอย่างน้อย 1 รอบ

ตัวอย่าง:

1. แก้ไข profile
2. เข้าหน้า dashboard
3. ส่งคำขอหรือกรอกฟอร์มหลักของระบบ
4. ตรวจว่าข้อมูลถูกบันทึก
5. เปิดอ่านข้อมูลที่เพิ่งสร้าง

Expected:

- ผู้ใช้ใหม่ทำงานหลักได้จริง
- ไม่มีหน้าใดที่ติด permission หรือข้อมูลตั้งต้นหาย

## 7. ทดสอบ Attendance ถ้าจะให้ใช้งานจริง

1. ใช้บัญชีที่มี `internship active`
2. เข้า `/student/attendance`
3. ทดสอบ check-in
4. ทดสอบว่า row เกิดในตาราง `attendance`
5. เปิด dashboard แล้วดูว่าสถานะตรงกัน

Expected:

- attendance flow ทำงานจริงบน production
- dashboard และ attendance ใช้ข้อมูลชุดเดียวกัน

## 8. ตรวจ Permission และ Role

1. ลองเข้าหน้า student ด้วย student account
2. ลองเข้าหน้าที่ไม่ควรเข้าถึง
3. ทดสอบ admin account แยกต่างหาก
4. ยืนยันว่า role guard ทำงาน

Expected:

- ผู้ใช้เห็นเฉพาะสิทธิ์ของตัวเอง
- ไม่มี route สำคัญเปิดกว้างเกินไป

## 9. ตรวจ Error Monitoring ระหว่างทดสอบ

1. เปิด Railway logs ขณะทดสอบ
2. เปิด Supabase logs ถ้าจำเป็น
3. ระหว่างสมัครและ login ให้ดู error สด
4. จด endpoint ที่มี warning หรือ exception

Expected:

- ไม่มี error สำคัญใน flow หลัก
- ถ้ามี warning ต้องเข้าใจสาเหตุและยอมรับได้ก่อนปล่อย

## 10. ตรวจความพร้อมก่อนส่งลิงก์

1. ยืนยันว่า production URL คือ URL ที่จะส่งให้เพื่อน
2. ยืนยันว่าไม่มี test banner หรือ debug output หลุดอยู่
3. ยืนยันว่าไม่มี test account/sample data ที่อาจทำให้ผู้ใช้สับสนในหน้าแรก
4. ยืนยันว่าคุณมี admin account สำหรับช่วยเหลือถ้าเพื่อนติดปัญหา
5. เตรียมช่องทางให้เพื่อนแจ้ง bug เช่น LINE หรือข้อความ

Expected:

- พร้อมส่งลิงก์ได้อย่างมั่นใจ

## 11. หลังส่งลิงก์ให้เพื่อน

1. เฝ้าดู Railway logs ช่วงแรก
2. เฝ้าดู row ใหม่ใน Supabase
3. ถามเพื่อนให้ลองสมัครและใช้งานตาม flow ที่กำหนด
4. ให้เพื่อนส่ง screenshot ถ้าเจอ error
5. บันทึก bug ที่พบทันที

Expected:

- ถ้ามีปัญหา จะจับได้เร็วและแก้ได้เร็ว

## Exit Criteria

- สมัครสมาชิกใหม่ได้จริงบน production
- login และ logout ได้
- flow หลักอย่างน้อย 1 เส้นทางผ่าน
- ถ้าเปิด attendance ให้ใช้จริง ต้องทดสอบ attendance ผ่านด้วย
- Railway และ Supabase ไม่มี error สำคัญระหว่างทดสอบ
- คุณพร้อม support ผู้ใช้ชุดแรกหลังส่งลิงก์
