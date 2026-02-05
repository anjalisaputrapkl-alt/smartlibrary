<?php
$pdo = require __DIR__ . '/src/db.php';
$stmt = $pdo->query("SHOW COLUMNS FROM schools LIKE 'borrow_duration'");
$exists = $stmt->fetch();
if ($exists) {
    echo "Columns exist.\n";
} else {
    echo "Columns do not exist. Attempting to add...\n";
    try {
        $pdo->exec("ALTER TABLE schools ADD COLUMN borrow_duration INT DEFAULT 7");
        $pdo->exec("ALTER TABLE schools ADD COLUMN late_fine DECIMAL(10,2) DEFAULT 500");
        $pdo->exec("ALTER TABLE schools ADD COLUMN max_books INT DEFAULT 3");
        echo "Successfully added columns: borrow_duration, late_fine, max_books\n";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
