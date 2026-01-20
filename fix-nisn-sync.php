<?php
// Fix Migration - Sync NISN dari members ke users yang sudah ada

$pdo = require __DIR__ . '/src/db.php';

echo "═══════════════════════════════════════════════════════════\n";
echo "FIX MIGRATION: SYNC NISN DARI MEMBERS KE USERS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

try {
    // 1. Cek data di tabel members yang tidak punya NISN di users
    echo "1️⃣ Mencari siswa yang belum tersync...\n";
    $stmt = $pdo->prepare("
        SELECT m.id, m.name, m.email, m.nisn, u.id as user_id
        FROM members m
        LEFT JOIN users u ON m.email = u.email AND u.role = 'student'
        WHERE m.nisn IS NOT NULL
    ");
    $stmt->execute();
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($members) === 0) {
        echo "   ℹ Tidak ada data members dengan NISN.\n\n";
    } else {
        echo "   Ditemukan " . count($members) . " member(s):\n\n";
        
        foreach ($members as $member) {
            echo "   Nama: {$member['name']}\n";
            echo "   Email: {$member['email']}\n";
            echo "   NISN: {$member['nisn']}\n";
            
            if (empty($member['user_id'])) {
                echo "   Status: ⚠️ BELUM ADA AKUN - akan dibuat sekarang\n\n";
                
                // Buat akun baru jika belum ada
                $nisn = $member['nisn'];
                $default_password = password_hash($nisn, PASSWORD_BCRYPT);
                
                $insertStmt = $pdo->prepare("
                    INSERT INTO users (school_id, name, email, password, role, nisn)
                    SELECT school_id, name, email, :password, 'student', nisn
                    FROM members
                    WHERE id = :mid
                    ON DUPLICATE KEY UPDATE 
                        nisn = VALUES(nisn),
                        password = VALUES(password),
                        role = 'student'
                ");
                $insertStmt->execute(['mid' => $member['id'], 'password' => $default_password]);
                
                echo "   ✅ Akun dibuat dengan NISN: $nisn, Password: $nisn\n\n";
            } else {
                echo "   Status: ✅ Akun sudah ada (ID: {$member['user_id']})\n\n";
                
                // Update NISN di users jika belum ada
                $updateStmt = $pdo->prepare("
                    UPDATE users 
                    SET nisn = :nisn
                    WHERE id = :uid AND nisn IS NULL
                ");
                $updateStmt->execute(['nisn' => $member['nisn'], 'uid' => $member['user_id']]);
                
                if ($updateStmt->rowCount() > 0) {
                    echo "   ✅ NISN berhasil di-update\n\n";
                }
            }
        }
    }
    
    // 2. Cek data siswa yang sudah ada
    echo "\n2️⃣ VERIFIKASI DATA SISWA DI TABEL USERS:\n";
    $stmt = $pdo->prepare("SELECT id, name, email, nisn, role FROM users WHERE role = 'student' ORDER BY id");
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($students) === 0) {
        echo "   ⚠️ TIDAK ADA SISWA! Silakan tambahkan siswa di admin panel.\n";
    } else {
        echo "   Total: " . count($students) . " siswa\n\n";
        foreach ($students as $student) {
            $nisn_status = empty($student['nisn']) ? "⚠️ KOSONG" : "✅ " . $student['nisn'];
            echo "   [{$student['id']}] {$student['name']} | Email: {$student['email']} | NISN: $nisn_status\n";
        }
    }
    
    echo "\n═══════════════════════════════════════════════════════════\n";
    echo "✅ MIGRATION SELESAI!\n";
    echo "═══════════════════════════════════════════════════════════\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
