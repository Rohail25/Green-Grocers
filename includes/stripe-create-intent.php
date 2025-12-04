<?php
/**
 * Create Stripe Payment Intent
 * Called via AJAX from checkout page
 */
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/stripe-payment.php';

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$amount = (float)($input['amount'] ?? 0);
$paymentMethodId = $input['payment_method_id'] ?? '';

if ($amount <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid amount']);
    exit;
}

try {
    $currentUser = getCurrentUser();
    $metadata = [
        'user_id' => $currentUser['id'],
        'user_email' => $currentUser['email'] ?? ''
    ];
    
    $paymentIntent = createStripePaymentIntent($amount, 'usd', $metadata);
    
    // If payment method is provided, attach it
    if (!empty($paymentMethodId)) {
        $updateData = ['payment_method' => $paymentMethodId];
        stripeRequest('payment_intents/' . $paymentIntent['id'], 'POST', $updateData);
    }
    
    echo json_encode([
        'payment_intent_id' => $paymentIntent['id'],
        'client_secret' => $paymentIntent['client_secret']
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

