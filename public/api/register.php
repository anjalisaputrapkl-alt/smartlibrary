<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// Catch any output before JSON
ob_start();

try {
    $pdo = require __DIR__ . '/../../src/db.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

try {
    require __DIR__ . '/../../src/EmailHelper.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'EmailHelper load failed: ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$school_name = trim($_POST['school_name'] ?? '');
$admin_name = trim($_POST['admin_name'] ?? '');
$admin_email = trim($_POST['admin_email'] ?? '');
$admin_password = $_POST['admin_password'] ?? '';

if (empty($school_name) || empty($admin_name) || empty($admin_email) || empty($admin_password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi']);
    exit;
}

if (strlen($admin_password) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter']);
    exit;
}

try {
    // Check if email already exists
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
    $stmt->execute(['email' => $admin_email]);
    if ($stmt->fetchColumn() > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email sudah terdaftar']);
        exit;
    }

    // Create school
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($school_name)));
    $stmt = $pdo->prepare('INSERT INTO schools (name, slug) VALUES (:name, :slug)');
    $stmt->execute(['name' => $school_name, 'slug' => $slug]);
    $school_id = $pdo->lastInsertId();

    // Generate verification code
    $verification_code = generateVerificationCode();
    $code_expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    // Create admin user dengan status is_verified = 0
    $password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        'INSERT INTO users (school_id, name, email, password, verification_code, code_expires_at, is_verified, role) 
         VALUES (:school_id, :name, :email, :password, :verification_code, :code_expires_at, 0, "admin")'
    );
    $stmt->execute([
        'school_id' => $school_id,
        'name' => $admin_name,
        'email' => $admin_email,
        'password' => $password_hash,
        'verification_code' => $verification_code,
        'code_expires_at' => $code_expires_at
    ]);
    $user_id = $pdo->lastInsertId();

    // Send verification email
    $email_sent = sendVerificationEmail($admin_email, $school_name, $admin_name, $verification_code);

    // Even if email fails, continue - we've logged it
    // Return user_id, email, and verification code for frontend verification modal
    echo json_encode([
        'success' => true,
        'message' => 'Pendaftaran berhasil. Silakan verifikasi email Anda.',
        'user_id' => $user_id,
        'email' => $admin_email,
        'verification_code' => $verification_code
    ]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server: ' . $e->getMessage()]);
    exit;
}
?>