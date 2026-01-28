<?php
require __DIR__ . '/src/config.php';
require __DIR__ . '/src/db.php';

// Note: PDO doesn't execute multiple statements, so we do them one by one

try {
    // Drop old table
    $pdo->exec('DROP TABLE IF EXISTS barcode_sessions');
    echo "✓ Old table dropped\n";

    // Create new table with corrected structure
    $pdo->exec('
        CREATE TABLE `barcode_sessions` (
          `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `school_id` int(11) NOT NULL,
          `session_token` varchar(32) NOT NULL UNIQUE,
          `status` enum("active","completed","expired") DEFAULT "active",
          `member_barcode` varchar(255) DEFAULT NULL,
          `member_id` int(11) DEFAULT NULL,
          `books_scanned` longtext DEFAULT NULL,
          `due_date` datetime DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          `expires_at` timestamp NOT NULL DEFAULT (current_timestamp() + interval 30 minute),
          KEY `school_id` (`school_id`),
          KEY `member_id` (`member_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');
    echo "✓ New table created with AUTO_INCREMENT\n";

    echo "\n✓✓✓ SUCCESS! barcode_sessions table has been fixed! ✓✓✓\n";
    echo "The id column now has AUTO_INCREMENT\n";
    echo "\nYou can now delete this file after confirming the fix works.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>