<?php
// Base configuration for paths

// Detect base URL automatically
if (!defined('BASE_PATH')) {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $path = dirname($script);

    // Detect if app is running inside /green-grocers folder
    if (strpos($path, '/green-grocers') !== false || strpos($requestUri, '/green-grocers') !== false) {
        $basePath = '/green-grocers';
    } else {
        // Default for PHP built-in server when run from project root
        $basePath = '';
    }

    define('BASE_PATH', $basePath);
    define('PUBLIC_PATH', $basePath . '/public');
}

