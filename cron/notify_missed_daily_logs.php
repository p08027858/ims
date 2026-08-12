<?php
/**
 * RULE-LOG-04. Prepared in Phase 6 alongside the daily log system itself; NOT yet wired to a
 * real scheduler — that's Phase 9's job (AI_AGENT_PHASES.md), same pattern as
 * cron/close_incomplete_attendance.php from Phase 5. Run manually until then:
 *   php cron/notify_missed_daily_logs.php
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

$notified = (new App\Services\DailyLogService())->notifyMissedLogs();
echo "Sent {$notified} missed-daily-log notification(s).\n";
