<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

requireAuth('admin');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$productId = intval($_POST['id'] ?? $_GET['id'] ?? 0);

if ($action === 'delete' && $productId > 0) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("DELETE FROM products WHERE id = :id");

    if ($stmt->execute([':id' => $productId])) {
        $_SESSION['success'] = 'Product deleted successfully';
    } else {
        $_SESSION['error'] = 'Failed to delete product';
    }
    
    header('Location: ' . BASE_PATH . '/dashboard/pages/products.php');
    exit;
}

header('Location: ' . BASE_PATH . '/dashboard/pages/products.php');
exit;
?>

