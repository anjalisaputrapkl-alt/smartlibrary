<?php
session_start();
header('Content-Type: application/json');

// Only GET or POST method allowed
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$pdo = require __DIR__ . '/../../src/db.php';

try {
    // Get session_id from query string or POST data
    $session_id = (int) ($_GET['session_id'] ?? $_POST['session_id'] ?? 0);

    if (!$session_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Session ID is required']);
        exit;
    }

    // Get session data
    $stmt = $pdo->prepare(
        'SELECT id, school_id, member_id, member_barcode, books_scanned, status, due_date, updated_at FROM barcode_sessions 
         WHERE id = :id'
    );
    $stmt->execute(['id' => $session_id]);
    $session = $stmt->fetch();

    if (!$session) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Session not found']);
        exit;
    }

    // Parse books_scanned JSON
    $scannedBooks = json_decode($session['books_scanned'] ?? '[]', true);

    // Get member info if scanned
    $memberInfo = null;
    if ($session['member_id']) {
        $memberStmt = $pdo->prepare(
            'SELECT id, name, nisn FROM members WHERE id = :id'
        );
        $memberStmt->execute(['id' => $session['member_id']]);
        $memberInfo = $memberStmt->fetch();
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'session_id' => $session['id'],
            'school_id' => $session['school_id'],
            'status' => $session['status'],
            'member' => $memberInfo ? [
                'id' => $memberInfo['id'],
                'name' => $memberInfo['name'],
                'nisn' => $memberInfo['nisn']
            ] : null,
            'books_scanned' => $scannedBooks,
            'books_count' => count($scannedBooks),
            'due_date' => $session['due_date'],
            'updated_at' => $session['updated_at']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
}
