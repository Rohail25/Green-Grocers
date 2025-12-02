<?php
// Cart Management Functions

function getCart() {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return $_SESSION['cart'];
}

function addToCart($productId, $quantity = 1, $type = 'product') {
    $cart = getCart();
    $key = $type . '_' . $productId;
    
    if (isset($cart[$key])) {
        $cart[$key]['quantity'] += $quantity;
    } else {
        $cart[$key] = [
            'id' => $productId,
            'type' => $type,
            'quantity' => $quantity
        ];
    }
    
    $_SESSION['cart'] = $cart;
    return true;
}

function removeFromCart($productId, $type = 'product') {
    $cart = getCart();
    $key = $type . '_' . $productId;
    
    if (isset($cart[$key])) {
        unset($cart[$key]);
        $_SESSION['cart'] = $cart;
        return true;
    }
    return false;
}

function updateCartQuantity($productId, $quantity, $type = 'product') {
    $cart = getCart();
    $key = $type . '_' . $productId;
    
    if (isset($cart[$key])) {
        if ($quantity <= 0) {
            return removeFromCart($productId, $type);
        }
        $cart[$key]['quantity'] = $quantity;
        $_SESSION['cart'] = $cart;
        return true;
    }
    return false;
}

function clearCart() {
    $_SESSION['cart'] = [];
    return true;
}

function getCartItems() {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/functions.php';
    
    $cart = getCart();
    $items = [];
    
    foreach ($cart as $item) {
        if ($item['type'] === 'product') {
            $conn = getDBConnection();
            $stmt = $conn->prepare("SELECT p.*, c.title as category_name 
                                    FROM products p 
                                    LEFT JOIN categories c ON p.categoryId = c.id 
                                    WHERE p.id = :id");
            $stmt->execute([':id' => $item['id']]);
            if ($product = $stmt->fetch()) {
                $product['images'] = !empty($product['images']) ? json_decode($product['images'], true) : [];
                $product['discount'] = !empty($product['discount']) ? json_decode($product['discount'], true) : ['type' => 'percentage', 'value' => 0];
                if (!isset($product['discount']['value'])) {
                    $product['discount']['value'] = 0;
                }
                $product['cart_quantity'] = $item['quantity'];
                $items[] = $product;
            }
        } elseif ($item['type'] === 'package') {
            $conn = getDBConnection();
            $stmt = $conn->prepare("SELECT * FROM packages WHERE id = :id");
            $stmt->execute([':id' => $item['id']]);
            if ($package = $stmt->fetch()) {
                $package['items'] = !empty($package['items']) ? json_decode($package['items'], true) : [];
                $package['discount'] = !empty($package['discount']) ? json_decode($package['discount'], true) : ['type' => 'percentage', 'value' => 0];
                if (!isset($package['discount']['value'])) {
                    $package['discount']['value'] = 0;
                }
                $package['cart_quantity'] = $item['quantity'];
                $items[] = $package;
            }
        }
    }
    
    return $items;
}

function getCartTotal() {
    $items = getCartItems();
    $total = 0;
    
    foreach ($items as $item) {
        $price = $item['retailPrice'] ?? $item['retail_price'] ?? 0;
        $discountValue = $item['discount']['value'] ?? ($item['discount_value'] ?? 0);
        $finalPrice = $price * (1 - ($discountValue / 100));
        $total += $finalPrice * ($item['cart_quantity'] ?? 1);
    }
    
    return $total;
}

function getCartCount() {
    $cart = getCart();
    $count = 0;
    foreach ($cart as $item) {
        $count += $item['quantity'] ?? 1;
    }
    return $count;
}

?>

