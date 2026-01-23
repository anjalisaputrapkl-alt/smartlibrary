<?php
$pdo = require 'src/db.php';

// Check books table structure
echo "=== BOOKS TABLE STRUCTURE ===\n";
$result = $pdo->query('DESCRIBE books')->fetchAll(PDO::FETCH_ASSOC);
print_r($result);

// Check sample book data
echo "\n=== SAMPLE BOOKS DATA ===\n";
$books = $pdo->query('SELECT id, title, cover_image FROM books LIMIT 3')->fetchAll(PDO::FETCH_ASSOC);
print_r($books);
?>