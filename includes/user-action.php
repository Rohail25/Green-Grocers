<?php
/**
 * User Action Handler - Handles create, update, delete operations for users
 * Admin only access
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/encryption.php';

// Check authentication and admin role
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$currentUser = getCurrentUser();
if ($currentUser['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $conn = getDBConnection();
    
    switch ($action) {
        case 'create':
            $email = strtolower(trim($_POST['email'] ?? ''));
            $password = $_POST['password'] ?? '';
            $firstName = $_POST['firstName'] ?? '';
            $lastName = $_POST['lastName'] ?? '';
            $role = $_POST['role'] ?? 'customer';
            $platform = $_POST['platform'] ?? 'trivemart';
            $phone = $_POST['phone'] ?? '';
            
            // Validation
            if (empty($email) || empty($password) || empty($firstName)) {
                echo json_encode(['success' => false, 'message' => 'Email, password, and first name are required']);
                exit;
            }
            
            if (strlen($password) < 6) {
                echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
                exit;
            }
            
            // Encrypt email and phone before storing
            $encryptedEmail = encryptEmail($email);
            $encryptedPhone = !empty($phone) ? encryptPhone($phone) : '';
            
            // Check if user already exists (compare encrypted emails)
            $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = :email AND platform = :platform");
            $checkStmt->execute([':email' => $encryptedEmail, ':platform' => $platform]);
            if ($checkStmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'User with this email and platform already exists']);
                exit;
            }
            
            // Generate vendorId/clientId based on platform
            $vendorId = null;
            $clientId = null;
            if ($platform === 'trivestore') {
                $vendorId = 'VEND-' . strtoupper(substr(uniqid(), -8));
            } elseif ($platform === 'trivemart') {
                $clientId = 'MART-' . strtoupper(substr(uniqid(), -8));
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $conn->prepare("
                INSERT INTO users (
                    id, email, phone, password, firstName, lastName, role, platform,
                    vendorId, clientId, isEmailConfirmed, isVerified,
                    verificationDocuments, preferredVendors, addresses,
                    created_at, updated_at
                ) VALUES (
                    UUID(), :email, :phone, :password, :firstName, :lastName, :role, :platform,
                    :vendorId, :clientId, 1, 1,
                    '[]', '[]', '[]',
                    NOW(), NOW()
                )
            ");
            
            $stmt->execute([
                ':email' => $encryptedEmail, // Store encrypted email
                ':phone' => $encryptedPhone, // Store encrypted phone
                ':password' => $hashedPassword,
                ':firstName' => $firstName,
                ':lastName' => $lastName,
                ':role' => $role,
                ':platform' => $platform,
                ':vendorId' => $vendorId,
                ':clientId' => $clientId
            ]);
            
            echo json_encode(['success' => true, 'message' => 'User created successfully']);
            break;
            
        case 'update':
            $userId = $_POST['id'] ?? '';
            $email = strtolower(trim($_POST['email'] ?? ''));
            $firstName = $_POST['firstName'] ?? '';
            $lastName = $_POST['lastName'] ?? '';
            $role = $_POST['role'] ?? '';
            $platform = $_POST['platform'] ?? '';
            $phone = $_POST['phone'] ?? '';
            
            if (empty($userId)) {
                echo json_encode(['success' => false, 'message' => 'User ID is required']);
                exit;
            }
            
            // Check if user exists
            $checkStmt = $conn->prepare("SELECT id FROM users WHERE id = :id");
            $checkStmt->execute([':id' => $userId]);
            if (!$checkStmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }
            
            // Encrypt email and phone before storing
            $encryptedEmail = encryptEmail($email);
            $encryptedPhone = !empty($phone) ? encryptPhone($phone) : '';
            
            // Check if email is already taken by another user (compare encrypted emails)
            $emailCheckStmt = $conn->prepare("SELECT id FROM users WHERE email = :email AND platform = :platform AND id != :id");
            $emailCheckStmt->execute([':email' => $encryptedEmail, ':platform' => $platform, ':id' => $userId]);
            if ($emailCheckStmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Email already taken by another user']);
                exit;
            }
            
            // Update user
            $stmt = $conn->prepare("
                UPDATE users SET 
                    email = :email,
                    firstName = :firstName,
                    lastName = :lastName,
                    role = :role,
                    platform = :platform,
                    phone = :phone,
                    updated_at = NOW()
                WHERE id = :id
            ");
            
            $stmt->execute([
                ':email' => $encryptedEmail, // Store encrypted email
                ':firstName' => $firstName,
                ':lastName' => $lastName,
                ':role' => $role,
                ':platform' => $platform,
                ':phone' => $encryptedPhone, // Store encrypted phone
                ':id' => $userId
            ]);
            
            echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            break;
            
        case 'delete':
            $userId = $_POST['id'] ?? $_GET['id'] ?? '';
            
            if (empty($userId)) {
                echo json_encode(['success' => false, 'message' => 'User ID is required']);
                exit;
            }
            
            // Prevent deleting own account
            if ($userId === $currentUser['id']) {
                echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
                exit;
            }
            
            // Delete user
            $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            
            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            break;
            
        case 'change_password':
            $userId = $_POST['userId'] ?? $currentUser['id'];
            $currentPassword = $_POST['currentPassword'] ?? '';
            $newPassword = $_POST['newPassword'] ?? '';
            $confirmPassword = $_POST['confirmPassword'] ?? '';
            
            if (empty($currentPassword)) {
                echo json_encode(['success' => false, 'message' => 'Current password is required']);
                exit;
            }
            
            if (empty($newPassword)) {
                echo json_encode(['success' => false, 'message' => 'New password is required']);
                exit;
            }
            
            if ($newPassword !== $confirmPassword) {
                echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
                exit;
            }
            
            if (strlen($newPassword) < 6) {
                echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
                exit;
            }
            
            // Get current user password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password'])) {
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                exit;
            }
            
            // Hash new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = $conn->prepare("UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id");
            $stmt->execute([
                ':password' => $hashedPassword,
                ':id' => $userId
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("User action error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}

