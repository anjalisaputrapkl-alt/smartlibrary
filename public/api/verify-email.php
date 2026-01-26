<?php
session_start();
header('Content-Type: application/json');

$pdo = require __DIR__ . '/../../src/db.php';
require __DIR__ . '/../../src/EmailHelper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$action = trim($_POST['action'] ?? 'verify');
$user_id = intval($_POST['user_id'] ?? 0);
$verification_code = trim($_POST['verification_code'] ?? '');

if (!$user_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID harus diisi']);
    exit;
}

// Handle resend_code action
if ($action === 'resend_code') {
    try {
        // Get user data
        $stmt = $pdo->prepare(
            'SELECT id, email, name, school_id FROM users WHERE id = :user_id'
        );
        $stmt->execute(['user_id' => $user_id]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User tidak ditemukan']);
            exit;
        }

        // Generate new verification code
        $new_code = generateVerificationCode();
        $new_expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        // Update user with new code and expiration
        $stmt = $pdo->prepare(
            'UPDATE users SET verification_code = :code, code_expires_at = :expires WHERE id = :user_id'
        );
        $stmt->execute([
            'code' => $new_code,
            'expires' => $new_expires,
            'user_id' => $user_id
        ]);

        // Get school name for email
        $stmt = $pdo->prepare('SELECT name FROM schools WHERE id = :school_id');
        $stmt->execute(['school_id' => $user['school_id']]);
        $school = $stmt->fetch();
        $school_name = $school['name'] ?? 'Perpustakaan Digital';

        // Send verification email with new code
        $email_sent = sendVerificationEmail($user['email'], $school_name, $user['name'], $new_code);

        echo json_encode([
            'success' => true,
            'message' => 'Kode verifikasi baru telah dikirim ke email Anda',
            'verification_code' => $new_code  // For development/testing
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Terjadi kesalahan saat mengirim ulang kode: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Verify code action (default)
if (empty($verification_code)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Kode verifikasi harus diisi']);
    exit;
}

try {
    // Get user dengan verification_code dan check expiration
    $stmt = $pdo->prepare(
        'SELECT id, email, verification_code, code_expires_at, is_verified 
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

    // Cek apakah kode sudah expired
    $now = new DateTime();
    $expires = new DateTime($user['code_expires_at']);
    if ($now > $expires) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Kode verifikasi telah kadaluarsa. Silakan minta kode baru.'
        ]);
        exit;
    }

    // Update user menjadi verified
    $stmt = $pdo->prepare(
        'UPDATE users 
         SET is_verified = 1, 
             verified_at = NOW(), 
             verification_code = NULL 
         WHERE id = :user_id'
    );
    $stmt->execute(['user_id' => $user_id]);

    // Set session untuk auto login
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_type'] = 'school';

    // Get updated user info untuk response
    $stmt = $pdo->prepare(
        'SELECT id, school_id, name, email, role, is_verified 
         FROM users WHERE id = :user_id'
    );
    $stmt->execute(['user_id' => $user_id]);
    $verified_user = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'message' => 'Email berhasil diverifikasi! Anda sekarang dapat login.',
        'user' => $verified_user,
        'redirect_url' => 'index.php'
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
