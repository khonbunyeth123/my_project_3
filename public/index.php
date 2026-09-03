<?php
date_default_timezone_set('Asia/Phnom_Penh');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Helpers/PermissionHelper.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// -----------------------------------------------------------------------
// FIX 1: error display off by default; only on when APP_DEBUG=true
// -----------------------------------------------------------------------
$appDebug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
error_reporting(E_ALL);
ini_set('display_errors',  $appDebug ? '1' : '0');
ini_set('log_errors',      '1');

// -----------------------------------------------------------------------
// FIX 2: secure session settings before session_start()
// -----------------------------------------------------------------------
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure',   $appDebug ? '0' : '1'); // only secure in production
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', '1');
ini_set('session.gc_maxlifetime',  '86400'); // 24 h

// -----------------------------------------------------------------------
// FIX 3: security headers on every response
// -----------------------------------------------------------------------
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
if (!$appDebug) {
    header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://code.iconify.design; style-src 'self' https://cdnjs.cloudflare.com https://fonts.googleapis.com 'unsafe-inline'; img-src 'self' data:; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com;");
}

// -----------------------------------------------------------------------
// URI resolution
// -----------------------------------------------------------------------
$uri      = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = rtrim((string) ($_ENV['APP_BASE_PATH'] ?? ''), '/');

if ($basePath !== '' && strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}
if ($uri === '') $uri = '/';

if ($uri === '/departments' || $uri === '/departments/') {
    $_GET['page'] = 'departments';
}

error_log("DEBUG: Resolved URI: " . $uri);

// -----------------------------------------------------------------------
// Public auth pages used by mobile deep links and email fallback
// -----------------------------------------------------------------------
if ($uri === '/forgot-password' || $uri === '/forgot-password/') {
    require_once __DIR__ . '/forgot-password.php';
    exit;
}

if ($uri === '/reset-password' || $uri === '/reset-password/') {
    require_once __DIR__ . '/reset-password.php';
    exit;
}

// -----------------------------------------------------------------------
// API requests — hand off to api router (session started inside api.php)
// -----------------------------------------------------------------------
if (strpos($uri, '/api/') === 0) {
    require_once __DIR__ . '/../routes/api.php';
    exit;
}

// -----------------------------------------------------------------------
// Web requests — start session here
// -----------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['login']) && $_SESSION['login'] === true;

// FIX 4: whitelist allowed page values — never trust raw $_GET['page']
$allowedPages = [
    'dashboard', 'attendance', 'employee', 'leave', 'audits',
    'report', 'report/report_daily', 'report/report_summary',
    'report/report_detail', 'report/report_top_employee',
    'user', 'roles', 'departments', 'permissions', 'checkin', 'calendar', 'payroll',
];

$rawPage = $_GET['page'] ?? 'dashboard';
// FIX 4: reject any page not in the whitelist — prevents path traversal
$page = in_array($rawPage, $allowedPages, true) ? $rawPage : 'dashboard';

$pagePermissions = [
    'dashboard'                   => ['dashboard.view'],
    'attendance'                  => ['attendance.view'],
    'employee'                    => ['employee.view', 'employees.view'],
    'leave'                       => ['leave.view'],
    'calendar'                    => ['calendar.view', 'calendar.manage'],
    'payroll'                     => ['payroll.view', 'payroll.manage'],
    'report'                      => ['report.view'],
    'report/report_daily'         => ['report.view_daily',   'report.view'],
    'report/report_summary'       => ['report.view_summary', 'report.view'],
    'report/report_detail'        => ['report.view_detail',  'report.view'],
    'report/report_top_employee'  => ['report.view_top',     'report.view'],
    'user'                        => ['user.view',       'users.view'],
    'roles'                       => ['role.view',       'roles.view'],
    'departments'                 => ['roles.manage'],
    'permissions'                 => ['permission.view', 'permissions.view'],
    'audits'                      => ['audits.view',     'audit.view'],
];

$protectedPages = array_keys($pagePermissions);

$canAccessSlugs = static function (array $slugs): bool {
    if (function_exists('hasAnyPermissionSlugs')) {
        return hasAnyPermissionSlugs($slugs);
    }
    foreach ($slugs as $slug) {
        if (is_string($slug) && hasPermissionSlug($slug)) return true;
    }
    return false;
};

$canAccessPage = static function (string $pageName) use ($pagePermissions, $canAccessSlugs): bool {
    if (!isset($pagePermissions[$pageName])) return true;
    $slugs = is_array($pagePermissions[$pageName]) ? $pagePermissions[$pageName] : [$pagePermissions[$pageName]];
    return $canAccessSlugs($slugs);
};

$findFirstAccessiblePage = static function () use ($pagePermissions, $canAccessSlugs): ?string {
    foreach ($pagePermissions as $candidate => $slugs) {
        $slugs = is_array($slugs) ? $slugs : [$slugs];
        if ($canAccessSlugs($slugs)) return $candidate;
    }
    return null;
};

$isProtectedPage = in_array($page, $protectedPages, true) || isset($pagePermissions[$page]);

// -----------------------------------------------------------------------
// Auth check
// -----------------------------------------------------------------------
if (!$isLoggedIn && $isProtectedPage) {
    header('Location: /login.php');
    exit;
}

if ($isLoggedIn && $page === 'login') {
    $homePage = $findFirstAccessiblePage() ?? 'dashboard';
    header('Location: /index.php?page=' . urlencode($homePage));
    exit;
}

// -----------------------------------------------------------------------
// Page-level RBAC check
// -----------------------------------------------------------------------
if ($isLoggedIn && $isProtectedPage && !$canAccessPage($page)) {
    $homePage = $findFirstAccessiblePage();
    if ($homePage !== null && $homePage !== $page) {
        header('Location: /index.php?page=' . urlencode($homePage));
        exit;
    }

    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Denied</title>
        <style>
            body { margin:0; font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; background:#f3f4f6; color:#111827; }
            .wrap { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
            .card { background:#fff; border-radius:12px; padding:24px; max-width:560px; width:100%; box-shadow:0 10px 25px rgba(0,0,0,.08); }
            h1 { margin:0 0 12px; font-size:24px; }
            p  { margin:0; line-height:1.5; }
            a  { display:inline-block; margin-top:16px; text-decoration:none; color:#1d4ed8; }
        </style>
    </head>
    <body>
        <div class="wrap"><div class="card">
            <h1>403 — Access Denied</h1>
            <p>You do not have permission to access this page.</p>
            <a href="/index.php?page=dashboard">Go to dashboard</a>
        </div></div>
    </body>
    </html>
    <?php
    exit;
}

// -----------------------------------------------------------------------
// View system
// -----------------------------------------------------------------------
$baseDir   = __DIR__;
$layoutDir = $baseDir . '/../resources/views/layouts';
$viewDir   = $baseDir . '/../resources/views';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DoorStep Technology Co.,Ltd</title>
    <link rel="icon" href="/assets/img/logo.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://code.iconify.design/2/2.2.1/iconify.min.js"></script>
</head>
<body class="bg-slate-50 text-slate-900 overflow-x-hidden" style="margin:0;padding:0;">

<?php if ($isLoggedIn): ?>
    <div class="flex flex-col h-screen">
        <?php include $layoutDir . '/navbar.php'; ?>

        <div class="flex flex-1 min-h-0 overflow-hidden">
            <div id="sidebarOverlay" class="hidden fixed inset-0 top-[56px] z-30 bg-slate-900/20 md:hidden"></div>
            <?php if (file_exists($layoutDir . '/sidebar.php')) include $layoutDir . '/sidebar.php'; ?>

            <main class="flex-1 min-w-0 flex flex-col overflow-hidden">
                <!-- Breadcrumbs & Header -->


                <header class="bg-white/80 backdrop-blur-md border-b border-slate-100 px-4 py-3">
                    <nav class="flex text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-0.5" aria-label="Breadcrumb">
                        <ol class="flex items-center space-x-1">
                            <li><a href="?page=dashboard" class="hover:text-indigo-600 transition-colors">Dashboard</a></li>
                            <?php if ($page !== 'dashboard'): ?>
                                <li><span class="mx-1 text-slate-200">/</span></li>
                                <li class="text-indigo-600 capitalize"><?= str_replace('_', ' ', basename($page)) ?></li>
                            <?php endif; ?>
                        </ol>
                    </nav>
                    <h2 class="text-lg font-black text-slate-900 tracking-tight capitalize">
                        <?= str_replace(['/', '_'], [' ', ' '], $page) ?>
                    </h2>
                </header>

                <div class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden p-4">
                    <?php
                    // FIX 5: resolve view path and verify it stays inside $viewDir
                    //         prevents path traversal via crafted $page values
                    $realViewDir = realpath($viewDir);
                    $pageFile    = realpath($viewDir . '/' . $page . '.php');

                    if ($pageFile !== false
                        && strpos($pageFile, $realViewDir . DIRECTORY_SEPARATOR) === 0
                        && file_exists($pageFile)
                    ) {
                        // Inject employee data for attendance view
                        if ($page === 'attendance' && !empty($_SESSION['employee_id'])) {
                            $pdo  = \App\Core\Database::getInstance()->getConnection();
                            $stmt = $pdo->prepare("
                                SELECT id, full_name
                                FROM tbl_employees
                                WHERE id = ? AND deleted_at IS NULL
                                LIMIT 1
                            ");
                            $stmt->execute([$_SESSION['employee_id']]);
                            $employee = $stmt->fetch(\PDO::FETCH_ASSOC) ?? [
                                'id'        => 0,
                                'full_name' => $_SESSION['full_name'] ?? '',
                            ];
                        }
                        include $pageFile;
                    } else {
                        echo '<div class="text-center text-gray-500 mt-10"><h1>Page not found</h1></div>';
                    }
                    ?>
                </div>
            </main>
        </div>

        <?php if (file_exists($layoutDir . '/footer.php')) include $layoutDir . '/footer.php'; ?>
    </div>

<?php else: ?>
    <?php include $baseDir . '/login.php'; ?>
<?php endif; ?>

<script src="/assets/js/jquery.js"></script>
<script src="/assets/js/script.js"></script>
</body>
</html>
