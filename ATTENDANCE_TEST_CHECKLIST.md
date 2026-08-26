# Attendance Test Checklist

Checklist นี้ใช้สำหรับทดสอบ flow `Attendance` จริงบนสภาพแวดล้อม `Railway + Supabase` หลังจากย้าย logic หลักมาใช้ตาราง `attendance` เป็น source เดียว

## ก่อนเริ่ม

1. เตรียมผู้ใช้ `student` ที่มี `internship` สถานะ `active`
2. ตรวจใน Supabase ว่าบริษัทของ internship นี้มี `latitude`, `longitude`, `gps_radius_m`
3. ตรวจว่า `internship_batches.min_hours_before_checkout` มีค่า เช่น `4.0`
4. เปิดเว็บที่ deploy บน Railway ด้วยบัญชีนักศึกษาคนนั้น
5. เปิด DevTools แท็บ `Network`
6. เปิด Supabase Table Editor ไว้ที่ตาราง `attendance`

## ข้อมูลที่ควรจดระหว่างทดสอบ

- เวลาเริ่มทดสอบ
- URL ที่ใช้
- `internship_id`
- response ของ request `/student/attendance`
- row ที่ถูกสร้างหรือแก้ในตาราง `attendance`
- สิ่งที่หน้าเว็บแสดง
- สิ่งที่ dashboard แสดง

## TC1 Check-in ปกติ

1. ไปหน้า `/student/attendance`
2. อนุญาต GPS ให้ browser
3. อยู่ในตำแหน่งที่ควรอยู่ในรัศมีบริษัท
4. กด `Check-in`
5. ดูใน `Network` ว่า request เป็น `200` หรือ success JSON
6. รีเฟรชหน้า
7. ตรวจใน Supabase
8. ยืนยันว่ามี row ของ `internship_id` และ `work_date = วันนี้`
9. ยืนยันว่า `check_in_at` ไม่เป็น `null`
10. ยืนยันว่า `check_in_status` เป็น `on_time` หรือ `late`
11. ยืนยันว่า `day_status` เป็น `present` หรือ `late`
12. ตรวจหน้า dashboard ว่าแสดงว่า check-in แล้ว
13. ตรวจว่าชั่วโมงและสถานะไม่เพี้ยน

Expected:

- หน้าไม่ error
- มี attendance row ถูกสร้าง 1 แถว
- dashboard สะท้อนข้อมูลตรงกับ Supabase

## TC2 Check-in ซ้ำวันเดียวกัน

1. หลัง TC1 กด `Check-in` อีกครั้ง
2. ดู response ใน `Network`
3. ดูข้อความ error บนหน้า

Expected:

- ระบบต้องไม่สร้าง row ใหม่
- ควรได้ error เช่น `ALREADY_CHECKED_IN`
- ข้อมูลเดิมใน Supabase ต้องไม่เสีย

## TC3 Check-out ก่อนครบชั่วโมงขั้นต่ำ

1. ใช้ row ที่เพิ่ง check-in จาก TC1
2. ถ้ายังไม่ครบ `min_hours_before_checkout` ให้กด `Check-out`
3. ดู response ใน `Network`
4. ตรวจ Supabase row เดิม

Expected:

- ควรได้ error เช่น `CHECKOUT_TOO_EARLY`
- `check_out_at` ต้องยังเป็น `null`
- `total_hours` ต้องยังไม่ถูกนับเป็นสำเร็จ

## TC4 Check-out ปกติหลังครบชั่วโมง

1. รอให้ครบชั่วโมงขั้นต่ำ หรือใช้ test account/test date ที่พร้อม
2. กด `Check-out`
3. ดู response ใน `Network`
4. รีเฟรชหน้า attendance และ dashboard
5. ตรวจ Supabase
6. ยืนยันว่า `check_out_at` ไม่เป็น `null`
7. ยืนยันว่า `check_out_status` เป็น `normal`
8. ยืนยันว่า `total_hours` มีค่า

Expected:

- check-out สำเร็จ
- หน้า attendance แสดงว่าครบวันแล้ว
- dashboard และ summary ชั่วโมงตรงกับฐานข้อมูล

## TC5 Check-out ซ้ำ

1. หลัง TC4 กด `Check-out` อีกครั้ง
2. ดู response ใน `Network`
3. ตรวจ row ใน Supabase

Expected:

- ควรได้ error เช่น `ALREADY_CHECKED_OUT`
- ค่า `check_out_at` และ `total_hours` ต้องไม่ถูกแก้ซ้ำ

## TC6 Check-in นอก GPS Radius

1. ใช้ account ที่ยังไม่มี attendance วันนี้
2. ทดสอบจากตำแหน่งที่อยู่นอกรัศมี หรือปรับ mock location
3. กด `Check-in`
4. ดู response ใน `Network`
5. ตรวจ Supabase

Expected:

- ควรได้ error เช่น `OUT_OF_GPS_RANGE`
- ถ้าระบบเก็บ evidence row:
- `check_in_at` ต้องยังเป็น `null`
- `check_in_status` ควรเป็น `out_of_range`
- dashboard ไม่ควรนับว่าเข้างานแล้ว

## TC7 Check-out นอก GPS Radius

1. ใช้ row ที่ check-in สำเร็จแล้วและครบชั่วโมงขั้นต่ำ
2. ออกจากรัศมี
3. กด `Check-out`
4. ดู response
5. ตรวจ Supabase

Expected:

- ควรได้ error `OUT_OF_GPS_RANGE`
- ตรวจว่า `check_out_status` เป็น `out_of_range`
- ยืนยันว่าพฤติกรรมนี้ตรงกับธุรกิจที่ต้องการจริง

## TC8 ไม่มี active internship

1. ใช้บัญชีนักศึกษาที่ไม่มี internship active
2. เข้า `/student/attendance`
3. ลองกดส่ง attendance ถ้าหน้ายังเปิดปุ่มได้

Expected:

- หน้าแสดงสถานะไม่มี internship
- API ตอบ error เช่น `NO_ACTIVE_INTERNSHIP`
- Supabase ต้องไม่เกิด row ใหม่

## TC9 Dashboard Consistency

1. หลังทำ TC1-TC7 เสร็จ เปิด `/student/dashboard`
2. เทียบกับ Supabase ตาราง `attendance`

จุดที่ต้องตรง:

- วันนี้ check-in แล้วหรือยัง
- recent attendance
- ชั่วโมงสะสม
- days present / late / absent

## TC10 CSRF And Request Integrity

1. เปิด `Network`
2. กด check-in หรือ check-out
3. ดู request headers

Expected:

- มี `X-CSRF-Token`
- request body เป็น JSON
- ถ้าตัด token ออกแล้ว replay เอง ควรถูกปฏิเสธ

## สรุปผลที่ควรบันทึก

- `Pass/Fail`
- เวลา
- account ที่ใช้
- `internship_id`
- request payload
- response JSON
- row ใน Supabase ก่อนและหลัง
- screenshot หน้าเว็บถ้ามี error

## Exit Criteria

- TC1-TC5 ต้องผ่านทั้งหมด
- TC6-TC8 ต้องให้ error ถูกต้องและไม่สร้างข้อมูลผิด
- TC9 ต้องตรงกับข้อมูลใน Supabase
- TC10 ต้องยืนยันได้ว่า CSRF ยังทำงาน
