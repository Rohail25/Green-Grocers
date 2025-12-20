<?php
/**
 * Dynamic Text Action Handler - Handles CRUD operations for dynamic text content
 * Admin only access
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

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
    
    // Create table if it doesn't exist
    $conn->exec("
        CREATE TABLE IF NOT EXISTS dynamic_texts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            position INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    switch ($action) {
        case 'create':
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $position = intval($_POST['position'] ?? 0);
            $isActive = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;
            
            if (empty($title) || empty($content)) {
                echo json_encode(['success' => false, 'message' => 'Title and content are required']);
                exit;
            }
            
            $stmt = $conn->prepare("
                INSERT INTO dynamic_texts (title, content, position, is_active) 
                VALUES (:title, :content, :position, :is_active)
            ");
            $stmt->execute([
                ':title' => $title,
                ':content' => $content,
                ':position' => $position,
                ':is_active' => $isActive
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Text created successfully']);
            break;
            
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $position = intval($_POST['position'] ?? 0);
            $isActive = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;
            
            if (empty($id) || empty($title) || empty($content)) {
                echo json_encode(['success' => false, 'message' => 'ID, title and content are required']);
                exit;
            }
            
            $stmt = $conn->prepare("
                UPDATE dynamic_texts 
                SET title = :title, content = :content, position = :position, is_active = :is_active 
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $id,
                ':title' => $title,
                ':content' => $content,
                ':position' => $position,
                ':is_active' => $isActive
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Text updated successfully']);
            break;
            
        case 'delete':
            $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
            
            if (empty($id)) {
                echo json_encode(['success' => false, 'message' => 'ID is required']);
                exit;
            }
            
            $stmt = $conn->prepare("DELETE FROM dynamic_texts WHERE id = :id");
            $stmt->execute([':id' => $id]);
            
            echo json_encode(['success' => true, 'message' => 'Text deleted successfully']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("Dynamic text action error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}

