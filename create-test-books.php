<?php
$pdo = require 'src/db.php';

// Get the original book
$original = $pdo->query('SELECT * FROM books WHERE id = 29')->fetch();

// Create 4 more copies of it
$titles = ['Madilog 2', 'Madilog 3', 'Madilog 4', 'Madilog 5'];

foreach ($titles as $title) {
    $insert = $pdo->prepare(
        'INSERT INTO books (school_id, title, author, category, copies, created_at)
         VALUES (?, ?, ?, ?, ?, NOW())'
    );
    $insert->execute([
        9,
        $title,
        $original['author'],
        $original['category'],
        5
    ]);
    echo "Created: $title\n";
}

// Show all books now
echo "\n=== ALL BOOKS NOW ===\n";
$books = $pdo->query('SELECT id, title, copies FROM books WHERE school_id = 9')->fetchAll();
foreach ($books as $b) {
    echo "ID: {$b['id']}, {$b['title']}, Copies: {$b['copies']}\n";
}
?>