<?php
$pdo = require 'src/db.php';

echo "<h2>Test Registrasi Sekolah Baru</h2>";

try {
    $pdo->beginTransaction();

    // Step 1: Insert school
    echo "<p>1. Insert sekolah...</p>";
    $school_name = "Sekolah Test " . date('Y-m-d H:i:s');
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($school_name)));

    $stmt = $pdo->prepare('INSERT INTO schools (name, slug) VALUES (:name, :slug)');
    $result = $stmt->execute(['name' => $school_name, 'slug' => $slug]);

    if ($result) {
        echo "   ✓ Sekolah insert OK<br>";
    } else {
        echo "   ✗ Sekolah insert GAGAL<br>";
        print_r($stmt->errorInfo());
    }

    $school_id = $pdo->lastInsertId();
    echo "   School ID: $school_id<br>";

    // Step 2: Insert user
    echo "<p>2. Insert user admin...</p>";
    $admin_name = "Admin Test";
    $admin_email = "admin" . time() . "@test.com";
    $admin_password = password_hash("password123", PASSWORD_DEFAULT);

    $stmt = $pdo->prepare('INSERT INTO users (school_id, name, email, password, role) VALUES (:school_id, :name, :email, :password, :role)');
    $result = $stmt->execute([
        'school_id' => $school_id,
        'name' => $admin_name,
        'email' => $admin_email,
        'password' => $admin_password,
        'role' => 'admin'
    ]);

    if ($result) {
        echo "   ✓ User insert OK<br>";
    } else {
        echo "   ✗ User insert GAGAL<br>";
        print_r($stmt->errorInfo());
    }

    $user_id = $pdo->lastInsertId();
    echo "   User ID: $user_id<br>";

    // Step 3: Verify
    echo "<p>3. Verify data...</p>";
    $stmt = $pdo->prepare('SELECT * FROM schools WHERE id = :id');
    $stmt->execute(['id' => $school_id]);
    $school = $stmt->fetch();
    echo "   School: " . $school['name'] . "<br>";

    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch();
    echo "   User: " . $user['email'] . "<br>";

    $pdo->commit();
    echo "<p><strong>✓ SEMUA OK - Registrasi bisa berfungsi</strong></p>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<p><strong>✗ ERROR:</strong></p>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    echo "<p>Error Code: " . $e->getCode() . "</p>";
}
?>