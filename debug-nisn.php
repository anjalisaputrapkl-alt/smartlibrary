<?php
// Debug Script - Cek NISN dan Password di Database

$pdo = require __DIR__ . '/src/db.php';

echo "═══════════════════════════════════════════════════════════\n";
echo "DEBUG: CEK SISWA DI DATABASE\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// 1. Cek struktur tabel users
echo "1️⃣ STRUKTUR TABEL USERS:\n";
$descStmt = $pdo->query("DESCRIBE users");
$columns = $descStmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo "   - {$col['Field']}: {$col['Type']}\n";
}
echo "\n";

// 2. Cek struktur tabel members
echo "2️⃣ STRUKTUR TABEL MEMBERS:\n";
$descStmt = $pdo->query("DESCRIBE members");
$columns = $descStmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo "   - {$col['Field']}: {$col['Type']}\n";
}
echo "\n";

// 3. Cek data siswa
echo "3️⃣ DATA SISWA (STUDENTS) DI TABEL USERS:\n";
$stmt = $pdo->prepare("SELECT id, name, email, nisn, role FROM users WHERE role = 'student'");
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($students) === 0) {
    echo "   ⚠️ TIDAK ADA SISWA! Silakan tambahkan siswa di admin panel terlebih dahulu.\n";
} else {
    foreach ($students as $student) {
        echo "   ID: {$student['id']}, Nama: {$student['name']}\n";
        echo "   Email: {$student['email']}\n";
        echo "   NISN: {$student['nisn']}\n";
        echo "   Role: {$student['role']}\n";
        echo "   ---\n";
    }
}
echo "\n";

// 4. Test login manual
echo "4️⃣ TEST LOGIN MANUAL:\n";
$test_nisn = $_GET['test_nisn'] ?? '1234567890';
$test_password = $_GET['test_password'] ?? '1234567890';

echo "   NISN Test: $test_nisn\n";
echo "   Password Test: $test_password\n\n";

// Cek apakah siswa dengan NISN ini ada
$stmt = $pdo->prepare("SELECT id, name, password FROM users WHERE nisn = :nisn AND role = 'student' LIMIT 1");
$stmt->execute(['nisn' => $test_nisn]);
$user = $stmt->fetch();

if (!$user) {
    echo "   ❌ ERROR: NISN '$test_nisn' TIDAK DITEMUKAN di database!\n";
    echo "      Silakan pastikan NISN yang dimasukkan benar.\n";
} else {
    echo "   ✅ NISN ditemukan!\n";
    echo "   Nama Siswa: {$user['name']}\n";
    echo "   Password Hash: {$user['password']}\n\n";

    // Cek password
    if (password_verify($test_password, $user['password'])) {
        echo "   ✅ PASSWORD BENAR! Login harus berhasil.\n";
    } else {
        echo "   ❌ PASSWORD SALAH!\n";
        echo "   Password yang diinput tidak cocok dengan hash.\n";

        // Debug: coba hash password yang diinput
        $new_hash = password_hash($test_password, PASSWORD_BCRYPT);
        echo "\n   DEBUG - Hash password '$test_password': $new_hash\n";
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "CARA PENGGUNAAN:\n";
echo "   php debug-nisn.php?test_nisn=NISN_SISWA&test_password=PASSWORD\n";
echo "   Contoh: php debug-nisn.php?test_nisn=1234567890&test_password=1234567890\n";
echo "═══════════════════════════════════════════════════════════\n";
