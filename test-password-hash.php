<?php
header('Content-Type: application/json');

require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/config.php';

$nisn = $_GET['nisn'] ?? '';

if (empty($nisn)) {
    echo json_encode(['error' => 'NISN required']);
    exit;
}

// Hash password (password = NISN)
$hash = password_hash($nisn, PASSWORD_BCRYPT);

// Check if exists in DB
$pdo = getDatabase();
$stmt = $pdo->prepare("SELECT id, name, role, password FROM users WHERE nisn = :nisn AND role = 'student' LIMIT 1");
$stmt->execute(['nisn' => $nisn]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$response = [
    'nisn' => $nisn,
    'hash' => $hash,
    'algorithm' => 'PASSWORD_BCRYPT',
    'found_in_db' => (bool)$user,
    'name' => $user['name'] ?? null,
    'role' => $user['role'] ?? null,
    'db_hash' => $user['password'] ?? null,
    'verify_result' => $user ? password_verify($nisn, $user['password']) : false
];

echo json_encode($response);
?>
