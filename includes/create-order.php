<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/cart.php';

requireAuth();

$currentUser = getCurrentUser();
$cartItems = getCartItems();

if (empty($cartItems)) {
    $_SESSION['error'] = 'Your cart is empty';
    header('Location: ' . BASE_PATH . '/cart/checkout.php');
    exit;
}

// Get form data
$paymentMethod = $_POST['payment_method'] ?? 'card';
$deliveryAddress = $_POST['delivery_address'] ?? '';
$notes = $_POST['notes'] ?? '';
$fullName = $_POST['full_name'] ?? ($currentUser['first_name'] . ' ' . $currentUser['last_name']);
$phone = $_POST['phone'] ?? '';

if (empty($deliveryAddress)) {
    $_SESSION['error'] = 'Please provide a delivery address';
    header('Location: ' . BASE_PATH . '/cart/checkout.php?step=2');
    exit;
}

$conn = getDBConnection();

// Calculate total
$total = getCartTotal();
$vat = round($total * 0.05);
$finalTotal = $total + $vat;

// Generate order number
$orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

try {
    // Start transaction
    $conn->beginTransaction();

    // Create order
    $stmt = $conn->prepare("INSERT INTO orders (user_id, order_number, total_amount, status, payment_method, delivery_address, notes) 
                            VALUES (:user_id, :order_number, :total_amount, 'Placed', :payment_method, :delivery_address, :notes)");
    $stmt->execute([
        ':user_id'         => $currentUser['id'],
        ':order_number'    => $orderNumber,
        ':total_amount'    => $finalTotal,
        ':payment_method'  => $paymentMethod,
        ':delivery_address'=> $deliveryAddress,
        ':notes'           => $notes,
    ]);

    $orderId = $conn->lastInsertId();

    // Create order items
    $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, package_id, name, quantity, price) 
                                VALUES (:order_id, :product_id, :package_id, :name, :quantity, :price)");

    foreach ($cartItems as $item) {
        $price = $item['retailPrice'] ?? $item['retail_price'] ?? 0;
        $discountValue = $item['discount']['value'] ?? ($item['discount_value'] ?? 0);
        $finalPrice = $price * (1 - ($discountValue / 100));
        $quantity = $item['cart_quantity'] ?? 1;
        $itemName = $item['name'];

        $isPackage = isset($item['packageDay']) || isset($item['package_day']);
        $productId = $isPackage ? null : $item['id'];
        $packageId = $isPackage ? $item['id'] : null;

        $itemStmt->execute([
            ':order_id'   => $orderId,
            ':product_id' => $productId,
            ':package_id' => $packageId,
            ':name'       => $itemName,
            ':quantity'   => $quantity,
            ':price'      => $finalPrice,
        ]);
    }

    // Commit
    $conn->commit();

    // Clear cart
    clearCart();

    $_SESSION['order_success'] = true;
    $_SESSION['order_number'] = $orderNumber;

    header('Location: ' . BASE_PATH . '/website/pages/order-success.php?order=' . $orderNumber);
    exit;
} catch (PDOException $e) {
    $conn->rollBack();
    $_SESSION['error'] = 'Failed to create order: ' . $e->getMessage();
    header('Location: ' . BASE_PATH . '/cart/checkout.php?step=3');
    exit;
}
?>

