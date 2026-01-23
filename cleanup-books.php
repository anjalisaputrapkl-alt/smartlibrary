<?php
$pdo = require 'src/db.php';

// Delete duplicate books (keep only original Madilog ID 29)
$delete = $pdo->prepare('DELETE FROM books WHERE school_id = 9 AND id > 29');
$delete->execute();

echo "Deleted duplicate books\n\n";

// Delete old favorite records for these deleted books
$deleteFav = $pdo->prepare('DELETE FROM favorites WHERE book_id > 29');
$deleteFav->execute();

echo "Deleted old favorite records\n\n";

// Show remaining books
$books = $pdo->query('SELECT id, title, copies FROM books WHERE school_id = 9')->fetchAll();
echo "Remaining books: " . count($books) . "\n";
foreach ($books as $b) {
    echo "ID: {$b['id']}, {$b['title']}, Copies: {$b['copies']}\n";
}
?>