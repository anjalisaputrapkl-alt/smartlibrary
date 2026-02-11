<?php
require __DIR__ . '/../src/db.php';

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM schools");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns in schools table:\n";
    print_r($columns);

    $stmt = $pdo->query("SHOW COLUMNS FROM books");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns in books table:\n";
    print_r($columns);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
