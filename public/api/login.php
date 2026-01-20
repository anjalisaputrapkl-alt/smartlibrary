<?php
session_start();
header('Content-Type: application/json');

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
    // Student login with NISN + Password
    if (empty($nisn) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'NISN dan password harus diisi']);
        exit;
    }

    try {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE nisn = :nisn AND role = :role LIMIT 1');
        $stmt->execute(['nisn' => $nisn, 'role' => 'student']);
        $user = $stmt->fetch();

        if (!$user) {
            // Log untuk debugging
            error_log("LOGIN FAILED: NISN '$nisn' tidak ditemukan atau role bukan student");
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'NISN atau password salah']);
            exit;
        }

        if (!password_verify($password, $user['password'])) {
            // Log untuk debugging
            error_log("LOGIN FAILED: Password tidak match untuk NISN '$nisn'");
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'NISN atau password salah']);
            exit;
        }

        $_SESSION['user'] = [
            'id' => $user['id'],
            'school_id' => $user['school_id'],
            'name' => $user['name'],
            'role' => $user['role'],
            'nisn' => $user['nisn']
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Login berhasil',
            'redirect_url' => 'student-dashboard.php'
        ]);
        exit;
    } catch (Exception $e) {
        error_log("LOGIN ERROR: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server: ' . $e->getMessage()]);
        exit;
    }
} else {
    // School admin login with email + password
    $email = $_POST['email'] ?? '';

    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email dan password harus diisi']);
        exit;
    }

    try {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'school_id' => $user['school_id'],
                'name' => $user['name'],
                'role' => $user['role']
            ];

            // Determine redirect URL based on role
            $redirect_url = 'index.php';
            if ($user['role'] === 'admin') {
                $redirect_url = 'index.php';
            }

            echo json_encode([
                'success' => true,
                'message' => 'Login berhasil',
                'redirect_url' => $redirect_url
            ]);
            exit;
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Email atau password salah']);
            exit;
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server']);
        exit;
    }
}

