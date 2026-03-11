<?php
// Check if this is an AJAX request FIRST
$isAjax = isset($_POST['ajax']) || isset($_GET['ajax']) || 
          isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Set JSON header for AJAX requests immediately
if ($isAjax) {
    header('Content-Type: application/json');
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

// Check authentication - return JSON for AJAX, redirect for normal requests
if (!isAuthenticated()) {
    if ($isAjax) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required. Please log in.']);
        exit;
    } else {
        header('Location: ' . BASE_PATH . '/auth/login.php');
        exit;
    }
}

$currentUser = getCurrentUser();
$userId = $currentUser['id'] ?? null;

if (!$userId) {
    if ($isAjax) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'User session invalid. Please log in again.']);
        exit;
    } else {
        header('Location: ' . BASE_PATH . '/auth/login.php');
        exit;
    }
}

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action'];

// Wrap in try-catch to handle any errors gracefully for AJAX requests
try {
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
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
}

// Return JSON response for AJAX requests
if ($isAjax) {
    echo json_encode($response);
    exit;
}

// For non-AJAX requests, redirect with message
$_SESSION['profile_message'] = $response['message'];
$_SESSION['profile_success'] = $response['success'];
header('Location: ' . BASE_PATH . '/website/pages/profile.php');
exit;
?>

