# Review And Evaluation Test Checklist

เช็กลิสต์นี้ใช้สำหรับทดสอบ flow ฝั่งครูนิเทศ, สถานประกอบการ และผู้ดูแลระบบ หลังจาก deploy บน Railway และเชื่อม Supabase production แล้ว

## 1. เตรียมข้อมูลก่อนทดสอบ

- ยืนยันว่ามี `teacher` record ที่ผูก `user_id` กับบัญชีอาจารย์จริง
- ยืนยันว่ามี `internships.teacher_id` และ `internships.company_id` ผูกกับนักศึกษาที่ต้องการทดสอบแล้ว
- ยืนยันว่า `internships.status` ของรายการทดสอบเป็น `approved`, `active`, `ongoing` หรือ `completed`
- ยืนยันว่ามี `daily_logs` อย่างน้อย 1 รายการในสถานะ `submitted`
- ยืนยันว่ามี `evaluation_templates` สำหรับ `company_weekly`, `company_final`, `teacher_final`

## 2. ทดสอบฝั่งครูนิเทศ

- ล็อกอินด้วยบัญชีอาจารย์
- เปิด `/teacher/dashboard`
- ตรวจว่า dashboard แสดงจำนวนนักศึกษาไม่เป็น `0` ถ้ามีข้อมูลใน `internships`
- ตรวจว่า list นักศึกษาขึ้นชื่อ, รหัสนักศึกษา, บริษัท, ชั่วโมงสะสม
- เปิด `/teacher/students`
- กดเข้าหน้ารายละเอียดนักศึกษา `/teacher/students/{internship_id}`
- สลับ tab `ภาพรวม`, `การเข้างาน`, `บันทึกงาน`, `การลา`, `ประเมินผล`
- ตรวจว่า timeline แสดงข้อมูลจาก `attendance`, `daily_logs`, `leave_requests`, `evaluations` ได้จริง
- เปิด `/teacher/daily-logs`
- ตรวจว่าหน้าตรวจบันทึกงานดึง daily log สถานะ `submitted` ของนักศึกษาในความดูแลขึ้นมาได้
- ทดลองกด `ตรวจแล้ว / ผ่าน`
- ตรวจใน Supabase ว่า `daily_logs.status` เปลี่ยนเป็น `reviewed`
- ทดลองอีกรายการด้วยปุ่ม `ให้แก้ไขใหม่`
- ตรวจใน Supabase ว่า `daily_logs.status` เปลี่ยนเป็น `revision_requested` และมี `supervisor_comment`

## 3. ทดสอบฝั่งสถานประกอบการ

- ล็อกอินด้วยบัญชี supervisor ของบริษัท
- เปิด `/company/students`
- ตรวจว่ามองเห็นเฉพาะนักศึกษาของบริษัทตัวเอง
- เปิด `/company/students/{internship_id}`
- ตรวจว่า timeline ขึ้นข้อมูลจริง
- เปิด `/company/daily-logs`
- ตรวจว่า review daily log ได้เฉพาะของบริษัทตัวเอง
- ทดลอง approve / revision แล้วตรวจการอัปเดตใน Supabase

## 4. ทดสอบการประเมินผล

- เปิดฟอร์มประเมินรายสัปดาห์ของบริษัท
- กรอกคะแนนและบันทึก
- ตรวจใน Supabase ว่ามี row ใหม่ใน `evaluations`
- เปิดฟอร์มประเมินปลายภาคของสถานประกอบการ
- บันทึกและตรวจคะแนนรวม / grade
- เปิดฟอร์มประเมินปลายภาคของครูนิเทศ
- บันทึกและตรวจว่า dashboard ฝั่งครูลดตัวเลข `pending final evaluation`

## 5. ทดสอบสิทธิ์และความปลอดภัย

- ใช้บัญชีอาจารย์ A เปิด `internship_id` ของอาจารย์ B ต้องถูกบล็อก
- ใช้บัญชีบริษัท A เปิดข้อมูลนักศึกษาของบริษัท B ต้องถูกบล็อก
- ใช้บัญชีนักศึกษาเปิด `/teacher/*` และ `/company/*` ต้องเข้าไม่ได้
- กด submit form ซ้ำหลายครั้ง ต้องไม่สร้างข้อมูลประเมินซ้ำโดยไม่ตั้งใจ

## 6. เกณฑ์พร้อมใช้งานจริง

- ครูนิเทศเห็นนักศึกษาในความดูแลครบ
- สถานประกอบการเห็นและตรวจ daily log ได้
- การประเมินผลถูกบันทึกลง Supabase ตรง schema จริง
- ไม่มี PHP Warning, ParseError, หรือ Supabase column/table error ใน production logs
- ทดสอบครบทั้ง happy path และ permission denial แล้ว