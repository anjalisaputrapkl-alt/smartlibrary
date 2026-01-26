<?php
session_start();
header('Content-Type: application/json');

$pdo = require __DIR__ . '/../../src/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$user_id = intval($_POST['user_id'] ?? 0);

if (!$user_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID harus diisi']);
    exit;
}

try {
    // Check if user has pending verification
    $stmt = $pdo->prepare(
        'SELECT id, email, is_verified, verification_code, code_expires_at 
         FROM users WHERE id = :user_id'
    );
    $stmt->execute(['user_id' => $user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User tidak ditemukan']);
        exit;
    }

    // If already verified, no pending verification
    if ($user['is_verified']) {
        echo json_encode([
            'success' => true,
            'has_pending' => false,
            'message' => 'Email sudah terverifikasi'
        ]);
        exit;
    }

    // Check if verification code exists and not expired
    if (!$user['verification_code'] || !$user['code_expires_at']) {
        echo json_encode([
            'success' => true,
            'has_pending' => false,
            'message' => 'Tidak ada verifikasi pending'
        ]);
        exit;
    }

    // Check if code is expired
    $now = new DateTime();
    $expires = new DateTime($user['code_expires_at']);
    
    if ($now > $expires) {
        // Code expired, no pending verification
        echo json_encode([
            'success' => true,
            'has_pending' => false,
            'message' => 'Kode verifikasi sudah kadaluarsa'
        ]);
        exit;
    }

    // User has pending verification
    echo json_encode([
        'success' => true,
        'has_pending' => true,
        'user_id' => $user['id'],
        'email' => $user['email'],
        'verification_code' => $user['verification_code'],  // For development/testing
        'message' => 'Ada verifikasi email yang pending'
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan server: ' . $e->getMessage()
    ]);
    exit;
}
?>
