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
  'SELECT b.*, bk.title, bk.cover_image, m.name AS member_name
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
  <script src="../assets/js/theme-loader.js"></script>
  <script src="../assets/js/theme.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/animations.css">
  <link rel="stylesheet" href="../assets/css/borrows.css">
</head>

<body>
  <?php require __DIR__ . '/partials/sidebar.php'; ?>

  <div class="app">

    <div class="topbar">
      <strong>Pinjam & Kembalikan</strong>
    </div>

    <div class="content">

      <div class="main">

        <div>
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
            <h2>Statistik Peminjaman</h2>
            <div class="stats-container">
              <div class="stat-card">
                <div class="stat-label">Total Peminjaman</div>
                <div class="stat-value"><?= count($borrows) ?></div>
              </div>
              <div class="stat-card">
                <div class="stat-label">Sedang Dipinjam</div>
                <div class="stat-value"><?= count(array_filter($borrows, fn($b) => $b['status'] !== 'returned')) ?></div>
              </div>
              <div class="stat-card">
                <div class="stat-label">Terlambat</div>
                <div class="stat-value"><?= count(array_filter($borrows, fn($b) => $b['status'] === 'overdue')) ?></div>
              </div>
            </div>
          </div>
        </div>

        <div>
          <div class="card">
            <h2>Daftar Peminjaman (<?= count($borrows) ?>)</h2>
            <div class="borrows-grid">
              <?php foreach ($borrows as $br): ?>
                <div class="borrow-card">
                  <div class="book-cover">
                    <?php if (!empty($br['cover_image']) && file_exists(__DIR__ . '/../img/covers/' . $br['cover_image'])): ?>
                      <img src="../img/covers/<?= htmlspecialchars($br['cover_image']) ?>" alt="<?= htmlspecialchars($br['title']) ?>">
                    <?php else: ?>
                      <div class="no-image">📚</div>
                    <?php endif; ?>
                  </div>
                  <div class="borrow-info">
                    <div class="borrow-title"><?= htmlspecialchars($br['title']) ?></div>
                    <div class="borrow-member"><?= htmlspecialchars($br['member_name']) ?></div>
                    <div class="borrow-dates">
                      <small>📅 <?= date('d/m/Y', strtotime($br['borrowed_at'])) ?></small>
                      <?php if ($br['due_at']): ?>
                        <small>⏰ <?= date('d/m/Y', strtotime($br['due_at'])) ?></small>
                      <?php endif; ?>
                    </div>
                    <div class="borrow-status">
                      <?php if ($br['status'] === 'overdue'): ?>
                        <span class="status-badge overdue">Terlambat</span>
                      <?php elseif ($br['status'] === 'returned'): ?>
                        <span class="status-badge returned">Dikembalikan</span>
                      <?php else: ?>
                        <span class="status-badge borrowed">Dipinjam</span>
                      <?php endif ?>
                    </div>
                  </div>
                  <div class="borrow-actions">
                    <button class="btn small" onclick="showBorrowDetail(<?= htmlspecialchars(json_encode($br)) ?>)">Detail</button>
                    <?php if ($br['status'] !== 'returned'): ?>
                      <a href="borrows.php?action=return&id=<?= $br['id'] ?>" class="btn small success">Kembalikan</a>
                    <?php else: ?>
                      <button class="btn small" disabled>Dikembalikan</button>
                    <?php endif ?>
                  </div>
                </div>
              <?php endforeach ?>
            </div>
          </div>

          <div class="card">
            <h2>Pertanyaan Umum</h2>
            <div class="faq-item">
              <div class="faq-question">Bagaimana cara menambah peminjaman baru? <span>+</span></div>
              <div class="faq-answer">Pilih buku dan anggota dari dropdown di form atas, atur tanggal jatuh tempo
                (opsional), lalu klik "Pinjamkan Buku".</div>
            </div>
            <div class="faq-item">
              <div class="faq-question">Bagaimana cara mengembalikan buku? <span>+</span></div>
              <div class="faq-answer">Cari peminjaman di daftar, jika status masih "Dipinjam" atau "Terlambat", klik
                tombol "Kembalikan" untuk menyelesaikan peminjaman.</div>
            </div>
            <div class="faq-item">
              <div class="faq-question">Apa itu status "Terlambat"? <span>+</span></div>
              <div class="faq-answer">Status terlambat muncul ketika tanggal jatuh tempo sudah berlalu tetapi buku belum
                dikembalikan. Segera kembalikan buku untuk menghindari denda.</div>
            </div>
            <div class="faq-item">
              <div class="faq-question">Bisakah saya mengubah tanggal jatuh tempo? <span>+</span></div>
              <div class="faq-answer">Saat ini, Anda perlu mengembalikan buku lalu meminjam ulang dengan tanggal baru.
                Atau hubungi admin untuk bantuan lebih lanjut.</div>
            </div>
          </div>
        </div>

      </div>

    </div>
  </div>

  <!-- Detail Modal -->
  <div id="borrowDetailModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Detail Peminjaman</h2>
        <button class="modal-close" onclick="closeBorrowDetail()">&times;</button>
      </div>
      <div class="modal-body">
        <div class="detail-layout">
          <div class="detail-image">
            <img id="borrowDetailCover" src="" alt="Book Cover">
          </div>
          <div class="detail-info">
            <div class="detail-field">
              <label>Judul Buku</label>
              <div id="borrowDetailTitle"></div>
            </div>
            <div class="detail-field">
              <label>Anggota</label>
              <div id="borrowDetailMember"></div>
            </div>
            <div class="detail-field">
              <label>Tanggal Pinjam</label>
              <div id="borrowDetailBorrowDate"></div>
            </div>
            <div class="detail-field">
              <label>Jatuh Tempo</label>
              <div id="borrowDetailDueDate"></div>
            </div>
            <div class="detail-field">
              <label>Tanggal Kembali</label>
              <div id="borrowDetailReturnDate"></div>
            </div>
            <div class="detail-field">
              <label>Status</label>
              <div id="borrowDetailStatus"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="../assets/js/borrows.js"></script>

</body>

</html>