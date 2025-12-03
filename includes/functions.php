<?php
// General Helper Functions - Matching Node.js Backend Flow

require_once __DIR__ . '/../config/database.php';

// Image path helper - automatically detects correct path
function imagePath($filename) {
    if (!defined('PUBLIC_PATH')) {
        // Detect base URL automatically
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $path = dirname($script);
        
        // If script is in green-grocers folder or URI contains green-grocers
        if (strpos($path, '/green-grocers') !== false || strpos($requestUri, '/green-grocers') !== false) {
            $basePath = '/green-grocers';
        } else {
            // Default for PHP built-in server
            $basePath = '';
        }
        
        define('BASE_PATH', $basePath);
        define('PUBLIC_PATH', $basePath . '/public');
    }
    
    // If no filename provided, fall back to a default asset to avoid requests to /public/
    $clean = trim((string)$filename);
    if ($clean === '') {
        $clean = 'GGLOGO.png';
    }
    
    return PUBLIC_PATH . '/' . ltrim($clean, '/');
}

function getCategories() {
    $conn = getDBConnection();
    $stmt = $conn->query("SELECT * FROM categories ORDER BY title");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Match Node.js: getFeaturedProducts with proper JSON handling
function getFeaturedProducts($limit = 6) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT p.*, c.title as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.categoryId = c.id 
        WHERE p.isFeatured = 1 AND p.status = 'active' 
        ORDER BY p.created_at DESC 
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    $products = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Match Node.js: decode JSON fields
        $row['images'] = !empty($row['images']) ? json_decode($row['images'], true) : [];
        $row['variants'] = !empty($row['variants']) ? json_decode($row['variants'], true) : [];
        $row['discount'] = !empty($row['discount']) ? json_decode($row['discount'], true) : ['type' => 'percentage', 'value' => 0];
        $row['tags'] = !empty($row['tags']) ? json_decode($row['tags'], true) : [];
        $row['appliedCoupons'] = !empty($row['appliedCoupons']) ? json_decode($row['appliedCoupons'], true) : [];
        
        if (!isset($row['discount']['value'])) {
            $row['discount']['value'] = 0;
        }
        $products[] = $row;
    }
    return $products;
}

// Match Node.js: getProductsByCategoryName
function getProductsByCategory($categoryName) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT p.*, c.title as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.categoryId = c.id 
        WHERE c.title = :title AND p.status = 'active' 
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([':title' => $categoryName]);
    $products = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Match Node.js: decode all JSON fields
        $row['images'] = !empty($row['images']) ? json_decode($row['images'], true) : [];
        $row['variants'] = !empty($row['variants']) ? json_decode($row['variants'], true) : [];
        $row['discount'] = !empty($row['discount']) ? json_decode($row['discount'], true) : ['type' => 'percentage', 'value' => 0];
        $row['tags'] = !empty($row['tags']) ? json_decode($row['tags'], true) : [];
        
        if (!isset($row['discount']['value'])) {
            $row['discount']['value'] = 0;
        }
        $products[] = $row;
    }
    return $products;
}

// Match Node.js: getFeaturedPackages (used on public site - show all active packages)
function getFeaturedPackages($limit = 6) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT * FROM packages 
        WHERE status = 'active' 
        ORDER BY rating DESC, totalOrders DESC, created_at DESC 
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    $packages = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Match Node.js: decode JSON fields
        $row['items'] = !empty($row['items']) ? json_decode($row['items'], true) : [];
        $row['discount'] = !empty($row['discount']) ? json_decode($row['discount'], true) : ['type' => 'percentage', 'value' => 0];
        $row['tags'] = !empty($row['tags']) ? json_decode($row['tags'], true) : [];
        
        if (!isset($row['discount']['value'])) {
            $row['discount']['value'] = 0;
        }
        $packages[] = $row;
    }
    return $packages;
}

// Match Node.js: getAllProducts
function getAllProducts() {
    $conn = getDBConnection();
    $stmt = $conn->query("
        SELECT p.*, c.title as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.categoryId = c.id 
        WHERE p.status = 'active' 
        ORDER BY p.created_at DESC
    ");
    $products = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Match Node.js: decode all JSON fields
        $row['images'] = !empty($row['images']) ? json_decode($row['images'], true) : [];
        $row['variants'] = !empty($row['variants']) ? json_decode($row['variants'], true) : [];
        $row['discount'] = !empty($row['discount']) ? json_decode($row['discount'], true) : ['type' => 'percentage', 'value' => 0];
        $row['tags'] = !empty($row['tags']) ? json_decode($row['tags'], true) : [];
        $row['appliedCoupons'] = !empty($row['appliedCoupons']) ? json_decode($row['appliedCoupons'], true) : [];
        
        if (!isset($row['discount']['value'])) {
            $row['discount']['value'] = 0;
        }
        $products[] = $row;
    }
    return $products;
}

// Match Node.js: getAllPackages
function getAllPackages() {
    $conn = getDBConnection();
    $stmt = $conn->query("SELECT * FROM packages ORDER BY created_at DESC");
    $packages = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Match Node.js: decode JSON fields
        $row['items'] = !empty($row['items']) ? json_decode($row['items'], true) : [];
        $row['discount'] = !empty($row['discount']) ? json_decode($row['discount'], true) : ['type' => 'percentage', 'value' => 0];
        $row['tags'] = !empty($row['tags']) ? json_decode($row['tags'], true) : [];
        
        if (!isset($row['discount']['value'])) {
            $row['discount']['value'] = 0;
        }
        $packages[] = $row;
    }
    return $packages;
}

// Match Node.js: getAllOrders
function getAllOrders() {
    $conn = getDBConnection();
    $stmt = $conn->query("
        SELECT o.*, u.firstName, u.lastName, u.email 
        FROM orders o 
        LEFT JOIN users u ON o.userId = u.id 
        ORDER BY o.created_at DESC
    ");
    $orders = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Match Node.js: decode JSON fields
        $row['items'] = !empty($row['items']) ? json_decode($row['items'], true) : [];
        $row['shippingAddress'] = !empty($row['shippingAddress']) ? json_decode($row['shippingAddress'], true) : null;
        $row['statusHistory'] = !empty($row['statusHistory']) ? json_decode($row['statusHistory'], true) : [];
        $row['returnRequest'] = !empty($row['returnRequest']) ? json_decode($row['returnRequest'], true) : null;
        $orders[] = $row;
    }
    return $orders;
}

// Match Node.js: updateOrderStatus with statusHistory
function updateOrderStatus($orderId, $status) {
    $conn = getDBConnection();
    
    // Match Node.js: get existing order
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = :id");
    $stmt->execute([':id' => $orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        return false;
    }
    
    // Match Node.js: status to deliveryStatus mapping
    $statusToDeliveryStatus = [
        'assigned' => 'Out for Delivery',
        'dispatched' => 'Out for Delivery',
        'inprogress' => 'Pending',
        'delivered' => 'Delivered',
        'canceled' => 'Failed'
    ];
    
    // Match Node.js: update statusHistory
    $statusHistory = !empty($order['statusHistory']) ? json_decode($order['statusHistory'], true) : [];
    $statusHistory[] = [
        'status' => $status,
        'updatedAt' => date('c'),  // This is JSON key, not DB column - keep as is
        'updatedBy' => $_SESSION['user']['id'] ?? 'system'
    ];
    
    $updates = [
        'status' => $status,
        'deliveryStatus' => $statusToDeliveryStatus[$status] ?? 'Pending',
        'statusHistory' => json_encode($statusHistory),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Match Node.js: generate authentication code for assigned status
    if ($status === 'assigned') {
        $updates['authenticationCode'] = (string)rand(1000, 9999);
        $updates['deliveryTimeline'] = date('l d/m/y'); // e.g., "Tuesday 24/04/24"
    }
    
    $setClause = [];
    $params = [];
    foreach ($updates as $key => $value) {
        $setClause[] = "$key = ?";
        $params[] = $value;
    }
    $params[] = $orderId;
    
    $sql = "UPDATE orders SET " . implode(', ', $setClause) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    return $stmt->execute($params);
}

// Match Node.js: toggleProductFeatured
function toggleProductFeatured($productId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE products SET isFeatured = NOT isFeatured WHERE id = :id");
    return $stmt->execute([':id' => $productId]);
}

// Match Node.js: getSingleProduct with reviews
function getSingleProduct($productId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        return null;
    }
    
    // Match Node.js: decode all JSON fields
    $product['images'] = !empty($product['images']) ? json_decode($product['images'], true) : [];
    $product['variants'] = !empty($product['variants']) ? json_decode($product['variants'], true) : [];
    $product['discount'] = !empty($product['discount']) ? json_decode($product['discount'], true) : ['type' => 'percentage', 'value' => 0];
    $product['tags'] = !empty($product['tags']) ? json_decode($product['tags'], true) : [];
    $product['appliedCoupons'] = !empty($product['appliedCoupons']) ? json_decode($product['appliedCoupons'], true) : [];
    
    // Match Node.js: get reviews (if reviewRating table exists)
    $reviewStmt = $conn->prepare("SELECT * FROM reviewRating WHERE productId = :productId ORDER BY created_at DESC");
    $reviewStmt->execute([':productId' => $productId]);
    $product['reviews'] = $reviewStmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $product;
}

// Match Node.js: getPackagesByDay
function getPackagesByDay($day) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT * FROM packages 
        WHERE packageDay = :day AND status = 'active'
        ORDER BY rating DESC
    ");
    $stmt->execute([':day' => $day]);
    $packages = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['items'] = !empty($row['items']) ? json_decode($row['items'], true) : [];
        $row['discount'] = !empty($row['discount']) ? json_decode($row['discount'], true) : ['type' => 'percentage', 'value' => 0];
        $packages[] = $row;
    }
    return $packages;
}
?>
