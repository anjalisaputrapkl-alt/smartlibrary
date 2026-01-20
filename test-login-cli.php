<?php
// Test API Login secara langsung dari command line
// Usage: php test-login-cli.php NISN PASSWORD

if (php_sapi_name() !== 'cli') {
    die("This script must be run from command line");
}

if ($argc < 3) {
    echo "Usage: php test-login-cli.php NISN PASSWORD\n";
    echo "Example: php test-login-cli.php 1234567890 1234567890\n";
    exit(1);
}

$nisn = $argv[1];
$password = $argv[2];

echo "=== TEST LOGIN SISWA ===\n";
echo "NISN: $nisn\n";
echo "Password: " . str_repeat('*', strlen($password)) . "\n";
echo "\n";

// Load database
require_once __DIR__ . '/src/db.php';
$pdo = require __DIR__ . '/src/db.php';

// 1. Check if NISN exists
echo "1️⃣  Checking NISN in database...\n";
$stmt = $pdo->prepare('SELECT id, name, email, role, password, nisn FROM users WHERE nisn = :nisn');
$stmt->execute(['nisn' => $nisn]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "❌ NISN '$nisn' NOT FOUND in database!\n";
    echo "\n📊 All students in database:\n";
    $stmt = $pdo->query('SELECT id, nisn, name, email, role FROM users WHERE role = "student" ORDER BY id');
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($students)) {
        echo "   (No students found)\n";
    } else {
        printf("   %-5s %-15s %-30s %-25s %-10s\n", "ID", "NISN", "Name", "Email", "Role");
        printf("   %s\n", str_repeat("-", 85));
        foreach ($students as $s) {
            printf("   %-5s %-15s %-30s %-25s %-10s\n", 
                $s['id'], 
                $s['nisn'] ?? '(NULL)', 
                substr($s['name'], 0, 28),
                substr($s['email'], 0, 23),
                $s['role']
            );
        }
    }
    exit(1);
}

echo "✅ NISN found!\n";
echo "   ID: " . $user['id'] . "\n";
echo "   Name: " . $user['name'] . "\n";
echo "   Email: " . $user['email'] . "\n";
echo "   Role: " . $user['role'] . "\n";
echo "   Password Hash: " . substr($user['password'], 0, 20) . "...\n";
echo "\n";

// 2. Check role
echo "2️⃣  Checking role...\n";
if ($user['role'] !== 'student') {
    echo "❌ Role is '{$user['role']}', not 'student'!\n";
    exit(1);
}
echo "✅ Role is 'student'\n\n";

// 3. Verify password
echo "3️⃣  Verifying password...\n";
echo "   Input password: $password\n";
echo "   DB hash: " . substr($user['password'], 0, 30) . "...\n";

$verify = password_verify($password, $user['password']);

if (!$verify) {
    echo "❌ Password does NOT match!\n";
    echo "\n💡 Hint: Password should match NISN ($nisn)\n";
    echo "   Try with password: $nisn\n";
    
    // Test with NISN as password
    $verify_nisn = password_verify($nisn, $user['password']);
    if ($verify_nisn) {
        echo "   ✅ This works! Use password: $nisn\n";
    }
    exit(1);
}

echo "✅ Password verified successfully!\n\n";

// 4. Summary
echo "=== RESULT ===\n";
echo "✅ Login would SUCCEED\n";
echo "   NISN: $nisn\n";
echo "   Password: " . str_repeat('*', strlen($password)) . "\n";
echo "   User: " . $user['name'] . "\n";
echo "   School ID: " . $user['school_id'] . "\n";
echo "\n";
echo "📱 Student dapat login di halaman utama\n";

?>
