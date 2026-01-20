<?php
// Quick check: list all students in database
$pdo = require __DIR__ . '/src/db.php';

try {
    echo "=== CHECKING STUDENTS IN DATABASE ===\n\n";

    // Check users table
    echo "1️⃣  USERS table (role = 'student'):\n";
    $stmt = $pdo->query('SELECT id, nisn, name, email, role, school_id FROM users WHERE role = "student" ORDER BY id');
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($students)) {
        echo "   ❌ NO STUDENTS FOUND\n\n";
    } else {
        echo "   Total: " . count($students) . " students\n\n";
        foreach ($students as $i => $s) {
            echo ($i + 1) . ". ID: {$s['id']}\n";
            echo "   NISN: " . ($s['nisn'] ? "{$s['nisn']}" : "⚠️  NULL") . "\n";
            echo "   Name: {$s['name']}\n";
            echo "   Email: {$s['email']}\n";
            echo "   Role: {$s['role']}\n";
            echo "   School: {$s['school_id']}\n";
            echo "\n";
        }
    }

    // Check members table
    echo "2️⃣  MEMBERS table:\n";
    $stmt = $pdo->query('SELECT id, nisn, name, email, member_no, school_id FROM members ORDER BY id');
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($members)) {
        echo "   ❌ NO MEMBERS FOUND\n\n";
    } else {
        echo "   Total: " . count($members) . " members\n\n";
        foreach ($members as $i => $m) {
            echo ($i + 1) . ". ID: {$m['id']}\n";
            echo "   NISN: " . ($m['nisn'] ? "{$m['nisn']}" : "⚠️  NULL") . "\n";
            echo "   Name: {$m['name']}\n";
            echo "   Email: {$m['email']}\n";
            echo "   Member No: {$m['member_no']}\n";
            echo "   School: {$m['school_id']}\n";
            echo "\n";
        }
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>