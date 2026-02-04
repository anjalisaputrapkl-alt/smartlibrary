<?php
require_once __DIR__ . '/../../src/db.php';
require __DIR__ . '/../../src/auth.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$schoolId = (int)$_SESSION['user']['school_id'];
$type = $_GET['type'] ?? 'members';

try {
    if ($type === 'books') {
        $stmt = $pdo->prepare("SELECT id, title, author, isbn, created_at FROM books WHERE school_id = ? AND created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY) ORDER BY created_at DESC");
    } else {
        $stmt = $pdo->prepare("SELECT id, name, nisn, created_at FROM members WHERE school_id = ? AND created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY) ORDER BY created_at DESC");
    }
    
    $stmt->execute([$schoolId]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $data]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
