<?php
session_start();
$pdo = require 'src/db.php';

// Set demo user dari session yang actual atau dari database
$_SESSION['user'] = [
    'id' => 15,
    'school_id' => 9,
    'nisn' => '111111'
];

$user = $_SESSION['user'];
$school_id = $user['school_id'];
$nisn = $user['nisn'];

// Lookup member_id using NISN
$memberStmt = $pdo->prepare(
    'SELECT id FROM members WHERE nisn = :nisn AND school_id = :school_id LIMIT 1'
);
$memberStmt->execute(['nisn' => $nisn, 'school_id' => $school_id]);
$member = $memberStmt->fetch();

if (!$member) {
    echo "Member tidak ditemukan untuk NISN: $nisn\n";
    exit;
}

$member_id = $member['id'];
echo "Member ID: $member_id\n";
echo "School ID: $school_id\n\n";

// Check all borrows for this member
echo "=== SEMUA PEMINJAMAN MEMBER ===\n";
$allBorrows = $pdo->prepare(
    'SELECT id, book_id, borrowed_at, status FROM borrows 
     WHERE member_id = :member_id AND school_id = :school_id
     ORDER BY borrowed_at DESC'
);
$allBorrows->execute(['member_id' => $member_id, 'school_id' => $school_id]);
$records = $allBorrows->fetchAll();

foreach ($records as $rec) {
    echo "ID: {$rec['id']}, Book: {$rec['book_id']}, Status: {$rec['status']}, Borrowed: {$rec['borrowed_at']}\n";
}

// Check for active borrows (borrowed or overdue)
echo "\n=== PEMINJAMAN AKTIF (borrowed/overdue) ===\n";
$activeBorrows = $pdo->prepare(
    'SELECT id, book_id, borrowed_at, status FROM borrows 
     WHERE member_id = :member_id AND school_id = :school_id 
     AND (status = "borrowed" OR status = "overdue")'
);
$activeBorrows->execute(['member_id' => $member_id, 'school_id' => $school_id]);
$active = $activeBorrows->fetchAll();

echo "Total aktif: " . count($active) . "\n";
foreach ($active as $rec) {
    echo "ID: {$rec['id']}, Book: {$rec['book_id']}, Status: {$rec['status']}\n";
}
?>