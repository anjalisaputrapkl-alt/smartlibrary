<?php
/**
 * Endpoint untuk verifikasi email dan langsung login (gabungan)
 * Digunakan setelah verifikasi berhasil untuk auto-login
 */
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$user_id = intval($_POST['user_id'] ?? 0);
$verification_code = trim($_POST['verification_code'] ?? '');

if (!$user_id || !$verification_code) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    $pdo = require __DIR__ . '/../../src/db.php';

    // Get user dengan verification_code dan check expiration
    $stmt = $pdo->prepare(
        'SELECT id, school_id, name, email, role, is_verified, code_expires_at
         FROM users WHERE id = :user_id AND verification_code = :code'
    );
    $stmt->execute([
        'user_id' => $user_id,
        'code' => $verification_code
    ]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Kode verifikasi tidak valid']);
        exit;
    }

    // Check if already verified
    if ($user['is_verified']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email sudah terverifikasi sebelumnya']);
        exit;
    }

    // Check expiration
    $now = new DateTime();
    $expires = new DateTime($user['code_expires_at']);
    if ($now > $expires) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Kode verifikasi telah kadaluarsa'
        ]);
        exit;
    }

    // Update user menjadi verified
    $stmt = $pdo->prepare(
        'UPDATE users 
         SET is_verified = 1, 
             verified_at = NOW(), 
             verification_code = NULL,
             code_expires_at = NULL
         WHERE id = :user_id'
    );
    $stmt->execute(['user_id' => $user_id]);

    // Get fresh user data
    $stmt = $pdo->prepare(
        'SELECT id, school_id, name, email, role, is_verified 
         FROM users WHERE id = :user_id'
    );
    $stmt->execute(['user_id' => $user_id]);
    $verified_user = $stmt->fetch();

    // Set session immediately
    $_SESSION['user'] = [
        'id' => $verified_user['id'],
        'school_id' => $verified_user['school_id'],
        'name' => $verified_user['name'],
        'email' => $verified_user['email'],
        'role' => $verified_user['role'],
        'is_verified' => $verified_user['is_verified']
    ];

    // Make sure session is saved
    session_write_close();

    // Return success with dashboard redirect
    echo json_encode([
        'success' => true,
        'message' => 'Email verified successfully!',
        'redirect_url' => 'index.php'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
