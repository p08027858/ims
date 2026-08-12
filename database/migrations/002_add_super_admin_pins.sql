-- Migration 002: PIN confirmation for Super Admin destructive/critical actions (Phase 10).
--
-- Why: SECURITY.md §6 requires a 6-digit PIN, separate from the Supabase Auth login password,
-- confirming any action that destroys or edits already-submitted critical data (RULE-SEC-01).
-- SECURITY.md only ever described this as "stored in a secret table of our own" conceptually —
-- no DDL for it existed anywhere in the blueprint docs until this phase (see DATABASE.md §2.4).
--
-- Deliberately a separate table (not columns bolted onto public.users): the PIN hash and its
-- own failed-attempt/lockout counters are unrelated to the Supabase Auth-managed login flow that
-- already owns public.users.failed_login_count/locked_until (RULE-AUTH-03) — mixing the two would
-- make it easy to accidentally couple login lockout with PIN lockout, which SECURITY.md treats as
-- two independent mechanisms.
--
-- Safe to run multiple times (idempotent).

CREATE TABLE IF NOT EXISTS public.super_admin_pins (
    user_id          UUID PRIMARY KEY REFERENCES public.users(id) ON DELETE CASCADE,
    pin_hash         VARCHAR(255) NOT NULL,
    failed_attempts  SMALLINT NOT NULL DEFAULT 0,
    locked_until     TIMESTAMPTZ,
    created_at       TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at       TIMESTAMPTZ NOT NULL DEFAULT now()
);

COMMENT ON TABLE public.super_admin_pins IS
    'PIN 6 digits, separate from the Supabase Auth login password, gating destructive/critical actions per SECURITY.md §6 and RULE-SEC-01. One row per super_admin user, created on first-time PIN setup (App\Services\SuperAdminPinService::setup()).';
