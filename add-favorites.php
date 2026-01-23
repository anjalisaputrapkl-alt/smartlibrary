<?php
$pdo = require 'src/db.php';
require_once 'src/FavoriteModel.php';

$studentId = 15;  // User ID

$model = new FavoriteModel($pdo);

// Get all books
$books = $pdo->prepare(
    'SELECT id, title, category FROM books WHERE school_id = 9 AND copies > 0 LIMIT 10'
);
$books->execute();
$allBooks = $books->fetchAll();

echo "=== ADDING FAVORITES ===\n";
foreach ($allBooks as $book) {
    try {
        $model->addFavorite($studentId, $book['id'], $book['category']);
        echo "✓ Added: {$book['title']}\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'sudah ada') !== false) {
            echo "~ Already added: {$book['title']}\n";
        } else {
            echo "✗ Error: {$e->getMessage()}\n";
        }
    }
}

// Show final favorites
echo "\n=== FINAL FAVORITES ===\n";
$favorites = $model->getFavorites($studentId);
echo "Total: " . count($favorites) . " buku\n";
foreach ($favorites as $fav) {
    echo "- {$fav['judul']}\n";
}
?>