<?php
/**
 * Migration: Fix Category Names
 * - Change "Veges" to "Vegetable"
 * - Add "Global Pantry" category if it doesn't exist
 * - Change references in filter buttons
 */

require_once __DIR__ . '/../config/database.php';

try {
    $conn = getDBConnection();

    $groups = [
        [
            'canonical' => 'Vegetable',
            'image' => 'uploads/categories/icon.png',
            'aliases' => ['Vegetable', 'Vegetables', 'Veges']
        ],
        [
            'canonical' => 'Global Pantry',
            'image' => 'pantry.png',
            'aliases' => ['Global Pantry', 'Global Pentary', 'Grocery']
        ]
    ];

    foreach ($groups as $group) {
        $placeholders = implode(',', array_fill(0, count($group['aliases']), '?'));
        $stmt = $conn->prepare("SELECT id, title FROM categories WHERE title IN ($placeholders)");
        $stmt->execute($group['aliases']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $canonicalRow = null;
        foreach ($rows as $row) {
            if ($row['title'] === $group['canonical']) {
                $canonicalRow = $row;
                break;
            }
        }

        if (!$canonicalRow) {
            if (!empty($rows)) {
                $canonicalRow = $rows[0];
                $renameStmt = $conn->prepare("UPDATE categories SET title = :title, image = :image WHERE id = :id");
                $renameStmt->execute([
                    ':title' => $group['canonical'],
                    ':image' => $group['image'],
                    ':id' => $canonicalRow['id']
                ]);
                echo "✓ Renamed '{$canonicalRow['title']}' to '{$group['canonical']}'\n";
            } else {
                $insertStmt = $conn->prepare("INSERT INTO categories (id, title, image) VALUES (UUID(), :title, :image)");
                $insertStmt->execute([
                    ':title' => $group['canonical'],
                    ':image' => $group['image']
                ]);

                $fetchStmt = $conn->prepare("SELECT id, title FROM categories WHERE title = :title LIMIT 1");
                $fetchStmt->execute([':title' => $group['canonical']]);
                $canonicalRow = $fetchStmt->fetch(PDO::FETCH_ASSOC);
                echo "✓ Added '{$group['canonical']}' category\n";
            }
        } else {
            $updateStmt = $conn->prepare("UPDATE categories SET image = :image WHERE id = :id");
            $updateStmt->execute([
                ':image' => $group['image'],
                ':id' => $canonicalRow['id']
            ]);
            echo "✓ '{$group['canonical']}' category already exists\n";
        }

        foreach ($rows as $row) {
            if ($row['id'] === $canonicalRow['id']) {
                continue;
            }

            $productUpdateStmt = $conn->prepare("UPDATE products SET categoryId = :canonicalId WHERE categoryId = :duplicateId");
            $productUpdateStmt->execute([
                ':canonicalId' => $canonicalRow['id'],
                ':duplicateId' => $row['id']
            ]);

            $deleteStmt = $conn->prepare("DELETE FROM categories WHERE id = :id");
            $deleteStmt->execute([':id' => $row['id']]);
            echo "✓ Merged '{$row['title']}' into '{$group['canonical']}'\n";
        }
    }
    
    echo "\nCategory migration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
