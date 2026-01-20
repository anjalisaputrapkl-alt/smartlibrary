<?php
session_start();
$pdo = require __DIR__ . '/../src/db.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_name = trim($_POST['school_name'] ?? '');
    $admin_name = trim($_POST['admin_name'] ?? '');
    $admin_email = trim($_POST['admin_email'] ?? '');
    $admin_password = $_POST['admin_password'] ?? '';

    // Validation
    if ($school_name === '' || $admin_name === '' || $admin_email === '' || $admin_password === '') {
        $errors[] = 'Semua field wajib diisi.';
    }

    if (empty($errors)) {
        try {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($school_name)));

            // Insert school
            $stmt = $pdo->prepare('INSERT INTO schools (name, slug) VALUES (:name, :slug)');
            $stmt->execute(['name' => $school_name, 'slug' => $slug]);
            $school_id = $pdo->lastInsertId();

            // Insert admin user
            $password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (school_id, name, email, password, role) VALUES (:school_id, :name, :email, :password, :role)');
            $stmt->execute([
                'school_id' => $school_id,
                'name' => $admin_name,
                'email' => $admin_email,
                'password' => $password_hash,
                'role' => 'admin'
            ]);

            $success = 'Sekolah berhasil didaftarkan! Silakan login.';

        } catch (PDOException $e) {
            $errors[] = 'Database Error: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Sekolah</title>
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
        }

        .errors {
            background: #fee;
            border: 1px solid #fcc;
            border-radius: 4px;
            padding: 12px;
            margin-bottom: 16px;
            color: #c33;
        }

        .success {
            background: #efe;
            border: 1px solid #0c0;
            border-radius: 4px;
            padding: 12px;
            margin-bottom: 16px;
            color: #060;
        }

        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-family: inherit;
        }

        input:focus {
            outline: none;
            border-color: #2563eb;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Daftar Sekolah Baru</h1>

        <?php if (!empty($errors)): ?>
            <div class="errors">
                <?php foreach ($errors as $err): ?>
                    <p><?php echo htmlspecialchars($err); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success">
                <p><?php echo htmlspecialchars($success); ?></p>
                <p><a href="/perpustakaan-online/public/login.php">Klik di sini untuk login</a></p>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label for="school_name">Nama Sekolah</label>
                    <input type="text" id="school_name" name="school_name" required>
                </div>

                <div class="form-group">
                    <label for="admin_name">Nama Admin</label>
                    <input type="text" id="admin_name" name="admin_name" required>
                </div>

                <div class="form-group">
                    <label for="admin_email">Email Admin</label>
                    <input type="email" id="admin_email" name="admin_email" required>
                </div>

                <div class="form-group">
                    <label for="admin_password">Password Admin</label>
                    <input type="password" id="admin_password" name="admin_password" required minlength="6">
                </div>

                <button type="submit">Daftarkan Sekolah</button>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>