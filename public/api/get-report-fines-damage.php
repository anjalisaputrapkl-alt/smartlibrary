<?php
require_once __DIR__ . '/../../src/db.php';
require __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/maintenance/DamageController.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$schoolId = (int)$_SESSION['user']['school_id'];
$status = $_GET['status'] ?? null;

try {
    $controller = new DamageController($pdo, $schoolId);
    $allRecords = $controller->getAll();
    
    if ($status) {
        $data = array_values(array_filter($allRecords, fn($r) => $r['status'] === $status));
    } else {
        $data = $allRecords;
    }

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
