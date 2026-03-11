<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$query = trim((string)($_GET['q'] ?? ''));
$limit = (int)($_GET['limit'] ?? 10);
$category = trim((string)($_GET['category'] ?? ''));

if ($query === '') {
    echo json_encode(['success' => true, 'items' => []]);
    exit;
}

try {
    $items = [];
    
    // If category is specified, search only in that category
    if (!empty($category)) {
        $categoryProducts = searchProductsByCategory($query, $category);
        foreach ($categoryProducts as $product) {
            $items[] = [
                'name' => $product['name'],
                'type' => 'product'
            ];
        }
        $items = array_slice($items, 0, $limit);
    } else {
        // Otherwise, use global search (products and packages)
        $items = getNameSuggestions($query, $limit);
    }
    
    echo json_encode(['success' => true, 'items' => $items]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'items' => [],
        'message' => 'Unable to load suggestions'
    ]);
}
