<?php
/**
 * Stripe Payment Handler
 * Handles Stripe payment processing using Stripe API
 */

// Load Stripe API Keys from secure config file
// Keys are stored in config/stripe-keys.php for security
$stripeKeysPath = __DIR__ . '/../config/stripe-keys.php';
if (file_exists($stripeKeysPath)) {
    require_once $stripeKeysPath;
} else {
    // Fallback: If config file doesn't exist, show error
    die('ERROR: Stripe keys configuration file not found. Please create config/stripe-keys.php');
}

// Ensure keys are defined
if (!defined('STRIPE_SECRET_KEY') || !defined('STRIPE_PUBLISHABLE_KEY')) {
    die('ERROR: Stripe API keys are not properly configured. Please check config/stripe-keys.php');
}

// Initialize Stripe (using cURL since we don't have Composer)
function stripeRequest($endpoint, $method = 'POST', $data = []) {
    $ch = curl_init();
    $url = 'https://api.stripe.com/v1/' . $endpoint;
    
    // For GET requests, append query string
    if ($method === 'GET' && !empty($data)) {
        $url .= '?' . http_build_query($data);
    }
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SECRET_KEY . ':');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        throw new Exception('cURL error: ' . $curlError);
    }
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return json_decode($response, true);
    } else {
        $error = json_decode($response, true);
        throw new Exception($error['error']['message'] ?? 'Stripe API error (HTTP ' . $httpCode . ')');
    }
}

// Create Payment Intent
function createStripePaymentIntent($amount, $currency = 'usd', $metadata = []) {
    $amountInCents = (int)($amount * 100); // Convert to cents
    
    $data = [
        'amount' => $amountInCents,
        'currency' => $currency,
        'payment_method_types[]' => 'card'
    ];
    
    // Add metadata if provided
    if (!empty($metadata)) {
        foreach ($metadata as $key => $value) {
            $data['metadata[' . $key . ']'] = $value;
        }
    }
    
    return stripeRequest('payment_intents', 'POST', $data);
}

// Confirm Payment Intent
function confirmStripePaymentIntent($paymentIntentId, $paymentMethodId) {
    $data = [
        'payment_method' => $paymentMethodId
    ];
    
    return stripeRequest('payment_intents/' . $paymentIntentId . '/confirm', 'POST', $data);
}

// Retrieve Payment Intent
function retrieveStripePaymentIntent($paymentIntentId) {
    return stripeRequest('payment_intents/' . $paymentIntentId, 'GET');
}

