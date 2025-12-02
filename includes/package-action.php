<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

requireAuth('admin');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$packageId = intval($_POST['id'] ?? $_GET['id'] ?? 0);

if ($action === 'delete' && $packageId > 0) {
    $conn = getDBConnection();

    // Delete package (package_items table may not exist in new schema)
    $stmt = $conn->prepare("DELETE FROM packages WHERE id = :id");

    if ($stmt->execute([':id' => $packageId])) {
        $_SESSION['success'] = 'Package deleted successfully';
    } else {
        $_SESSION['error'] = 'Failed to delete package';
    }
    
    header('Location: ' . BASE_PATH . '/dashboard/pages/packages.php');
    exit;
}

header('Location: ' . BASE_PATH . '/dashboard/pages/packages.php');
exit;
?>

