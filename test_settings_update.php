<?php
require __DIR__ . '/src/db.php';
session_start();
// Simulate admin login
$stmt = $pdo->query("SELECT id, school_id, role FROM users WHERE role='admin' LIMIT 1");
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) die("No admin user found");

$_SESSION['user'] = $user;

// Set POST data
$_POST['action'] = 'update_borrows';
$_POST['borrow_duration'] = 4;
$_POST['late_fine'] = 500;
$_POST['max_books'] = 3;
$_POST['school_npsn'] = '12345'; // dummy
$_SERVER['REQUEST_METHOD'] = 'POST';

// Include settings.php to run the logic
// Output buffering removed
require __DIR__ . '/public/settings.php';

// Check result
$stmt = $pdo->prepare("SELECT borrow_duration FROM schools WHERE id = ?");
$stmt->execute([$user['school_id']]);
$new_duration = $stmt->fetchColumn();

echo "New borrow_duration in DB: " . $new_duration . "\n";
