<?php
session_start();
$pdo = require __DIR__ . '/../src/db.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $_POST['email'] ?? '';
  $password = $_POST['password'] ?? '';
  // find user by email
  $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
  $stmt->execute(['email' => $email]);
  $user = $stmt->fetch();
  if ($user && password_verify($password, $user['password'])) {
    // store user in session
    $_SESSION['user'] = [
      'id' => $user['id'],
      'school_id' => $user['school_id'],
      'name' => $user['name'],
      'role' => $user['role']
    ];
    header('Location: /perpustakaan-online/public/index.php');
    exit;
  } else {
    $message = 'Email atau password salah.';
  }
}
?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - Perpustakaan Online</title>
  <link rel="stylesheet" href="/perpustakaan-online/public/assets/css/styles.css">
</head>

<body>
  <?php include __DIR__ . '/partials/header.php'; ?>

  <div class="container">
    <div class="card" style="max-width: 450px; margin: 60px auto;">
      <div style="text-align: center; margin-bottom: 28px;">
        <div style="font-size: 48px; margin-bottom: 16px;">📚</div>
        <h1 style="font-size: 28px; margin-bottom: 8px;">Masuk ke Perpustakaan</h1>
        <p style="color: var(--text-muted); margin: 0; font-size: 13px;">Kelola perpustakaan sekolah Anda dengan mudah
        </p>
      </div>

      <?php if ($message): ?>
        <div class="alert danger">
          <span>⚠️</span>
          <div><?php echo $message; ?></div>
        </div>
      <?php endif; ?>

      <form method="post">
        <div class="form-group">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" required placeholder="admin@sekolah.com">
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" required placeholder="••••••••">
        </div>
        <button type="submit" class="btn" style="width: 100%; justify-content: center;">🔓 Login</button>
      </form>

      <div style="text-align: center; margin-top: 28px; padding-top: 20px; border-top: 1px solid var(--border);">
        <p style="margin: 0 0 12px 0; color: var(--text-muted); font-size: 13px;">Belum punya akun?</p>
        <a href="/perpustakaan-online/public/register.php" class="btn secondary"
          style="width: 100%; justify-content: center;">📝 Daftar di sini</a>
      </div>

      <p style="text-align: center; margin-top: 20px; color: var(--text-muted); font-size: 12px;">
        <a href="/perpustakaan-online/public/index.php" class="btn secondary"
          style="width: 100%; justify-content: center; margin-top: 12px;">← Kembali ke Home</a>
      </p>

      <?php include __DIR__ . '/partials/footer.php'; ?>
    </div>
  </div>

</body>

</html>