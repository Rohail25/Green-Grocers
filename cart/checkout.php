<?php
/**
 * Checkout Page - Routes to the actual checkout page
 * This file exists to handle the URL: /cart/checkout.php
 * The actual checkout functionality is in website/pages/place-order.php
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Require authentication
requireAuth();

// Load the actual checkout page
$checkoutFile = __DIR__ . '/../website/pages/place-order.php';
if (file_exists($checkoutFile)) {
    require_once $checkoutFile;
} else {
    // Fallback if file doesn't exist
    error_log("Checkout file not found: " . $checkoutFile);
    header('Location: ' . (defined('BASE_PATH') ? BASE_PATH : '/green-grocers') . '/');
    exit;
}
?>

