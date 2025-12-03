<?php
/**
 * Cart Actions - Matching Node.js Backend Flow (Database-based cart)
 */
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/config.php'; // for BASE_PATH
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/cart.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (!isAuthenticated()) {
    $_SESSION['error'] = 'Please login to add items to cart';
    header('Location: ' . BASE_PATH . '/auth/login.php');
    exit;
}

$user = getCurrentUser();
$userId = $user['id'];

switch ($action) {
    case 'add':
        $productId = $_POST['id'] ?? '';
        $type = $_POST['type'] ?? 'product';
        $quantity = (int)($_POST['quantity'] ?? 1);
        
        if (empty($productId)) {
            $_SESSION['error'] = 'Product ID is required';
            header('Location: ' . BASE_PATH . '/');
            exit;
        }
        
        // Get product data for cart
        $conn = getDBConnection();
        if ($type === 'product') {
            $stmt = $conn->prepare("SELECT name, images, retailPrice, vendorId FROM products WHERE id = :id");
            $stmt->execute([':id' => $productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                $images = !empty($product['images']) ? json_decode($product['images'], true) : [];
                $result = addToCart($productId, $quantity, $type, [
                    'productName' => $product['name'],
                    'productImage' => !empty($images) ? $images[0] : '',
                    'price' => (float)$product['retailPrice'],
                    'vendorId' => $product['vendorId'] ?? null
                ]);
            } else {
                $_SESSION['error'] = 'Product not found';
            }
        } elseif ($type === 'package') {
            $stmt = $conn->prepare("SELECT name, image, retailPrice FROM packages WHERE id = :id");
            $stmt->execute([':id' => $productId]);
            $package = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($package) {
                $result = addToCart($productId, $quantity, $type, [
                    'productName' => $package['name'],
                    'productImage' => $package['image'] ?? '',
                    'price' => (float)$package['retailPrice']
                ]);
            } else {
                $_SESSION['error'] = 'Package not found';
            }
        }
        
        if (isset($result) && $result['success']) {
            $_SESSION['success'] = 'Item added to cart';
        } else {
            $_SESSION['error'] = $result['message'] ?? 'Failed to add item to cart';
        }
        break;
        
    case 'remove':
        $productId = $_POST['id'] ?? $_GET['id'] ?? '';
        $type = $_POST['type'] ?? $_GET['type'] ?? 'product';
        
        if (empty($productId)) {
            $_SESSION['error'] = 'Product ID is required';
        } else {
            $result = removeFromCart($productId, $type);
            if ($result['success']) {
                $_SESSION['success'] = 'Item removed from cart';
            } else {
                $_SESSION['error'] = $result['message'] ?? 'Failed to remove item';
            }
        }
        break;
        
    case 'update':
        $productId = $_POST['id'] ?? '';
        $type = $_POST['type'] ?? 'product';
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : null;
        $change = isset($_POST['change']) ? (int)$_POST['change'] : 0;
        
        if (empty($productId)) {
            $_SESSION['error'] = 'Product ID is required';
        } else {
            // If only a change (+1/-1) is sent, compute new quantity from current cart
            if ($quantity === null && $change !== 0) {
                $cart = getCart($userId);
                $items = $cart['items'] ?? [];
                $currentQty = 0;
                foreach ($items as $item) {
                    if (($item['productId'] ?? '') === $productId) {
                        $currentQty = (int)($item['quantity'] ?? 0);
                        break;
                    }
                }
                $quantity = max(0, $currentQty + $change);
            }
            
            if ($quantity === null) {
                $_SESSION['error'] = 'Quantity is required';
            } else {
                $result = updateCartQuantity($productId, $quantity, $type);
                if ($result['success']) {
                    $_SESSION['success'] = 'Cart updated';
                } else {
                    $_SESSION['error'] = $result['message'] ?? 'Failed to update cart';
                }
            }
        }
        break;
        
    case 'clear':
        $result = clearCart();
        if ($result['success']) {
            $_SESSION['success'] = 'Cart cleared';
        } else {
            $_SESSION['error'] = $result['message'] ?? 'Failed to clear cart';
        }
        break;
        
    default:
        $_SESSION['error'] = 'Invalid action';
        break;
}

// Redirect back to previous page or cart
$redirect = $_SERVER['HTTP_REFERER'] ?? BASE_PATH . '/';
header('Location: ' . $redirect);
exit;
?>
