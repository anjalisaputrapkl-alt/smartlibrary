<?php
header('Content-Type: application/json');
session_start();

$pdo = require __DIR__ . '/../../src/db.php';

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan.']);
    exit;
}

$user = $_SESSION['user'];
$book_id = $_POST['book_id'] ?? null;
$rating = $_POST['rating'] ?? null;
$comment = $_POST['comment'] ?? '';

if (!$book_id || !$rating || empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
    exit;
}

// Check if user has already rated this book (optional, but good practice)
/*
$checkStmt = $pdo->prepare('SELECT id_rating FROM rating_buku WHERE id_user = :user_id AND id_buku = :book_id');
$checkStmt->execute(['user_id' => $user['id'], 'book_id' => $book_id]);
if ($checkStmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Anda sudah memberikan ulasan untuk buku ini.']);
    exit;
}
*/

try {
    $stmt = $pdo->prepare('
        INSERT INTO rating_buku (id_user, id_buku, rating, komentar, created_at)
        VALUES (:id_user, :id_buku, :rating, :komentar, NOW())
    ');
    
    $stmt->execute([
        'id_user' => $user['id'],
        'id_buku' => $book_id,
        'rating' => (int)$rating,
        'komentar' => $comment
    ]);

    echo json_encode(['success' => true, 'message' => 'Ulasan Anda berhasil dikirim!']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Gagal mengirim ulasan: ' . $e->getMessage()]);
}
