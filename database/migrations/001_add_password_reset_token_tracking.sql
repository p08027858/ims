-- Migration 001: track consumed password-recovery tokens per user.
--
-- Why: Supabase Auth does NOT invalidate a recovery access_token after it's used once — the
-- same token can be replayed to reset the password again and again until it naturally expires
-- (1 hour). Confirmed by live testing 2026-07-30 (TC-AUTH-007, see ISSUES.md): the same recovery
-- token successfully changed the password twice in a row. We close this gap ourselves by
-- remembering the `iat` (issued-at, unix seconds) claim of the last recovery token we accepted
-- per user, and rejecting any token whose `iat` is not strictly newer (app/Services/AuthService.php
-- resetPassword()).
--
-- Safe to run multiple times (idempotent).

ALTER TABLE public.users
    ADD COLUMN IF NOT EXISTS last_password_reset_token_iat BIGINT NULL;

COMMENT ON COLUMN public.users.last_password_reset_token_iat IS
    'Unix timestamp (iat claim) of the most recently consumed Supabase recovery access_token. Used to reject reuse of an already-consumed reset link (TC-AUTH-007) since Supabase itself does not invalidate these tokens after first use.';
