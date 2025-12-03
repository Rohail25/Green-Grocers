<?php
// Cart Management Functions - Matching Node.js Backend Flow (Database-based cart)

function getCart($userId = null) {
    // Match Node.js: use database cart, not session
    if (!$userId) {
        $user = getCurrentUser();
        $userId = $user['id'] ?? null;
    }
    
    if (!$userId) {
        return ['items' => [], 'totalPrice' => 0];
    }
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM carts WHERE userId = :userId");
    $stmt->execute([':userId' => $userId]);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cart) {
        return ['items' => [], 'totalPrice' => 0];
    }
    
    // Match Node.js: parse items JSON
    $cart['items'] = !empty($cart['items']) ? json_decode($cart['items'], true) : [];
    
    return $cart;
}

function addToCart($productId, $quantity = 1, $type = 'product', $productData = []) {
    // Match Node.js: requires userId
    $user = getCurrentUser();
    if (!$user || !isset($user['id'])) {
        return ['success' => false, 'message' => 'User must be logged in'];
    }
    
    $userId = $user['id'];
    $conn = getDBConnection();
    
    // Match Node.js: get or create cart
    $stmt = $conn->prepare("SELECT * FROM carts WHERE userId = :userId");
    $stmt->execute([':userId' => $userId]);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $items = [];
    if ($cart) {
        $items = !empty($cart['items']) ? json_decode($cart['items'], true) : [];
    }
    
    // Match Node.js: check if item exists (match by productId and type)
    $existingIndex = -1;
    foreach ($items as $index => $item) {
        if (($item['productId'] ?? '') === $productId && (($item['type'] ?? 'product') === $type)) {
            $existingIndex = $index;
            break;
        }
    }
    
    // Match Node.js: add or update item
    if ($existingIndex !== -1) {
        $items[$existingIndex]['quantity'] += $quantity;
    } else {
        // Get product data if not provided
        if (empty($productData)) {
            $productStmt = $conn->prepare("SELECT name, images, retailPrice FROM products WHERE id = :id");
            $productStmt->execute([':id' => $productId]);
            $product = $productStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                $productData = [
                    'productName' => $product['name'],
                    'productImage' => !empty($product['images']) ? json_decode($product['images'], true)[0] ?? '' : '',
                    'price' => (float)$product['retailPrice']
                ];
            }
        }
        
        $items[] = [
            'productId' => $productId,
            'type' => $type,
            'productName' => $productData['productName'] ?? '',
            'productImage' => $productData['productImage'] ?? '',
            'price' => (float)($productData['price'] ?? 0),
            'quantity' => $quantity,
            'vendorId' => $productData['vendorId'] ?? null,
            'variantIndex' => $productData['variantIndex'] ?? 0
        ];
    }
    
    // Match Node.js: calculate total price
    $totalPrice = 0;
    foreach ($items as $item) {
        $totalPrice += (float)$item['price'] * (int)$item['quantity'];
    }
    
    // Match Node.js: update or create cart
    if ($cart) {
        $stmt = $conn->prepare("UPDATE carts SET items = :items, totalPrice = :totalPrice, updated_at = NOW() WHERE userId = :userId");
        $stmt->execute([
            ':items' => json_encode($items),
            ':totalPrice' => $totalPrice,
            ':userId' => $userId
        ]);
    } else {
        $stmt = $conn->prepare("INSERT INTO carts (id, userId, items, totalPrice, created_at, updated_at) VALUES (UUID(), :userId, :items, :totalPrice, NOW(), NOW())");
        $stmt->execute([
            ':userId' => $userId,
            ':items' => json_encode($items),
            ':totalPrice' => $totalPrice
        ]);
    }
    
    return ['success' => true, 'message' => 'Item added to cart'];
}

function removeFromCart($productId, $type = 'product') {
    $user = getCurrentUser();
    if (!$user || !isset($user['id'])) {
        return ['success' => false, 'message' => 'User must be logged in'];
    }
    
    $userId = $user['id'];
    $conn = getDBConnection();
    
    // Match Node.js: get cart
    $stmt = $conn->prepare("SELECT * FROM carts WHERE userId = :userId");
    $stmt->execute([':userId' => $userId]);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cart) {
        return ['success' => false, 'message' => 'Cart not found'];
    }
    
    // Match Node.js: filter out item
    $items = !empty($cart['items']) ? json_decode($cart['items'], true) : [];
    $items = array_filter($items, function($item) use ($productId) {
        return $item['productId'] !== $productId;
    });
    $items = array_values($items);
    
    // Recalculate total
    $totalPrice = 0;
    foreach ($items as $item) {
        $totalPrice += (float)$item['price'] * (int)$item['quantity'];
    }
    
    $stmt = $conn->prepare("UPDATE carts SET items = :items, totalPrice = :totalPrice, updated_at = NOW() WHERE userId = :userId");
    $stmt->execute([
        ':items' => json_encode($items),
        ':totalPrice' => $totalPrice,
        ':userId' => $userId
    ]);
    
    return ['success' => true, 'message' => 'Item removed from cart'];
}

function updateCartQuantity($productId, $quantity, $type = 'product') {
    $user = getCurrentUser();
    if (!$user || !isset($user['id'])) {
        return ['success' => false, 'message' => 'User must be logged in'];
    }
    
    $userId = $user['id'];
    $conn = getDBConnection();
    
    // Match Node.js: get cart
    $stmt = $conn->prepare("SELECT * FROM carts WHERE userId = :userId");
    $stmt->execute([':userId' => $userId]);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cart) {
        return ['success' => false, 'message' => 'Cart not found'];
    }
    
    $items = !empty($cart['items']) ? json_decode($cart['items'], true) : [];
    $itemIndex = -1;
    
    foreach ($items as $index => $item) {
        if ($item['productId'] === $productId) {
            $itemIndex = $index;
            break;
        }
    }
    
    if ($itemIndex === -1) {
        return ['success' => false, 'message' => 'Item not found in cart'];
    }
    
    if ($quantity <= 0) {
        return removeFromCart($productId, $type);
    }
    
    // Match Node.js: update quantity
    $items[$itemIndex]['quantity'] = $quantity;
    
    // Recalculate total
    $totalPrice = 0;
    foreach ($items as $item) {
        $totalPrice += (float)$item['price'] * (int)$item['quantity'];
    }
    
    $stmt = $conn->prepare("UPDATE carts SET items = :items, totalPrice = :totalPrice, updated_at = NOW() WHERE userId = :userId");
    $stmt->execute([
        ':items' => json_encode($items),
        ':totalPrice' => $totalPrice,
        ':userId' => $userId
    ]);
    
    return ['success' => true, 'message' => 'Cart item updated'];
}

function clearCart() {
    $user = getCurrentUser();
    if (!$user || !isset($user['id'])) {
        return ['success' => false, 'message' => 'User must be logged in'];
    }
    
    $userId = $user['id'];
    $conn = getDBConnection();
    
    // Match Node.js: clear cart items
    $stmt = $conn->prepare("UPDATE carts SET items = '[]', totalPrice = 0, updated_at = NOW() WHERE userId = :userId");
    $stmt->execute([':userId' => $userId]);
    
    return ['success' => true, 'message' => 'Cart cleared'];
}

function getCartItems() {
    $user = getCurrentUser();
    if (!$user || !isset($user['id'])) {
        return [];
    }
    
    $cart = getCart($user['id']);
    $items = $cart['items'] ?? [];
    $result = [];
    
    // Match Node.js: fetch product details for each cart item
    $conn = getDBConnection();
    foreach ($items as $item) {
        $productId = $item['productId'] ?? '';
        if (empty($productId)) continue;

        $type = $item['type'] ?? 'product';

        if ($type === 'package') {
            // Fetch package details
            $stmt = $conn->prepare("SELECT * FROM packages WHERE id = :id");
            $stmt->execute([':id' => $productId]);
            $package = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($package) {
                $package['images'] = !empty($package['image']) ? [$package['image']] : [];
                $package['discount'] = !empty($package['discount']) ? json_decode($package['discount'], true) : ['type' => 'percentage', 'value' => 0];
                $package['cart_quantity'] = $item['quantity'] ?? 1;
                $package['category_name'] = 'Package';
                $package['cart_type'] = 'package';
                $result[] = $package;
            }
        } else {
            // Fetch product details
            $stmt = $conn->prepare("
                SELECT p.*, c.title as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.categoryId = c.id 
                WHERE p.id = :id
            ");
            $stmt->execute([':id' => $productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                // Decode JSON fields
                $product['images'] = !empty($product['images']) ? json_decode($product['images'], true) : [];
                $product['discount'] = !empty($product['discount']) ? json_decode($product['discount'], true) : ['type' => 'percentage', 'value' => 0];
                $product['variants'] = !empty($product['variants']) ? json_decode($product['variants'], true) : [];
                $product['cart_quantity'] = $item['quantity'] ?? 1;
                $product['cart_type'] = 'product';
                $result[] = $product;
            }
        }
    }
    
    return $result;
}

function getCartTotal() {
    $user = getCurrentUser();
    if (!$user || !isset($user['id'])) {
        return 0;
    }
    
    $cart = getCart($user['id']);
    return (float)($cart['totalPrice'] ?? 0);
}

function getCartCount() {
    $user = getCurrentUser();
    if (!$user || !isset($user['id'])) {
        return 0;
    }
    
    $cart = getCart($user['id']);
    $items = $cart['items'] ?? [];
    $count = 0;
    foreach ($items as $item) {
        $count += (int)($item['quantity'] ?? 1);
    }
    return $count;
}
?>
