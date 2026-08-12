<?php
/**
 * Shared app shell (layout).
 * Every page view is rendered INSIDE this file via $contentView (see public/index.php router).
 * Design tokens below are copied 1:1 from design-reference/ims/DESIGN.md — do not hand-edit
 * colors here without updating that file too.
 *
 * TODO (pre-production): replace the Tailwind CDN <script> below with a compiled Tailwind CLI
 * build (see Internship_Project_Blueprint/MASTER_SPEC.md §9.2 — CDN JIT is dev-only, not
 * acceptable for production performance). Left as CDN for now because this machine has no
 * Node/npm installed yet (see ISSUES.md).
 */

/** @var string $pageTitle */
/** @var string $activeNav e.g. 'dashboard', 'attendance', 'daily_log', 'notifications', 'profile' */
/** @var string $role one of: guest, student, company, teacher, admin, super_admin */
/** @var string $contentView absolute path to the view file to render inside <main> */
/** @var array $user current user mock data (see each controller/view for shape) */

$pageTitle = $pageTitle ?? 'IMS THAI';
$role = $role ?? 'guest';
$activeNav = $activeNav ?? '';
$user = $user ?? ['first_name' => 'ผู้ใช้งาน', 'avatar_initial' => 'U'];
$showAppShell = !in_array($role, ['guest'], true); // login/register/pending-approval render standalone
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0, viewport-fit=cover" name="viewport"/>
<!-- SECURITY.md §3 CSRF (Phase 12) — the few fetch()-based endpoints (attendance checkin/out,
     Super Admin PIN verify) read this to send X-CSRF-Token; every plain <form> instead carries
     the same token as a hidden csrf_token field. Validated centrally in public/index.php. -->
<meta name="csrf-token" content="<?= htmlspecialchars(\App\Support\Session::csrfToken()) ?>"/>
<title><?= htmlspecialchars($pageTitle) ?></title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script id="tailwind-config">
  tailwind.config = {
    darkMode: "class",
    theme: {
      extend: {
        colors: {
          "surface-container": "#f0ecf9", "on-tertiary-fixed": "#351000", "surface-tint": "#4d44e3",
          "on-error": "#ffffff", "surface-dark": "#1E293B", "tertiary-container": "#a44100",
          "inverse-on-surface": "#f3effc", "surface-dim": "#dcd8e5", "on-primary": "#ffffff",
          "status-success": "#10B981", "on-primary-fixed-variant": "#3323cc", "tertiary-fixed": "#ffdbcc",
          "on-tertiary-fixed-variant": "#7b2f00", "primary-container": "#4f46e5", "outline-variant": "#c7c4d8",
          "inverse-surface": "#302f39", "on-background": "#1b1b24", "surface-container-low": "#f5f2ff",
          "text-dark-mode": "#E2E8F0", "on-primary-fixed": "#0f0069", "error-container": "#ffdad6",
          "surface": "#fcf8ff", "tertiary-fixed-dim": "#ffb695", "status-error": "#EF4444",
          "on-surface": "#1b1b24", "outline": "#777587", "surface-container-highest": "#e4e1ee",
          "bg-dark": "#0F172A", "background": "#fcf8ff", "surface-container-lowest": "#ffffff",
          "secondary-container": "#6063ee", "surface-variant": "#e4e1ee", "on-secondary": "#ffffff",
          "surface-bright": "#fcf8ff", "tertiary": "#7e3000", "primary-fixed-dim": "#c3c0ff",
          "on-tertiary-container": "#ffd2be", "status-inactive": "#94A3B8", "on-secondary-fixed-variant": "#2f2ebe",
          "on-error-container": "#93000a", "on-surface-variant": "#464555", "on-tertiary": "#ffffff",
          "bg-light": "#F8FAFC", "primary": "#3525cd", "on-secondary-fixed": "#07006c",
          "secondary-fixed-dim": "#c0c1ff", "error": "#ba1a1a", "on-primary-container": "#dad7ff",
          "secondary": "#4648d4", "on-secondary-container": "#fffbff", "secondary-fixed": "#e1e0ff",
          "inverse-primary": "#c3c0ff", "primary-fixed": "#e2dfff", "status-warning": "#F59E0B",
          "surface-container-high": "#eae6f4"
        },
        borderRadius: { DEFAULT: "0.25rem", lg: "0.5rem", xl: "0.75rem", full: "9999px" },
        spacing: {
          "sidebar-width": "280px", "margin-mobile": "1.25rem", "margin-desktop": "2rem",
          "touch-target": "44px", "gutter": "1rem", "bottom-nav-height": "64px"
        },
        fontFamily: {
          "headline-lg": ["Plus Jakarta Sans"], "metadata": ["Be Vietnam Pro"],
          "headline-lg-mobile": ["Plus Jakarta Sans"], "body-md": ["Be Vietnam Pro"],
          "headline-md": ["Plus Jakarta Sans"], "label-md": ["Be Vietnam Pro"],
          "display-metrics": ["Plus Jakarta Sans"], "body-lg": ["Be Vietnam Pro"]
        },
        fontSize: {
          "headline-lg": ["28px", {lineHeight: "36px", fontWeight: "700"}],
          "metadata": ["12px", {lineHeight: "16px", fontWeight: "400"}],
          "headline-lg-mobile": ["24px", {lineHeight: "32px", fontWeight: "700"}],
          "body-md": ["16px", {lineHeight: "24px", fontWeight: "400"}],
          "headline-md": ["20px", {lineHeight: "28px", fontWeight: "600"}],
          "label-md": ["14px", {lineHeight: "20px", fontWeight: "500"}],
          "display-metrics": ["40px", {lineHeight: "48px", letterSpacing: "-0.02em", fontWeight: "700"}],
          "body-lg": ["18px", {lineHeight: "28px", fontWeight: "400"}]
        }
      }
    }
  };
</script>
<style>
  .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
  .material-symbols-outlined.fill { font-variation-settings: 'FILL' 1; }
  .shadow-soft { box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
  @keyframes shake { 0%,100%{transform:translateX(0);} 20%,60%{transform:translateX(-4px);} 40%,80%{transform:translateX(4px);} }
  .shake-anim { animation: shake 0.4s cubic-bezier(0.36,0.07,0.19,0.97) both; }
</style>
</head>
<body class="bg-bg-light dark:bg-bg-dark text-on-surface dark:text-text-dark-mode font-body-md min-h-screen<?= $showAppShell ? ' flex' : ' flex items-center justify-center p-margin-mobile' ?>">
<?php if ($showAppShell): ?>
  <?php include __DIR__ . '/../partials/sidebar.php'; ?>
  <div class="flex-1 flex flex-col lg:ml-sidebar-width min-h-screen pb-[calc(var(--bottom-nav-height,64px)+env(safe-area-inset-bottom))] lg:pb-0">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <main class="flex-1 p-margin-mobile md:p-margin-desktop mt-16 overflow-y-auto">
      <?php include $contentView; ?>
    </main>
  </div>
  <?php include __DIR__ . '/../partials/bottom_nav.php'; ?>
<?php else: ?>
  <?php include $contentView; ?>
<?php endif; ?>
</body>
</html>
