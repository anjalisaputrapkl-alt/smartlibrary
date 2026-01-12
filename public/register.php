<?php
session_start();
// Public registration to create a new school and initial admin
require_once __DIR__ . '/../src/db.php';
$pdo = require __DIR__ . '/../src/db.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_name = trim($_POST['school_name'] ?? '');
    $admin_name = trim($_POST['admin_name'] ?? '');
    $admin_email = trim($_POST['admin_email'] ?? '');
    $admin_password = $_POST['admin_password'] ?? '';

    if ($school_name === '' || $admin_name === '' || $admin_email === '' || $admin_password === '') {
        $errors[] = 'Semua field wajib diisi.';
    }

    if (empty($errors)) {
        // create slug
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i','-', trim($school_name)));
        // insert school
        $stmt = $pdo->prepare('INSERT INTO schools (name, slug) VALUES (:name, :slug)');
        $stmt->execute(['name' => $school_name, 'slug' => $slug]);
        $school_id = $pdo->lastInsertId();

        // create admin user
        $password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (school_id, name, email, password, role) VALUES (:school_id, :name, :email, :password, "admin")');
        $stmt->execute([
            'school_id' => $school_id,
            'name' => $admin_name,
            'email' => $admin_email,
            'password' => $password_hash
        ]);

        // Redirect to login (or display success)
        header('Location: /perpustakaan-online/public/login.php?registered=1');
        exit;
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Daftar Sekolah - Perpustakaan Online</title>
  <link rel="stylesheet" href="/perpustakaan-online/public/assets/css/styles.css">
</head>
<body>
<?php include __DIR__ . '/partials/header.php'; ?>

<div class="container">
  <div class="card" style="max-width: 500px; margin: 60px auto;">
    <div style="text-align: center; margin-bottom: 28px;">
      <div style="font-size: 48px; margin-bottom: 16px;">📖</div>
      <h1 style="font-size: 28px; margin-bottom: 8px;">Daftar Sekolah Baru</h1>
      <p style="color: var(--text-muted); margin: 0; font-size: 13px;">Kelola perpustakaan sekolah dengan sistem yang modern</p>
    </div>

    <?php if ($errors): ?>
      <div class="alert danger">
        <span>⚠️</span>
        <div><?php echo implode('<br>', $errors); ?></div>
      </div>
    <?php endif; ?>

    <form method="post">
      <div class="form-group">
        <label for="school_name">Nama Sekolah</label>
        <input id="school_name" name="school_name" required placeholder="SMA Maju Jaya">
      </div>
      <div class="form-group">
        <label for="admin_name">Nama Admin</label>
        <input id="admin_name" name="admin_name" required placeholder="Budi Santoso">
      </div>
      <div class="form-group">
        <label for="admin_email">Email Admin</label>
        <input id="admin_email" name="admin_email" type="email" required placeholder="admin@sekolah.com">
      </div>
      <div class="form-group">
        <label for="admin_password">Password Admin</label>
        <input id="admin_password" name="admin_password" type="password" required placeholder="••••••••">
      </div>
      <button type="submit" class="btn" style="width: 100%; justify-content: center;">✓ Daftarkan Sekolah</button>
    </form>

    <div style="text-align: center; margin-top: 28px; padding-top: 20px; border-top: 1px solid var(--border);">
      <p style="margin: 0 0 12px 0; color: var(--text-muted); font-size: 13px;">Sudah punya akun?</p>
      <a href="/perpustakaan-online/public/login.php" class="btn secondary" style="width: 100%; justify-content: center;">🔓 Login di sini</a>
    </div>

    <p style="text-align: center; margin-top: 20px; color: var(--text-muted); font-size: 12px;">
      <a href="/perpustakaan-online/public/index.php" class="btn secondary" style="width: 100%; justify-content: center; margin-top: 12px;">← Kembali ke Home</a>
    </p>

    <?php include __DIR__ . '/partials/footer.php'; ?>
  </div>
</div>

</body>
</html>