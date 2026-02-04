<?php
require __DIR__ . '/../../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare(
        'SELECT b.*, bk.title, bk.isbn, m.name AS member_name, m.nisn
         FROM borrows b
         JOIN books bk ON b.book_id = bk.id
         JOIN members m ON b.member_id = m.id
         WHERE b.school_id = :sid 
         AND b.status NOT IN ("returned", "pending_return", "pending_confirmation")
         ORDER BY b.borrowed_at DESC'
    );
    $stmt->execute(['sid' => $sid]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
