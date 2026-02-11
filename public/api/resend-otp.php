<?php
header('Content-Type: application/json');

try {
    $pdo = require __DIR__ . '/../../src/db.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';

if (empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email harus diisi']);
    exit;
}

try {
    // Get user by email
    $stmt = $pdo->prepare('SELECT id, is_verified FROM users WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User tidak ditemukan']);
        exit;
    }

    if ($user['is_verified']) {
        echo json_encode([
            'success' => false,
            'message' => 'User sudah terverifikasi'
        ]);
        exit;
    }

    // Generate new verification code
    $verification_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    // Update user with new code
    $stmt = $pdo->prepare('UPDATE users SET verification_code = :code, code_expires_at = :expires WHERE id = :id');
    $stmt->execute([
        'code' => $verification_code,
        'expires' => $expires_at,
        'id' => $user['id']
    ]);

    // In production, send email here
    // For demo, return the code
    echo json_encode([
        'success' => true,
        'message' => 'Kode OTP baru telah dikirim',
        'verification_code' => $verification_code // For demo only
    ]);

} catch (Exception $e) {
    error_log("Resend OTP Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
}
