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
        SELECT m.id, m.name, m.nisn, MAX(br.borrowed_at) as last_borrow, COUNT(br.id) as total_borrows
        FROM members m
        JOIN borrows br ON m.id = br.member_id
        JOIN books b ON br.book_id = b.id
        WHERE m.school_id = ? 
          AND br.borrowed_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY)
        GROUP BY m.id
        ORDER BY last_borrow DESC
    ");
    $stmt->execute([$schoolId]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $data]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
