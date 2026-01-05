<?php
/**
 * FireWeb Messenger - Ultimate Edition (Premium UI v0.0.2)
 * PWA-Ready with Correct Routing & Fixed Setup
 * 
 * @author Alion (@prgpu / @Learn_launch)
 * @license MIT
 */

// Favicon
echo '<link rel="icon" href="assets/images/icon-96.png" type="image/png">' . PHP_EOL;

// Configuration
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Session configuration (before session_start)
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', 86400); // 24 hours

session_start();

// Define paths
define('BASE_PATH', __DIR__);
define('CONFIG_PATH', BASE_PATH . '/config');
define('APP_PATH', BASE_PATH . '/app');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('ASSETS_PATH', BASE_PATH . '/assets');
define('PUBLIC_PATH', BASE_PATH . '/public');

// Check installation
$setupLockFile = CONFIG_PATH . '/setup.lock';
$isInstalled = file_exists($setupLockFile);

if (!$isInstalled && (!isset($_GET['route']) || $_GET['route'] !== 'setup')) {
    header('Location: ?route=setup');
    exit;
}

// Auto-load classes
spl_autoload_register(function ($class) {
    $paths = [
        APP_PATH . '/controllers/' . $class . '.php',
        APP_PATH . '/models/' . $class . '.php',
        CONFIG_PATH . '/' . $class . '.php'
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return true;
        }
    }
    return false;
});

// ✅ FIX: Load database.php ALWAYS (needed for setup too)
if (file_exists(CONFIG_PATH . '/database.php')) {
    require_once CONFIG_PATH . '/database.php';
}

// Load configuration
if ($isInstalled) {
    if (file_exists(CONFIG_PATH . '/app.php')) {
        $config = require CONFIG_PATH . '/app.php';
    }
}

// Get route
$route = $_GET['route'] ?? null;

// Default route based on authentication status
if ($route === null) {
    if ($isInstalled) {
        if (isset($_SESSION['user_id'])) {
            $route = 'chat';
        }
        else {
            $route = 'home';
        }
    } else {
        $route = 'setup';
    }
}

// ✅ FIXED: Special routes with correct file paths
if ($route === 'manifest') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: public, max-age=604800');
    $manifestPath = BASE_PATH . '/manifest.json';
    if (file_exists($manifestPath)) {
        readfile($manifestPath);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Manifest not found']);
    }
    exit;
}

if ($route === 'sw') {
    header('Content-Type: application/javascript; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Service-Worker-Allowed: /FireWebCHAT/');
    $swPath = BASE_PATH . '/service-worker.js';
    if (file_exists($swPath)) {
        readfile($swPath);
    } else {
        http_response_code(404);
        echo 'console.error("Service worker not found");';
    }
    exit;
}

// Maintenance mode check
if (isset($config['maintenance_mode']) && $config['maintenance_mode'] === true) {
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        require APP_PATH . '/views/maintenance.php';
        exit;
    }
}

// Route handling
$allowedRoutes = ['setup', 'home', 'login', 'register', 'chat', 'admin', 'logout', 'profile'];

if (!in_array($route, $allowedRoutes)) {
    $route = 'home';
}

// Authentication check
$publicRoutes = ['setup', 'home', 'login', 'register'];
$requiresAuth = !in_array($route, $publicRoutes);

if ($requiresAuth && !isset($_SESSION['user_id'])) {
    header('Location: ?route=login');
    exit;
}

// Redirect authenticated users from auth pages
if (in_array($route, ['login', 'register']) && isset($_SESSION['user_id'])) {
    header('Location: ?route=chat');
    exit;
}

// Route to view
switch ($route) {
    case 'setup':
        require APP_PATH . '/views/setup.php';
        break;
        
    case 'home':
        require APP_PATH . '/views/home.php';
        break;
        
    case 'login':
        require APP_PATH . '/views/login.php';
        break;
        
    case 'register':
        require APP_PATH . '/views/register.php';
        break;
        
    case 'chat':
        require APP_PATH . '/views/chat.php';
        break;
        
    case 'admin':
        if (($_SESSION['role'] ?? '') !== 'admin') {
            header('Location: ?route=chat');
            exit;
        }
        require APP_PATH . '/views/admin.php';
        break;
        
    case 'profile':
        require APP_PATH . '/views/profile.php';
        break;
        
    case 'logout':
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');
        header('Location: ?route=home');
        exit;
        
    default:
        header('Location: ?route=home');
        exit;
}
