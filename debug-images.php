<?php
// Debug script to test image paths
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

echo "<h1>Image Path Debug</h1>";
echo "<p><strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "</p>";
echo "<p><strong>Script Name:</strong> " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "</p>";
echo "<p><strong>Request URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "</p>";
echo "<p><strong>BASE_PATH:</strong> " . (defined('BASE_PATH') ? BASE_PATH : 'NOT DEFINED') . "</p>";
echo "<p><strong>PUBLIC_PATH:</strong> " . (defined('PUBLIC_PATH') ? PUBLIC_PATH : 'NOT DEFINED') . "</p>";

echo "<hr>";
echo "<h2>Test Images:</h2>";

$testImages = ['GGLOGO.png', 'product.jpg', 'random1.png'];

foreach ($testImages as $img) {
    $path = imagePath($img);
    echo "<div style='margin: 20px 0; padding: 20px; border: 1px solid #ccc;'>";
    echo "<p><strong>File:</strong> $img</p>";
    echo "<p><strong>Generated Path:</strong> $path</p>";
    echo "<p><strong>File Exists:</strong> " . (file_exists(__DIR__ . '/public/' . $img) ? 'YES ✓' : 'NO ✗') . "</p>";
    echo "<img src='$path' alt='$img' style='max-width: 200px; border: 2px solid " . (file_exists(__DIR__ . '/public/' . $img) ? 'green' : 'red') . ";' />";
    echo "</div>";
}

echo "<hr>";
echo "<h2>Direct Path Tests:</h2>";
echo "<p>Try these paths manually in your browser:</p>";
echo "<ul>";
echo "<li><a href='/green-php/public/GGLOGO.png' target='_blank'>/green-php/public/GGLOGO.png</a></li>";
echo "<li><a href='/public/GGLOGO.png' target='_blank'>/public/GGLOGO.png</a></li>";
echo "<li><a href='public/GGLOGO.png' target='_blank'>public/GGLOGO.png (relative)</a></li>";
echo "</ul>";
?>


