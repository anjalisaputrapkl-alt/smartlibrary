<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];
$action = $_GET['action'] ?? 'list';

// Create uploads directory if not exists
$uploadsDir = __DIR__ . '/../img/covers';
if (!is_dir($uploadsDir)) {
  mkdir($uploadsDir, 0755, true);
}

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $coverImage = '';
  
  // Handle image upload
  if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'tiff'];
    $filename = basename($_FILES['cover_image']['name']);
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (in_array($ext, $allowed)) {
      $newFilename = 'book_' . time() . '_' . uniqid() . '.' . $ext;
      $uploadPath = $uploadsDir . '/' . $newFilename;
      
      if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $uploadPath)) {
        $coverImage = $newFilename;
      }
    }
  }
  
  $pdo->prepare(
    'INSERT INTO books (school_id,title,author,isbn,category,shelf,row_number,copies,cover_image)
     VALUES (:sid,:title,:author,:isbn,:category,:shelf,:row,:copies,:cover_image)'
  )->execute([
        'sid' => $sid,
        'title' => $_POST['title'],
        'author' => $_POST['author'],
        'isbn' => $_POST['isbn'],
        'category' => $_POST['category'],
        'shelf' => $_POST['shelf'],
        'row' => $_POST['row_number'],
        'copies' => (int) $_POST['copies'],
        'cover_image' => $coverImage
      ]);
  header('Location: books.php');
  exit;
}

if ($action === 'edit' && isset($_GET['id'])) {
  $id = (int) $_GET['id'];
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare('SELECT cover_image FROM books WHERE id=:id AND school_id=:sid');
    $stmt->execute(['id' => $id, 'sid' => $sid]);
    $oldBook = $stmt->fetch();
    $coverImage = $oldBook['cover_image'] ?? '';
    
    // Handle new image upload
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
      $allowed = ['jpg', 'jpeg', 'png', 'gif'];
      $filename = basename($_FILES['cover_image']['name']);
      $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
      
      if (in_array($ext, $allowed)) {
        $newFilename = 'book_' . time() . '_' . uniqid() . '.' . $ext;
        $uploadPath = $uploadsDir . '/' . $newFilename;
        
        if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $uploadPath)) {
          // Delete old image if exists
          if ($coverImage && file_exists($uploadsDir . '/' . $coverImage)) {
            unlink($uploadsDir . '/' . $coverImage);
          }
          $coverImage = $newFilename;
        }
      }
    }
    
    $pdo->prepare(
      'UPDATE books SET title=:title,author=:author,isbn=:isbn,category=:category,shelf=:shelf,row_number=:row,copies=:copies,cover_image=:cover_image
       WHERE id=:id AND school_id=:sid'
    )->execute([
          'title' => $_POST['title'],
          'author' => $_POST['author'],
          'isbn' => $_POST['isbn'],
          'category' => $_POST['category'],
          'shelf' => $_POST['shelf'],
          'row' => $_POST['row_number'],
          'copies' => (int) $_POST['copies'],
          'cover_image' => $coverImage,
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

$categories = [
  'Fiksi',
  'Non-Fiksi',
  'Referensi',
  'Biografi',
  'Sejarah',
  'Seni & Budaya',
  'Teknologi',
  'Pendidikan',
  'Anak-anak',
  'Komik',
  'Majalah',
  'Lainnya'
];
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kelola Buku</title>
  <script src="../assets/js/theme-loader.js"></script>
  <script src="../assets/js/theme.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/animations.css">
  <link rel="stylesheet" href="../assets/css/books.css">
</head>

<body>
  <?php require __DIR__ . '/partials/sidebar.php'; ?>

  <div class="app">

    <div class="topbar">
      <strong>Kelola Buku</strong>
    </div>

    <div class="content">
      <div class="main">

        <div>
          <div class="card">
            <h2><?= $action === 'edit' ? 'Edit Buku' : 'Tambah Buku' ?></h2>
            <form method="post" action="<?= $action === 'edit' ? '' : 'books.php?action=add' ?>" enctype="multipart/form-data">
              <div class="form-group"><label>Judul Buku</label>
                <input name="title" required value="<?= $book['title'] ?? '' ?>">
              </div>
              <div class="form-group"><label>Pengarang</label>
                <input name="author" required value="<?= $book['author'] ?? '' ?>">
              </div>
              <div class="form-group"><label>ISBN</label>
                <input name="isbn" value="<?= $book['isbn'] ?? '' ?>">
              </div>
              <div class="form-group"><label>Kategori</label>
                <select name="category">
                  <option value="">-- Pilih Kategori --</option>
                  <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat ?>" <?= ($book['category'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?>
                    </option>
                  <?php endforeach ?>
                </select>
              </div>
              <div class="form-group"><label>Rak/Lemari</label>
                <input name="shelf" value="<?= $book['shelf'] ?? '' ?>">
              </div>
              <div class="form-group"><label>Baris</label>
                <input type="number" min="1" name="row_number" value="<?= $book['row_number'] ?? '' ?>">
              </div>
              <div class="form-group"><label>Jumlah</label>
                <input type="number" min="1" name="copies" value="<?= $book['copies'] ?? 1 ?>">
              </div>
              <div class="form-group"><label>Gambar Buku</label>
                <input type="file" name="cover_image" accept="image/jpeg,image/png,image/gif" id="imageInput" onchange="previewImage(event)">
                <small>Format: JPG, PNG, GIF (Max 5MB)</small>
                <div id="imagePreview" class="image-preview" style="margin-top: 12px;">
                  <?php if ($action === 'edit' && !empty($book['cover_image'])): ?>
                    <img src="../img/covers/<?= htmlspecialchars($book['cover_image']) ?>" alt="Preview">
                  <?php endif; ?>
                </div>
              </div>
              <button class="btn primary"><?= $action === 'edit' ? 'Simpan' : 'Tambah Buku' ?></button>
            </form>
          </div>

          <div class="card">
            <h2>Pertanyaan Umum</h2>
            <div class="faq-item">
              <div class="faq-question">Bagaimana cara menambah buku baru? <span>+</span></div>
              <div class="faq-answer">Isi form di atas dengan judul, pengarang, ISBN (opsional), dan jumlah salinan,
                lalu klik tombol "Tambah Buku".</div>
            </div>
            <div class="faq-item">
              <div class="faq-question">Bisakah saya mengedit data buku? <span>+</span></div>
              <div class="faq-answer">Ya, klik tombol "Edit" pada kartu buku yang ingin diubah di daftar buku, ubah data,
                lalu klik "Simpan".</div>
            </div>
            <div class="faq-item">
              <div class="faq-question">Apa yang terjadi jika saya menghapus buku? <span>+</span></div>
              <div class="faq-answer">Buku akan dihapus dari sistem. Pastikan tidak ada peminjaman aktif untuk buku
                tersebut sebelum menghapus.</div>
            </div>
            <div class="faq-item">
              <div class="faq-question">Bagaimana cara menambah salinan buku yang sudah ada? <span>+</span></div>
              <div class="faq-answer">Klik tombol "Edit" pada buku yang ingin ditambah salinannya, ubah nilai "Jumlah",
                lalu klik "Simpan".</div>
            </div>
          </div>
        </div>

        <div>
          <div class="card">
            <h2>Daftar Buku (<?= count($books) ?>)</h2>
            <div class="books-grid">
              <?php foreach ($books as $b): ?>
                <div class="book-card">
                  <div class="book-cover">
                    <?php if (!empty($b['cover_image']) && file_exists(__DIR__ . '/../img/covers/' . $b['cover_image'])): ?>
                      <img src="../img/covers/<?= htmlspecialchars($b['cover_image']) ?>" alt="<?= htmlspecialchars($b['title']) ?>">
                    <?php else: ?>
                      <div class="no-image">📚</div>
                    <?php endif; ?>
                  </div>
                  <div class="book-info">
                    <div class="book-title"><?= htmlspecialchars($b['title']) ?></div>
                    <div class="book-author"><?= htmlspecialchars($b['author']) ?></div>
                    <div class="book-meta">
                      <span class="badge"><?= htmlspecialchars($b['category'] ?? '-') ?></span>
                      <span class="copies-badge"><?= $b['copies'] ?> salinan</span>
                    </div>
                  </div>
                  <div class="book-actions">
                    <button class="btn small" onclick="showDetail(<?= htmlspecialchars(json_encode($b)) ?>)">Detail</button>
                    <a href="books.php?action=edit&id=<?= $b['id'] ?>" class="btn small">Edit</a>
                    <a href="books.php?action=delete&id=<?= $b['id'] ?>" class="btn small danger" onclick="return confirm('Hapus buku ini?')">Hapus</a>
                  </div>
                </div>
              <?php endforeach ?>
            </div>
          </div>

          <div class="card">
            <h2>Statistik Buku</h2>
            <div class="stats-container">
              <div class="stat-card">
                <div class="stat-label">Total Buku</div>
                <div class="stat-value"><?= count($books) ?></div>
              </div>
              <div class="stat-card">
                <div class="stat-label">Total Salinan</div>
                <div class="stat-value"><?= array_sum(array_map(fn($b) => $b['copies'], $books)) ?></div>
              </div>
              <div class="stat-card">
                <div class="stat-label">Rata-rata Salinan</div>
                <div class="stat-value"><?= count($books) > 0 ? round(array_sum(array_map(fn($b) => $b['copies'], $books)) / count($books), 1) : 0 ?></div>
              </div>
            </div>
          </div>
        </div>

      </div>

    </div>
  </div>

  <!-- Detail Modal -->
  <div id="detailModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Detail Buku</h2>
        <button class="modal-close" onclick="closeDetail()">&times;</button>
      </div>
      <div class="modal-body">
        <div class="detail-layout">
          <div class="detail-image">
            <img id="detailCover" src="" alt="Book Cover">
          </div>
          <div class="detail-info">
            <div class="detail-field">
              <label>Judul</label>
              <div id="detailTitle"></div>
            </div>
            <div class="detail-field">
              <label>Pengarang</label>
              <div id="detailAuthor"></div>
            </div>
            <div class="detail-field">
              <label>ISBN</label>
              <div id="detailISBN"></div>
            </div>
            <div class="detail-field">
              <label>Kategori</label>
              <div id="detailCategory"></div>
            </div>
            <div class="detail-field">
              <label>Lokasi</label>
              <div id="detailLocation"></div>
            </div>
            <div class="detail-field">
              <label>Jumlah Salinan</label>
              <div id="detailCopies"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="../assets/js/books.js"></script>

</body>

</html>