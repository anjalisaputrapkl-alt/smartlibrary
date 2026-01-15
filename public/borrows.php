<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];
$action = $_GET['action'] ?? 'list';

if ($action === 'new' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $stmt = $pdo->prepare(
    'INSERT INTO borrows (school_id, book_id, member_id, due_at)
     VALUES (:sid,:book,:member,:due)'
  );
  $stmt->execute([
    'sid' => $sid,
    'book' => (int) $_POST['book_id'],
    'member' => (int) $_POST['member_id'],
    'due' => $_POST['due_at'] ?: null
  ]);
  header('Location: borrows.php');
  exit;
}

if ($action === 'return' && isset($_GET['id'])) {
  $stmt = $pdo->prepare(
    'UPDATE borrows SET returned_at=NOW(), status="returned"
     WHERE id=:id AND school_id=:sid'
  );
  $stmt->execute([
    'id' => (int) $_GET['id'],
    'sid' => $sid
  ]);
  header('Location: borrows.php');
  exit;
}

$pdo->prepare(
  'UPDATE borrows SET status="overdue"
   WHERE school_id=:sid AND returned_at IS NULL AND due_at < NOW()'
)->execute(['sid' => $sid]);

$stmt = $pdo->prepare(
  'SELECT b.*, bk.title, m.name AS member_name
   FROM borrows b
   JOIN books bk ON b.book_id = bk.id
   JOIN members m ON b.member_id = m.id
   WHERE b.school_id = :sid
   ORDER BY b.borrowed_at DESC'
);
$stmt->execute(['sid' => $sid]);
$borrows = $stmt->fetchAll();

$books = $pdo->prepare('SELECT id,title FROM books WHERE school_id=:sid');
$books->execute(['sid' => $sid]);
$books = $books->fetchAll();

$members = $pdo->prepare('SELECT id,name FROM members WHERE school_id=:sid');
$members->execute(['sid' => $sid]);
$members = $members->fetchAll();
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pinjam & Kembalikan</title>
  <script src="../assets/js/theme.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

  <style>
    :root {
      --bg: #f1f4f8;
      --surface: #fff;
      --text: #1f2937;
      --muted: #6b7280;
      --border: #e5e7eb;
      --accent: #2563eb;
      --success: #16a34a;
      --danger: #dc2626;
      --info: #0284c7;
    }

    * {
      box-sizing: border-box
    }

    html,
    body {
      margin: 0;
    }

    body {
      font-family: Inter, sans-serif;
      background: var(--bg);
      color: var(--text)
    }

    .app {
      min-height: 100vh;
      display: grid;
      grid-template-rows: 64px 1fr;
      margin-left: 260px;
    }

    .topbar {
      background: var(--surface);
      border-bottom: 1px solid var(--border);
      padding: 22px 32px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: fixed;
      top: 0;
      left: 260px;
      right: 0;
      z-index: 999;
    }

    .content {
      padding: 32px;
      display: grid;
      grid-template-columns: 1fr;
      gap: 32px;
      margin-top: 64px;

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

      input,
      select {
        width: 100%;
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

      .btn.success {
        background: #dcfce7;
        color: var(--success);
        border: 1px solid #bbf7d0
      }

      .table-wrap {
        overflow-x: auto
      }

      table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        table-layout: fixed;
        /* 🔒 KUNCI */
      }

      col.id {
        width: 70px
      }

      col.book {
        width: 28%
      }

      col.member {
        width: 22%
      }

      col.date {
        width: 120px
      }

      col.status {
        width: 140px
      }

      col.action {
        width: 140px
      }

      th,
      td {
        padding: 12px;
        border-bottom: 1px solid var(--border);
        vertical-align: middle;
        /* 🔒 KUNCI */
      }

      th {
        color: var(--muted);
        font-weight: 500;
        text-align: left
      }

      .text-center {
        text-align: center
      }

      .status {
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 500
      }

      .status.borrowed {
        background: #e0f2fe;
        color: var(--info)
      }

      .status.overdue {
        background: #fee2e2;
        color: var(--danger)
      }

      .status.returned {
        background: #dcfce7;
        color: var(--success)
      }

      .sidebar {
        display: none;
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
        font-size: 13px;
        padding: 10px 12px;
        border-radius: 8px;
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
        font-size: 13px;
        cursor: pointer;
        display: flex;
        justify-content: space-between
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
  <?php require __DIR__ . '/partials/sidebar.php'; ?>

  <div class="app">

    <div class="topbar">
      <strong>Pinjam & Kembalikan</strong>
    </div>

    <div class="content">

      <div class="main">

        <div class="card">
          <h2>Pinjam Buku Baru</h2>
          <form method="post" action="borrows.php?action=new">
            <div class="form-group">
              <label>Buku</label>
              <select name="book_id" required>
                <option value="">Pilih buku</option>
                <?php foreach ($books as $b): ?>
                  <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['title']) ?></option>
                <?php endforeach ?>
              </select>
            </div>
            <div class="form-group">
              <label>Anggota</label>
              <select name="member_id" required>
                <option value="">Pilih anggota</option>
                <?php foreach ($members as $m): ?>
                  <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
                <?php endforeach ?>
              </select>
            </div>
            <div class="form-group">
              <label>Jatuh Tempo</label>
              <input type="date" name="due_at">
            </div>
            <button class="btn primary">Pinjamkan Buku</button>
          </form>
        </div>

        <div class="card">
          <h2>Daftar Peminjaman (<?= count($borrows) ?>)</h2>

          <div class="table-wrap">
            <table>
              <colgroup>
                <col class="id">
                <col class="book">
                <col class="member">
                <col class="date">
                <col class="date">
                <col class="status">
                <col class="action">
              </colgroup>

              <thead>
                <tr>
                  <th>ID</th>
                  <th>Buku</th>
                  <th>Anggota</th>
                  <th>Pinjam</th>
                  <th>Tempo</th>
                  <th class="text-center">Status</th>
                  <th class="text-center">Aksi</th>
                </tr>
              </thead>

              <tbody>
                <?php foreach ($borrows as $br): ?>
                  <tr>
                    <td>#<?= $br['id'] ?></td>
                    <td><strong><?= htmlspecialchars($br['title']) ?></strong></td>
                    <td><?= htmlspecialchars($br['member_name']) ?></td>
                    <td><?= date('d/m/Y', strtotime($br['borrowed_at'])) ?></td>
                    <td><?= $br['due_at'] ? date('d/m/Y', strtotime($br['due_at'])) : '-' ?></td>
                    <td class="text-center">
                      <?php if ($br['status'] === 'overdue'): ?>
                        <span class="status overdue">Terlambat</span>
                      <?php elseif ($br['status'] === 'returned'): ?>
                        <span class="status returned">Dikembalikan</span>
                      <?php else: ?>
                        <span class="status borrowed">Dipinjam</span>
                      <?php endif ?>
                    </td>
                    <td class="text-center">
                      <?php if ($br['status'] !== 'returned'): ?>
                        <a class="btn success" href="borrows.php?action=return&id=<?= $br['id'] ?>">Kembalikan</a>
                      <?php else: ?>—<?php endif ?>
                    </td>
                  </tr>
                <?php endforeach ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>

    </div>
  </div>

  <script>
    document.querySelectorAll('.faq-question').forEach(q => {
      q.onclick = () => {
        const p = q.parentElement;
        p.classList.toggle('active');
        q.querySelector('span').textContent = p.classList.contains('active') ? '−' : '+';
      }
    });
  </script>

</body>

</html>