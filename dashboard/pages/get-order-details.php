<?php
/**
 * Get Order Details - AJAX Endpoint
 * Returns order details as JSON for the View modal
 */
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Allow both admin and vendor to access
if (!isAuthenticated()) {
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$currentUser = getCurrentUser();
if (!in_array($currentUser['role'], ['admin', 'vendor'])) {
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$orderId = $_GET['order_id'] ?? '';

if (empty($orderId)) {
    echo json_encode(['error' => 'Order ID is required']);
    exit;
}

$conn = getDBConnection();

// Fetch order with user details
$stmt = $conn->prepare("
    SELECT o.*, u.firstName, u.lastName, u.email 
    FROM orders o 
    LEFT JOIN users u ON o.userId = u.id 
    WHERE o.id = :id
");
$stmt->execute([':id' => $orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo json_encode(['error' => 'Order not found']);
    exit;
}

// Decode JSON fields
$order['items'] = !empty($order['items']) ? json_decode($order['items'], true) : [];
$order['shippingAddress'] = !empty($order['shippingAddress']) ? json_decode($order['shippingAddress'], true) : null;
$order['statusHistory'] = !empty($order['statusHistory']) ? json_decode($order['statusHistory'], true) : [];

// Return order as JSON
echo json_encode($order);
exit;
?>

