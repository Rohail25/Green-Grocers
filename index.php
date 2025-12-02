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
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Simple routing
$path = str_replace('/green-grocers', '', $path);
$path = ltrim($path, '/');

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
} elseif ($path === 'cart/checkout.php' || $path === 'cart/checkout') {
    requireAuth();
    require_once __DIR__ . '/website/pages/place-order.php';
} elseif ($path === 'customer/dashboard.php') {
    requireAuth('customer');
    require_once __DIR__ . '/website/pages/dashboard.php';
} elseif (strpos($path, 'order-success') !== false) {
    requireAuth();
    require_once __DIR__ . '/website/pages/order-success.php';
} else {
    // Try direct file access
    $filePath = __DIR__ . '/' . str_replace('..', '', $path);
    if (file_exists($filePath) && is_file($filePath)) {
        require_once $filePath;
    } else {
        header('Location: /green-grocers/');
        exit;
    }
}
?>

