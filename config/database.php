<?php
// Database Configuration
define('DB_HOST', 'localhost');
// define('DB_USER', 'zarifresh_green-grocers');
// define('DB_PASS', 'U6tzCkdzWH%1d_?,');
// define('DB_NAME', 'zarifresh_green-grocers');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'green_grocers');

// Create and return a PDO connection to the existing database
// NOTE: Database and tables must already be created in MySQL
function getDBConnection()
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        error_log("DB Connection Error: " . $e->getMessage());
        exit('Database connection failed. Please try again later.');
    }

    return $pdo;
}
