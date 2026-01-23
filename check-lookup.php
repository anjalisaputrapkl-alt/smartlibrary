<?php
$pdo = require 'src/db.php';

// Simulasi session
$_SESSION['user'] = [
    'id' => 1,
    'school_id' => 9,
    'nisn' => '111111'  // Cek ini
];

$user = $_SESSION['user'];

// Try lookup by NISN
echo "=== Looking up member by NISN ===\n";
$stmt = $pdo->prepare('SELECT id FROM members WHERE nisn = :nisn AND school_id = :school_id');
$stmt->execute([
    'nisn' => $user['nisn'] ?? null,
    'school_id' => $user['school_id']
]);
$member = $stmt->fetch();
echo "Member found by NISN: ";
print_r($member);

// Check session user data structure
echo "\n=== Current User Session ===\n";
print_r($user);

// Check users table with ID 1
echo "\n=== Check users table ===\n";
$userRecord = $pdo->query('SELECT * FROM users WHERE school_id = 9 LIMIT 2')->fetchAll();
print_r($userRecord);
?>