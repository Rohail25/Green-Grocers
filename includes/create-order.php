<?php
/**
 * Order Creation - Matching Node.js Backend Flow
 */
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/config.php'; // for BASE_PATH
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/stripe-payment.php';

requireAuth();

$currentUser = getCurrentUser();
$cart = getCart($currentUser['id']);

if (empty($cart['items']) || count($cart['items']) === 0) {
    $_SESSION['error'] = 'Your cart is empty';
    header('Location: ' . BASE_PATH . '/cart/checkout.php');
    exit;
}

// Get form data
$paymentMethod = $_POST['payment_method'] ?? 'card';
$shippingAddress = $_POST['delivery_address'] ?? '';
$notes = $_POST['notes'] ?? '';
$customerName = $_POST['full_name'] ?? trim(($currentUser['firstName'] ?? '') . ' ' . ($currentUser['lastName'] ?? ''));
$phone = $_POST['phone'] ?? '';
$discount = (float)($_POST['discount'] ?? 0);
$couponCode = $_POST['coupon_code'] ?? null;
$stripePaymentIntentId = $_POST['stripe_payment_intent_id'] ?? '';
$stripePaymentMethodId = $_POST['stripe_payment_method_id'] ?? '';

// Check if shipping address is provided - if not, redirect back to step 2
if (empty($shippingAddress)) {
    $_SESSION['error'] = 'Please provide a delivery address';
    // Use absolute URL to ensure proper redirect
    $redirectUrl = BASE_PATH . '/cart/checkout.php?step=2';
    header('Location: ' . $redirectUrl);
    exit;
}

$conn = getDBConnection();

// Calculate total from cart record
$items = $cart['items'];
$totalAmount = (float)$cart['totalPrice'];

// Apply discount if any
$finalTotal = max(0, $totalAmount - $discount);

// Handle Stripe payment verification
$paymentStatus = 'PENDING';
$transactionId = null;

if ($paymentMethod === 'stripe' && !empty($stripePaymentIntentId)) {
    try {
        // Retrieve and confirm payment intent
        $paymentIntent = retrieveStripePaymentIntent($stripePaymentIntentId);
        
        if ($paymentIntent['status'] === 'succeeded') {
            $paymentStatus = 'PAID';
            $transactionId = $paymentIntent['id'];
        } elseif ($paymentIntent['status'] === 'requires_payment_method') {
            // Try to confirm with payment method
            if (!empty($stripePaymentMethodId)) {
                $confirmedIntent = confirmStripePaymentIntent($stripePaymentIntentId, $stripePaymentMethodId);
                if ($confirmedIntent['status'] === 'succeeded') {
                    $paymentStatus = 'PAID';
                    $transactionId = $confirmedIntent['id'];
                } else {
                    $_SESSION['error'] = 'Payment failed: ' . ($confirmedIntent['last_payment_error']['message'] ?? 'Unknown error');
                    header('Location: ' . BASE_PATH . '/cart/checkout.php?step=3');
                    exit;
                }
            } else {
                $_SESSION['error'] = 'Payment method is required';
                header('Location: ' . BASE_PATH . '/cart/checkout.php?step=3');
                exit;
            }
        } else {
            $_SESSION['error'] = 'Payment not completed. Status: ' . $paymentIntent['status'];
            header('Location: ' . BASE_PATH . '/cart/checkout.php?step=3');
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Stripe payment error: ' . $e->getMessage();
        header('Location: ' . BASE_PATH . '/cart/checkout.php?step=3');
        exit;
    }
}

// Create order with items as JSON array
try {
    $conn->beginTransaction();
    
    // Fetch vendorId from products table for the first product in cart
    // If cart has multiple products from different vendors, use the first one
    $vendorId = 'UNKNOWN';
    if (!empty($items)) {
        $firstItem = $items[0];
        $firstProductId = $firstItem['productId'] ?? null;
        
        if ($firstProductId) {
            // Check if it's a product (not a package)
            $type = $firstItem['type'] ?? 'product';
            
            if ($type === 'product') {
                // Fetch vendorId from products table
                $vendorStmt = $conn->prepare("SELECT vendorId FROM products WHERE id = :id");
                $vendorStmt->execute([':id' => $firstProductId]);
                $product = $vendorStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($product && !empty($product['vendorId'])) {
                    $vendorId = $product['vendorId'];
                }
            } else {
                // For packages, packages don't have vendorId, so use 'UNKNOWN' or fetch from first product in package
                // For now, keep as 'UNKNOWN' for packages
                $vendorId = 'UNKNOWN';
            }
        }
    }

    // Generate UUID for order ID
    function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    $orderId = generateUUID();
    
    // Generate order number in ORD-XXXX format
    function generateOrderNumber($conn) {
        try {
            // Get the highest order number
            $stmt = $conn->query("SELECT orderNumber FROM orders WHERE orderNumber LIKE 'ORD-%' ORDER BY CAST(SUBSTRING(orderNumber, 5) AS UNSIGNED) DESC LIMIT 1");
            $lastOrder = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($lastOrder && !empty($lastOrder['orderNumber'])) {
                // Extract number from last order (e.g., ORD-1001 -> 1001)
                $lastNumber = intval(substr($lastOrder['orderNumber'], 4));
                $nextNumber = $lastNumber + 1;
            } else {
                // Start from 1001 if no orders exist
                $nextNumber = 1001;
            }
        } catch (PDOException $e) {
            // If orderNumber column doesn't exist, start from 1001
            $nextNumber = 1001;
        }
        
        return 'ORD-' . $nextNumber;
    }
    
    $orderNumber = generateOrderNumber($conn);

    // Try to insert with orderNumber first, fallback to without it
    try {
        $stmt = $conn->prepare("
            INSERT INTO orders (
                id,
                orderNumber,
                userId,
                vendorId,
                items,
                shippingAddress,
                customerName,
                totalAmount,
                discountAmount,
                couponCode,
                paymentMethod,
                paymentStatus,
                transactionId,
                notes
            ) VALUES (
                :id,
                :orderNumber,
                :userId,
                :vendorId,
                :items,
                :shippingAddress,
                :customerName,
                :totalAmount,
                :discountAmount,
                :couponCode,
                :paymentMethod,
                :paymentStatus,
                :transactionId,
                :notes
            )
        ");
        
        $stmt->execute([
            ':id' => $orderId,
            ':orderNumber' => $orderNumber,
            ':userId' => $currentUser['id'],
            ':vendorId' => $vendorId,
            ':items' => json_encode($items),
            ':shippingAddress' => json_encode([
                'street' => $shippingAddress,
                'phone' => $phone,
                'notes' => $notes
            ]),
            ':customerName' => $customerName,
            ':totalAmount' => $finalTotal,
            ':discountAmount' => $discount,
            ':couponCode' => $couponCode,
            ':paymentMethod' => strtoupper($paymentMethod),
            ':paymentStatus' => $paymentStatus,
            ':transactionId' => $transactionId,
            ':notes' => $notes
        ]);
    } catch (PDOException $e) {
        // If orderNumber column doesn't exist, insert without it
        if (strpos($e->getMessage(), 'orderNumber') !== false || strpos($e->getMessage(), 'Unknown column') !== false) {
            $stmt = $conn->prepare("
                INSERT INTO orders (
                    id,
                    userId,
                    vendorId,
                    items,
                    shippingAddress,
                    customerName,
                    totalAmount,
                    discountAmount,
                    couponCode,
                    paymentMethod,
                    paymentStatus,
                    transactionId,
                    notes
                ) VALUES (
                    :id,
                    :userId,
                    :vendorId,
                    :items,
                    :shippingAddress,
                    :customerName,
                    :totalAmount,
                    :discountAmount,
                    :couponCode,
                    :paymentMethod,
                    :paymentStatus,
                    :transactionId,
                    :notes
                )
            ");
            
            $stmt->execute([
                ':id' => $orderId,
                ':userId' => $currentUser['id'],
                ':vendorId' => $vendorId,
                ':items' => json_encode($items),
                ':shippingAddress' => json_encode([
                    'street' => $shippingAddress,
                    'phone' => $phone,
                    'notes' => $notes
                ]),
                ':customerName' => $customerName,
                ':totalAmount' => $finalTotal,
                ':discountAmount' => $discount,
                ':couponCode' => $couponCode,
                ':paymentMethod' => strtoupper($paymentMethod),
                ':paymentStatus' => $paymentStatus,
                ':transactionId' => $transactionId,
                ':notes' => $notes
            ]);
        } else {
            // If error is not about orderNumber column, re-throw it
            throw $e;
        }
    }
    
    // Match Node.js: update product quantities
    foreach ($items as $item) {
        if (isset($item['productId']) && isset($item['quantity'])) {
            // Update product stock
            $updateStmt = $conn->prepare("
                UPDATE products 
                SET totalQuantityInStock = totalQuantityInStock - :quantity 
                WHERE id = :productId
            ");
            $updateStmt->execute([
                ':quantity' => (int)$item['quantity'],
                ':productId' => $item['productId']
            ]);
            
            // Match Node.js: update variant quantity if variantIndex provided
            if (isset($item['variantIndex'])) {
                $productStmt = $conn->prepare("SELECT variants FROM products WHERE id = :id");
                $productStmt->execute([':id' => $item['productId']]);
                $product = $productStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($product && !empty($product['variants'])) {
                    $variants = json_decode($product['variants'], true);
                    $variantIndex = (int)$item['variantIndex'];
                    
                    if (isset($variants[$variantIndex])) {
                        $variants[$variantIndex]['quantity'] = ($variants[$variantIndex]['quantity'] ?? 0) - (int)$item['quantity'];
                        $variants[$variantIndex]['inStock'] = ($variants[$variantIndex]['quantity'] ?? 0) > 0;
                        
                        $variantStmt = $conn->prepare("UPDATE products SET variants = :variants WHERE id = :id");
                        $variantStmt->execute([
                            ':variants' => json_encode($variants),
                            ':id' => $item['productId']
                        ]);
                    }
                }
            }
        }
    }
    
    // Match Node.js: clear cart after order creation
    clearCart();
    
    $conn->commit();
    
    $_SESSION['order_success'] = true;
    $_SESSION['order_id'] = $orderId;
    
    // Redirect to order success page - use a route that index.php can handle
    // Use absolute URL to ensure proper redirect
    $successUrl = BASE_PATH . '/order-success.php?order=' . urlencode($orderId);
    header('Location: ' . $successUrl);
    exit;
} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Order creation error: " . $e->getMessage());
    $_SESSION['error'] = 'Failed to create order: ' . $e->getMessage();
    header('Location: ' . BASE_PATH . '/cart/checkout.php?step=3');
    exit;
}
?>
