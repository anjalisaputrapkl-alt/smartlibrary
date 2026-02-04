<?php
require_once __DIR__ . '/../../src/db.php';
require __DIR__ . '/../../src/auth.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$schoolId = (int)$_SESSION['user']['school_id'];

try {
    $stmt = $pdo->prepare("
        SELECT br.id, br.borrowed_at, br.due_at, br.returned_at, br.status, 
               b.title, b.isbn, m.name as member_name, m.nisn
        FROM borrows br
        JOIN books b ON br.book_id = b.id
        JOIN members m ON br.member_id = m.id
        WHERE b.school_id = ? 
          AND MONTH(br.borrowed_at) = MONTH(CURRENT_DATE()) 
          AND YEAR(br.borrowed_at) = YEAR(CURRENT_DATE())
        ORDER BY br.borrowed_at DESC
    ");
    $stmt->execute([$schoolId]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $data]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
