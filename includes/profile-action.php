<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

requireAuth();
$currentUser = getCurrentUser();
$userId = $currentUser['id'] ?? null;

if (!$userId) {
    header('Location: ' . BASE_PATH . '/auth/login.php');
    exit;
}

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action'];

if ($action === 'update_password') {
    $currentPassword = $_POST['currentPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $response = ['success' => false, 'message' => 'All password fields are required'];
    } elseif ($newPassword !== $confirmPassword) {
        $response = ['success' => false, 'message' => 'New passwords do not match'];
    } else {
        $response = updatePassword($userId, $currentPassword, $newPassword);
    }
} elseif ($action === 'add_address' || $action === 'update_address') {
    $addressData = [
        'streetAddressLine1' => $_POST['streetAddressLine1'] ?? '',
        'streetAddressLine2' => $_POST['streetAddressLine2'] ?? '',
        'suburb' => $_POST['suburb'] ?? '',
        'state' => $_POST['state'] ?? '',
        'postalCode' => $_POST['postalCode'] ?? '',
        'isDefault' => isset($_POST['isDefault']) && $_POST['isDefault'] === '1'
    ];
    
    $addressIndex = ($action === 'update_address') ? ($_POST['addressIndex'] ?? null) : null;
    $response = updateAddress($userId, $addressIndex, $addressData);
} elseif ($action === 'delete_address') {
    $addressIndex = $_POST['addressIndex'] ?? null;
    if ($addressIndex === null) {
        $response = ['success' => false, 'message' => 'Address index is required'];
    } else {
        $response = deleteAddress($userId, (int)$addressIndex);
    }
} elseif ($action === 'set_default_address') {
    $addressIndex = $_POST['addressIndex'] ?? null;
    if ($addressIndex === null) {
        $response = ['success' => false, 'message' => 'Address index is required'];
    } else {
        $response = setDefaultAddress($userId, (int)$addressIndex);
    }
}

// Return JSON response for AJAX requests
if (isset($_POST['ajax']) || isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// For non-AJAX requests, redirect with message
$_SESSION['profile_message'] = $response['message'];
$_SESSION['profile_success'] = $response['success'];
header('Location: ' . BASE_PATH . '/website/pages/profile.php');
exit;
?>

