<?php
// Direct API test - simulate login request
session_start();

if (php_sapi_name() === 'cli') {
    // CLI mode
    if ($argc < 3) {
        die("Usage: php test-api-direct.php NISN PASSWORD\n");
    }
    $nisn = $argv[1];
    $password = $argv[2];
    $user_type = 'student';
} else {
    // Web mode - check GET params
    $nisn = $_GET['nisn'] ?? '';
    $password = $_GET['password'] ?? '';
    $user_type = $_GET['user_type'] ?? 'student';
    
    header('Content-Type: application/json');
}

// Simulate POST request to login API
$pdo = require __DIR__ . '/src/db.php';

$response = ['success' => false, 'message' => ''];

if ($user_type === 'student') {
    if (empty($nisn) || empty($password)) {
        $response['message'] = 'NISN dan password harus diisi';
        if (php_sapi_name() === 'cli') {
            echo json_encode($response, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo json_encode($response);
        }
        exit;
    }

    try {
        error_log("TEST: Checking NISN: $nisn");
        
        $stmt = $pdo->prepare('SELECT * FROM users WHERE nisn = :nisn AND role = :role LIMIT 1');
        $stmt->execute(['nisn' => $nisn, 'role' => 'student']);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            error_log("TEST: NISN '$nisn' tidak ditemukan");
            $response['message'] = 'NISN atau password salah';
            if (php_sapi_name() === 'cli') {
                echo json_encode($response, JSON_PRETTY_PRINT) . "\n";
            } else {
                echo json_encode($response);
            }
            exit;
        }

        error_log("TEST: User found. Checking password");

        if (!password_verify($password, $user['password'])) {
            error_log("TEST: Password tidak match");
            $response['message'] = 'NISN atau password salah';
            if (php_sapi_name() === 'cli') {
                echo json_encode($response, JSON_PRETTY_PRINT) . "\n";
            } else {
                echo json_encode($response);
            }
            exit;
        }

        error_log("TEST: Login successful");

        $_SESSION['user'] = [
            'id' => $user['id'],
            'school_id' => $user['school_id'],
            'name' => $user['name'],
            'role' => $user['role'],
            'nisn' => $user['nisn']
        ];

        $response['success'] = true;
        $response['message'] = 'Login berhasil';
        $response['redirect_url'] = 'student-dashboard.php';
        $response['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'nisn' => $user['nisn'],
            'school_id' => $user['school_id']
        ];

        if (php_sapi_name() === 'cli') {
            echo json_encode($response, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo json_encode($response);
        }
        exit;

    } catch (Exception $e) {
        error_log("TEST: Error: " . $e->getMessage());
        $response['message'] = 'Terjadi kesalahan: ' . $e->getMessage();
        if (php_sapi_name() === 'cli') {
            echo json_encode($response, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo json_encode($response);
        }
        exit;
    }
}
?>
