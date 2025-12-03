<?php
/**
 * Run All Seeders
 * 
 * This file runs all seeder files in the seeders directory
 * 
 * Usage: php database/seeders/run_seeder.php
 * Or visit: http://localhost/green-grocers/database/seeders/run_seeder.php
 */

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Seeder</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        pre { background: #fff; padding: 15px; border-radius: 5px; border: 1px solid #ddd; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
    </style>
</head>
<body>
    <h1>🌱 Database Seeder</h1>
    <pre>";

// Run user seeder
echo "Running User Seeder...\n";
echo str_repeat("=", 60) . "\n";
require_once __DIR__ . '/user_seeder.php';

echo "</pre>
    <p><a href='../../index.php'>← Back to Home</a></p>
</body>
</html>";
?>

