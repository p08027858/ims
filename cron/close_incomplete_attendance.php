<?php
/**
 * DEPLOYMENT.md §7 — "ทุกวันเที่ยงคืน (00:05)". RULE-ATT-05 / TC-ATT-007.
 * Prepared in Phase 5 alongside the attendance system itself; NOT yet wired to a real scheduler
 * (crontab/Task Scheduler) — that's Phase 9's job (AI_AGENT_PHASES.md). Run manually until then:
 *   php cron/close_incomplete_attendance.php
 */

declare(strict_types=1);

date_default_timezone_set('Asia/Bangkok');

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/../app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

$closed = (new App\Services\AttendanceService())->closeStaleIncompleteDays();
echo "Closed {$closed} incomplete attendance row(s).\n";
