-- Migration 003: เลขบัตรประชาชน (Thai national ID) on public.students.
--
-- Why: found by reviewing "IMS Prototype.dc.html" (an earlier interactive design mockup at
-- d:\vscode isms) alongside this project's real build — its registration flow collects the
-- student's 13-digit citizen ID (เลขบัตรประชาชน) as a required identity field, which
-- public.students never had at all (register() only ever collected student_code, full_name,
-- faculty_id, department_id, year_level, email, password). Confirmed with the user this is a
-- genuine gap worth closing, not scope creep — Thai institutional systems commonly require this
-- for identity verification. See App\Services\AuthService::validateThaiCitizenId() for the
-- mod-11 checksum validation applied server-side on every new registration.
--
-- Nullable (not NOT NULL): existing student rows created before this migration have no value
-- and must not break. New registrations validate + require it at the application layer
-- (AuthService::register()), not via a NOT NULL constraint here.
--
-- Safe to run multiple times (idempotent).

ALTER TABLE public.students
    ADD COLUMN IF NOT EXISTS citizen_id VARCHAR(13);

-- Partial unique index: two students can never share a real citizen_id, but multiple NULLs
-- (pre-migration rows, or any future bulk-import path that doesn't collect it) must stay allowed.
CREATE UNIQUE INDEX IF NOT EXISTS idx_students_citizen_id
    ON public.students (citizen_id)
    WHERE citizen_id IS NOT NULL;

COMMENT ON COLUMN public.students.citizen_id IS
    'Thai national ID, 13 digits, mod-11 checksum-validated server-side (AuthService::validateThaiCitizenId()). Nullable for rows that predate this migration.';
