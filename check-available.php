<?php
session_start();
$pdo = require 'src/db.php';
require_once 'src/FavoriteModel.php';

$_SESSION['user'] = ['id' => 15, 'school_id' => 9, 'nisn' => '111111'];
$studentId = $_SESSION['user']['id'];

$model = new FavoriteModel($pdo);
$favorites = $model->getFavorites($studentId);

echo "=== USER FAVORITES ===\n";
foreach ($favorites as $fav) {
    echo "Book ID: {$fav['id_buku']}, Title: {$fav['judul']}\n";
}

// Check books that user hasn't borrowed
echo "\n=== BOOKS AVAILABLE TO BORROW ===\n";
$available = $pdo->prepare(
    'SELECT b.id, b.title FROM books b
     WHERE b.school_id = :school_id
     AND b.copies > 0
     AND b.id NOT IN (
        SELECT book_id FROM borrows 
        WHERE member_id = 13 AND school_id = 9 AND (status = "borrowed" OR status = "overdue")
     )
     LIMIT 5'
);
$available->execute(['school_id' => 9]);
$books = $available->fetchAll();

foreach ($books as $book) {
    echo "Book ID: {$book['id']}, Title: {$book['title']}\n";
}
?>