<?php
$pdo = require __DIR__ . '/src/db.php';
try {
    $stmt = $pdo->query("DESC members"); // assuming MySQL based on common XAMPP usage
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($columns, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    try {
        $stmt = $pdo->query("PRAGMA table_info(members)"); // fallback if it's SQLite
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($columns, JSON_PRETTY_PRINT);
    } catch (Exception $e2) {
        echo "Error: " . $e2->getMessage();
    }
}
unlink(__FILE__);
