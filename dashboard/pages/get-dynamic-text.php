<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Admin only access
requireAuth();
$currentUser = getCurrentUser();
if ($currentUser['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);
if (empty($id)) {
    echo json_encode(['error' => 'ID required']);
    exit;
}

$text = getDynamicTextById($id);
if (!$text) {
    echo json_encode(['error' => 'Text not found']);
    exit;
}

echo json_encode($text);

