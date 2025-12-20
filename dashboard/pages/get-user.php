<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/encryption.php';

// Admin only access
requireAuth();
$currentUser = getCurrentUser();
if ($currentUser['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

header('Content-Type: application/json');

$userId = $_GET['id'] ?? '';
if (empty($userId)) {
    echo json_encode(['error' => 'User ID required']);
    exit;
}

$user = getUserById($userId);
if (!$user) {
    echo json_encode(['error' => 'User not found']);
    exit;
}

// Decrypt email and phone for admin to edit (admin needs to see the actual values)
$user['email'] = decryptEmail($user['email']);
$user['phone'] = decryptPhone($user['phone']);

echo json_encode($user);

