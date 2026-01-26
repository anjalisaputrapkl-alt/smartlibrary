<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];

$action = $_GET['action'] ?? 'list';

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    // Insert into members table
    $stmt = $pdo->prepare(
      'INSERT INTO members (school_id,name,email,member_no,nisn)
       VALUES (:sid,:name,:email,:no,:nisn)'
    );
    $stmt->execute([
      'sid' => $sid,
      'name' => $_POST['name'],
      'email' => $_POST['email'],
      'no' => $_POST['member_no'],
      'nisn' => $_POST['nisn']
    ]);

    // Get the inserted NISN for password generation
    $nisn = $_POST['nisn'];
    $password = $_POST['password'];
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Create student account in users table
    $userStmt = $pdo->prepare(
      'INSERT INTO users (school_id, name, email, password, role, nisn)
       VALUES (:sid, :name, :email, :password, :role, :nisn)'
    );
    $userStmt->execute([
      'sid' => $sid,
      'name' => $_POST['name'],
      'email' => $_POST['email'],
      'password' => $hashed_password,
      'role' => 'student',
      'nisn' => $nisn
    ]);

    // Success message
    $_SESSION['success'] = 'Murid berhasil ditambahkan. Akun siswa otomatis terbuat dengan NISN: ' . $nisn;
    header('Location: members.php');
    exit;
  } catch (Exception $e) {
    $_SESSION['error'] = 'Gagal menambahkan murid: ' . $e->getMessage();
    header('Location: members.php');
    exit;
  }
}

if ($action === 'edit' && isset($_GET['id'])) {
  $id = (int) $_GET['id'];
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare(
      'UPDATE members SET name=:name,email=:email,nisn=:nisn
       WHERE id=:id AND school_id=:sid'
    );
    $stmt->execute([
      'name' => $_POST['name'],
      'email' => $_POST['email'],
      'nisn' => $_POST['nisn'],
      'id' => $id,
      'sid' => $sid
    ]);

    // Update password jika diisi
    if (!empty($_POST['password'])) {
      $hashed_password = password_hash($_POST['password'], PASSWORD_BCRYPT);
      $updatePasswordStmt = $pdo->prepare(
        'UPDATE users SET password=:password WHERE nisn=:nisn AND role=:role'
      );
      $updatePasswordStmt->execute([
        'password' => $hashed_password,
        'nisn' => $_POST['nisn'],
        'role' => 'student'
      ]);
    }

    header('Location: members.php');
    exit;
  }
  $stmt = $pdo->prepare('SELECT * FROM members WHERE id=:id AND school_id=:sid');
  $stmt->execute(['id' => $id, 'sid' => $sid]);
  $member = $stmt->fetch();
}

if ($action === 'delete' && isset($_GET['id'])) {
  try {
    // Get member data to find associated user
    $getMemberStmt = $pdo->prepare('SELECT email, nisn FROM members WHERE id=:id AND school_id=:sid');
    $getMemberStmt->execute(['id' => (int) $_GET['id'], 'sid' => $sid]);
    $member = $getMemberStmt->fetch();

    if ($member) {
      // Delete user account if exists (by NISN)
      $deleteUserStmt = $pdo->prepare('DELETE FROM users WHERE nisn=:nisn AND role=:role');
      $deleteUserStmt->execute(['nisn' => $member['nisn'], 'role' => 'student']);

      // Delete member
      $stmt = $pdo->prepare('DELETE FROM members WHERE id=:id AND school_id=:sid');
      $stmt->execute(['id' => (int) $_GET['id'], 'sid' => $sid]);
    }

    $_SESSION['success'] = 'Murid dan akun siswa berhasil dihapus';
    header('Location: members.php');
    exit;
  } catch (Exception $e) {
    $_SESSION['error'] = 'Gagal menghapus murid: ' . $e->getMessage();
    header('Location: members.php');
    exit;
  }
}

$stmt = $pdo->prepare('SELECT * FROM members WHERE school_id=:sid ORDER BY id DESC');
$stmt->execute(['sid' => $sid]);
$members = $stmt->fetchAll();
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kelola Murid</title>
  <script src="../assets/js/theme-loader.js"></script>
  <script src="../assets/js/theme.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
  <link rel="stylesheet" href="../assets/css/animations.css">
  <link rel="stylesheet" href="../assets/css/members.css">
</head>

<body>
  <?php require __DIR__ . '/partials/sidebar.php'; ?>

  <div class="app">

    <div class="topbar">
      <strong>Kelola Murid</strong>
    </div>

    <div class="content">
      <div class="main">

        <?php if (isset($_SESSION['success'])): ?>
          <div class="alert alert-success">
            <?php echo htmlspecialchars($_SESSION['success']); ?>
            <?php unset($_SESSION['success']); ?>
          </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
          <div class="alert alert-error">
            <?php echo htmlspecialchars($_SESSION['error']); ?>
            <?php unset($_SESSION['error']); ?>
          </div>
        <?php endif; ?>

        <div class="card">
          <h2><?= $action === 'edit' ? 'Edit Murid' : 'Tambah Murid' ?></h2>
          <?php if ($action === 'add'): ?>
            <div
              style="background: #e0f2fe; border-left: 4px solid #0284c7; padding: 12px; border-radius: 6px; margin-bottom: 16px; font-size: 12px; color: #0c4a6e;">
              <strong>ℹ️ Info:</strong> Ketika murid ditambahkan, akun siswa akan otomatis terbuat. <strong>Siswa login
                dengan NISN sebagai username dan password yang Anda buat</strong>.
            </div>
          <?php endif; ?>
          <form method="post" action="<?= $action === 'edit' ? '' : 'members.php?action=add' ?>" autocomplete="off"
            id="member-form">
            <div class="form-group">
              <label>Nama Lengkap</label>
              <input type="text" name="name" required autocomplete="off"
                value="<?= $action === 'edit' && isset($member['name']) ? htmlspecialchars($member['name']) : '' ?>">
            </div>
            <div class="form-group">
              <label>Email</label>
              <input type="email" name="email" required autocomplete="off"
                value="<?= $action === 'edit' && isset($member['email']) ? htmlspecialchars($member['email']) : '' ?>">
            </div>
            <div class="form-group">
              <label>NISN Siswa</label>
              <input type="text" name="nisn" required placeholder="Nomor Induk Siswa Nasional" autocomplete="off"
                value="<?= $action === 'edit' && isset($member['nisn']) ? htmlspecialchars($member['nisn']) : '' ?>">
            </div>
            <div class="form-group">
              <label>Password</label>
              <input type="password" name="password" autocomplete="new-password" <?= $action === 'edit' ? '' : 'required' ?>
                placeholder="<?= $action === 'edit' ? 'Kosongkan jika tidak ingin mengubah password' : 'Buat password untuk siswa' ?>"
                value="">
            </div>
            <button class="btn" type="submit">
              <?= $action === 'edit' ? 'Simpan Perubahan' : 'Tambah Murid' ?>
            </button>
          </form>
        </div>

        <div class="card">
          <h2>Daftar Murid (<?= count($members) ?>)</h2>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Nama</th>
                  <th>Email</th>
                  <th>NISN</th>
                  <th>Status Akun</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($members as $m):
                  // Check if student account exists
                  $checkUserStmt = $pdo->prepare('SELECT id FROM users WHERE nisn = :nisn AND role = :role');
                  $checkUserStmt->execute(['nisn' => $m['nisn'], 'role' => 'student']);
                  $userExists = $checkUserStmt->fetch() ? true : false;
                  ?>
                  <tr>
                    <td>#<?= $m['id'] ?></td>
                    <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
                    <td><?= htmlspecialchars($m['email']) ?></td>
                    <td><strong><?= htmlspecialchars($m['nisn']) ?></strong></td>
                    <td>
                      <?php if ($userExists): ?>
                        <span
                          style="display: inline-block; background: rgba(16, 185, 129, 0.1); color: #065f46; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500;"><iconify-icon
                            icon="mdi:check-circle" style="vertical-align: middle; margin-right: 4px;"></iconify-icon> Akun
                          Terbuat</span>
                      <?php else: ?>
                        <span
                          style="display: inline-block; background: rgba(107, 114, 128, 0.1); color: #374151; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500;"><iconify-icon
                            icon="mdi:minus-circle" style="vertical-align: middle; margin-right: 4px;"></iconify-icon>
                          Belum</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="actions">
                        <a class="btn btn-sm btn-secondary"
                          href="members.php?action=edit&id=<?= $m['id'] ?>"><iconify-icon icon="mdi:pencil"
                            style="vertical-align: middle;"></iconify-icon> Edit</a>
                        <a class="btn btn-sm btn-danger"
                          onclick="return confirm('Hapus murid ini? Akun siswa juga akan dihapus.')"
                          href="members.php?action=delete&id=<?= $m['id'] ?>"><iconify-icon icon="mdi:trash-can"
                            style="vertical-align: middle;"></iconify-icon> Hapus</a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card" style="grid-column: 1/-1">
          <h2>Statistik Murid</h2>
          <div class="stats-container" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
            <div class="stat-card">
              <div class="stat-label">Total Murid</div>
              <div class="stat-value"><?= count($members) ?></div>
            </div>
            <div class="stat-card">
              <div class="stat-label">Email Terdaftar</div>
              <div class="stat-value"><?= count(array_filter($members, fn($m) => !empty($m['email']))) ?></div>
            </div>
          </div>
        </div>

        <div class="card" style="grid-column: 1/-1">
          <h2>Pertanyaan Umum</h2>
          <div class="faq-item">
            <div class="faq-question">Bagaimana cara menambah murid baru? <span>+</span></div>
            <div class="faq-answer">Isi form dengan nama lengkap, email, no murid, dan NISN siswa, lalu klik "Tambah
              Murid". Akun siswa akan otomatis terbuat dengan NISN sebagai username dan password.</div>
          </div>
          <div class="faq-item">
            <div class="faq-question">Apa perbedaan No Murid dan NISN? <span>+</span></div>
            <div class="faq-answer"><strong>No Murid</strong> adalah nomor internal sekolah (ex: 001, 002).
              <strong>NISN</strong> adalah Nomor Induk Siswa Nasional yang unik dan digunakan untuk login. Siswa login
              menggunakan NISN sebagai username.
            </div>
          </div>
          <div class="faq-item">
            <div class="faq-question">Apa itu "Status Akun"? <span>+</span></div>
            <div class="faq-answer">Status Akun menunjukkan apakah akun siswa sudah terbuat di sistem. Ketika Anda
              menambah murid, akun siswa otomatis terbuat dengan NISN dan Password = NISN.</div>
          </div>
          <div class="faq-item">
            <div class="faq-question">Bagaimana siswa login ke dashboard? <span>+</span></div>
            <div class="faq-answer">Siswa login di halaman siswa menggunakan <strong>NISN sebagai username</strong> dan
              <strong>Password = NISN</strong> (sama dengan username). Siswa sangat disarankan untuk mengubah password
              setelah login pertama kali.
            </div>
          </div>
          <div class="faq-item">
            <div class="faq-question">Bisakah saya mengedit data murid? <span>+</span></div>
            <div class="faq-answer">Ya, klik "Edit" pada baris murid yang ingin diubah. Anda bisa mengubah nama, email,
              no murid, dan NISN. Perubahan NISN juga akan mengubah kredensial login siswa.</div>
          </div>
          <div class="faq-item">
            <div class="faq-question">Apa yang terjadi jika saya menghapus murid? <span>+</span></div>
            <div class="faq-answer">Murid dan akun siswa akan dihapus dari sistem. Siswa tidak bisa login lagi. Pastikan
              murid tidak memiliki peminjaman aktif sebelum menghapus.</div>
          </div>
          <div class="faq-item">
            <div class="faq-question">Apakah NISN harus unik? <span>+</span></div>
            <div class="faq-answer">Ya, NISN harus unik karena digunakan sebagai identitas login siswa. Setiap siswa
              hanya memiliki satu NISN yang valid secara nasional.</div>
          </div>
        </div>

      </div>

    </div>
  </div>

  <script src="../assets/js/members.js"></script>
  <script>
    // Only reset form fields when in ADD mode, not EDIT mode
    document.addEventListener('DOMContentLoaded', function () {
      const form = document.getElementById('member-form');
      if (form) {
        // Check if we're in edit mode by checking if name field has a value
        const nameField = form.querySelector('input[name="name"]');
        const isEditMode = nameField && nameField.value.trim() !== '';

        // Only clear password field (always reset password on load)
        const passwordField = form.querySelector('input[name="password"]');
        if (passwordField) {
          passwordField.value = '';
        }

        // If in ADD mode, clear all fields
        if (!isEditMode) {
          form.reset();
          const inputs = form.querySelectorAll('input');
          inputs.forEach(input => {
            input.value = '';
          });
        }
      }
    });
  </script>

</body>

</html>