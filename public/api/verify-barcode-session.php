<?php
session_start();
header('Content-Type: application/json');

// Only POST method allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$pdo = require __DIR__ . '/../../src/db.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $token = $data['token'] ?? null;

    if (!$token) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Token is required']);
        exit;
    }

    // Validate session token
    $stmt = $pdo->prepare(
        'SELECT id, school_id, status, expires_at FROM barcode_sessions 
         WHERE session_token = :token'
    );
    $stmt->execute(['token' => $token]);
    $session = $stmt->fetch();

    if (!$session) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Session not found']);
        exit;
    }

    // Check if session is expired
    $expiry = strtotime($session['expires_at']);
    if ($expiry < time()) {
        // Update session status to expired
        $updateStmt = $pdo->prepare(
            'UPDATE barcode_sessions SET status = "expired" WHERE id = :id'
        );
        $updateStmt->execute(['id' => $session['id']]);

        http_response_code(410);
        echo json_encode(['success' => false, 'message' => 'Session expired']);
        exit;
    }

    // Check if session is already completed
    if ($session['status'] === 'completed') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Session already completed']);
        exit;
    }

    // Store session data in client for use throughout scanning
    $_SESSION['barcode_session_id'] = $session['id'];
    $_SESSION['barcode_school_id'] = $session['school_id'];

    echo json_encode([
        'success' => true,
        'message' => 'Session verified',
        'data' => [
            'session_id' => $session['id'],
            'school_id' => $session['school_id']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
}
