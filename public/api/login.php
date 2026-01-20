<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// Log all login attempts
error_log("=== LOGIN REQUEST ===");
error_log("POST data: " . json_encode($_POST));
error_log("User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A'));

$pdo = require __DIR__ . '/../../src/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$username = $_POST['username'] ?? '';
$nisn = $_POST['nisn'] ?? '';
$password = $_POST['password'] ?? '';
$user_type = $_POST['user_type'] ?? '';

// Determine if login is for student or school admin
if ($user_type === 'student') {
    error_log("Student login attempt");

    // Student login with NISN + Password
    if (empty($nisn) || empty($password)) {
        error_log("Student login failed: NISN atau password kosong");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'NISN dan password harus diisi']);
        exit;
    }

    error_log("Checking NISN: $nisn");

    try {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE nisn = :nisn AND role = :role LIMIT 1');
        $stmt->execute(['nisn' => $nisn, 'role' => 'student']);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        error_log("Query result: " . ($user ? "User found (ID: " . $user['id'] . ")" : "User NOT found"));

        if (!$user) {
            error_log("LOGIN FAILED: NISN '$nisn' tidak ditemukan atau role bukan student");
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'NISN atau password salah']);
            exit;
        }

        error_log("User found. Verifying password...");
        error_log("Password from input: '$password'");
        error_log("Hash from DB: " . $user['password']);

        if (!password_verify($password, $user['password'])) {
            error_log("LOGIN FAILED: Password tidak match untuk NISN '$nisn'");
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'NISN atau password salah']);
            exit;
        }

        error_log("Password verified successfully!");

        $_SESSION['user'] = [
            'id' => $user['id'],
            'school_id' => $user['school_id'],
            'name' => $user['name'],
            'role' => $user['role'],
            'nisn' => $user['nisn']
        ];

        error_log("LOGIN SUCCESS: NISN '$nisn' logged in successfully");

        echo json_encode([
            'success' => true,
            'message' => 'Login berhasil',
            'redirect_url' => 'student-dashboard.php'
        ]);
        exit;
    } catch (Exception $e) {
        error_log("LOGIN ERROR: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server']);
        exit;
    }
} else {
    // School admin login with email + password
    error_log("Admin login attempt");

    $email = $_POST['email'] ?? '';

    if (empty($email) || empty($password)) {
        error_log("Admin login failed: Email atau password kosong");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email dan password harus diisi']);
        exit;
    }

    error_log("Checking email: $email");

    try {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        error_log("Query result: " . ($user ? "User found (ID: " . $user['id'] . ")" : "User NOT found"));

        if (!$user) {
            error_log("LOGIN FAILED: Email '$email' tidak ditemukan");
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Email atau password salah']);
            exit;
        }

        if (!password_verify($password, $user['password'])) {
            error_log("LOGIN FAILED: Password tidak match untuk email '$email'");
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Email atau password salah']);
            exit;
        }

        $_SESSION['user'] = [
            'id' => $user['id'],
            'school_id' => $user['school_id'],
            'name' => $user['name'],
            'role' => $user['role']
        ];

        error_log("LOGIN SUCCESS: Email '$email' logged in successfully");

        // Determine redirect URL based on role
        $redirect_url = 'index.php';

        echo json_encode([
            'success' => true,
            'message' => 'Login berhasil',
            'redirect_url' => $redirect_url
        ]);
        exit;
    } catch (Exception $e) {
        error_log("LOGIN ERROR: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server']);
        exit;
    }
}
?>