<?php
require_once __DIR__ . '/../../src/db.php';
require __DIR__ . '/../../src/auth.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$schoolId = (int)$_SESSION['user']['school_id'];
$per_day = 1000;

try {
    $stmt = $pdo->prepare("
        SELECT br.id, br.borrowed_at, br.due_at, br.returned_at, 
               b.title, m.name as member_name, m.nisn
        FROM borrows br
        JOIN books b ON br.book_id = b.id
        JOIN members m ON br.member_id = m.id
        WHERE b.school_id = ? 
          AND br.due_at IS NOT NULL 
          AND (br.returned_at IS NOT NULL OR CURRENT_DATE() > br.due_at)
    ");
    $stmt->execute([$schoolId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($rows as $r) {
        $due = new DateTime($r['due_at']);
        $returned = $r['returned_at'] ? new DateTime($r['returned_at']) : new DateTime();
        $diff = (int) $due->diff($returned)->format('%r%a');
        if ($diff > 0) {
            $r['fine_amount'] = $diff * $per_day;
            $r['days_late'] = $diff;
            $data[] = $r;
        }
    }

    echo json_encode(['success' => true, 'data' => $data]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
