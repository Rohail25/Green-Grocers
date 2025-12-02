<?php
session_start();
require_once __DIR__ . '/cart.php';

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $id = intval($_POST['id'] ?? 0);
    $type = $_POST['type'] ?? 'product';
    $quantity = intval($_POST['quantity'] ?? 1);
    
    if ($id > 0) {
        addToCart($id, $quantity, $type);
    }
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/green-php/'));
    exit;
} elseif ($action === 'update') {
    $id = intval($_POST['id'] ?? 0);
    $type = $_POST['type'] ?? 'product';
    $change = intval($_POST['change'] ?? 0);
    
    if ($id > 0) {
        $cart = getCart();
        $key = $type . '_' . $id;
        if (isset($cart[$key])) {
            $newQuantity = $cart[$key]['quantity'] + $change;
            updateCartQuantity($id, $newQuantity, $type);
        }
    }
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/green-php/'));
    exit;
} elseif ($action === 'remove') {
    $id = intval($_POST['id'] ?? 0);
    $type = $_POST['type'] ?? 'product';
    
    if ($id > 0) {
        removeFromCart($id, $type);
    }
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/green-php/'));
    exit;
} else {
    header('Location: /green-php/');
    exit;
}
?>

