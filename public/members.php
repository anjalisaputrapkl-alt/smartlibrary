<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];

$action = $_GET['action'] ?? 'list';

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $stmt = $pdo->prepare(
    'INSERT INTO members (school_id,name,email,member_no)
     VALUES (:sid,:name,:email,:no)'
  );
  $stmt->execute([
    'sid' => $sid,
    'name' => $_POST['name'],
    'email' => $_POST['email'],
    'no' => $_POST['member_no']
  ]);
  header('Location: members.php');
  exit;
}

if ($action === 'edit' && isset($_GET['id'])) {
  $id = (int) $_GET['id'];
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare(
      'UPDATE members SET name=:name,email=:email,member_no=:no
       WHERE id=:id AND school_id=:sid'
    );
    $stmt->execute([
      'name' => $_POST['name'],
      'email' => $_POST['email'],
      'no' => $_POST['member_no'],
      'id' => $id,
      'sid' => $sid
    ]);
    header('Location: members.php');
    exit;
  }
  $stmt = $pdo->prepare('SELECT * FROM members WHERE id=:id AND school_id=:sid');
  $stmt->execute(['id' => $id, 'sid' => $sid]);
  $member = $stmt->fetch();
}

if ($action === 'delete' && isset($_GET['id'])) {
  $stmt = $pdo->prepare('DELETE FROM members WHERE id=:id AND school_id=:sid');
  $stmt->execute(['id' => (int) $_GET['id'], 'sid' => $sid]);
  header('Location: members.php');
  exit;
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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
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

        <div class="card">
          <h2><?= $action === 'edit' ? 'Edit Murid' : 'Tambah Murid' ?></h2>
          <form method="post" action="<?= $action === 'edit' ? '' : 'members.php?action=add' ?>">
            <div class="form-group">
              <label>Nama Lengkap</label>
              <input name="name" required value="<?= $member['name'] ?? '' ?>">
            </div>
            <div class="form-group">
              <label>Email</label>
              <input type="email" name="email" required value="<?= $member['email'] ?? '' ?>">
            </div>
            <div class="form-group">
              <label>No Murid</label>
              <input name="member_no" required value="<?= $member['member_no'] ?? '' ?>">
            </div>
            <button class="btn primary">
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
                  <th>No Murid</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($members as $m): ?>
                  <tr>
                    <td>#<?= $m['id'] ?></td>
                    <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
                    <td><?= htmlspecialchars($m['email']) ?></td>
                    <td><?= htmlspecialchars($m['member_no']) ?></td>
                    <td>
                      <div class="actions">
                        <a class="btn" href="members.php?action=edit&id=<?= $m['id'] ?>">Edit</a>
                        <a class="btn danger" onclick="return confirm('Hapus murid ini?')"
                          href="members.php?action=delete&id=<?= $m['id'] ?>">Hapus</a>
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
          <div class="stats-container">
            <div class="stat-card">
              <div class="stat-label">Total Murid</div>
              <div class="stat-value"><?= count($members) ?></div>
            </div>
            <div class="stat-card">
              <div class="stat-label">Murid Baru</div>
              <div class="stat-value">—</div>
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
            <div class="faq-answer">Isi form di kolom kiri dengan nama lengkap, email, dan nomor murid, lalu klik
              tombol "Tambah Murid".</div>
          </div>
          <div class="faq-item">
            <div class="faq-question">Bisakah saya mengedit data murid? <span>+</span></div>
            <div class="faq-answer">Ya, klik tombol "Edit" pada baris murid yang ingin diubah di daftar murid, ubah
              data, lalu klik "Simpan Perubahan".</div>
          </div>
          <div class="faq-item">
            <div class="faq-question">Apa yang terjadi jika saya menghapus murid? <span>+</span></div>
            <div class="faq-answer">Murid akan dihapus dari sistem. Pastikan murid tidak memiliki peminjaman aktif
              sebelum menghapus.</div>
          </div>
          <div class="faq-item">
            <div class="faq-question">Apakah nomor murid harus unik? <span>+</span></div>
            <div class="faq-answer">Ya, setiap murid harus memiliki nomor unik untuk identifikasi dan sistem
              peminjaman.</div>
          </div>
        </div>

      </div>

    </div>
  </div>

  <script src="../assets/js/members.js"></script>

</body>

</html>