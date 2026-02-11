<?php
require __DIR__ . '/src/db.php';
// Simulate student login
$school_id = 1; // Assuming school ID 1 exists
$member_id = 1; // Assuming member ID 1 exists

$query = 'SELECT bk.id, bk.title, 
          (SELECT AVG(rating) FROM rating_buku WHERE id_buku = bk.id) as avg_rating
          FROM books bk 
          WHERE bk.school_id = :sid 
          ORDER BY bk.created_at DESC LIMIT 5';

$stmt = $pdo->prepare($query);
$stmt->execute(['sid' => $school_id]);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Books found: " . count($books) . "\n";
if (count($books) > 0) {
    echo "First book: " . $books[0]['title'] . " (Rating: " . $books[0]['avg_rating'] . ")\n";
} else {
    echo "No books found (check if school_id 1 has books)\n";
}
