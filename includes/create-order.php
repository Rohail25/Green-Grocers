<?php
/**
 * Order Creation - Matching Node.js Backend Flow
 */
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/config.php'; // for BASE_PATH
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/cart.php';

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

if (empty($shippingAddress)) {
    $_SESSION['error'] = 'Please provide a delivery address';
    header('Location: ' . BASE_PATH . '/cart/checkout.php?step=2');
    exit;
}

$conn = getDBConnection();

// Calculate total from cart record
$items = $cart['items'];
$totalAmount = (float)$cart['totalPrice'];

// Apply discount if any
$finalTotal = max(0, $totalAmount - $discount);

// Create order with items as JSON array
try {
    $conn->beginTransaction();
    
    // Extract vendorId from first item if available (fallback to empty string to satisfy NOT NULL)
    $vendorId = !empty($items) && isset($items[0]['vendorId']) && $items[0]['vendorId']
        ? $items[0]['vendorId']
        : 'UNKNOWN';

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
            notes
        ) VALUES (
            UUID(),
            :userId,
            :vendorId,
            :items,
            :shippingAddress,
            :customerName,
            :totalAmount,
            :discountAmount,
            :couponCode,
            :paymentMethod,
            :notes
        )
    ");
    
    $stmt->execute([
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
        ':notes' => $notes
    ]);
    
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
    $_SESSION['order_number'] = $orderNumber;
    
    header('Location: ' . BASE_PATH . '/website/pages/order-success.php?order=' . $orderNumber);
    exit;
} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Order creation error: " . $e->getMessage());
    $_SESSION['error'] = 'Failed to create order: ' . $e->getMessage();
    header('Location: ' . BASE_PATH . '/cart/checkout.php?step=3');
    exit;
}
?>
