<?php
/**
 * Create Stripe Payment Intent
 * Called via AJAX from checkout page
 */
// Set JSON header first
header('Content-Type: application/json');

// Prevent output errors from interfering
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set custom error handler to catch all errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $errstr . ' in ' . $errfile . ' on line ' . $errline]);
    exit;
}, E_ALL);

// Load required configuration and files
try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/encryption.php';
    require_once __DIR__ . '/auth.php';
    require_once __DIR__ . '/stripe-payment.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load required files: ' . $e->getMessage()]);
    exit;
}

// Check authentication
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// Get user
$currentUser = getCurrentUser();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['error' => 'User not found']);
    exit;
}

// Parse input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON request']);
    exit;
}

$amount = (float)($input['amount'] ?? 0);
$paymentMethodId = $input['payment_method_id'] ?? '';

if ($amount <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid amount']);
    exit;
}

try {
    $metadata = [
        'user_id' => $currentUser['id'] ?? '',
        'user_email' => $currentUser['email'] ?? ''
    ];
    
    $paymentIntent = createStripePaymentIntent($amount, 'usd', $metadata);
    
    if (!$paymentIntent || !isset($paymentIntent['id'])) {
        throw new Exception('Failed to create payment intent');
    }
    
    // If payment method is provided, attach it
    if (!empty($paymentMethodId)) {
        $updateData = ['payment_method' => $paymentMethodId];
        stripeRequest('payment_intents/' . $paymentIntent['id'], 'POST', $updateData);
    }
    
    http_response_code(200);
    echo json_encode([
        'payment_intent_id' => $paymentIntent['id'],
        'client_secret' => $paymentIntent['client_secret']
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Payment processing error: ' . $e->getMessage()]);
}
exit;
