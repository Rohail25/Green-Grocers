<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

// Allow both admin and vendor to delete products
requireAuth();
$currentUser = getCurrentUser();
if (!in_array($currentUser['role'], ['admin', 'vendor'])) {
    header('Location: ' . BASE_PATH . '/');
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
// Products use UUID (string) IDs, so do not cast to int
$productId = $_POST['id'] ?? $_GET['id'] ?? '';

if ($action === 'delete' && !empty($productId)) {
    $conn = getDBConnection();

    // Hard delete: completely remove the product row from the database
    $stmt = $conn->prepare("DELETE FROM products WHERE id = :id");

    if ($stmt->execute([':id' => $productId]) && $stmt->rowCount() > 0) {
        $_SESSION['success'] = 'Product deleted successfully';
    } else {
        $_SESSION['error'] = 'Failed to delete product';
    }
}

// Redirect back to products page in all cases (refresh list)
header('Location: ' . BASE_PATH . '/dashboard/pages/products.php');
exit;
?>

