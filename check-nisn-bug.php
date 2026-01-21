<?php
$pdo = require 'src/db.php';

echo "=== CHECKING NISN BUG ===\n";
echo "\n=== MEMBERS TABLE ===\n";
$result = $pdo->query('SELECT id, name, nisn, member_no, school_id FROM members ORDER BY id LIMIT 10');
foreach ($result as $row) {
    echo "ID=" . $row['id'] . " NAME=" . $row['name'] . " NISN=" . $row['nisn'] . " MEMBER_NO=" . $row['member_no'] . " SCHOOL=" . $row['school_id'] . "\n";
}

echo "\n=== SISWA TABLE ===\n";
$result = $pdo->query('SELECT id_siswa, nama_lengkap, nisn, nis FROM siswa ORDER BY id_siswa LIMIT 10');
foreach ($result as $row) {
    echo "ID=" . $row['id_siswa'] . " NAME=" . $row['nama_lengkap'] . " NISN=" . $row['nisn'] . " NIS=" . $row['nis'] . "\n";
}

echo "\n=== CHECKING FOR MISMATCHES ===\n";
$mismatch = $pdo->query('
    SELECT m.id, m.name, m.nisn, s.id_siswa, s.nama_lengkap, s.nisn as siswa_nisn
    FROM members m
    JOIN siswa s ON m.id = s.id_siswa
    WHERE m.nisn != s.nisn
    LIMIT 10
');
if ($mismatch->rowCount() > 0) {
    echo "⚠️ FOUND MISMATCHES:\n";
    foreach ($mismatch as $row) {
        echo "Member ID=" . $row['id'] . " NISN=" . $row['nisn'] . " -> Siswa ID=" . $row['id_siswa'] . " NISN=" . $row['siswa_nisn'] . "\n";
    }
} else {
    echo "✓ No NISN mismatches found\n";
}
