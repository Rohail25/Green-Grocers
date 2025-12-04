<?php
/**
 * Order Success Page - Routes to the actual order success page
 * This file exists to handle the URL: /order-success.php
 * The actual order success functionality is in website/pages/order-success.php
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Require authentication
requireAuth();

// Load the actual order success page
$orderSuccessFile = __DIR__ . '/website/pages/order-success.php';
if (file_exists($orderSuccessFile)) {
    require_once $orderSuccessFile;
} else {
    // Fallback if file doesn't exist
    error_log("Order success file not found: " . $orderSuccessFile);
    header('Location: ' . (defined('BASE_PATH') ? BASE_PATH : '/green-grocers') . '/');
    exit;
}
?>

