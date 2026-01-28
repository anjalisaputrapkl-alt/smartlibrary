<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Only POST method allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$pdo = require __DIR__ . '/../../src/db.php';

try {
    $admin = $_SESSION['user'];
    $school_id = $admin['school_id'] ?? null;

    if (!$school_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid session data']);
        exit;
    }

    // Generate unique session token
    $token = bin2hex(random_bytes(16)); // 32 character hex string

    // Create barcode session
    $stmt = $pdo->prepare(
        'INSERT INTO barcode_sessions (school_id, session_token, status, created_at, expires_at)
         VALUES (:school_id, :token, "active", NOW(), DATE_ADD(NOW(), INTERVAL 30 MINUTE))'
    );
    $stmt->execute([
        'school_id' => $school_id,
        'token' => $token
    ]);

    $session_id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Session created successfully',
        'data' => [
            'session_id' => $session_id,
            'token' => $token,
            'expires_in' => 1800 // 30 minutes in seconds
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
}
