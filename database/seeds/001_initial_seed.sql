-- ============================================================================
-- 001_initial_seed.sql
-- Initial seed data for Phase 1 (AI_AGENT_PHASES.md Phase 1, steps 2-5).
-- Run this in the Supabase SQL Editor AFTER all DDL from DATABASE.md has been applied.
-- Safe to re-run: every INSERT uses ON CONFLICT DO NOTHING / DO UPDATE where a natural
-- unique key exists, so re-running this file won't create duplicates.
--
-- Faculty/department names below are PLACEHOLDER examples carried over from
-- MASTER_SPEC.md/DATA_DICTIONARY.md throughout the blueprint (ISSUES.md still lists
-- "confirm real faculty/department names" as an open TODO) — replace with your
-- institution's real names via simple UPDATE statements once known, no schema change needed.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. faculties + departments (DATA_DICTIONARY.md §1-2)
-- ----------------------------------------------------------------------------
insert into public.faculties (code, name_th, name_en) values
    ('ENG', 'วิศวกรรมศาสตร์', 'Faculty of Engineering'),
    ('SCI', 'วิทยาศาสตร์', 'Faculty of Science'),
    ('BUS', 'บริหารธุรกิจ', 'Faculty of Business Administration')
on conflict (code) do nothing;

insert into public.departments (faculty_id, code, name_th, name_en) values
    ((select id from public.faculties where code = 'ENG'), 'CPE', 'วิศวกรรมคอมพิวเตอร์', 'Computer Engineering'),
    ((select id from public.faculties where code = 'ENG'), 'EE',  'วิศวกรรมไฟฟ้า', 'Electrical Engineering'),
    ((select id from public.faculties where code = 'SCI'), 'CS',  'วิทยาการคอมพิวเตอร์', 'Computer Science'),
    ((select id from public.faculties where code = 'SCI'), 'IT',  'เทคโนโลยีสารสนเทศ', 'Information Technology'),
    ((select id from public.faculties where code = 'BUS'), 'MKT', 'การตลาด', 'Marketing'),
    ((select id from public.faculties where code = 'BUS'), 'FIN', 'การเงิน', 'Finance')
on conflict (faculty_id, code) do nothing;

-- ----------------------------------------------------------------------------
-- 2. settings — full default list (SETTINGS.md §1)
-- ----------------------------------------------------------------------------
insert into public.settings (setting_key, setting_value, value_type, description) values
    ('default_gps_radius_m', '100', 'integer', 'รัศมี GPS เริ่มต้น (เมตร) เมื่อบริษัทยังไม่ตั้งค่าของตนเอง'),
    ('max_gps_radius_m', '500', 'integer', 'รัศมีสูงสุดที่บริษัทตั้งเองได้'),
    ('min_hours_before_checkout', '4.0', 'decimal', 'ชั่วโมงขั้นต่ำต่อวันก่อนลงเวลาออกได้ (ค่า default)'),
    ('max_upload_size_kb', '1024', 'integer', 'ขนาดไฟล์แนบสูงสุดต่อไฟล์ (KB)'),
    ('allowed_file_types', '["image/jpeg","image/png","application/pdf"]', 'json', 'ชนิดไฟล์ที่อนุญาตอัปโหลด'),
    ('sick_leave_certificate_min_days', '3', 'integer', 'จำนวนวันลาป่วยขั้นต่ำที่บังคับแนบใบรับรองแพทย์'),
    ('max_total_leave_days', '15', 'integer', 'จำนวนวันลารวมสูงสุดต่อการฝึกงานก่อนระบบเตือน Admin'),
    ('leave_requires_dual_approval', 'false', 'boolean', 'บังคับให้ทั้งผู้ประกอบการและครูอนุมัติการลาร่วมกันหรือไม่'),
    ('score_visible_to_student', 'true', 'boolean', 'เปิด/ปิดการมองเห็นคะแนนของนักศึกษา'),
    ('grading_scale', '{"weight_company":0.6,"weight_teacher":0.4,"bands":[{"min":80,"grade":"A"},{"min":75,"grade":"B+"},{"min":70,"grade":"B"},{"min":65,"grade":"C+"},{"min":60,"grade":"C"},{"min":0,"grade":"F"}]}', 'json', 'สูตรคำนวณเกรดรวม'),
    ('failed_login_lockout_threshold', '5', 'integer', 'จำนวนครั้งล็อกอินผิดก่อนล็อกบัญชี'),
    ('failed_login_lockout_minutes', '15', 'integer', 'ระยะเวลาล็อกบัญชี (นาที)'),
    ('session_token_ttl_minutes', '60', 'integer', 'อายุ token การเข้าสู่ระบบ'),
    ('super_admin_pin_attempts', '3', 'integer', 'จำนวนครั้งกรอก PIN ผิดก่อนล็อกการยืนยัน'),
    ('notification_no_checkin_time', '09:00', 'string', 'เวลาที่ระบบเช็คว่ายังไม่ลงเวลาเข้าแล้วแจ้งเตือน'),
    ('notification_eval_pending_days', '3', 'integer', 'จำนวนวันหลังครบสัปดาห์ที่ยังไม่ประเมินแล้วแจ้งเตือน'),
    ('deadline_warning_days', '7', 'integer', 'จำนวนวันก่อนครบกำหนดฝึกงานที่เริ่มแจ้งเตือน'),
    ('audit_log_retention_years', '5', 'integer', 'ระยะเวลาที่ต้องเก็บ audit log ขั้นต่ำ'),
    ('attendance_photo_required', 'false', 'boolean', 'บังคับถ่ายภาพยืนยันตอนลงเวลาหรือไม่')
on conflict (setting_key) do nothing;

-- ----------------------------------------------------------------------------
-- 3. evaluation_templates + evaluation_criteria (LEAVE_EVALUATION_SIGNATURE.md §2.2)
-- ----------------------------------------------------------------------------
insert into public.evaluation_templates (name, evaluator_type, max_score) values
    ('ประเมินรายสัปดาห์โดยผู้ประกอบการ', 'company_weekly', 20.00),
    ('ประเมินปลายภาคโดยผู้ประกอบการ', 'company_final', 100.00),
    ('ประเมินปลายภาคโดยครูนิเทศ', 'teacher_final', 100.00);

-- Template A: company_weekly (max 20)
insert into public.evaluation_criteria (template_id, criteria_name, max_score, sort_order)
select id, c.name, c.max_score, c.sort_order
from public.evaluation_templates,
     (values
        ('ความรับผิดชอบต่องาน', 5.00, 1),
        ('คุณภาพของงานที่ได้รับมอบหมาย', 5.00, 2),
        ('การทำงานร่วมกับผู้อื่น/มนุษยสัมพันธ์', 5.00, 3),
        ('ทัศนคติและความตรงต่อเวลา', 5.00, 4)
     ) as c(name, max_score, sort_order)
where evaluator_type = 'company_weekly';

-- Template B: company_final (max 100)
insert into public.evaluation_criteria (template_id, criteria_name, max_score, sort_order)
select id, c.name, c.max_score, c.sort_order
from public.evaluation_templates,
     (values
        ('ความรู้ความสามารถทางวิชาชีพ', 25.00, 1),
        ('ทักษะการปฏิบัติงานจริง', 25.00, 2),
        ('ความรับผิดชอบและวินัยในการทำงาน', 20.00, 3),
        ('มนุษยสัมพันธ์และการทำงานเป็นทีม', 15.00, 4),
        ('ความคิดริเริ่มสร้างสรรค์/การแก้ปัญหา', 15.00, 5)
     ) as c(name, max_score, sort_order)
where evaluator_type = 'company_final';

-- Template C: teacher_final (max 100)
insert into public.evaluation_criteria (template_id, criteria_name, max_score, sort_order)
select id, c.name, c.max_score, c.sort_order
from public.evaluation_templates,
     (values
        ('ความสม่ำเสมอในการบันทึกงาน/รายงาน', 20.00, 1),
        ('ความสอดคล้องของงานกับสาขาวิชา', 25.00, 2),
        ('พัฒนาการระหว่างการฝึกงาน', 20.00, 3),
        ('การนำเสนอผลสรุปการฝึกงาน', 20.00, 4),
        ('คุณธรรม จริยธรรม และการอุทิศตน', 15.00, 5)
     ) as c(name, max_score, sort_order)
where evaluator_type = 'teacher_final';

-- Sanity check (should return 3 rows, each summing to its template's max_score):
-- select t.evaluator_type, t.max_score as template_max, sum(c.max_score) as criteria_sum
-- from public.evaluation_templates t join public.evaluation_criteria c on c.template_id = t.id
-- group by t.id, t.evaluator_type, t.max_score;

-- ----------------------------------------------------------------------------
-- 4. permissions — full RBAC matrix (PERMISSIONS.md §2)
--    scope column already encodes "own" vs "all" etc., so "view (own)" and
--    "view (others)" from the human-readable matrix collapse into one `view`
--    row per role with the appropriate scope.
-- ----------------------------------------------------------------------------
insert into public.permissions (role, module, action, allowed, scope) values
    -- 2.1 users
    ('student','users','view',true,'own'), ('company','users','view',true,'own'), ('teacher','users','view',true,'own'), ('admin','users','view',true,'all'), ('super_admin','users','view',true,'all'),
    ('student','users','create',true,'own'), ('company','users','create',false,'own'), ('teacher','users','create',false,'own'), ('admin','users','create',true,'all'), ('super_admin','users','create',true,'all'),
    ('student','users','update',true,'own'), ('company','users','update',true,'own'), ('teacher','users','update',true,'own'), ('admin','users','update',true,'all'), ('super_admin','users','update',true,'all'),
    ('student','users','approve',false,'own'), ('company','users','approve',false,'own'), ('teacher','users','approve',false,'own'), ('admin','users','approve',true,'all'), ('super_admin','users','approve',true,'all'),
    ('student','users','suspend',false,'own'), ('company','users','suspend',false,'own'), ('teacher','users','suspend',false,'own'), ('admin','users','suspend',true,'all'), ('super_admin','users','suspend',true,'all'),
    ('student','users','delete',false,'own'), ('company','users','delete',false,'own'), ('teacher','users','delete',false,'own'), ('admin','users','delete',false,'all'), ('super_admin','users','delete',true,'all'),

    -- 2.2 companies
    ('student','companies','view',true,'all'), ('company','companies','view',true,'own'), ('teacher','companies','view',true,'all'), ('admin','companies','view',true,'all'), ('super_admin','companies','view',true,'all'),
    ('student','companies','create',false,'all'), ('company','companies','create',false,'own'), ('teacher','companies','create',false,'all'), ('admin','companies','create',true,'all'), ('super_admin','companies','create',true,'all'),
    ('student','companies','update',false,'all'), ('company','companies','update',true,'own'), ('teacher','companies','update',false,'all'), ('admin','companies','update',true,'all'), ('super_admin','companies','update',true,'all'),
    ('student','companies','approve',false,'all'), ('company','companies','approve',false,'own'), ('teacher','companies','approve',false,'all'), ('admin','companies','approve',true,'all'), ('super_admin','companies','approve',true,'all'),
    ('student','companies','delete',false,'all'), ('company','companies','delete',false,'own'), ('teacher','companies','delete',false,'all'), ('admin','companies','delete',false,'all'), ('super_admin','companies','delete',true,'all'),

    -- 2.3 internship_applications
    ('student','internship_applications','create',true,'own'), ('company','internship_applications','create',false,'company'), ('teacher','internship_applications','create',false,'assigned'), ('admin','internship_applications','create',true,'all'), ('super_admin','internship_applications','create',true,'all'),
    ('student','internship_applications','view',true,'own'), ('company','internship_applications','view',true,'company'), ('teacher','internship_applications','view',true,'assigned'), ('admin','internship_applications','view',true,'all'), ('super_admin','internship_applications','view',true,'all'),
    ('student','internship_applications','decide',false,'own'), ('company','internship_applications','decide',true,'company'), ('teacher','internship_applications','decide',false,'assigned'), ('admin','internship_applications','decide',true,'all'), ('super_admin','internship_applications','decide',true,'all'),
    ('student','internship_applications','cancel',true,'own'), ('company','internship_applications','cancel',false,'company'), ('teacher','internship_applications','cancel',false,'assigned'), ('admin','internship_applications','cancel',true,'all'), ('super_admin','internship_applications','cancel',true,'all'),

    -- 2.4 internships
    ('student','internships','view',true,'own'), ('company','internships','view',true,'company'), ('teacher','internships','view',true,'assigned'), ('admin','internships','view',true,'all'), ('super_admin','internships','view',true,'all'),
    ('student','internships','create',false,'own'), ('company','internships','create',false,'company'), ('teacher','internships','create',true,'assigned'), ('admin','internships','create',true,'all'), ('super_admin','internships','create',true,'all'),
    ('student','internships','approve',false,'own'), ('company','internships','approve',false,'company'), ('teacher','internships','approve',false,'assigned'), ('admin','internships','approve',true,'all'), ('super_admin','internships','approve',true,'all'),
    ('student','internships','terminate',false,'own'), ('company','internships','terminate',false,'company'), ('teacher','internships','terminate',true,'assigned'), ('admin','internships','terminate',true,'all'), ('super_admin','internships','terminate',true,'all'),
    ('student','internships','delete',false,'own'), ('company','internships','delete',false,'company'), ('teacher','internships','delete',false,'assigned'), ('admin','internships','delete',false,'all'), ('super_admin','internships','delete',true,'all'),

    -- 2.5 attendance
    ('student','attendance','checkin',true,'own'), ('company','attendance','checkin',false,'company'), ('teacher','attendance','checkin',false,'assigned'), ('admin','attendance','checkin',false,'all'), ('super_admin','attendance','checkin',false,'all'),
    ('student','attendance','view',true,'own'), ('company','attendance','view',true,'company'), ('teacher','attendance','view',true,'assigned'), ('admin','attendance','view',true,'all'), ('super_admin','attendance','view',true,'all'),
    ('student','attendance','edit',false,'own'), ('company','attendance','edit',false,'company'), ('teacher','attendance','edit',false,'assigned'), ('admin','attendance','edit',true,'all'), ('super_admin','attendance','edit',true,'all'),
    ('student','attendance','delete',false,'own'), ('company','attendance','delete',false,'company'), ('teacher','attendance','delete',false,'assigned'), ('admin','attendance','delete',false,'all'), ('super_admin','attendance','delete',true,'all'),

    -- 2.6 daily_logs
    ('student','daily_logs','create',true,'own'), ('company','daily_logs','create',false,'company'), ('teacher','daily_logs','create',false,'assigned'), ('admin','daily_logs','create',false,'all'), ('super_admin','daily_logs','create',false,'all'),
    ('student','daily_logs','submit',true,'own'), ('company','daily_logs','submit',false,'company'), ('teacher','daily_logs','submit',false,'assigned'), ('admin','daily_logs','submit',false,'all'), ('super_admin','daily_logs','submit',false,'all'),
    ('student','daily_logs','view',true,'own'), ('company','daily_logs','view',true,'company'), ('teacher','daily_logs','view',true,'assigned'), ('admin','daily_logs','view',true,'all'), ('super_admin','daily_logs','view',true,'all'),
    ('student','daily_logs','review',false,'own'), ('company','daily_logs','review',true,'company'), ('teacher','daily_logs','review',true,'assigned'), ('admin','daily_logs','review',true,'all'), ('super_admin','daily_logs','review',true,'all'),
    ('student','daily_logs','delete',false,'own'), ('company','daily_logs','delete',false,'company'), ('teacher','daily_logs','delete',false,'assigned'), ('admin','daily_logs','delete',false,'all'), ('super_admin','daily_logs','delete',true,'all'),

    -- 2.7 leave_requests
    ('student','leave_requests','create',true,'own'), ('company','leave_requests','create',false,'company'), ('teacher','leave_requests','create',false,'assigned'), ('admin','leave_requests','create',false,'all'), ('super_admin','leave_requests','create',false,'all'),
    ('student','leave_requests','cancel',true,'own'), ('company','leave_requests','cancel',false,'company'), ('teacher','leave_requests','cancel',false,'assigned'), ('admin','leave_requests','cancel',true,'all'), ('super_admin','leave_requests','cancel',true,'all'),
    ('student','leave_requests','view',true,'own'), ('company','leave_requests','view',true,'company'), ('teacher','leave_requests','view',true,'assigned'), ('admin','leave_requests','view',true,'all'), ('super_admin','leave_requests','view',true,'all'),
    ('student','leave_requests','approve',false,'own'), ('company','leave_requests','approve',true,'company'), ('teacher','leave_requests','approve',true,'assigned'), ('admin','leave_requests','approve',true,'all'), ('super_admin','leave_requests','approve',true,'all'),

    -- 2.8 evaluations (covers evaluation_scores too — no separate RBAC entries needed)
    ('student','evaluations','create_weekly',false,'own'), ('company','evaluations','create_weekly',true,'company'), ('teacher','evaluations','create_weekly',false,'assigned'), ('admin','evaluations','create_weekly',false,'all'), ('super_admin','evaluations','create_weekly',false,'all'),
    ('student','evaluations','create_final_company',false,'own'), ('company','evaluations','create_final_company',true,'company'), ('teacher','evaluations','create_final_company',false,'assigned'), ('admin','evaluations','create_final_company',false,'all'), ('super_admin','evaluations','create_final_company',false,'all'),
    ('student','evaluations','create_final_teacher',false,'own'), ('company','evaluations','create_final_teacher',false,'company'), ('teacher','evaluations','create_final_teacher',true,'assigned'), ('admin','evaluations','create_final_teacher',false,'all'), ('super_admin','evaluations','create_final_teacher',false,'all'),
    ('student','evaluations','view',true,'own'), ('company','evaluations','view',true,'company'), ('teacher','evaluations','view',true,'assigned'), ('admin','evaluations','view',true,'all'), ('super_admin','evaluations','view',true,'all'),
    ('student','evaluations','edit_after_submit',false,'own'), ('company','evaluations','edit_after_submit',false,'company'), ('teacher','evaluations','edit_after_submit',false,'assigned'), ('admin','evaluations','edit_after_submit',false,'all'), ('super_admin','evaluations','edit_after_submit',true,'all'),

    -- 2.9 digital_signatures (view own score gate for students lives in evaluations.view + settings.score_visible_to_student, not here)
    ('student','digital_signatures','create',false,'own'), ('company','digital_signatures','create',true,'own'), ('teacher','digital_signatures','create',true,'own'), ('admin','digital_signatures','create',true,'own'), ('super_admin','digital_signatures','create',true,'own'),
    ('student','digital_signatures','view',false,'own'), ('company','digital_signatures','view',true,'own'), ('teacher','digital_signatures','view',true,'own'), ('admin','digital_signatures','view',true,'all'), ('super_admin','digital_signatures','view',true,'all'),
    ('student','digital_signatures','delete',false,'own'), ('company','digital_signatures','delete',false,'own'), ('teacher','digital_signatures','delete',false,'own'), ('admin','digital_signatures','delete',false,'all'), ('super_admin','digital_signatures','delete',false,'all'),

    -- 2.10 settings
    ('student','settings','view',false,'all'), ('company','settings','view',false,'all'), ('teacher','settings','view',false,'all'), ('admin','settings','view',true,'all'), ('super_admin','settings','view',true,'all'),
    ('student','settings','update',false,'all'), ('company','settings','update',false,'all'), ('teacher','settings','update',false,'all'), ('admin','settings','update',true,'all'), ('super_admin','settings','update',true,'all'),

    -- 2.11 audit_logs (view only — immutable, no delete/update toggle exists by design)
    ('student','audit_logs','view',false,'all'), ('company','audit_logs','view',false,'all'), ('teacher','audit_logs','view',false,'all'), ('admin','audit_logs','view',true,'all'), ('super_admin','audit_logs','view',true,'all'),

    -- 2.12 announcements
    ('student','announcements','view',true,'all'), ('company','announcements','view',true,'all'), ('teacher','announcements','view',true,'all'), ('admin','announcements','view',true,'all'), ('super_admin','announcements','view',true,'all'),
    ('student','announcements','create',false,'all'), ('company','announcements','create',false,'all'), ('teacher','announcements','create',false,'all'), ('admin','announcements','create',true,'all'), ('super_admin','announcements','create',true,'all')

on conflict (role, module, action) do update set allowed = excluded.allowed, scope = excluded.scope;

-- ============================================================================
-- Verification queries (run manually after seeding to sanity-check):
--
-- select count(*) from public.faculties;                 -- expect 3
-- select count(*) from public.departments;                -- expect 6
-- select count(*) from public.settings;                   -- expect 19
-- select count(*) from public.evaluation_templates;        -- expect 3
-- select count(*) from public.evaluation_criteria;         -- expect 14 (4+5+5)
-- select count(*) from public.permissions;                 -- expect 230 (30+25+20+25+20+25+20+25+15+10+5+10)
-- ============================================================================
