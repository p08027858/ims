<?php
/**
 * DEPLOYMENT.md §7 — "ทุก 30 นาที". Runs all of RULE-NOTI-01 to 05
 * (App\Services\NotificationService's run*() methods — see that class's docblock for the two
 * dedup strategies used). Prepared in Phase 9; NOT yet wired to a real scheduler — run manually
 * until then: php cron/send_notifications.php
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

$notifications = new App\Services\NotificationService();

$counts = [
    'RULE-NOTI-01 (no check-in)' => $notifications->runNoCheckinReminders(),
    'RULE-NOTI-02 (no daily log)' => $notifications->runNoDailyLogReminders(),
    'RULE-NOTI-03 (weekly eval overdue)' => $notifications->runWeeklyEvalOverdueReminders(),
    'RULE-NOTI-04 (final eval overdue)' => $notifications->runFinalEvalOverdueReminders(),
    'RULE-NOTI-05 (deadline approaching)' => $notifications->runDeadlineApproachingReminders(),
];

foreach ($counts as $label => $count) {
    echo "{$label}: sent {$count}\n";
}
echo 'Total: ' . array_sum($counts) . " notification(s) sent.\n";
