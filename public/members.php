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
  <title>Kelola Anggota</title>
  <script src="../assets/js/theme.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

  <style>
    :root {
      --bg: #f1f4f8;
      --surface: #ffffff;
      --text: #1f2937;
      --muted: #6b7280;
      --border: #e5e7eb;
      --accent: #2563eb;
      --danger: #dc2626;
    }

    * {
      box-sizing: border-box
    }

    html,
    body {
      margin: 0;
    }

    body {
      font-family: Inter, system-ui, sans-serif;
      background: var(--bg);
      color: var(--text);
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
        gap: 32px;
      }

      .card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 24px;
      }

      .card h2 {
        font-size: 14px;
        margin: 0 0 16px;
      }

      .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-bottom: 16px;
      }

      label {
        font-size: 12px;
        color: var(--muted)
      }

      input {
        padding: 12px 14px;
        border: 1px solid var(--border);
        border-radius: 8px;
        font-size: 13px;
      }

      /* ========= FIX TABEL (INI YANG PENTING) ========= */
      .table-wrap {
        overflow-x: auto
      }

      table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        /* KUNCI LEBAR KOLOM */
        font-size: 13px;
      }

      thead {
        display: table-header-group
      }

      tbody {
        display: table-row-group
      }

      tr {
        display: table-row
      }

      th,
      td {
        display: table-cell;
        padding: 12px;
        border-bottom: 1px solid var(--border);
        vertical-align: middle;
        text-align: left;
      }

      th {
        color: var(--muted);
        font-weight: 500;
        white-space: nowrap;
      }

      td strong {
        font-weight: 500;
      }

      /* ========= END FIX ========= */

      .btn {
        padding: 7px 14px;
        border: 1px solid var(--border);
        border-radius: 6px;
        background: white;
        font-size: 13px;
      }

      .btn.primary {
        background: var(--accent);
        color: white;
        border: none;
      }

      .btn.danger {
        background: #fee2e2;
        color: var(--danger);
        border: 1px solid #fecaca;
      }

      .actions {
        display: flex;
        gap: 6px;
      }

      .sidebar {
        display: none;
      }

      .panel {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 20px;
      }

      .menu {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-top: 12px;
      }

      .menu a {
        font-size: 13px;
        padding: 10px 12px;
        border-radius: 8px;
      }

      .menu a.active {
        background: rgba(37, 99, 235, .1);
        color: var(--accent);
        font-weight: 500;
      }

      .faq-item {
        border-bottom: 1px solid var(--border);
        padding: 10px 0;
      }

      .faq-question {
        font-size: 13px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
      }

      .faq-answer {
        font-size: 12px;
        color: var(--muted);
        margin-top: 6px;
        display: none;
        line-height: 1.6;
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
      <strong>Kelola Anggota</strong>
    </div>

    <div class="content">
      <div class="main">

        <div class="card">
          <h2><?= $action === 'edit' ? 'Edit Anggota' : 'Tambah Anggota' ?></h2>
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
              <label>No Anggota</label>
              <input name="member_no" required value="<?= $member['member_no'] ?? '' ?>">
            </div>
            <button class="btn primary">
              <?= $action === 'edit' ? 'Simpan Perubahan' : 'Tambah Anggota' ?>
            </button>
          </form>
        </div>

        <div class="card">
          <h2>Daftar Anggota (<?= count($members) ?>)</h2>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Nama</th>
                  <th>Email</th>
                  <th>No Anggota</th>
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
                        <a class="btn danger" onclick="return confirm('Hapus anggota ini?')"
                          href="members.php?action=delete&id=<?= $m['id'] ?>">Hapus</a>
                      </div>
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