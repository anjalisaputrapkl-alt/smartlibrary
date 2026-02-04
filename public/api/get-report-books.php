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
    $stmt = $pdo->prepare("SELECT id, title, author, isbn, copies, created_at FROM books WHERE school_id = ? ORDER BY title ASC");
    $stmt->execute([$schoolId]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $data]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
