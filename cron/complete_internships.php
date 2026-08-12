<?php
/**
 * DEPLOYMENT.md §7 — "ทุกวัน 00:15". RULE-GRADE-01: active → completed once hours/evaluations/
 * leave criteria are all met. Prepared in Phase 9; NOT yet wired to a real scheduler — run
 * manually until then: php cron/complete_internships.php
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

$completed = (new App\Services\InternshipService())->completeEligible();
echo "Completed {$completed} internship(s).\n";
