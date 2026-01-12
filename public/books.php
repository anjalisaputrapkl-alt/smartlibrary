<?php
session_start();
if (empty($_SESSION['user'])) {
  header('Location: /perpustakaan-online/public/login');
  exit;
}

$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];
$action = $_GET['action'] ?? 'list';

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $pdo->prepare(
    'INSERT INTO books (school_id,title,author,isbn,copies)
     VALUES (:sid,:title,:author,:isbn,:copies)'
  )->execute([
        'sid' => $sid,
        'title' => $_POST['title'],
        'author' => $_POST['author'],
        'isbn' => $_POST['isbn'],
        'copies' => (int) $_POST['copies']
      ]);
  header('Location: books.php');
  exit;
}

if ($action === 'edit' && isset($_GET['id'])) {
  $id = (int) $_GET['id'];
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->prepare(
      'UPDATE books SET title=:title,author=:author,isbn=:isbn,copies=:copies
       WHERE id=:id AND school_id=:sid'
    )->execute([
          'title' => $_POST['title'],
          'author' => $_POST['author'],
          'isbn' => $_POST['isbn'],
          'copies' => (int) $_POST['copies'],
          'id' => $id,
          'sid' => $sid
        ]);
    header('Location: books.php');
    exit;
  }
  $stmt = $pdo->prepare('SELECT * FROM books WHERE id=:id AND school_id=:sid');
  $stmt->execute(['id' => $id, 'sid' => $sid]);
  $book = $stmt->fetch();
}

if ($action === 'delete' && isset($_GET['id'])) {
  $pdo->prepare('DELETE FROM books WHERE id=:id AND school_id=:sid')
    ->execute(['id' => (int) $_GET['id'], 'sid' => $sid]);
  header('Location: books.php');
  exit;
}

$stmt = $pdo->prepare('SELECT * FROM books WHERE school_id=:sid ORDER BY id DESC');
$stmt->execute(['sid' => $sid]);
$books = $stmt->fetchAll();
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kelola Buku</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

  <style>
    :root {
      --bg: #f1f4f8;
      --surface: #fff;
      --text: #1f2937;
      --muted: #6b7280;
      --border: #e5e7eb;
      --accent: #2563eb;
      --danger: #dc2626;
    }

    * {
      box-sizing: border-box
    }

    body {
      margin: 0;
      font-family: Inter, sans-serif;
      background: var(--bg);
      color: var(--text)
    }

    .app {
      min-height: 100vh;
      display: grid;
      grid-template-rows: 64px 1fr
    }

    .topbar {
      background: var(--surface);
      border-bottom: 1px solid var(--border);
      padding: 0 32px;
      display: flex;
      justify-content: space-between;
      align-items: center
    }

    .content {
      padding: 32px;
      display: grid;
      grid-template-columns: 1fr 320px;
      gap: 32px
    }

    .main {
      display: flex;
      flex-direction: column;
      gap: 32px
    }

    .card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 24px
    }

    .card h2 {
      font-size: 14px;
      margin: 0 0 16px
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
      margin-bottom: 16px
    }

    label {
      font-size: 12px;
      color: var(--muted)
    }

    input {
      padding: 12px 14px;
      border: 1px solid var(--border);
      border-radius: 8px;
      font-size: 13px
    }

    .btn {
      padding: 7px 14px;
      border-radius: 6px;
      border: 1px solid var(--border);
      background: #fff;
      font-size: 13px
    }

    .btn.primary {
      background: var(--accent);
      color: #fff;
      border: none
    }

    .btn.danger {
      background: #fee2e2;
      color: var(--danger);
      border: 1px solid #fecaca
    }

    .table-wrap {
      overflow-x: auto
    }

    /* 🔒 KUNCI KESELARASAN */
    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13px;
      table-layout: fixed;
    }

    col.id {
      width: 70px
    }

    col.title {
      width: 30%
    }

    col.author {
      width: 22%
    }

    col.isbn {
      width: 18%
    }

    col.qty {
      width: 90px
    }

    col.action {
      width: 160px
    }

    th,
    td {
      padding: 12px;
      border-bottom: 1px solid var(--border);
      vertical-align: middle;
    }

    th {
      color: var(--muted);
      font-weight: 500;
      text-align: left
    }

    .text-center {
      text-align: center
    }

    .actions {
      display: flex;
      gap: 6px;
      justify-content: center
    }

    .sidebar {
      display: flex;
      flex-direction: column;
      gap: 24px
    }

    .panel {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 20px
    }

    .menu {
      display: flex;
      flex-direction: column;
      gap: 6px;
      margin-top: 12px
    }

    .menu a {
      padding: 10px 12px;
      border-radius: 8px;
      font-size: 13px;
      text-decoration: none;
      color: inherit
    }

    .menu a.active {
      background: rgba(37, 99, 235, .1);
      color: var(--accent);
      font-weight: 500
    }

    .faq-item {
      border-bottom: 1px solid var(--border);
      padding: 10px 0
    }

    .faq-question {
      cursor: pointer;
      display: flex;
      justify-content: space-between;
      font-size: 13px
    }

    .faq-answer {
      font-size: 12px;
      color: var(--muted);
      margin-top: 6px;
      display: none
    }

    .faq-item.active .faq-answer {
      display: block
    }
  </style>
</head>

<body>
  <div class="app">

    <div class="topbar">
      <strong>Kelola Buku</strong>
      <a href="index.php" class="btn">← Dashboard</a>
    </div>

    <div class="content">
      <div class="main">

        <div class="card">
          <h2><?= $action === 'edit' ? 'Edit Buku' : 'Tambah Buku' ?></h2>
          <form method="post" action="<?= $action === 'edit' ? '' : 'books.php?action=add' ?>">
            <div class="form-group"><label>Judul Buku</label>
              <input name="title" required value="<?= $book['title'] ?? '' ?>">
            </div>
            <div class="form-group"><label>Pengarang</label>
              <input name="author" required value="<?= $book['author'] ?? '' ?>">
            </div>
            <div class="form-group"><label>ISBN</label>
              <input name="isbn" value="<?= $book['isbn'] ?? '' ?>">
            </div>
            <div class="form-group"><label>Jumlah</label>
              <input type="number" min="1" name="copies" value="<?= $book['copies'] ?? 1 ?>">
            </div>
            <button class="btn primary"><?= $action === 'edit' ? 'Simpan' : 'Tambah Buku' ?></button>
          </form>
        </div>

        <div class="card">
          <h2>Daftar Buku (<?= count($books) ?>)</h2>

          <div class="table-wrap">
            <table>
              <colgroup>
                <col class="id">
                <col class="title">
                <col class="author">
                <col class="isbn">
                <col class="qty">
                <col class="action">
              </colgroup>

              <thead>
                <tr>
                  <th>ID</th>
                  <th>Judul</th>
                  <th>Pengarang</th>
                  <th>ISBN</th>
                  <th class="text-center">Jumlah</th>
                  <th class="text-center">Aksi</th>
                </tr>
              </thead>

              <tbody>
                <?php foreach ($books as $b): ?>
                  <tr>
                    <td>#<?= $b['id'] ?></td>
                    <td><strong><?= htmlspecialchars($b['title']) ?></strong></td>
                    <td><?= htmlspecialchars($b['author']) ?></td>
                    <td><?= htmlspecialchars($b['isbn']) ?></td>
                    <td class="text-center"><?= $b['copies'] ?></td>
                    <td class="text-center">
                      <div class="actions">
                        <a class="btn" href="books.php?action=edit&id=<?= $b['id'] ?>">Edit</a>
                        <a class="btn danger" onclick="return confirm('Hapus buku ini?')"
                          href="books.php?action=delete&id=<?= $b['id'] ?>">Hapus</a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>

      <div class="sidebar">
        <div class="panel">
          <h3 style="font-size:14px">Menu</h3>
          <div class="menu">
            <a href="index.php">📊 Dashboard</a>
            <a class="active" href="books.php">📚 Buku</a>
            <a href="members.php">👥 Anggota</a>
            <a href="borrows.php">📖 Peminjaman</a>
            <a href="reports.php">📈 Laporan</a>
            <a href="settings.php">⚙️ Pengaturan</a>
          </div>
        </div>

        <div class="panel">
          <h3 style="font-size:14px">FAQ</h3>
          <div class="faq-item">
            <div class="faq-question">Bagaimana menambah buku? <span>+</span></div>
            <div class="faq-answer">Isi form lalu klik tambah.</div>
          </div>
          <div class="faq-item">
            <div class="faq-question">ISBN wajib? <span>+</span></div>
            <div class="faq-answer">Tidak wajib.</div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <script>
    document.querySelectorAll('.faq-question').forEach(q => {
      q.onclick = () => {
        const i = q.parentElement;
        i.classList.toggle('active');
        q.querySelector('span').textContent = i.classList.contains('active') ? '−' : '+';
      }
    });
  </script>

</body>

</html>