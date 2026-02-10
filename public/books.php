<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/NotificationsHelper.php';

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
    'INSERT INTO books (school_id,title,author,isbn,category,shelf,row_number,copies,max_borrow_days,cover_image)
     VALUES (:sid,:title,:author,:isbn,:category,:shelf,:row,:copies,:max_borrow_days,:cover_image)'
  )->execute([
        'sid' => $sid,
        'title' => $_POST['title'],
        'author' => $_POST['author'],
        'isbn' => $_POST['isbn'],
        'category' => $_POST['category'],
        'shelf' => $_POST['shelf'],
        'row' => $_POST['row_number'],
        'copies' => 1,
        'max_borrow_days' => !empty($_POST['max_borrow_days']) ? (int)$_POST['max_borrow_days'] : null,
        'cover_image' => $coverImage
      ]);
  
  // Get all students in this school to notify them about new book
  $studentsStmt = $pdo->prepare(
    'SELECT id FROM users WHERE school_id = :school_id AND role = "student"'
  );
  $studentsStmt->execute(['school_id' => $sid]);
  $students = $studentsStmt->fetchAll(PDO::FETCH_COLUMN);
  
  // Broadcast notification to all students
  if (!empty($students)) {
    $helper = new NotificationsHelper($pdo);
    $bookTitle = $_POST['title'];
    $notificationMessage = 'Buku "' . htmlspecialchars($bookTitle) . '" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!';
    
    $helper->broadcastNotification(
      $sid,
      $students,
      'new_book',
      'Buku Baru Tersedia',
      $notificationMessage
    );
  }
  
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
      'UPDATE books SET title=:title,author=:author,isbn=:isbn,category=:category,shelf=:shelf,row_number=:row,copies=:copies,max_borrow_days=:max_borrow_days,cover_image=:cover_image
       WHERE id=:id AND school_id=:sid'
    )->execute([
          'title' => $_POST['title'],
          'author' => $_POST['author'],
          'isbn' => $_POST['isbn'],
          'category' => $_POST['category'],
          'shelf' => $_POST['shelf'],
          'row' => $_POST['row_number'],
          'copies' => 1,
          'max_borrow_days' => !empty($_POST['max_borrow_days']) ? (int)$_POST['max_borrow_days'] : null,
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
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
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

        <!-- SECTION 1: ADD/EDIT FORM (Full Width) -->
        <div class="card form-card">
          <h2><?= $action === 'edit' ? 'Edit Buku' : 'Tambah Buku' ?></h2>
          <form method="post" action="<?= $action === 'edit' ? '' : 'books.php?action=add' ?>"
            enctype="multipart/form-data">
            
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group"><label>Judul Buku</label>
                        <input name="title" required value="<?= $book['title'] ?? '' ?>" placeholder="Judul Lengkap">
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group"><label>Pengarang</label>
                        <input name="author" required value="<?= $book['author'] ?? '' ?>" placeholder="Nama Pengarang">
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group"><label>ISBN</label>
                        <input name="isbn" value="<?= $book['isbn'] ?? '' ?>" placeholder="Contoh: 978-602...">
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <div class="form-group"><label>Kategori</label>
                        <select name="category">
                        <option value="">-- Pilih --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat ?>" <?= ($book['category'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?>
                            </option>
                        <?php endforeach ?>
                        </select>
                    </div>
                </div>
                <div class="form-col">
                     <!-- Stock hidden, always 1 -->
                     <input type="hidden" name="copies" value="1">
                </div>
                <div class="form-col">
                    <div class="form-group"><label>Lokasi (Rak / Baris)</label>
                        <div class="book-location-input">
                            <input name="shelf" value="<?= $book['shelf'] ?? '' ?>" placeholder="Rak A1">
                            <input type="number" min="1" name="row_number" value="<?= $book['row_number'] ?? '' ?>" placeholder="Baris 1">
                        </div>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group"><label>Batas Pinjam Khusus (Hari)</label>
                        <input type="number" min="1" name="max_borrow_days" value="<?= $book['max_borrow_days'] ?? '' ?>" placeholder="Default Sekolah">
                        <small style="color: var(--text-muted); font-size: 10px;">Kosongkan untuk menggunakan aturan sekolah</small>
                    </div>
                </div>
            </div>
            
             <div class="form-row">
                 <div class="form-col wide">
                    <div class="form-group"><label>Sampul Buku</label>
                        <div class="file-input-wrapper">
                            <input type="file" name="cover_image" accept="image/jpeg,image/png,image/gif" id="imageInput"
                            onchange="previewImage(event)">
                        </div>
                        <small>Format: JPG, PNG, GIF (Max 5MB)</small>
                    </div>
                 </div>
                 <div class="form-col">
                     <?php if ($action === 'edit' || !empty($book['cover_image'])): ?>
                        <div id="imagePreview" class="image-preview-mini">
                             <?php if (!empty($book['cover_image'])): ?>
                                <img src="../img/covers/<?= htmlspecialchars($book['cover_image']) ?>" alt="Preview">
                             <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div id="imagePreview" class="image-preview-mini"></div>
                    <?php endif; ?>
                 </div>
            </div>

            <div class="form-actions">
                <button class="btn" type="submit"><?= $action === 'edit' ? 'Simpan Perubahan' : 'Tambah Buku Baru' ?></button>
                <?php if($action === 'edit'): ?>
                    <a href="books.php" class="btn btn-secondary">Batal</a>
                <?php endif; ?>
            </div>
          </form>
        </div>

        <!-- SECTION 2: BOOK LIST (Full Width) -->
        <div class="card">
          <div class="card-header-flex">
             <h2>Daftar Buku (<?= count($books) ?>)</h2>
             <div class="view-controls">
                 <!-- Optional search -->
             </div>
          </div>
          
          <div class="books-grid">
            <?php foreach ($books as $idx => $b): ?>
              <div class="book-card-vertical">
                <div class="book-cover-container">
                  <?php if (!empty($b['cover_image']) && file_exists(__DIR__ . '/../img/covers/' . $b['cover_image'])): ?>
                    <img src="../img/covers/<?= htmlspecialchars($b['cover_image']) ?>"
                      alt="<?= htmlspecialchars($b['title']) ?>" loading="lazy">
                  <?php else: ?>
                    <div class="no-image-placeholder">
                        <iconify-icon icon="mdi:book-open-variant" style="font-size: 32px;"></iconify-icon>
                    </div>
                  <?php endif; ?>
                  
                  <div class="stock-badge-overlay" style="
                      background: <?= $b['copies'] > 0 ? 'color-mix(in srgb, var(--success) 15%, transparent)' : 'color-mix(in srgb, var(--danger) 15%, transparent)' ?>;
                      color: <?= $b['copies'] > 0 ? 'var(--success)' : 'var(--danger)' ?>;
                      border: 1px solid <?= $b['copies'] > 0 ? 'color-mix(in srgb, var(--success) 30%, transparent)' : 'color-mix(in srgb, var(--danger) 30%, transparent)' ?>;
                  ">
                      <?= $b['copies'] > 0 ? 'Tersedia' : 'Dipinjam' ?>
                  </div>
                </div>
                
                <div class="book-card-body">
                  <div class="book-category"><?= htmlspecialchars($b['category'] ?? 'Umum') ?></div>
                  <div class="book-title" title="<?= htmlspecialchars($b['title']) ?>"><?= htmlspecialchars($b['title']) ?></div>
                  <div class="book-author"><?= htmlspecialchars($b['author']) ?></div>
                  
                  <div class="book-card-footer">
                      <div class="shelf-info">
                          <iconify-icon icon="mdi:bookshelf"></iconify-icon> <?= htmlspecialchars($b['shelf'] ?? '-') ?>/<?= htmlspecialchars($b['row_number'] ?? '-') ?>
                      </div>
                      
                      <div class="action-buttons">
                        <button class="btn-icon-sm" onclick="openDetailModal(<?= $idx ?>)" title="Detail">
                           <iconify-icon icon="mdi:eye"></iconify-icon>
                        </button>
                        <a href="books.php?action=edit&id=<?= $b['id'] ?>" class="btn-icon-sm" title="Edit">
                           <iconify-icon icon="mdi:pencil"></iconify-icon>
                        </a>
                        <a href="books.php?action=delete&id=<?= $b['id'] ?>" class="btn-icon-sm btn-icon-danger" 
                           onclick="return confirm('Hapus buku ini?')" title="Hapus">
                           <iconify-icon icon="mdi:trash-can"></iconify-icon>
                        </a>
                      </div>
                  </div>
                </div>
              </div>
            <?php endforeach ?>
          </div>
        </div>

        <!-- SECTION 3: BOTTOM INFO (Grid) -->
        <div class="bottom-grid">
            <!-- FAQ -->
            <div class="card">
                <h2>Pertanyaan Umum</h2>
                <div class="faq-container">
                    <div class="faq-item" onclick="toggleFaq(this)">
                        <div class="faq-question">Bagaimana cara menambah buku? <iconify-icon icon="mdi:chevron-down"></iconify-icon></div>
                        <div class="faq-answer">Isi formulir "Tambah Buku" di bagian atas halaman dengan lengkap, lalu klik tombol simpan.</div>
                    </div>
                    <div class="faq-item" onclick="toggleFaq(this)">
                        <div class="faq-question">Bagaimana edit stok? <iconify-icon icon="mdi:chevron-down"></iconify-icon></div>
                        <div class="faq-answer">Cari buku di daftar, klik tombol pensil (edit), lalu ubah jumlah stok dan simpan.</div>
                    </div>
                </div>
            </div>

            <!-- STATS -->
            <div class="card">
                <h2>Statistik Perpustakaan</h2>
                <div class="stats-grid-modern">
                    
                    <!-- Card 1: Total Buku -->
                    <div class="stat-card-modern" onclick="showStatDetail('books')">
                        <div class="stat-icon blue">
                            <iconify-icon icon="mdi:book-open-page-variant"></iconify-icon>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value"><?= count($books) ?></div>
                            <div class="stat-label">Total Judul Buku</div>
                        </div>
                        <div class="stat-arrow">
                            <iconify-icon icon="mdi:chevron-right" style="font-size: 24px;"></iconify-icon>
                        </div>
                    </div>

                    <!-- Card 2: Total Salinan -->
                    <div class="stat-card-modern" onclick="showStatDetail('copies')">
                        <div class="stat-icon purple">
                            <iconify-icon icon="mdi:layers-triple"></iconify-icon>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value"><?= array_sum(array_map(fn($b) => $b['copies'], $books)) ?></div>
                            <div class="stat-label">Total Eksemplar</div>
                        </div>
                        <div class="stat-arrow">
                            <iconify-icon icon="mdi:chevron-right" style="font-size: 24px;"></iconify-icon>
                        </div>
                    </div>

                    <!-- Card 3: Kategori -->
                    <div class="stat-card-modern" onclick="showStatDetail('categories')">
                        <div class="stat-icon teal">
                            <iconify-icon icon="mdi:shape"></iconify-icon>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value"><?= count(array_unique(array_column($books, 'category'))) ?></div>
                            <div class="stat-label">Kategori Buku</div>
                        </div>
                        <div class="stat-arrow">
                            <iconify-icon icon="mdi:chevron-right" style="font-size: 24px;"></iconify-icon>
                        </div>
                    </div>

                </div>
            </div>
        </div>

      </div>

    </div>
  </div>

  <!-- Stat Detail Modal -->
  <div id="statModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="statModalTitle">Statistik Detail</h2>
        <button class="modal-close" onclick="closeStatModal()">&times;</button>
      </div>
      <div class="modal-body" id="statModalBody">
          <!-- Content injected via JS -->
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
              <label>Batas Pinjam</label>
              <div id="detailMaxBorrow"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Data Payload for JS -->
  <script>
    // Pass PHP data to JS
    window.booksData = <?= json_encode(array_values($books)) ?>;

    /**
     * UTILITY: Image Preview
     */
    function previewImage(event) {
        const input = event.target;
        const preview = document.getElementById('imagePreview');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" style="width:100%; height:100%; object-fit:cover; border-radius:6px;">`;
            }
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.innerHTML = '';
        }
    }

    /**
     * UTILITY: FAQ Accordion
     */
    function toggleFaq(element) {
        // Close other FAQs
        const allFaqs = document.querySelectorAll('.faq-item');
        allFaqs.forEach(item => {
            if (item !== element) {
                item.classList.remove('active');
            }
        });
        // Toggle current
        element.classList.toggle('active');
    }

    /**
     * FEATURE 1: DETAIL MODAL (Mata Icon)
     */
    function openDetailModal(index) {
        if (!window.booksData || !window.booksData[index]) {
            alert('Data buku tidak ditemukan!');
            return;
        }

        const book = window.booksData[index];
        
        // Helper to set text
        const set = (id, val) => {
            const el = document.getElementById(id);
            if(el) {
                // Force string and handle empty/null
                let content = (val !== null && val !== undefined && val !== '') ? String(val) : '-';
                el.textContent = content;
                console.log(`Set #${id} to "${content}"`);
            } else {
                console.error(`Element #${id} not found in Modal!`);
            }
        };

        console.log('Populating modal with:', book);

        set('detailTitle', book.title);
        set('detailAuthor', book.author);
        set('detailISBN', book.isbn);
        set('detailCategory', book.category);
        set('detailLocation', `Rak ${book.shelf || '?'} / Baris ${book.row_number || '?'}`);
        set('detailCopies', `${book.copies} Salinan`);
        set('detailMaxBorrow', book.max_borrow_days ? `${book.max_borrow_days} Hari` : 'Default Sekolah');
        
        // Image Handling
        const imgContainer = document.getElementById('detailCover');
        if (imgContainer) {
            console.log('Cover image:', book.cover_image);
            if (book.cover_image && book.cover_image !== '') {
                imgContainer.src = '../img/covers/' + book.cover_image;
                imgContainer.style.display = 'block';
            } else {
                // Use a placeholder if no image
                imgContainer.src = 'https://via.placeholder.com/150x200?text=No+Cover';
                imgContainer.style.display = 'block';
            }
        }
        
        const modal = document.getElementById('detailModal');
        if (modal) modal.style.display = 'block';
    }

    function closeDetail() {
        const modal = document.getElementById('detailModal');
        if (modal) modal.style.display = 'none';
    }

    /**
     * FEATURE 2: STATS MODAL (Statistic Cards)
     */
    function showStatDetail(type) {
        const books = window.booksData || [];
        const modal = document.getElementById('statModal');
        const titleEl = document.getElementById('statModalTitle');
        const bodyEl = document.getElementById('statModalBody');

        if (!modal) {
             console.error('Modal element #statModal not found in DOM');
             return;
        }

        let content = '';

        if (type === 'books') {
            titleEl.textContent = 'Daftar Semua Buku';
            content = `<div class="modal-stat-list">`;
            // Get 10 newest
            const recent = books.slice(0, 10);
            if (recent.length === 0) {
                content += `<div class="empty-state">Belum ada buku.</div>`;
            } else {
                recent.forEach(b => {
                    content += `
                        <div class="modal-stat-item">
                            <span class="stat-item-label">${b.title}</span>
                            <span class="stat-item-val">${b.category || '-'}</span>
                        </div>
                    `;
                });
            }
            content += `</div>`;

        } else if (type === 'copies') {
            titleEl.textContent = 'Stok Buku Tertinggi';
            const sorted = [...books].sort((a,b) => b.copies - a.copies).slice(0, 10);
            content = `<div class="modal-stat-list">`;
            sorted.forEach(b => {
                content += `
                    <div class="modal-stat-item">
                        <span class="stat-item-label">${b.title}</span>
                        <span class="stat-item-val">${b.copies} Eks.</span>
                    </div>
                `;
            });
            content += `</div>`;

        } else if (type === 'categories') {
            titleEl.textContent = 'Statistik Kategori';
            const counts = {};
            books.forEach(b => {
                const cat = b.category || 'Lainnya';
                counts[cat] = (counts[cat] || 0) + 1;
            });
            content = `<div class="modal-stat-list">`;
            for (const [key, val] of Object.entries(counts)) {
                content += `
                    <div class="modal-stat-item">
                        <span class="stat-item-label">${key}</span>
                        <span class="stat-item-val">${val} Judul</span>
                    </div>
                `;
            }
            content += `</div>`;
        }

        bodyEl.innerHTML = content;
        modal.style.display = 'block';
    }

    function closeStatModal() {
        document.getElementById('statModal').style.display = 'none';
    }

    // Global Close Click Outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
  </script>

</body>

</html>