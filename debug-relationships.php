<?php
$pdo = require 'src/db.php';

echo "=== CHECKING TABLE STRUCTURE ===\n\n";

echo "=== USERS TABLE ===\n";
$result = $pdo->query('DESCRIBE users');
foreach ($result as $col) {
    echo $col['Field'] . " (" . $col['Type'] . ")\n";
}

echo "\n=== MEMBERS TABLE ===\n";
$result = $pdo->query('DESCRIBE members');
foreach ($result as $col) {
    echo $col['Field'] . " (" . $col['Type'] . ")\n";
}

echo "\n=== SISWA TABLE ===\n";
$result = $pdo->query('DESCRIBE siswa');
foreach ($result as $col) {
    echo $col['Field'] . " (" . $col['Type'] . ")\n";
}

echo "\n\n=== SAMPLE DATA ===\n";
echo "\n--- USERS (for login) ---\n";
$result = $pdo->query('SELECT id, name, email, school_id FROM users LIMIT 5');
foreach ($result as $row) {
    echo "ID=" . $row['id'] . " NAME=" . $row['name'] . " EMAIL=" . $row['email'] . " SCHOOL=" . $row['school_id'] . "\n";
}

echo "\n--- MEMBERS (student registration) ---\n";
$result = $pdo->query('SELECT id, name, nisn, member_no, school_id FROM members LIMIT 5');
foreach ($result as $row) {
    echo "ID=" . $row['id'] . " NAME=" . $row['name'] . " NISN=" . $row['nisn'] . " MEMBER_NO=" . $row['member_no'] . " SCHOOL=" . $row['school_id'] . "\n";
}

echo "\n--- SISWA (student profile) ---\n";
$result = $pdo->query('SELECT id_siswa, nama_lengkap, nisn, nis FROM siswa LIMIT 5');
foreach ($result as $row) {
    echo "ID=" . $row['id_siswa'] . " NAME=" . $row['nama_lengkap'] . " NISN=" . $row['nisn'] . " NIS=" . $row['nis'] . "\n";
}

echo "\n\n=== CHECKING RELATIONSHIPS ===\n";
echo "\n--- Do users have corresponding members? ---\n";
$result = $pdo->query('
    SELECT u.id, u.name, u.email, m.id as member_id, m.name as member_name, m.nisn
    FROM users u
    LEFT JOIN members m ON u.id = m.id AND u.school_id = m.school_id
    LIMIT 5
');
foreach ($result as $row) {
    echo "User ID=" . $row['id'] . " " . $row['name'] . " -> Member ID=" . ($row['member_id'] ?? 'NULL') . " NISN=" . ($row['nisn'] ?? 'NULL') . "\n";
}
