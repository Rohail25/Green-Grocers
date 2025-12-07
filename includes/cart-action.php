<?php
/**
 * Cart Actions - Matching Node.js Backend Flow (Database-based cart)
 */
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/config.php'; // for BASE_PATH
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/cart.php';

// Check if this is an AJAX request
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$isAjax = $isAjax || (isset($_GET['ajax']) || isset($_POST['ajax']));

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Allow guests to add items to cart - authentication only required at checkout
$user = getCurrentUser();
$userId = $user['id'] ?? null;

$result = ['success' => false, 'message' => ''];

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
                $result = ['success' => false, 'message' => 'Product not found'];
                if (!$isAjax) {
                    $_SESSION['error'] = 'Product not found';
                }
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
                $result = ['success' => false, 'message' => 'Package not found'];
                if (!$isAjax) {
                    $_SESSION['error'] = 'Package not found';
                }
            }
        }
        
        if (isset($result) && $result['success']) {
            if ($isAjax) {
                $result['message'] = 'Item added to cart';
            } else {
                $_SESSION['success'] = 'Item added to cart';
            }
        } else {
            if ($isAjax) {
                $result = $result ?? ['success' => false, 'message' => 'Failed to add item to cart'];
            } else {
                $_SESSION['error'] = ($result['message'] ?? 'Failed to add item to cart');
            }
        }
        break;
        
    case 'remove':
        $productId = $_POST['id'] ?? $_GET['id'] ?? '';
        $type = $_POST['type'] ?? $_GET['type'] ?? 'product';
        
        if (empty($productId)) {
            if ($isAjax) {
                $result = ['success' => false, 'message' => 'Product ID is required'];
            } else {
                $_SESSION['error'] = 'Product ID is required';
            }
        } else {
            $result = removeFromCart($productId, $type);
            if ($result['success']) {
                if ($isAjax) {
                    $result['message'] = 'Item removed from cart';
                } else {
                    $_SESSION['success'] = 'Item removed from cart';
                }
            } else {
                if ($isAjax) {
                    $result = ['success' => false, 'message' => $result['message'] ?? 'Failed to remove item'];
                } else {
                    $_SESSION['error'] = $result['message'] ?? 'Failed to remove item';
                }
            }
        }
        break;
        
    case 'update':
        $productId = $_POST['id'] ?? '';
        $type = $_POST['type'] ?? 'product';
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : null;
        $change = isset($_POST['change']) ? (int)$_POST['change'] : 0;
        
        if (empty($productId)) {
            if ($isAjax) {
                $result = ['success' => false, 'message' => 'Product ID is required'];
            } else {
                $_SESSION['error'] = 'Product ID is required';
            }
        } else {
            // If only a change (+1/-1) is sent, compute new quantity from current cart
            if ($quantity === null && $change !== 0) {
                $cart = getCart($userId);
                $items = $cart['items'] ?? [];
                $currentQty = 0;
                foreach ($items as $item) {
                    if (($item['productId'] ?? '') === $productId && (($item['type'] ?? 'product') === $type)) {
                        $currentQty = (int)($item['quantity'] ?? 0);
                        break;
                    }
                }
                $quantity = max(0, $currentQty + $change);
            }
            
            if ($quantity === null) {
                if ($isAjax) {
                    $result = ['success' => false, 'message' => 'Quantity is required'];
                } else {
                    $_SESSION['error'] = 'Quantity is required';
                }
            } else {
                $result = updateCartQuantity($productId, $quantity, $type);
                if ($result['success']) {
                    if ($isAjax) {
                        $result['message'] = 'Cart updated';
                    } else {
                        $_SESSION['success'] = 'Cart updated';
                    }
                } else {
                    if ($isAjax) {
                        $result = ['success' => false, 'message' => $result['message'] ?? 'Failed to update cart'];
                    } else {
                        $_SESSION['error'] = $result['message'] ?? 'Failed to update cart';
                    }
                }
            }
        }
        break;
        
    case 'clear':
        $result = clearCart();
        if ($result['success']) {
            if ($isAjax) {
                $result['message'] = 'Cart cleared';
            } else {
                $_SESSION['success'] = 'Cart cleared';
            }
        } else {
            if ($isAjax) {
                $result = ['success' => false, 'message' => $result['message'] ?? 'Failed to clear cart'];
            } else {
                $_SESSION['error'] = $result['message'] ?? 'Failed to clear cart';
            }
        }
        break;
        
    case 'get_count':
        // Return cart data for AJAX requests
        if ($isAjax) {
            $cartItems = getCartItems();
            $cartTotal = getCartTotal();
            $cartCount = getCartCount();
            $result = [
                'success' => true,
                'count' => $cartCount,
                'cart' => [
                    'items' => $cartItems,
                    'total' => $cartTotal,
                    'count' => $cartCount
                ]
            ];
        } else {
            $result = ['success' => false, 'message' => 'Invalid request'];
        }
        break;
        
    default:
        if ($isAjax) {
            $result = ['success' => false, 'message' => 'Invalid action'];
        } else {
            $_SESSION['error'] = 'Invalid action';
        }
        break;
}

// Handle AJAX requests - return JSON
if ($isAjax) {
    header('Content-Type: application/json');
    
    // Get updated cart data
    $cartItems = getCartItems();
    $cartTotal = getCartTotal();
    $cartCount = getCartCount();
    
    $result['cart'] = [
        'items' => $cartItems,
        'total' => $cartTotal,
        'count' => $cartCount
    ];
    
    echo json_encode($result);
    exit;
}

// For non-AJAX requests, redirect back to previous page
// But ensure we don't redirect to cart-action.php itself (which would cause a loop)
$redirect = $_SERVER['HTTP_REFERER'] ?? BASE_PATH . '/';

// Never redirect to cart-action.php itself
if (strpos($redirect, 'cart-action.php') !== false) {
    $redirect = BASE_PATH . '/';
}

// If we have a return_url parameter, use it (but validate it's not cart-action.php)
if (isset($_POST['return_url']) && !empty($_POST['return_url'])) {
    $returnUrl = $_POST['return_url'];
    if (strpos($returnUrl, 'cart-action.php') === false) {
        $redirect = $returnUrl;
    }
} elseif (isset($_GET['return_url']) && !empty($_GET['return_url'])) {
    $returnUrl = $_GET['return_url'];
    if (strpos($returnUrl, 'cart-action.php') === false) {
        $redirect = $returnUrl;
    }
}

// Final safety check: never redirect to cart-action.php
if (strpos($redirect, 'cart-action.php') !== false) {
    $redirect = BASE_PATH . '/';
}

header('Location: ' . $redirect);
exit;
?>
