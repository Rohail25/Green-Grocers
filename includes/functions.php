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

function getSuggestedCategories(int $limit = 5): array {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT title 
        FROM categories 
        ORDER BY title ASC 
        LIMIT :lim
    ");
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Search products by name (supports partial matching, case-insensitive)
function searchProducts($searchQuery) {
    if (empty(trim($searchQuery))) {
        return [];
    }
    
    $conn = getDBConnection();
    $searchTerm = '%' . trim($searchQuery) . '%';
    $searchExact = trim($searchQuery) . '%';
    
    try {
        // Case-insensitive search in name, description, and category name
        // Use COALESCE to handle NULL descriptions
        $stmt = $conn->prepare("
            SELECT p.*, c.title as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.categoryId = c.id 
            WHERE p.status = 'active' 
            AND (
                LOWER(p.name) LIKE LOWER(:search) 
                OR LOWER(COALESCE(p.description, '')) LIKE LOWER(:search)
                OR LOWER(COALESCE(c.title, '')) LIKE LOWER(:search)
            )
            ORDER BY 
                CASE 
                    WHEN LOWER(p.name) LIKE LOWER(:searchExact) THEN 1
                    WHEN LOWER(p.name) LIKE LOWER(:search) THEN 2
                    ELSE 3
                END,
                p.created_at DESC
        ");
        
        $stmt->execute([
            ':search' => $searchTerm,
            ':searchExact' => $searchExact
        ]);
        
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
    } catch (PDOException $e) {
        error_log("Search error: " . $e->getMessage());
        // Fallback: try simpler search if complex one fails
        try {
            $stmt = $conn->prepare("
                SELECT p.*, c.title as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.categoryId = c.id 
                WHERE p.status = 'active' 
                AND p.name LIKE :search
                ORDER BY p.created_at DESC
            ");
            $stmt->execute([':search' => $searchTerm]);
            
            $products = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
        } catch (PDOException $e2) {
            error_log("Fallback search error: " . $e2->getMessage());
            return [];
        }
    }
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

// Get all users (admin only)
// Note: Email and phone remain encrypted in admin view for security
// Only individual users see their own decrypted data
function getAllUsers() {
    require_once __DIR__ . '/encryption.php';
    $conn = getDBConnection();
    $stmt = $conn->query("
        SELECT id, email, firstName, lastName, role, platform, phone, 
               vendorId, clientId, isEmailConfirmed, isVerified, created_at, updated_at
        FROM users 
        ORDER BY created_at DESC
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Decrypt email and phone for each user (admin viewing)
    foreach ($users as &$user) {
        $user['email'] = decryptEmail($user['email']);
        $user['phone'] = decryptPhone($user['phone']);
    }
    
    return $users;
}

// Get single user by ID
// Note: Returns encrypted data - caller should decrypt if needed
function getUserById($userId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT id, email, firstName, lastName, role, platform, phone, 
               vendorId, clientId, isEmailConfirmed, isVerified, created_at, updated_at
        FROM users 
        WHERE id = :id
    ");
    $stmt->execute([':id' => $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all active dynamic texts
function getActiveDynamicTexts() {
    $conn = getDBConnection();
    // Create table if it doesn't exist
    try {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS dynamic_texts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                position INT DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
    } catch (PDOException $e) {
        // Table might already exist, continue
    }
    
    $stmt = $conn->query("
        SELECT * FROM dynamic_texts 
        WHERE is_active = 1 
        ORDER BY position ASC, created_at DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all dynamic texts (admin)
function getAllDynamicTexts() {
    $conn = getDBConnection();
    // Create table if it doesn't exist
    try {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS dynamic_texts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                position INT DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
    } catch (PDOException $e) {
        // Table might already exist, continue
    }
    
    $stmt = $conn->query("
        SELECT * FROM dynamic_texts 
        ORDER BY position ASC, created_at DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get single dynamic text by ID
function getDynamicTextById($id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM dynamic_texts WHERE id = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Match Node.js: getAllOrders
function getAllOrders() {
    require_once __DIR__ . '/encryption.php';
    $conn = getDBConnection();
    $stmt = $conn->query("
        SELECT o.*, u.firstName, u.lastName, u.email, u.phone 
        FROM orders o 
        LEFT JOIN users u ON o.userId = u.id 
        ORDER BY o.created_at DESC
    ");
    $orders = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Decrypt email and phone for display
        if (!empty($row['email'])) {
            $row['email'] = decryptEmail($row['email']);
        }
        if (!empty($row['phone'])) {
            $row['phone'] = decryptPhone($row['phone']);
        }
        
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

// Generate placeholder image with product name
function generateProductPlaceholder($productName, $width = 400, $height = 400) {
    // Check if GD library is available
    if (!function_exists('imagecreatetruecolor')) {
        return null;
    }
    
    // Create directory if it doesn't exist
    $placeholderDir = __DIR__ . '/../public/uploads/placeholders/';
    if (!is_dir($placeholderDir)) {
        mkdir($placeholderDir, 0777, true);
    }
    
    // Generate filename based on product name (sanitized)
    $sanitizedName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $productName);
    $sanitizedName = substr($sanitizedName, 0, 50); // Limit length
    $filename = md5($productName) . '_' . $sanitizedName . '.png';
    $filepath = $placeholderDir . $filename;
    
    // If image already exists, return its URL
    if (file_exists($filepath)) {
        return BASE_PATH . '/public/uploads/placeholders/' . $filename;
    }
    
    // Create image
    $image = imagecreatetruecolor($width, $height);
    
    // Set colors
    $bgColor = imagecolorallocate($image, 240, 240, 240); // Light gray background
    $textColor = imagecolorallocate($image, 100, 100, 100); // Dark gray text
    $borderColor = imagecolorallocate($image, 200, 200, 200); // Light border
    
    // Fill background
    imagefill($image, 0, 0, $bgColor);
    
    // Draw border
    imagerectangle($image, 0, 0, $width - 1, $height - 1, $borderColor);
    
    // Prepare text - use built-in font for simplicity
    $font = 5; // Built-in font size
    $charWidth = imagefontwidth($font);
    $charHeight = imagefontheight($font);
    $maxWidth = $width - 40; // Padding
    $maxCharsPerLine = floor($maxWidth / $charWidth);
    
    // Word wrap text
    $words = explode(' ', $productName);
    $lines = [];
    $currentLine = '';
    
    foreach ($words as $word) {
        $testLine = $currentLine . ($currentLine ? ' ' : '') . $word;
        if (strlen($testLine) > $maxCharsPerLine && $currentLine) {
            $lines[] = $currentLine;
            $currentLine = $word;
        } else {
            $currentLine = $testLine;
        }
    }
    if ($currentLine) {
        $lines[] = $currentLine;
    }
    
    // Calculate total text height
    $lineHeight = $charHeight + 5;
    $totalHeight = count($lines) * $lineHeight;
    $startY = ($height - $totalHeight) / 2;
    
    // Draw text lines
    foreach ($lines as $index => $line) {
        $textWidth = $charWidth * strlen($line);
        $x = ($width - $textWidth) / 2;
        $y = $startY + ($index * $lineHeight);
        imagestring($image, $font, $x, $y, $line, $textColor);
    }
    
    // Save image
    imagepng($image, $filepath);
    imagedestroy($image);
    
    return BASE_PATH . '/public/uploads/placeholders/' . $filename;
}

// Get product image with placeholder fallback
function getProductImage($product, $index = 0) {
    $images = is_array($product['images'] ?? null) ? $product['images'] : (!empty($product['images']) ? json_decode($product['images'], true) : []);
    
    if (!empty($images) && isset($images[$index])) {
        return $images[$index];
    }
    
    // Generate placeholder if no image
    $productName = $product['name'] ?? 'Product';
    return generateProductPlaceholder($productName);
}
?>
