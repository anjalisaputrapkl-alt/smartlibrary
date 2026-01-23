<?php
$pdo = require 'src/db.php';

// Check all books
$books = $pdo->query('SELECT id, title, copies FROM books WHERE school_id = 9 ORDER BY id')->fetchAll();

echo "Total books: " . count($books) . "\n";
echo "=== SEMUA BUKU ===\n";
foreach ($books as $b) {
    echo "ID: {$b['id']}, Title: {$b['title']}, Copies: {$b['copies']}\n";
}
?>