<?php
/**
 * Front controller. Every request is routed through here (see DEPLOYMENT.md §2).
 *
 * Phase 2 (AI_AGENT_PHASES.md): real auth via Supabase now sits in front of every route.
 * config/actions.php (POST/GET actions -> Controllers) is checked first; anything left falls
 * back to config/routes.php (GET view routes), guarded by App\Middleware\AuthGuard using the
 * `role` column already declared per route. Business data (Phase 3+) is still mostly mock —
 * only auth/session/profile display data is real as of this phase.
 */

declare(strict_types=1);
error_reporting(E_ALL);

$baseDir = dirname(__DIR__);

// SECURITY.md §3 "Security Misconfiguration" / DEPLOYMENT.md §8 Go-Live checklist: never leak
// stack traces/paths to visitors in production. `APP_ENV` defaults to 'local' (DEPLOYMENT.md §4's
// documented env var) so local dev/testing keeps seeing errors on-screen exactly as before —
// only a real `APP_ENV=production` in the server's environment turns display off. Errors are
// always logged to a file regardless of environment (the checklist's other half).
$appEnv = getenv('APP_ENV') ?: 'local';
ini_set('display_errors', $appEnv === 'production' ? '0' : '1');
ini_set('log_errors', '1');
$logDir = $baseDir . '/storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/app.log');

// SECURITY.md §3 / TC-SEC-TEST-001 (Phase 12): any uncaught exception (e.g. an upstream
// PostgREST/Supabase failure) must never reach the visitor as a raw PHP fatal error with a
// stack trace and file paths.
set_exception_handler(function (\Throwable $e) use ($appEnv): void {
    error_log('Uncaught ' . get_class($e) . ': ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    if (!headers_sent()) {
        http_response_code(500);
    }
    if ($appEnv !== 'production') {
        echo '<pre>' . htmlspecialchars((string) $e) . '</pre>';
        return;
    }
    echo '<!DOCTYPE html><html lang="th"><head><meta charset="utf-8"><title>500</title></head>' .
         '<body style="font-family:sans-serif;text-align:center;padding:4rem;">' .
         '<h1>500 — เกิดข้อผิดพลาดที่ไม่คาดคิด กรุณาลองใหม่อีกครั้ง</h1><p><a href="/login">กลับไปหน้าเข้าสู่ระบบ</a></p></body></html>';
});

// ATTENDANCE_GPS.md §7: Asia/Bangkok (+07:00)
date_default_timezone_set('Asia/Bangkok');

spl_autoload_register(function (string $class) use ($baseDir): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = $baseDir . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

use App\Middleware\AuthGuard;
use App\Support\Session;
use App\Support\View;

Session::start();

$routes = require $baseDir . '/config/routes.php';
$actions = require $baseDir . '/config/actions.php';
$viewData = require $baseDir . '/config/view_data.php';

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestPath = rtrim($requestPath, '/');
if ($requestPath === '') {
    $requestPath = '/';
}

/**
 * Match either an exact path or a `{id}`-style dynamic segment
 */
function matchPath(string $pattern, string $requestPath): ?array
{
    if ($pattern === $requestPath) {
        return [];
    }
    if (strpos($pattern, '{') === false) {
        return null;
    }
    $paramNames = [];
    $regex = preg_replace_callback('/\{([a-zA-Z_]+)\}/', function ($m) use (&$paramNames) {
        $paramNames[] = $m[1];
        return '([^/]+)';
    }, $pattern);
    if (!preg_match('#^' . $regex . '$#', $requestPath, $matches)) {
        return null;
    }
    array_shift($matches);
    return array_combine($paramNames, $matches);
}

// ---------------------------------------------------------------------------
// 1. Actions (POST forms, and the GET /logout link)
// ---------------------------------------------------------------------------
foreach ($actions as [$method, $pattern, $handler, $requiredRole]) {
    if ($method !== $requestMethod) {
        continue;
    }
    $params = matchPath($pattern, $requestPath);
    if ($params === null) {
        continue;
    }

    if (in_array($requestMethod, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        $csrfToken = (string) ($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!Session::validateCsrfToken($csrfToken)) {
            $wantsJson = $requestMethod === 'DELETE' || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');
            if ($wantsJson) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => ['code' => 'CSRF_TOKEN_INVALID', 'message' => 'คำขอไม่ถูกต้องหรือเซสชันหมดอายุ กรุณาโหลดหน้าใหม่']], JSON_UNESCAPED_UNICODE);
            } else {
                Session::flashError('เซสชันหมดอายุหรือคำขอไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง');
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/login'));
            }
            exit;
        }
    }

    if ($requiredRole !== null) {
        AuthGuard::requireRole($requiredRole);
    }
    [$controllerClass, $methodName] = $handler;
    (new $controllerClass())->{$methodName}($params);
    exit;
}

if ($requestMethod !== 'GET') {
    http_response_code(405);
    echo 'Method not allowed';
    exit;
}

// ---------------------------------------------------------------------------
// 2. GET view routes
// ---------------------------------------------------------------------------

function matchRoute(string $requestPath, array $routes): ?array
{
    if (isset($routes[$requestPath])) {
        return [$routes[$requestPath], []];
    }
    foreach ($routes as $pattern => $definition) {
        $params = matchPath($pattern, $requestPath);
        if ($params !== null) {
            return [$definition, array_values($params)];
        }
    }
    return null;
}

$matched = matchRoute($requestPath, $routes);

if ($matched === null) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="th"><head><meta charset="utf-8"><title>404</title></head>' .
         '<body style="font-family:sans-serif;text-align:center;padding:4rem;">' .
         '<h1>404 — ไม่พบหน้านี้</h1><p><a href="/login">กลับไปหน้าเข้าสู่ระบบ</a></p></body></html>';
    exit;
}

[$definition, $routeParams] = $matched;

if ($definition[0] === 'redirect') {
    header('Location: ' . $definition[1]);
    exit;
}

[$viewName, $role, $activeNav, $pageTitle] = $definition;

if (!is_file($baseDir . '/app/Views/' . $viewName . '.php')) {
    http_response_code(500);
    echo 'Missing view file: ' . htmlspecialchars($viewName);
    exit;
}

AuthGuard::requireRole($role);

$extraData = [];
if (isset($viewData[$requestPath])) {
    [$loaderClass, $loaderMethod] = $viewData[$requestPath];
    $extraData = (new $loaderClass())->{$loaderMethod}($routeParams);
} else {
    foreach ($viewData as $pattern => $loaderHandler) {
        $params = matchPath($pattern, $requestPath);
        if ($params !== null) {
            [$loaderClass, $loaderMethod] = $loaderHandler;
            $extraData = (new $loaderClass())->{$loaderMethod}($params);
            break;
        }
    }
}

View::render($viewName, $role, array_merge($extraData, [
    'pageTitle' => $pageTitle,
    'activeNav' => $activeNav,
    'routeParams' => $routeParams,
]));