<?php
// Simple test to check image path
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Image Path Test</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .test-box { border: 2px solid #333; padding: 15px; margin: 10px 0; }
        .success { border-color: green; background: #d4edda; }
        .error { border-color: red; background: #f8d7da; }
        img { max-width: 200px; border: 2px solid #ccc; }
    </style>
</head>
<body>
    <h1>Image Path Debugging</h1>
    
    <div class="test-box">
        <h3>Server Information:</h3>
        <p><strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT']; ?></p>
        <p><strong>Script Name:</strong> <?php echo $_SERVER['SCRIPT_NAME']; ?></p>
        <p><strong>Request URI:</strong> <?php echo $_SERVER['REQUEST_URI']; ?></p>
        <p><strong>BASE_PATH:</strong> <?php echo defined('BASE_PATH') ? BASE_PATH : 'NOT DEFINED'; ?></p>
        <p><strong>PUBLIC_PATH:</strong> <?php echo defined('PUBLIC_PATH') ? PUBLIC_PATH : 'NOT DEFINED'; ?></p>
    </div>

    <h2>Test Images:</h2>
    
    <?php
    $testFiles = ['GGLOGO.png', 'product.jpg', 'random1.png', 'login.jpg'];
    
    foreach ($testFiles as $file) {
        $path = imagePath($file);
        $fileExists = file_exists(__DIR__ . '/public/' . $file);
        $class = $fileExists ? 'success' : 'error';
    ?>
        <div class="test-box <?php echo $class; ?>">
            <h4><?php echo $file; ?></h4>
            <p><strong>Generated Path:</strong> <code><?php echo htmlspecialchars($path); ?></code></p>
            <p><strong>File Exists:</strong> <?php echo $fileExists ? 'YES ✓' : 'NO ✗'; ?></p>
            <p><strong>Full Server Path:</strong> <code><?php echo __DIR__ . '/public/' . $file; ?></code></p>
            <img src="<?php echo htmlspecialchars($path); ?>" alt="<?php echo $file; ?>" 
                 onerror="this.style.border='3px solid red'; this.alt='IMAGE FAILED TO LOAD';" 
                 onload="this.style.border='3px solid green';" />
        </div>
    <?php } ?>
    
    <h2>Direct URL Tests:</h2>
    <p>Try accessing these URLs directly in your browser:</p>
    <ul>
        <li><a href="/green-php/public/GGLOGO.png" target="_blank">/green-php/public/GGLOGO.png</a></li>
        <li><a href="<?php echo PUBLIC_PATH; ?>/GGLOGO.png" target="_blank"><?php echo PUBLIC_PATH; ?>/GGLOGO.png</a></li>
    </ul>
</body>
</html>


