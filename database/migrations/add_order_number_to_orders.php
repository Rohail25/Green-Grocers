<?php
/**
 * Migration: Add orderNumber column to orders table
 * This migration adds a column to store human-readable order numbers like ORD-1001
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $conn = getDBConnection();
    
    // Check if column already exists
    $stmt = $conn->query("SHOW COLUMNS FROM orders LIKE 'orderNumber'");
    $columnExists = $stmt->rowCount() > 0;
    
    if (!$columnExists) {
        // Add orderNumber column
        $conn->exec("ALTER TABLE orders ADD COLUMN orderNumber VARCHAR(20) NULL AFTER id");
        
        // Create index for faster lookups
        $conn->exec("CREATE INDEX idx_orderNumber ON orders(orderNumber)");
        
        echo "Migration successful: orderNumber column added to orders table.\n";
    } else {
        echo "Migration skipped: orderNumber column already exists.\n";
    }
} catch (PDOException $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
    exit(1);
}
?>

