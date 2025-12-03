<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

// Allow both admin and vendor to delete packages
requireAuth();
$currentUser = getCurrentUser();
if (!in_array($currentUser['role'], ['admin', 'vendor'])) {
    header('Location: ' . BASE_PATH . '/');
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
// Packages use UUID (string) IDs, so do not cast to int
$packageId = $_POST['id'] ?? $_GET['id'] ?? '';

if ($action === 'delete' && !empty($packageId)) {
    $conn = getDBConnection();

    // Hard delete: completely remove the package row from the database
    $stmt = $conn->prepare("DELETE FROM packages WHERE id = :id");

    if ($stmt->execute([':id' => $packageId]) && $stmt->rowCount() > 0) {
        $_SESSION['success'] = 'Package deleted successfully';
    } else {
        $_SESSION['error'] = 'Failed to delete package';
    }
}

// Redirect back to packages page in all cases
header('Location: ' . BASE_PATH . '/dashboard/pages/packages.php');
exit;
?>

