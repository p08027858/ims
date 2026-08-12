<?php
/**
 * DEPLOYMENT.md §7 — "ทุกวัน 02:00" — ตรวจสอบว่า Supabase automated backup/PITR ของวันนั้นสำเร็จ.
 *
 * NOT actually implemented — this is a documented placeholder, not a working check. Verifying
 * backup/PITR status requires the Supabase **Management API** (https://api.supabase.com), which
 * needs its own personal access token scoped to the Supabase account itself — a completely
 * different credential from `config/supabase.php`'s anon/service_role keys (those only reach
 * this ONE project's own REST/Auth/Storage APIs, not Supabase's account-level management
 * surface). No such token has been provided/configured, so this script cannot make a real API
 * call and must not pretend to. Real usage: obtain a Management API token from the Supabase
 * dashboard (Account → Access Tokens), store it outside version control the same way
 * config/supabase.php is gitignored, then call
 * GET https://api.supabase.com/v1/projects/{ref}/database/backups and check the latest entry's
 * timestamp/status.
 */

declare(strict_types=1);

fwrite(STDERR, "verify_backup.php is NOT implemented — see this file's docblock. No Supabase Management API token is configured.\n");
exit(1);
