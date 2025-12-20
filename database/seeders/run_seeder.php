<?php
/**
 * Run All Seeders
 * 
 * This file runs all seeder files in the seeders directory
 * 
 * Usage: php database/seeders/run_seeder.php
 * Or visit: http://localhost/green-grocers/database/seeders/run_seeder.php
 */

// Enable output buffering for web display
ob_start();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Seeder</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        pre { background: #fff; padding: 15px; border-radius: 5px; border: 1px solid #ddd; white-space: pre-wrap; word-wrap: break-word; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
        h1 { color: #333; }
    </style>
</head>
<body>
    <h1>🌱 Database Seeder</h1>
    <pre>";

// Run admin seeder first
echo "Running Admin Seeder...\n";
echo str_repeat("=", 60) . "\n";

try {
    require_once __DIR__ . '/admin_seeder.php';
} catch (Exception $e) {
    echo "\n❌ Fatal Error in Admin Seeder: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n\n";

// Run category seeder
echo "Running Category Seeder...\n";
echo str_repeat("=", 60) . "\n";

try {
    require_once __DIR__ . '/category_seeder.php';
} catch (Exception $e) {
    echo "\n❌ Fatal Error in Category Seeder: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n\n";

// Run user seeder
echo "Running User Seeder...\n";
echo str_repeat("=", 60) . "\n";

try {
    require_once __DIR__ . '/user_seeder.php';
} catch (Exception $e) {
    echo "\n❌ Fatal Error in User Seeder: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>
    <p><a href='../../index.php'>← Back to Home</a></p>
    <p><a href='run_seeder.php'>🔄 Run Seeder Again</a></p>
</body>
</html>";

ob_end_flush();
?>
