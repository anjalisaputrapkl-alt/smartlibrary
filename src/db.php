<?php
/**
 * Database Connection Helper
 * Returns PDO connection or dies on error
 */

// Load config
$config = require __DIR__ . '/config.php';

try {
    // Create DSN
    $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
    
    // Create PDO connection with proper error mode
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    // Return connection
    return $pdo;
    
} catch (PDOException $e) {
    // Log error
    error_log('Database Connection Error: ' . $e->getMessage());
    
    // Output error message
    http_response_code(500);
    die('Database connection failed. Please try again later.');
}
?>
