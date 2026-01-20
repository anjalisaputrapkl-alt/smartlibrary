<?php

/**
 * EXAMPLE: Refactored Student Registration
 * 
 * File: public/student-register.php
 * 
 * Perubahan dari register.php (admin sekolah):
 * - Student registration memerlukan school selection + activation code
 * - Verifikasi kode aktivasi sebelum register
 * - Verifikasi sekolah status (tidak suspended)
 * - Set user.role = 'student'
 */

session_start();
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/MultiTenantManager.php';

$pdo = require __DIR__ . '/../src/db.php';
$mtManager = new MultiTenantManager($pdo);

$errors = [];
$schools = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Load daftar sekolah yang bisa diakses (tidak suspended)
    $stmt = $pdo->prepare('
        SELECT id, name FROM schools 
        WHERE status != "suspended"
        ORDER BY name ASC
    ');
    $stmt->execute();
    $schools = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_id = (int) ($_POST['school_id'] ?? 0);
    $activation_code = trim($_POST['activation_code'] ?? '');
    $nisn = trim($_POST['nisn'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validation
    if (!$school_id) {
        $errors[] = 'Pilih sekolah';
    }

    if (strlen($activation_code) < 10) {
        $errors[] = 'Kode aktivasi harus diisi dengan benar (format: XXXX-XXXX-XXXX)';
    }

    if (!$nisn) {
        $errors[] = 'NISN harus diisi';
    }

    if (!$name) {
        $errors[] = 'Nama harus diisi';
    }

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email tidak valid';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter';
    }

    if ($password !== $password_confirm) {
        $errors[] = 'Password tidak cocok';
    }

    // Check school exists
    if (empty($errors)) {
        $school = $mtManager->getSchool($school_id);
        if (!$school) {
            $errors[] = 'Sekolah tidak ditemukan';
        }
    }

    // Verify activation code
    if (empty($errors)) {
        if (!$mtManager->verifyActivationCode($school_id, $activation_code)) {
            $errors[] = 'Kode aktivasi salah atau tidak valid. Minta kode dari admin sekolah.';
        }
    }

    // Check school not suspended
    if (empty($errors)) {
        if ($mtManager->isSchoolSuspended($school_id)) {
            $errors[] = 'Sekolah ini sedang dinonaktifkan. Hubungi admin sekolah.';
        }
    }

    // Check NISN unique per school
    if (empty($errors)) {
        $stmt = $pdo->prepare('
            SELECT id FROM members 
            WHERE school_id = :school_id AND nisn = :nisn
        ');
        $stmt->execute(['school_id' => $school_id, 'nisn' => $nisn]);
        if ($stmt->fetch()) {
            $errors[] = 'NISN sudah terdaftar di sekolah ini';
        }
    }

    // Register
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Insert ke members (student profile)
            $stmt = $pdo->prepare('
                INSERT INTO members (school_id, name, email, nisn, status)
                VALUES (:school_id, :name, :email, :nisn, "active")
            ');
            $stmt->execute([
                'school_id' => $school_id,
                'name' => $name,
                'email' => $email,
                'nisn' => $nisn
            ]);
            $member_id = $pdo->lastInsertId();

            // Insert ke users (untuk login)
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('
                INSERT INTO users (school_id, name, email, nisn, password, role)
                VALUES (:school_id, :name, :email, :nisn, :password, "student")
            ');
            $stmt->execute([
                'school_id' => $school_id,
                'name' => $name,
                'email' => $email,
                'nisn' => $nisn,
                'password' => $password_hash
            ]);
            $user_id = $pdo->lastInsertId();

            // Log activity
            $mtManager->logActivity($school_id, 'student_registration', 'register', 1, $user_id);

            $pdo->commit();

            // Redirect to login success
            header('Location: /perpustakaan-online/public/login.php?student_registered=1');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }

    // Reload schools for form
    $stmt = $pdo->prepare('
        SELECT id, name FROM schools 
        WHERE status != "suspended"
        ORDER BY name ASC
    ');
    $stmt->execute();
    $schools = $stmt->fetchAll();
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Siswa - Perpustakaan Online</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: system-ui, -apple-system, sans-serif;
            background: #f5f5f5;
            color: #333;
        }

        .container {
            width: 100%;
            max-width: 500px;
            margin: 40px auto;
            background: white;
            border-radius: 8px;
            padding: 32px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            margin: 0 0 24px;
            font-size: 24px;
        }

        .errors {
            background: #fee;
            border: 1px solid #fcc;
            border-radius: 4px;
            padding: 12px;
            margin-bottom: 16px;
            color: #c33;
        }

        .errors li {
            margin: 4px 0;
        }

        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            font-size: 14px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-family: inherit;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .hint {
            font-size: 13px;
            color: #666;
            margin-top: 4px;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            margin-top: 20px;
        }

        button:hover {
            background: #1d4ed8;
        }

        .login-link {
            text-align: center;
            margin-top: 16px;
            font-size: 14px;
        }

        .login-link a {
            color: #2563eb;
            text-decoration: none;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .grid input {
            width: 100%;
        }

        .success-banner {
            background: #efe;
            border: 1px solid #0c0;
            color: #060;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 16px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Daftar Siswa</h1>

        <?php if (!empty($errors)): ?>
            <ul class="errors">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="school_id">Sekolah*</label>
                <select id="school_id" name="school_id" required>
                    <option value="">-- Pilih Sekolah --</option>
                    <?php foreach ($schools as $school): ?>
                        <option value="<?php echo $school['id']; ?>" <?php echo ($_POST['school_id'] ?? 0) == $school['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($school['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="activation_code">Kode Aktivasi Sekolah*</label>
                <input type="text" id="activation_code" name="activation_code" placeholder="XXXX-XXXX-XXXX" required
                    value="<?php echo htmlspecialchars($_POST['activation_code'] ?? ''); ?>">
                <div class="hint">Minta kode dari admin sekolah (format: 4-4-4)</div>
            </div>

            <div class="form-group">
                <label for="nisn">NISN*</label>
                <input type="text" id="nisn" name="nisn" placeholder="Nomor Induk Siswa Nasional" required
                    value="<?php echo htmlspecialchars($_POST['nisn'] ?? ''); ?>">
                <div class="hint">12 digit nomor NISN</div>
            </div>

            <div class="form-group">
                <label for="name">Nama Lengkap*</label>
                <input type="text" id="name" name="name" required
                    value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="email">Email*</label>
                <input type="email" id="email" name="email" required
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="password">Password*</label>
                <input type="password" id="password" name="password" required minlength="6">
                <div class="hint">Minimal 6 karakter</div>
            </div>

            <div class="form-group">
                <label for="password_confirm">Konfirmasi Password*</label>
                <input type="password" id="password_confirm" name="password_confirm" required>
            </div>

            <button type="submit">Daftar</button>
        </form>

        <div class="login-link">
            Sudah punya akun? <a href="/perpustakaan-online/public/login.php">Login di sini</a>
        </div>
    </div>
</body>

</html>