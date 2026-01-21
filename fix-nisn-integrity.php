<?php
/**
 * FIX NISN DATA INTEGRITY
 * 
 * Fixes cases where siswa table has wrong NISN for an ID
 * by checking against members table and correcting it
 */

$pdo = require 'src/db.php';

echo "=== FIXING NISN DATA INTEGRITY ===\n\n";

// Find all mismatches
$mismatch = $pdo->query('
    SELECT 
        s.id_siswa, 
        s.nisn as siswa_nisn,
        m.nisn as member_nisn,
        s.nama_lengkap,
        m.name as member_name
    FROM siswa s
    JOIN members m ON s.id_siswa = m.id
    WHERE s.nisn != m.nisn
');

$mismatches = $mismatch->fetchAll(PDO::FETCH_ASSOC);

if (count($mismatches) == 0) {
    echo "✅ No NISN mismatches found!\n";
} else {
    echo "⚠️ Found " . count($mismatches) . " mismatches:\n\n";
    foreach ($mismatches as $mismatch) {
        echo "ID=" . $mismatch['id_siswa'] . "\n";
        echo "  Siswa NISN: " . $mismatch['siswa_nisn'] . " (" . $mismatch['nama_lengkap'] . ")\n";
        echo "  Member NISN: " . $mismatch['member_nisn'] . " (" . $mismatch['member_name'] . ")\n";
        echo "  ACTION: Update siswa.nisn to match member.nisn\n\n";
    }

    // Ask for confirmation
    echo "\n=== TO FIX ALL MISMATCHES ===\n";
    echo "Run this SQL:\n\n";

    foreach ($mismatches as $m) {
        echo "UPDATE siswa SET nisn = '" . $m['member_nisn'] . "' WHERE id_siswa = " . $m['id_siswa'] . ";\n";
    }
}

// Also check for duplicate NISN within siswa table
echo "\n\n=== CHECKING FOR DUPLICATE NISN ===\n";
$duplicates = $pdo->query('
    SELECT nisn, COUNT(*) as count, GROUP_CONCAT(id_siswa) as ids
    FROM siswa
    GROUP BY nisn
    HAVING count > 1
');

$dups = $duplicates->fetchAll(PDO::FETCH_ASSOC);
if (count($dups) == 0) {
    echo "✅ No duplicate NISN found!\n";
} else {
    echo "⚠️ Found duplicates:\n";
    foreach ($dups as $dup) {
        echo "NISN=" . $dup['nisn'] . " found in IDs: " . $dup['ids'] . "\n";
    }
}
