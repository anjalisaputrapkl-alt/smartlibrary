<?php
require __DIR__ . '/src/db.php';
$stmt = $pdo->query("DESCRIBE books");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Columns in books table:\n";
print_r($columns);
