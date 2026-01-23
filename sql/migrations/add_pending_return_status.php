<?php
/**
 * Migration: Add pending_return status to borrows table
 * 
 * Current enum: 'borrowed','returned','overdue'
 * New enum: 'borrowed','returned','overdue','pending_return'
 */

$config = require __DIR__ . '/../../src/config.php';

try {
    $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Alter table to add pending_return to enum
    $sql = "ALTER TABLE `borrows` CHANGE `status` `status` ENUM('borrowed','returned','overdue','pending_return') DEFAULT 'borrowed'";

    echo "Executing migration...\n";
    $pdo->exec($sql);

    echo "✓ Successfully added 'pending_return' status to borrows table\n";
    echo "New enum values: 'borrowed', 'returned', 'overdue', 'pending_return'\n";

} catch (PDOException $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>