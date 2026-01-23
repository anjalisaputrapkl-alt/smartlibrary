<?php
session_start();
$pdo = require __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/MemberHelper.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: /?login_required=1');
    exit;
}

$user = $_SESSION['user'];
$school_id = $user['school_id'];

// Get member_id dengan auto-create jika belum ada
$memberHelper = new MemberHelper($pdo);
$member_id = $memberHelper->getMemberId($user);

// ===================== QUERY PEMINJAMAN SISWA =====================
// Update overdue status
$pdo->prepare(
    'UPDATE borrows SET status = "overdue"
     WHERE school_id = :school_id 
     AND member_id = :member_id
     AND returned_at IS NULL 
     AND due_at < NOW()'
)->execute(['school_id' => $school_id, 'member_id' => $member_id]);

// Get all borrowing records untuk siswa ini
$borrowStmt = $pdo->prepare(
    'SELECT b.id, b.borrowed_at, b.due_at, b.returned_at, b.status, 
            bk.id as book_id, bk.title, bk.author
     FROM borrows b
     JOIN books bk ON b.book_id = bk.id
     WHERE b.school_id = :school_id 
     AND b.member_id = :member_id
     ORDER BY b.borrowed_at DESC'
);
$borrowStmt->execute(['school_id' => $school_id, 'member_id' => $member_id]);
$my_borrows = $borrowStmt->fetchAll();

// Calculate statistics
$active_borrows = count(array_filter($my_borrows, fn($b) => $b['status'] !== 'returned'));
$overdue_count = count(array_filter($my_borrows, fn($b) => $b['status'] === 'overdue'));
$returned_count = count(array_filter($my_borrows, fn($b) => $b['status'] === 'returned'));
// ===================== END QUERY PEMINJAMAN SISWA =====================

// Get filter parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Build query to get books
$query = 'SELECT * FROM books WHERE school_id = :school_id';
$params = ['school_id' => $school_id];

if (!empty($search)) {
    $query .= ' AND (title LIKE :search OR author LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

if (!empty($category)) {
    $query .= ' AND category = :category';
    $params['category'] = $category;
}

// Sort options
switch ($sort) {
    case 'oldest':
        $query .= ' ORDER BY created_at ASC';
        break;
    case 'popular':
        $query .= ' ORDER BY view_count DESC';
        break;
    default: // newest
        $query .= ' ORDER BY created_at DESC';
}

$query .= ' LIMIT 100';

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $books = $stmt->fetchAll();
} catch (Exception $e) {
    $books = [];
}

// Get categories for filter
try {
    $catStmt = $pdo->prepare('SELECT DISTINCT category FROM books WHERE school_id = :school_id ORDER BY category');
    $catStmt->execute(['school_id' => $school_id]);
    $categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $categories = [];
}

// Get borrow counts for statistics
try {
    $borrowStmt = $pdo->prepare('SELECT COUNT(*) as total_borrows FROM borrows WHERE school_id = :school_id AND status = "borrowed"');
    $borrowStmt->execute(['school_id' => $school_id]);
    $borrowStats = $borrowStmt->fetch();
} catch (Exception $e) {
    $borrowStats = ['total_borrows' => 0];
}

$pageTitle = 'Dashboard Siswa';
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Perpustakaan Siswa - Dashboard</title>
    <script src="../assets/js/db-theme-loader.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/school-profile.css">
    <link rel="stylesheet" href="../assets/css/student-dashboard.css">
</head>

<body>
    <!-- Navigation Sidebar -->
    <?php include 'partials/student-sidebar.php'; ?>

    <!-- Hamburger Menu Button -->
    <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
        <iconify-icon icon="mdi:menu" width="24" height="24"></iconify-icon>
    </button>

    <!-- Global Student Header -->
    <?php include 'partials/student-header.php'; ?>

    <!-- Main Container -->
    <div class="container">
        <div class="content-wrapper">
            <!-- Sidebar -->
            <aside class="sidebar">
                <!-- Search Tips -->
                <div class="sidebar-section">
                    <h3><iconify-icon icon="mdi:lightbulb-on" width="16" height="16"></iconify-icon> Tips</h3>
                    <p style="font-size: 12px; color: var(--text-muted); line-height: 1.6;">
                        Gunakan search untuk mencari buku berdasarkan judul atau pengarang. Filter kategori membantu
                        Anda menemukan buku yang Anda inginkan.
                    </p>
                </div>

                <!-- Category Filter -->
                <?php if (!empty($categories)): ?>
                    <div class="sidebar-section">
                        <h3><iconify-icon icon="mdi:folder-multiple" width="16" height="16"></iconify-icon> Kategori</h3>
                        <form method="get" class="filter-group">
                            <?php if (!empty($search)): ?>
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <?php endif; ?>
                            <?php foreach ($categories as $cat): ?>
                                <div class="filter-item">
                                    <input type="radio" id="cat-<?php echo htmlspecialchars($cat); ?>" name="category"
                                        value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'checked' : ''; ?>>
                                    <label
                                        for="cat-<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></label>
                                </div>
                            <?php endforeach; ?>
                            <div class="filter-item" style="margin-top: 12px;">
                                <input type="radio" id="cat-all" name="category" value="" <?php echo empty($category) ? 'checked' : ''; ?>>
                                <label for="cat-all"><strong>Semua Kategori</strong></label>
                            </div>
                            <button type="submit" class="btn-search" style="width: 100%; margin-top: 12px;">Filter</button>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- Quick Stats -->
                <div class="sidebar-section">
                    <h3><iconify-icon icon="mdi:chart-box" width="16" height="16"></iconify-icon> Statistik</h3>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <div>
                            <p style="font-size: 11px; color: var(--text-muted); margin-bottom: 4px;">Total Buku</p>
                            <p style="font-size: 20px; font-weight: 700; color: var(--primary);">
                                <?php echo count($books); ?>
                            </p>
                        </div>
                        <div>
                            <p style="font-size: 11px; color: var(--text-muted); margin-bottom: 4px;">Sedang Dipinjam
                            </p>
                            <p style="font-size: 20px; font-weight: 700; color: var(--danger);">
                                <?php echo $borrowStats['total_borrows']; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="main-content">
                <!-- Search & Sort Bar -->
                <div class="search-sort-bar">
                    <form method="get" style="display: flex; gap: 16px; flex: 1; align-items: center;">
                        <input type="text" name="search" class="search-input"
                            placeholder="Cari buku berdasarkan judul atau pengarang..."
                            value="<?php echo htmlspecialchars($search); ?>">
                        <select name="sort" class="sort-select" onchange="this.form.submit()">
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Terbaru</option>
                            <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Terlama</option>
                            <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Populer</option>
                        </select>
                        <button type="submit" class="btn-search">Cari</button>
                    </form>
                </div>

                <!-- Books Grid -->
                <div class="books-grid">
                    <?php if (!empty($books)): ?>
                        <?php foreach ($books as $book): ?>
                            <?php
                            $isAvailable = ($book['copies'] ?? 1) > 0;
                            $statusClass = $isAvailable ? 'available' : 'unavailable';
                            $statusText = $isAvailable ? 'Tersedia' : 'Tidak Tersedia';
                            ?>
                            <div class="book-card" data-book-id="<?php echo $book['id']; ?>">
                                <div class="book-cover">
                                    <?php if (!empty($book['cover_image'])): ?>
                                        <img src="../img/covers/<?php echo htmlspecialchars($book['cover_image']); ?>"
                                            alt="<?php echo htmlspecialchars($book['title']); ?>"
                                            style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <iconify-icon icon="mdi:book-open-variant" width="48" height="48"></iconify-icon>
                                    <?php endif; ?>
                                    <button class="btn-love"
                                        onclick="toggleFavorite(event, <?php echo $book['id']; ?>, '<?php echo htmlspecialchars($book['title']); ?>')">
                                        <iconify-icon icon="mdi:heart-outline" width="20" height="20"></iconify-icon>
                                    </button>
                                    <span class="book-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                </div>
                                <div class="book-info">
                                    <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                                    <p class="book-author"><?php echo htmlspecialchars($book['author'] ?? '-'); ?></p>
                                    <p class="book-category"><?php echo htmlspecialchars($book['category'] ?? 'Umum'); ?></p>
                                    <div class="book-rating">
                                        <span style="font-size: 11px; color: var(--text-muted);">ISBN:
                                            <?php echo htmlspecialchars($book['isbn'] ?? '-'); ?></span>
                                    </div>
                                    <div class="book-actions">
                                        <button class="btn-borrow" <?php echo !$isAvailable ? 'disabled' : ''; ?>"
                                            onclick="borrowBook(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars($book['title']); ?>')">Pinjam</button>
                                        <button class="btn-detail"
                                            onclick="openBookModal(<?php echo htmlspecialchars(json_encode($book)); ?>)">Detail</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon"><iconify-icon icon="mdi:book-search-outline" width="64"
                                    height="64"></iconify-icon></div>
                            <h3>Buku Tidak Ditemukan</h3>
                            <p>Coba ubah filter atau cari dengan kata kunci yang berbeda.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Book Detail Modal -->
    <div class="modal" id="bookModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Detail Buku</h2>
                <button class="modal-close" onclick="closeBookModal()">×</button>
            </div>
            <div class="modal-body">
                <div class="modal-book-left">
                    <div class="modal-book-cover">
                        <img id="modalBookCover" src="" alt="Cover" style="display: none;">
                        <iconify-icon id="modalBookIcon" icon="mdi:book-open-variant" width="80"
                            height="80"></iconify-icon>
                    </div>
                    <h3 class="modal-book-title" id="modalBookTitle">-</h3>
                </div>

                <div class="modal-book-info">
                    <div class="modal-book-meta">
                        <div class="modal-book-item">
                            <span class="modal-book-item-label">Pengarang</span>
                            <span class="modal-book-item-value" id="modalBookAuthor">-</span>
                        </div>

                        <div class="modal-book-item">
                            <span class="modal-book-item-label">Kategori</span>
                            <span class="modal-book-item-value" id="modalBookCategory">-</span>
                        </div>

                        <div class="modal-book-item">
                            <span class="modal-book-item-label">ISBN</span>
                            <span class="modal-book-item-value" id="modalBookISBN">-</span>
                        </div>

                        <div class="modal-book-item">
                            <span class="modal-book-item-label">Jumlah Tersedia</span>
                            <span class="modal-book-item-value" id="modalBookCopies">-</span>
                        </div>

                        <div class="modal-book-item">
                            <span class="modal-book-item-label">Lokasi Rak</span>
                            <span class="modal-book-item-value" id="modalBookShelf">-</span>
                        </div>

                        <div class="modal-book-item">
                            <span class="modal-book-item-label">Status</span>
                            <span class="modal-book-status" id="modalBookStatus">-</span>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button class="modal-btn modal-btn-borrow" id="modalBorrowBtn"
                            onclick="borrowFromModal()">Pinjam</button>
                        <button class="modal-btn modal-btn-close" onclick="closeBookModal()">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentBookData = null;
        let favorites = new Set();

        // Load favorites on page load
        async function loadFavorites() {
            try {
                const response = await fetch('/perpustakaan-online/public/api/favorites.php?action=get_favorites');
                const data = await response.json();
                if (data.success && data.data) {
                    data.data.forEach(fav => {
                        favorites.add(fav.id_buku);
                        const btn = document.querySelector(`[data-book-id="${fav.id_buku}"] .btn-love`);
                        if (btn) {
                            btn.classList.add('loved');
                            const icon = btn.querySelector('iconify-icon');
                            if (icon) icon.setAttribute('icon', 'mdi:heart');
                        }
                    });
                }
            } catch (error) {
                console.error('Error loading favorites:', error);
            }
        }

        // Toggle favorite
        async function toggleFavorite(e, bookId, bookTitle) {
            e.preventDefault();
            e.stopPropagation();

            const btn = e.currentTarget;
            const icon = btn.querySelector('iconify-icon');
            const isLoved = btn.classList.contains('loved');

            try {
                const formData = new FormData();
                formData.append('id_buku', bookId);

                const action = isLoved ? 'remove' : 'add';
                const response = await fetch(`/perpustakaan-online/public/api/favorites.php?action=${action}`, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    if (isLoved) {
                        btn.classList.remove('loved');
                        icon.setAttribute('icon', 'mdi:heart-outline');
                        favorites.delete(bookId);
                    } else {
                        btn.classList.add('loved');
                        icon.setAttribute('icon', 'mdi:heart');
                        favorites.add(bookId);
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Gagal mengubah favorite');
            }
        }

        // Navigation Sidebar Toggle
        const navToggle = document.getElementById('navToggle');
        const navSidebar = document.getElementById('navSidebar');

        navToggle.addEventListener('click', () => {
            navSidebar.classList.toggle('active');
        });

        // Close sidebar when clicking on a link
        document.querySelectorAll('.nav-sidebar-menu a').forEach(link => {
            link.addEventListener('click', () => {
                navSidebar.classList.remove('active');
            });
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', (e) => {
            if (!navSidebar.contains(e.target) && !navToggle.contains(e.target)) {
                navSidebar.classList.remove('active');
            }
        });

        // Close sidebar on window resize if >= 768px
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                navSidebar.classList.remove('active');
            }
        });

        // Load favorites when page loads
        document.addEventListener('DOMContentLoaded', () => {
            loadFavorites();
        });

        // Modal functions
        function openBookModal(bookData) {
            currentBookData = bookData;

            // Set cover image
            const coverImg = document.getElementById('modalBookCover');
            const coverIcon = document.getElementById('modalBookIcon');
            if (bookData.cover_image) {
                coverImg.src = '../img/covers/' + bookData.cover_image;
                coverImg.style.display = 'block';
                coverIcon.style.display = 'none';
            } else {
                coverImg.style.display = 'none';
                coverIcon.style.display = 'block';
            }

            // Set book details
            document.getElementById('modalBookTitle').textContent = bookData.title || '-';
            document.getElementById('modalBookAuthor').textContent = bookData.author || '-';
            document.getElementById('modalBookCategory').textContent = bookData.category || 'Umum';
            document.getElementById('modalBookISBN').textContent = bookData.isbn || '-';
            document.getElementById('modalBookCopies').textContent = bookData.copies || '0';
            document.getElementById('modalBookShelf').textContent = (bookData.shelf || '-') + (bookData.row_number ? ' (Baris ' + bookData.row_number + ')' : '');

            // Set status
            const isAvailable = (bookData.copies || 1) > 0;
            const statusEl = document.getElementById('modalBookStatus');
            if (isAvailable) {
                statusEl.textContent = 'Tersedia';
                statusEl.className = 'modal-book-status available';
            } else {
                statusEl.textContent = 'Tidak Tersedia';
                statusEl.className = 'modal-book-status unavailable';
            }

            // Enable/disable borrow button
            const borrowBtn = document.getElementById('modalBorrowBtn');
            borrowBtn.disabled = !isAvailable;

            // Show modal
            document.getElementById('bookModal').classList.add('active');
        }

        function closeBookModal() {
            document.getElementById('bookModal').classList.remove('active');
            currentBookData = null;
        }

        function borrowFromModal() {
            if (currentBookData) {
                borrowBook(currentBookData.id, currentBookData.title);
                closeBookModal();
            }
        }

        // Close modal when clicking outside
        document.getElementById('bookModal').addEventListener('click', (e) => {
            if (e.target.id === 'bookModal') {
                closeBookModal();
            }
        });

        function borrowBook(bookId, bookTitle) {
            if (!confirm('Apakah Anda ingin meminjam ' + bookTitle + '?')) {
                return;
            }

            fetch('api/borrow-book.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'book_id=' + bookId
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Buku berhasil dipinjam! Silakan ambil di perpustakaan.');
                        location.reload();
                    } else {
                        alert(data.message || 'Gagal meminjam buku');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan');
                });
        }

        // Request return function
        function requestReturn(borrowId) {
            if (!confirm('Apakah Anda ingin mengajukan pengembalian buku ini?')) {
                return;
            }

            fetch('api/student-request-return.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'borrow_id=' + borrowId
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Permintaan pengembalian telah dikirim ke admin!');
                        location.reload();
                    } else {
                        alert(data.message || 'Gagal mengajukan pengembalian');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan');
                });
        }
    </script>
    <script src="../assets/js/sidebar.js"></script>
</body>

</html>