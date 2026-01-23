<?php
$pdo = require 'src/db.php';

$all = $pdo->query('SELECT COUNT(*) as total FROM books WHERE school_id = 9')->fetch();
$withCopies = $pdo->query('SELECT COUNT(*) as total FROM books WHERE school_id = 9 AND copies > 0')->fetch();

echo "Total books: " . $all['total'] . "\n";
echo "Books with copies > 0: " . $withCopies['total'] . "\n";

// Show books
$books = $pdo->query('SELECT id, title, copies FROM books WHERE school_id = 9 LIMIT 15')->fetchAll();
echo "\n=== BOOKS ===\n";
foreach ($books as $b) {
    echo "ID: {$b['id']}, Title: {$b['title']}, Copies: {$b['copies']}\n";
}
?>