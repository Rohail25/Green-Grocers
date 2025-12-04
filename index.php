<?php
// Allow direct access to public folder files
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Check if request is for a public file (images, CSS, JS)
if (preg_match('#^/green-grocers/public/#', $path) || preg_match('#^/public/#', $path)) {
    $filePath = __DIR__ . '/public/' . basename($path);
    if (file_exists($filePath)) {
        $mimeTypes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'css' => 'text/css',
            'js' => 'application/javascript'
        ];
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mime = $mimeTypes[$ext] ?? 'application/octet-stream';
        header('Content-Type: ' . $mime);
        readfile($filePath);
        exit;
    }
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/config.php'; // Define BASE_PATH
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Simple routing
$path = str_replace('/green-grocers', '', $path);
$path = ltrim($path, '/');
$path = str_replace('index.php', '', $path);
$path = trim($path, '/');

// Debug: Log the path for troubleshooting (remove in production)
// error_log("Routing path: " . $path);

// Route handling
if (empty($path) || $path === 'index.php') {
    require_once __DIR__ . '/website/pages/landing.php';
} elseif ($path === 'category') {
    require_once __DIR__ . '/website/pages/category.php';
} elseif ($path === 'auth/login.php') {
    require_once __DIR__ . '/auth/login.php';
} elseif ($path === 'auth/register.php') {
    require_once __DIR__ . '/auth/register.php';
} elseif ($path === 'auth/admin-login.php') {
    require_once __DIR__ . '/auth/admin-login.php';
} elseif ($path === 'auth/forgot.php') {
    require_once __DIR__ . '/auth/forgot.php';
} elseif ($path === 'auth/verify.php') {
    require_once __DIR__ . '/auth/email-verification.php';
} elseif ($path === 'cart/checkout.php' || $path === 'cart/checkout' || strpos($path, 'cart/checkout') === 0 || preg_match('#^cart/checkout#', $path)) {
    // Checkout route - require authentication and load checkout page
    requireAuth();
    $checkoutFile = __DIR__ . '/website/pages/place-order.php';
    if (file_exists($checkoutFile)) {
        require_once $checkoutFile;
        exit; // Important: exit after loading to prevent further processing
    } else {
        // Fallback if file doesn't exist
        error_log("Checkout file not found: " . $checkoutFile);
        header('Location: ' . (defined('BASE_PATH') ? BASE_PATH : '/green-grocers') . '/');
        exit;
    }
} elseif ($path === 'customer/dashboard.php') {
    requireAuth('customer');
    require_once __DIR__ . '/website/pages/dashboard.php';
} elseif ($path === 'order-success.php' || $path === 'order-success' || strpos($path, 'order-success') !== false || preg_match('#^order-success#', $path)) {
    // Order success page route - handle order success page
    requireAuth();
    $orderSuccessFile = __DIR__ . '/website/pages/order-success.php';
    if (file_exists($orderSuccessFile)) {
        require_once $orderSuccessFile;
        exit; // Important: exit after loading to prevent further processing
    } else {
        error_log("Order success file not found: " . $orderSuccessFile);
        header('Location: ' . (defined('BASE_PATH') ? BASE_PATH : '/green-grocers') . '/');
        exit;
    }
} elseif (strpos($path, 'database/seeders/') === 0) {
    // Allow direct access to seeder files
    $seederFile = str_replace('database/seeders/', '', $path);
    $seederFilePath = __DIR__ . '/database/seeders/' . $seederFile;
    if (file_exists($seederFilePath) && is_file($seederFilePath)) {
        require_once $seederFilePath;
        exit;
    } else {
        header('Location: ' . (defined('BASE_PATH') ? BASE_PATH : '/green-grocers') . '/');
        exit;
    }
} elseif (strpos($path, 'dashboard/pages/') === 0) {
    // Route dashboard pages
    $dashboardPage = str_replace('dashboard/pages/', '', $path);
    $dashboardFilePath = __DIR__ . '/dashboard/pages/' . $dashboardPage;
    if (file_exists($dashboardFilePath) && is_file($dashboardFilePath)) {
        require_once $dashboardFilePath;
    } else {
        header('Location: ' . BASE_PATH . '/dashboard/pages/dashboard.php');
        exit;
    }
} else {
    // Try direct file access
    $filePath = __DIR__ . '/' . str_replace('..', '', $path);
    if (file_exists($filePath) && is_file($filePath)) {
        require_once $filePath;
    } else {
        $basePath = defined('BASE_PATH') ? BASE_PATH : '/green-grocers';
        header('Location: ' . $basePath . '/');
        exit;
    }
}
?>

