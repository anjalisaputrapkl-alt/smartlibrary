<?php
$pdo = require 'src/db.php';

// Check members table structure
echo "=== MEMBERS TABLE STRUCTURE ===\n";
$result = $pdo->query('DESCRIBE members')->fetchAll(PDO::FETCH_ASSOC);
print_r($result);

// Check sample members data
echo "\n=== SAMPLE MEMBERS DATA ===\n";
$members = $pdo->query('SELECT * FROM members LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
print_r($members);

// Check users table structure
echo "\n=== USERS TABLE STRUCTURE ===\n";
$users_struct = $pdo->query('DESCRIBE users')->fetchAll(PDO::FETCH_ASSOC);
print_r($users_struct);

// Check relationship
echo "\n=== CHECK RELATIONSHIP ===\n";
$check = $pdo->query('SELECT id FROM members WHERE id = 1 LIMIT 1')->fetch();
echo "Member ID 1 exists: " . ($check ? "YES" : "NO") . "\n";

$check2 = $pdo->query('SELECT id FROM users WHERE id = 1 LIMIT 1')->fetch();
echo "User ID 1 exists: " . ($check2 ? "YES" : "NO") . "\n";
?>