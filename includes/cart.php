<?php
// Cart Management Functions - Supporting both session-based (guests) and database-based (authenticated users) carts

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get session cart for guests
 */
function getSessionCart() {
    return $_SESSION['guest_cart'] ?? ['items' => [], 'totalPrice' => 0];
}

/**
 * Save session cart for guests
 */
function saveSessionCart($cart) {
    $_SESSION['guest_cart'] = $cart;
}

/**
 * Get cart - supports both session (guests) and database (authenticated users)
 */
function getCart($userId = null) {
    if (!$userId) {
        $user = getCurrentUser();
        $userId = $user['id'] ?? null;
    }
    
    // If not authenticated, use session cart
    if (!$userId) {
        return getSessionCart();
    }
    
    // Authenticated users: use database cart
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM carts WHERE userId = :userId");
    $stmt->execute([':userId' => $userId]);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cart) {
        return ['items' => [], 'totalPrice' => 0];
    }
    
    // Parse items JSON
    $cart['items'] = !empty($cart['items']) ? json_decode($cart['items'], true) : [];
    
    return $cart;
}

/**
 * Add item to cart - supports both session and database
 */
function addToCart($productId, $quantity = 1, $type = 'product', $productData = []) {
    $user = getCurrentUser();
    $isAuthenticated = $user && isset($user['id']);
    
    // For guests, use session cart
    if (!$isAuthenticated) {
        $cart = getSessionCart();
        $items = $cart['items'] ?? [];
        
        // Check if item exists (match by productId and type)
        $existingIndex = -1;
        foreach ($items as $index => $item) {
            if (($item['productId'] ?? '') === $productId && (($item['type'] ?? 'product') === $type)) {
                $existingIndex = $index;
                break;
            }
        }
        
        // Add or update item
        if ($existingIndex !== -1) {
            $items[$existingIndex]['quantity'] += $quantity;
        } else {
            // Ensure productData is provided
            if (empty($productData)) {
                $conn = getDBConnection();
                if ($type === 'product') {
                    $productStmt = $conn->prepare("SELECT name, images, retailPrice, vendorId FROM products WHERE id = :id");
                    $productStmt->execute([':id' => $productId]);
                    $product = $productStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($product) {
                        $productData = [
                            'productName' => $product['name'],
                            'productImage' => !empty($product['images']) ? json_decode($product['images'], true)[0] ?? '' : '',
                            'price' => (float)$product['retailPrice'],
                            'vendorId' => $product['vendorId'] ?? null
                        ];
                    }
                } elseif ($type === 'package') {
                    $packageStmt = $conn->prepare("SELECT name, image, retailPrice FROM packages WHERE id = :id");
                    $packageStmt->execute([':id' => $productId]);
                    $package = $packageStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($package) {
                        $productData = [
                            'productName' => $package['name'],
                            'productImage' => $package['image'] ?? '',
                            'price' => (float)$package['retailPrice']
                        ];
                    }
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
        
        // Calculate total price
        $totalPrice = 0;
        foreach ($items as $item) {
            $totalPrice += (float)$item['price'] * (int)$item['quantity'];
        }
        
        // Save to session
        saveSessionCart(['items' => $items, 'totalPrice' => $totalPrice]);
        
        return ['success' => true, 'message' => 'Item added to cart'];
    }
    
    // For authenticated users, use database cart
    $userId = $user['id'];
    $conn = getDBConnection();
    
    // Get or create cart
    $stmt = $conn->prepare("SELECT * FROM carts WHERE userId = :userId");
    $stmt->execute([':userId' => $userId]);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $items = [];
    if ($cart) {
        $items = !empty($cart['items']) ? json_decode($cart['items'], true) : [];
    }
    
    // Check if item exists (match by productId and type)
    $existingIndex = -1;
    foreach ($items as $index => $item) {
        if (($item['productId'] ?? '') === $productId && (($item['type'] ?? 'product') === $type)) {
            $existingIndex = $index;
            break;
        }
    }
    
    // Add or update item
    if ($existingIndex !== -1) {
        $items[$existingIndex]['quantity'] += $quantity;
    } else {
        // Get product data if not provided
        if (empty($productData)) {
            if ($type === 'product') {
                $productStmt = $conn->prepare("SELECT name, images, retailPrice, vendorId FROM products WHERE id = :id");
                $productStmt->execute([':id' => $productId]);
                $product = $productStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($product) {
                    $productData = [
                        'productName' => $product['name'],
                        'productImage' => !empty($product['images']) ? json_decode($product['images'], true)[0] ?? '' : '',
                        'price' => (float)$product['retailPrice'],
                        'vendorId' => $product['vendorId'] ?? null
                    ];
                }
            } elseif ($type === 'package') {
                $packageStmt = $conn->prepare("SELECT name, image, retailPrice FROM packages WHERE id = :id");
                $packageStmt->execute([':id' => $productId]);
                $package = $packageStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($package) {
                    $productData = [
                        'productName' => $package['name'],
                        'productImage' => $package['image'] ?? '',
                        'price' => (float)$package['retailPrice']
                    ];
                }
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
    
    // Calculate total price
    $totalPrice = 0;
    foreach ($items as $item) {
        $totalPrice += (float)$item['price'] * (int)$item['quantity'];
    }
    
    // Update or create cart
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

/**
 * Remove item from cart - supports both session and database
 */
function removeFromCart($productId, $type = 'product') {
    $user = getCurrentUser();
    $isAuthenticated = $user && isset($user['id']);
    
    // For guests, use session cart
    if (!$isAuthenticated) {
        $cart = getSessionCart();
        $items = $cart['items'] ?? [];
        
        // Filter out item (match by productId and type)
        $items = array_filter($items, function($item) use ($productId, $type) {
            return !(($item['productId'] ?? '') === $productId && (($item['type'] ?? 'product') === $type));
        });
        $items = array_values($items);
        
        // Recalculate total
        $totalPrice = 0;
        foreach ($items as $item) {
            $totalPrice += (float)$item['price'] * (int)$item['quantity'];
        }
        
        // Save to session
        saveSessionCart(['items' => $items, 'totalPrice' => $totalPrice]);
        
        return ['success' => true, 'message' => 'Item removed from cart'];
    }
    
    // For authenticated users, use database cart
    $userId = $user['id'];
    $conn = getDBConnection();
    
    // Get cart
    $stmt = $conn->prepare("SELECT * FROM carts WHERE userId = :userId");
    $stmt->execute([':userId' => $userId]);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cart) {
        return ['success' => false, 'message' => 'Cart not found'];
    }
    
    // Filter out item (match by productId and type)
    $items = !empty($cart['items']) ? json_decode($cart['items'], true) : [];
    $items = array_filter($items, function($item) use ($productId, $type) {
        return !(($item['productId'] ?? '') === $productId && (($item['type'] ?? 'product') === $type));
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

/**
 * Update cart quantity - supports both session and database
 */
function updateCartQuantity($productId, $quantity, $type = 'product') {
    $user = getCurrentUser();
    $isAuthenticated = $user && isset($user['id']);
    
    // For guests, use session cart
    if (!$isAuthenticated) {
        $cart = getSessionCart();
        $items = $cart['items'] ?? [];
        
        $itemIndex = -1;
        foreach ($items as $index => $item) {
            if (($item['productId'] ?? '') === $productId && (($item['type'] ?? 'product') === $type)) {
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
        
        // Update quantity
        $items[$itemIndex]['quantity'] = $quantity;
        
        // Recalculate total
        $totalPrice = 0;
        foreach ($items as $item) {
            $totalPrice += (float)$item['price'] * (int)$item['quantity'];
        }
        
        // Save to session
        saveSessionCart(['items' => $items, 'totalPrice' => $totalPrice]);
        
        return ['success' => true, 'message' => 'Cart item updated'];
    }
    
    // For authenticated users, use database cart
    $userId = $user['id'];
    $conn = getDBConnection();
    
    // Get cart
    $stmt = $conn->prepare("SELECT * FROM carts WHERE userId = :userId");
    $stmt->execute([':userId' => $userId]);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cart) {
        return ['success' => false, 'message' => 'Cart not found'];
    }
    
    $items = !empty($cart['items']) ? json_decode($cart['items'], true) : [];
    $itemIndex = -1;
    
    foreach ($items as $index => $item) {
        if (($item['productId'] ?? '') === $productId && (($item['type'] ?? 'product') === $type)) {
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
    
    // Update quantity
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

/**
 * Clear cart - supports both session and database
 */
function clearCart() {
    $user = getCurrentUser();
    $isAuthenticated = $user && isset($user['id']);
    
    // For guests, clear session cart
    if (!$isAuthenticated) {
        saveSessionCart(['items' => [], 'totalPrice' => 0]);
        return ['success' => true, 'message' => 'Cart cleared'];
    }
    
    // For authenticated users, clear database cart
    $userId = $user['id'];
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("UPDATE carts SET items = '[]', totalPrice = 0, updated_at = NOW() WHERE userId = :userId");
    $stmt->execute([':userId' => $userId]);
    
    return ['success' => true, 'message' => 'Cart cleared'];
}

/**
 * Get cart items with product details - supports both session and database
 */
function getCartItems() {
    $cart = getCart();
    $items = $cart['items'] ?? [];
    
    if (empty($items)) {
        return [];
    }
    
    $result = [];
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
            // Fetch product details including vendorId
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

/**
 * Get cart total - supports both session and database
 */
function getCartTotal() {
    $cart = getCart();
    return (float)($cart['totalPrice'] ?? 0);
}

/**
 * Get cart count - supports both session and database
 */
function getCartCount() {
    $cart = getCart();
    $items = $cart['items'] ?? [];
    $count = 0;
    foreach ($items as $item) {
        $count += (int)($item['quantity'] ?? 1);
    }
    return $count;
}

/**
 * Merge session cart into database cart when user logs in
 */
function mergeSessionCartToDatabase($userId) {
    $sessionCart = getSessionCart();
    $sessionItems = $sessionCart['items'] ?? [];
    
    if (empty($sessionItems)) {
        return; // Nothing to merge
    }
    
    $conn = getDBConnection();
    
    // Get or create database cart
    $stmt = $conn->prepare("SELECT * FROM carts WHERE userId = :userId");
    $stmt->execute([':userId' => $userId]);
    $dbCart = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $dbItems = [];
    if ($dbCart) {
        $dbItems = !empty($dbCart['items']) ? json_decode($dbCart['items'], true) : [];
    }
    
    // Merge session items into database items
    foreach ($sessionItems as $sessionItem) {
        $productId = $sessionItem['productId'] ?? '';
        $type = $sessionItem['type'] ?? 'product';
        
        // Check if item already exists in database cart
        $existingIndex = -1;
        foreach ($dbItems as $index => $dbItem) {
            if (($dbItem['productId'] ?? '') === $productId && (($dbItem['type'] ?? 'product') === $type)) {
                $existingIndex = $index;
                break;
            }
        }
        
        if ($existingIndex !== -1) {
            // Merge quantities
            $dbItems[$existingIndex]['quantity'] += (int)($sessionItem['quantity'] ?? 1);
        } else {
            // Add new item
            $dbItems[] = $sessionItem;
        }
    }
    
    // Recalculate total
    $totalPrice = 0;
    foreach ($dbItems as $item) {
        $totalPrice += (float)$item['price'] * (int)$item['quantity'];
    }
    
    // Update or create database cart
    if ($dbCart) {
        $stmt = $conn->prepare("UPDATE carts SET items = :items, totalPrice = :totalPrice, updated_at = NOW() WHERE userId = :userId");
        $stmt->execute([
            ':items' => json_encode($dbItems),
            ':totalPrice' => $totalPrice,
            ':userId' => $userId
        ]);
    } else {
        $stmt = $conn->prepare("INSERT INTO carts (id, userId, items, totalPrice, created_at, updated_at) VALUES (UUID(), :userId, :items, :totalPrice, NOW(), NOW())");
        $stmt->execute([
            ':userId' => $userId,
            ':items' => json_encode($dbItems),
            ':totalPrice' => $totalPrice
        ]);
    }
    
    // Clear session cart after merging
    saveSessionCart(['items' => [], 'totalPrice' => 0]);
}
?>
