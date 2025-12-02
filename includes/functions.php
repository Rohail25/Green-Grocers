<?php
// General Helper Functions

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
    
    return PUBLIC_PATH . '/' . ltrim($filename, '/');
}

function getCategories() {
    $conn = getDBConnection();
    $stmt = $conn->query("SELECT * FROM categories ORDER BY title");
    return $stmt->fetchAll();
}

function getFeaturedProducts($limit = 6) {
    $conn = getDBConnection();
    // Adjusted for new schema: uses categoryId, isFeatured, and status fields
    $stmt = $conn->prepare("SELECT p.*, c.title as category_name 
                            FROM products p 
                            LEFT JOIN categories c ON p.categoryId = c.id 
                            WHERE p.isFeatured = 1 AND p.status = 'active' 
                            ORDER BY p.created_at DESC 
                            LIMIT :limit");
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    $products = [];
    while ($row = $stmt->fetch()) {
        // images and discount are JSON columns in the new schema
        $row['images'] = !empty($row['images']) ? json_decode($row['images'], true) : [];
        $row['discount'] = !empty($row['discount']) ? json_decode($row['discount'], true) : ['type' => 'percentage', 'value' => 0];
        if (!isset($row['discount']['value'])) {
            $row['discount']['value'] = 0;
        }
        $products[] = $row;
    }
    return $products;
}

function getProductsByCategory($categoryName) {
    $conn = getDBConnection();
    // Uses categoryId per new schema
    $stmt = $conn->prepare("SELECT p.*, c.title as category_name 
                            FROM products p 
                            LEFT JOIN categories c ON p.categoryId = c.id 
                            WHERE c.title = :title AND p.status = 'active' 
                            ORDER BY p.created_at DESC");
    $stmt->execute([':title' => $categoryName]);
    $products = [];
    while ($row = $stmt->fetch()) {
        $row['images'] = !empty($row['images']) ? json_decode($row['images'], true) : [];
        $row['discount'] = !empty($row['discount']) ? json_decode($row['discount'], true) : ['type' => 'percentage', 'value' => 0];
        if (!isset($row['discount']['value'])) {
            $row['discount']['value'] = 0;
        }
        $products[] = $row;
    }
    return $products;
}

function getFeaturedPackages($limit = 6) {
    $conn = getDBConnection();
    // Adjusted for new schema: isFeatured (camelCase), items & discount as JSON
    $stmt = $conn->prepare("SELECT * FROM packages WHERE isFeatured = 1 AND status = 'active' ORDER BY created_at DESC LIMIT :limit");
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    $packages = [];
    while ($row = $stmt->fetch()) {
        $row['items'] = !empty($row['items']) ? json_decode($row['items'], true) : [];
        $row['discount'] = !empty($row['discount']) ? json_decode($row['discount'], true) : ['type' => 'percentage', 'value' => 0];
        if (!isset($row['discount']['value'])) {
            $row['discount']['value'] = 0;
        }
        $packages[] = $row;
    }
    return $packages;
}

function getAllProducts() {
    $conn = getDBConnection();
    // Uses categoryId and status fields from new schema
    $stmt = $conn->query("SELECT p.*, c.title as category_name 
                          FROM products p 
                          LEFT JOIN categories c ON p.categoryId = c.id 
                          WHERE p.status = 'active' 
                          ORDER BY p.created_at DESC");
    $products = [];
    while ($row = $stmt->fetch()) {
        $row['images'] = !empty($row['images']) ? json_decode($row['images'], true) : [];
        $row['discount'] = !empty($row['discount']) ? json_decode($row['discount'], true) : ['type' => 'percentage', 'value' => 0];
        if (!isset($row['discount']['value'])) {
            $row['discount']['value'] = 0;
        }
        $products[] = $row;
    }
    return $products;
}

function getAllPackages() {
    $conn = getDBConnection();
    $stmt = $conn->query("SELECT * FROM packages ORDER BY created_at DESC");
    $packages = [];
    while ($row = $stmt->fetch()) {
        $row['items'] = !empty($row['items']) ? json_decode($row['items'], true) : [];
        $row['discount'] = !empty($row['discount']) ? json_decode($row['discount'], true) : ['type' => 'percentage', 'value' => 0];
        if (!isset($row['discount']['value'])) {
            $row['discount']['value'] = 0;
        }
        $packages[] = $row;
    }
    return $packages;
}

function getAllOrders() {
    $conn = getDBConnection();
    $stmt = $conn->query("SELECT o.*, u.first_name, u.last_name, u.email 
                          FROM orders o 
                          LEFT JOIN users u ON o.user_id = u.id 
                          ORDER BY o.created_at DESC");
    $orders = [];
    while ($row = $stmt->fetch()) {
        $orders[] = $row;
    }
    return $orders;
}

function updateOrderStatus($orderId, $status) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE orders SET status = :status WHERE id = :id");
    return $stmt->execute([
        ':status' => $status,
        ':id'     => $orderId,
    ]);
}

function toggleProductFeatured($productId) {
    $conn = getDBConnection();
    // Uses isFeatured (camelCase) and string UUID id in new schema
    $stmt = $conn->prepare("UPDATE products SET isFeatured = NOT isFeatured WHERE id = :id");
    return $stmt->execute([':id' => $productId]);
}
?>

