-- ============================================================================
-- 004_add_student_contact_fields.sql
-- Contact fields for /student/profile (phone / current address / emergency contact).
--
-- Why: app/Views/student/profile.php has always rendered an editable contact form, but
-- public.students only ever stored academic identity columns (student_code, citizen_id,
-- faculty_id, department_id, year_level — see AuthService::register), so the form could
-- never persist real data. This migration adds the missing columns; ProfileController
-- writes them via PostgREST once applied. Safe to re-run (IF NOT EXISTS).
-- ============================================================================

alter table public.students add column if not exists phone text;
alter table public.students add column if not exists address text;
alter table public.students add column if not exists emergency_contact_name text;
alter table public.students add column if not exists emergency_relation text;
alter table public.students add column if not exists emergency_contact_phone text;
