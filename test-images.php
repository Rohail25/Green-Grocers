<?php
// Quick test to verify image paths
require_once __DIR__ . '/includes/functions.php';

echo "<h1>Image Path Test</h1>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "</p>";
echo "<p>Script Name: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "</p>";

echo "<h2>Test Images:</h2>";
echo "<img src='/green-php/public/GGLOGO.png' alt='Logo' style='width:100px;' />";
echo "<p>Path: /green-php/public/GGLOGO.png</p>";

echo "<br><br>";
echo "<img src='/public/GGLOGO.png' alt='Logo' style='width:100px;' />";
echo "<p>Path: /public/GGLOGO.png</p>";

echo "<br><br>";
echo "<p>Check which image loads above to determine the correct path format.</p>";
?>


