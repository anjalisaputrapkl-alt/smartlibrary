<?php
require __DIR__ . '/src/db.php';
$stmt = $pdo->query("DESCRIBE rating_buku");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Columns in rating_buku table:\n";
print_r($columns);
