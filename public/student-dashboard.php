<?php
session_start();
$pdo = require __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/MemberHelper.php';
require_once __DIR__ . '/../src/maintenance/DamageController.php';

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

// Get damage fines for this member
$damageController = new DamageController($pdo, $school_id);
$memberDamageFines = $damageController->getByMember($member_id);
$totalMemberDenda = 0;
$pendingMemberDenda = 0;
foreach ($memberDamageFines as $fine) {
    $totalMemberDenda += $fine['fine_amount'];
    if ($fine['status'] === 'pending') {
        $pendingMemberDenda += $fine['fine_amount'];
    }
}

// ===================== STATISTIK DASHBOARD =====================
// 1. Total Buku di sekolah ini
try {
    $totalBooksStmt = $pdo->prepare('SELECT COUNT(*) as total FROM books WHERE school_id = :school_id');
    $totalBooksStmt->execute(['school_id' => $school_id]);
    $totalBooks = (int) ($totalBooksStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
} catch (Exception $e) {
    $totalBooks = 0;
}

// 2. Jumlah buku yang sedang dipinjam siswa ini
try {
    $borrowCountStmt = $pdo->prepare(
        'SELECT COUNT(*) as total FROM borrows 
         WHERE school_id = :school_id 
         AND member_id = :member_id 
         AND returned_at IS NULL'
    );
    $borrowCountStmt->execute(['school_id' => $school_id, 'member_id' => $member_id]);
    $borrowCount = (int) ($borrowCountStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
} catch (Exception $e) {
    $borrowCount = 0;
}

// 3. Denda tertunda (overdue fines) dari keterlambatan peminjaman
// Menghitung denda keterlambatan (bukan damage fine, melainkan denda dari due date yang terlewat)
try {
    $lateFinesStmt = $pdo->prepare(
        'SELECT COUNT(*) as total FROM borrows 
         WHERE school_id = :school_id 
         AND member_id = :member_id 
         AND returned_at IS NULL 
         AND due_at < NOW()'
    );
    $lateFinesStmt->execute(['school_id' => $school_id, 'member_id' => $member_id]);
    $overdueCount = (int) ($lateFinesStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
} catch (Exception $e) {
    $overdueCount = 0;
}
// ===================== END STATISTIK DASHBOARD =====================

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
    $catStmt = $pdo->prepare('SELECT DISTINCT category FROM books WHERE school_id = :school_id AND category IS NOT NULL AND category != "" ORDER BY category');
    $catStmt->execute(['school_id' => $school_id]);
    $categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $categories = [];
}

// Tambahkan default categories untuk option yang komprehensif
$defaultCategories = ['Fiksi', 'Non-Fiksi', 'Referensi', 'Biografi', 'Sejarah', 'Seni & Budaya', 'Teknologi', 'Pendidikan', 'Anak-anak', 'Komik', 'Majalah', 'Lainnya'];

// Merge dengan database categories untuk menampilkan semua opsi
$categories = array_unique(array_merge($categories, $defaultCategories));
sort($categories);



// Daftar semua buku sekolah (untuk ditampilkan ketika siswa klik 'Total Buku')
try {
    $booksAvailStmt = $pdo->prepare('SELECT id, title, author, cover_image, category, isbn, shelf, row_number, copies, created_at, view_count FROM books WHERE school_id = :school_id ORDER BY created_at DESC');
    $booksAvailStmt->execute(['school_id' => $school_id]);
    $books_available = $booksAvailStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $books_available = [];
}

// Top viewed books (to represent 'buku yang sedang dilihat' apabila tidak ada tracking per-user)
try {
    $topViewedStmt = $pdo->prepare('SELECT id, title, author, cover_image, view_count FROM books WHERE school_id = :school_id ORDER BY view_count DESC LIMIT 10');
    $topViewedStmt->execute(['school_id' => $school_id]);
    $top_viewed_books = $topViewedStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $top_viewed_books = [];
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
    <style>
        /* Small animation for sidebar stat when clicked */
        .stat-click-anim { transition: transform .18s ease, box-shadow .18s ease; }
        /* No movement on select — only subtle background change on hover */
        .stat-selected { transform: none !important; box-shadow: none !important; background: color-mix(in srgb, var(--primary) 8%, transparent); border-left: none !important; }
        .stat-hover { background: color-mix(in srgb, var(--primary) 10%, transparent); }
        .stat-box { transition: background .18s ease, box-shadow .18s ease; }
            .stat-preview-box {
                position: absolute;
                min-width: 220px;
                max-width: 320px;
                padding: 10px 12px;
                border-radius: 8px;
                background: var(--card, #fff);
                box-shadow: 0 10px 28px rgba(0,0,0,0.12);
                transform-origin: top left;
                animation: previewIn .16s ease;
                z-index: 2300;
                font-weight: 600;
            }
            @keyframes previewIn { from { transform: translateY(-6px) scale(.99); opacity: 0 } to { transform: translateY(0) scale(1); opacity: 1 } }

        /* Category Dropdown Select Styling */
        .category-dropdown-select {
            padding: 10px 12px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 13px;
            font-family: 'Inter', system-ui, sans-serif;
            color: var(--text);
            background: var(--muted);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .category-dropdown-select:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(58, 127, 242, 0.15);
            border-color: var(--primary-2);
        }

        .category-dropdown-select:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--primary);
            color: white;
            box-shadow: 0 0 0 3px rgba(58, 127, 242, 0.2);
            font-weight: 500;
        }

        /* Books Grid Animation */
        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Search Form Responsive */
        .modern-search-bar-form {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }
        /* ---- Simple KPI cards (student) ---- */
        .kpi-grid { display:grid; grid-template-columns: repeat(4,1fr); gap:14px; margin: 18px 0 20px; }
        .kpi-card { display:flex; align-items:center; justify-content:space-between; background:#fff; padding:14px 16px; border-radius:12px; text-decoration:none; color:#1E1E1E; box-shadow: 0 6px 18px rgba(0,0,0,0.06); border:1px solid rgba(0,0,0,0.04); transition:transform .12s ease, box-shadow .12s ease }
        .kpi-card:hover { transform:translateY(-4px); box-shadow: 0 10px 24px rgba(0,0,0,0.08) }
        .kpi-left { display:flex; flex-direction:column }
        .kpi-title { font-size:12px; color:#6b6b6b; font-weight:600; text-transform:uppercase; letter-spacing:0.6px }
        .kpi-value { font-size:20px; font-weight:700; margin-top:6px; color:#1E1E1E }
        .kpi-icon { width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; background:rgba(58,122,254,0.06); color:#3A7AFE; flex-shrink:0 }
        @media (max-width:900px){ .kpi-grid{ grid-template-columns: repeat(2,1fr) } }
        @media (max-width:480px){ .kpi-grid{ grid-template-columns: 1fr } .kpi-card{ padding:12px } }

        .search-bar-wrapper {
            flex: 1;
            min-width: 250px;
        }

        @media (max-width: 768px) {
            .modern-search-bar-form {
                flex-direction: column;
            }

            .search-bar-wrapper,
            .category-dropdown-select {
                width: 100%;
            }
        }
    </style>
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
                <!-- Total Denda -->
                <div class="sidebar-section" style="animation: fadeInSlideUp 0.4s ease-out;">
                    <h3><iconify-icon icon="mdi:alert-circle" width="16" height="16"></iconify-icon> Denda Anda</h3>
                    <div
                        style="padding: 12px; background-color: <?php echo $pendingMemberDenda > 0 ? 'rgba(239, 68, 68, 0.05)' : 'rgba(16, 185, 129, 0.05)'; ?>; border-radius: 6px; border-left: 4px solid <?php echo $pendingMemberDenda > 0 ? '#ef4444' : '#10b981'; ?>;">
                        <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 6px;">Denda Tertunda</div>
                        <div
                            style="font-size: 18px; font-weight: 700; color: <?php echo $pendingMemberDenda > 0 ? '#dc2626' : '#059669'; ?>; margin-bottom: 8px;">
                            Rp <?php echo number_format($pendingMemberDenda, 0, ',', '.'); ?></div>
                        <?php if ($pendingMemberDenda > 0): ?>
                            <p style="font-size: 11px; color: var(--text-muted); margin: 0; line-height: 1.5;">Denda dari
                                kerusakan buku saat peminjaman. Silakan hubungi admin untuk detail.</p>
                        <?php else: ?>
                            <p style="font-size: 11px; color: #10b981; margin: 0;">✓ Tidak ada denda tertunda</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Category Filter -->
                <?php if (!empty($categories)): ?>
                <?php endif; ?>

                <!-- Quick Stats -->
                <div class="sidebar-section">
                    <h3><iconify-icon icon="mdi:chart-box" width="16" height="16"></iconify-icon> Statistik</h3>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <!-- Total Buku Card -->
                        <div style="padding: 14px; background: color-mix(in srgb, var(--primary) 8%, transparent); border-radius: 8px; border-left: 4px solid var(--primary); cursor: pointer; transition: all 0.2s ease;">
                            <p style="font-size: 10px; color: var(--text-muted); margin: 0 0 6px 0; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;">Total Buku</p>
                            <div style="display: flex; align-items: baseline; gap: 8px;">
                                <p style="font-size: 28px; font-weight: 700; color: var(--primary); margin: 0;">
                                    <?php echo $totalBooks; ?>
                                </p>
                                <iconify-icon icon="mdi:book-multiple" width="20" height="20" style="color: var(--primary); opacity: 0.6;"></iconify-icon>
                            </div>
                        </div>

                        <!-- Sedang Dipinjam Card -->
                        <div style="padding: 14px; background: color-mix(in srgb, var(--danger) 8%, transparent); border-radius: 8px; border-left: 4px solid var(--danger); cursor: pointer; transition: all 0.2s ease;">
                            <p style="font-size: 10px; color: var(--text-muted); margin: 0 0 6px 0; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;">Sedang Dipinjam</p>
                            <div style="display: flex; align-items: baseline; gap: 8px;">
                                <p style="font-size: 28px; font-weight: 700; color: var(--danger); margin: 0;">
                                    <?php echo $borrowCount; ?>
                                </p>
                                <iconify-icon icon="mdi:clock-outline" width="20" height="20" style="color: var(--danger); opacity: 0.6;"></iconify-icon>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="main-content">
                <!-- Modern Search Bar with Category Dropdown -->
                <!-- KPI Cards -->
                <div class="kpi-grid" role="list">
                    <a class="kpi-card" href="books.php" role="listitem">
                        <div class="kpi-left">
                            <div class="kpi-title">Total Buku</div>
                            <div class="kpi-value"><?php echo $totalBooks; ?></div>
                        </div>
                        <div class="kpi-icon"><iconify-icon icon="mdi:book-open-variant" width="20" height="20"></iconify-icon></div>
                    </a>

                    <a class="kpi-card" href="student-borrowing-history.php" role="listitem">
                        <div class="kpi-left">
                            <div class="kpi-title">Sedang Dipinjam</div>
                            <div class="kpi-value"><?php echo $borrowCount; ?></div>
                        </div>
                        <div class="kpi-icon"><iconify-icon icon="mdi:clock-outline" width="20" height="20"></iconify-icon></div>
                    </a>

                    <a class="kpi-card" href="student-borrowing-history.php" role="listitem">
                        <div class="kpi-left">
                            <div class="kpi-title">Aktif Dipinjam</div>
                            <div class="kpi-value"><?php echo $active_borrows; ?></div>
                        </div>
                        <div class="kpi-icon"><iconify-icon icon="mdi:swap-vertical" width="20" height="20"></iconify-icon></div>
                    </a>

                    <a class="kpi-card" href="student-borrowing-history.php?filter=overdue" role="listitem">
                        <div class="kpi-left">
                            <div class="kpi-title">Terlambat / Overdue</div>
                            <div class="kpi-value"><?php echo $overdueCount ?? $overdue_count ?? 0; ?></div>
                        </div>
                        <div class="kpi-icon"><iconify-icon icon="mdi:alert-circle-outline" width="20" height="20"></iconify-icon></div>
                    </a>
                </div>

                <form method="get" class="modern-search-bar-form" onsubmit="return false;">
                    <!-- Search Bar (Dominant) -->
                    <div class="search-bar-wrapper">
                        <div class="search-bar-container">
                            <iconify-icon icon="mdi:magnify" class="search-icon"></iconify-icon>
                            <input type="text" name="search" class="modern-search-input"
                                placeholder="Cari buku…"
                                value="">
                        </div>
                    </div>

                    <!-- Category Dropdown - Select Element -->
                    <select id="categorySelect" class="category-dropdown-select">
                        <option value="">Semua Kategori</option>
                    </select>

                    <input type="hidden" name="category" id="categoryInput" value="">
                </form>

                <!-- Books Grid -->
                <div class="books-grid">
                    <?php if (!empty($books)): ?>
                        <?php foreach ($books as $book): ?>
                            <?php
                            $isAvailable = $book['copies'] > 0;
                            $statusClass = $isAvailable ? 'available' : 'unavailable';
                            $statusText = $isAvailable ? 'Tersedia' : 'HABIS';
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

        // ====== CATEGORY FILTER FUNCTIONALITY ======
        let allBooks = <?php echo json_encode($books); ?>;
        let filteredBooks = [...allBooks];
        let currentCategoryFilter = '';

        // Get unique categories from books
        function getUniqueCategoriesFromBooks() {
            const categories = new Set();
            allBooks.forEach(book => {
                if (book.category) {
                    categories.add(book.category);
                }
            });
            return Array.from(categories).sort();
        }

        // Initialize category filter dropdown
        function initCategoryFilter() {
            const categorySelect = document.getElementById('categorySelect');
            if (!categorySelect) return;

            const categories = getUniqueCategoriesFromBooks();
            const currentValue = categorySelect.value;

            // Clear existing options except the first one
            while (categorySelect.children.length > 1) {
                categorySelect.removeChild(categorySelect.lastChild);
            }

            // Add categories
            categories.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat;
                option.textContent = cat;
                categorySelect.appendChild(option);
            });

            // Restore previous value if it exists
            if (currentValue && categories.includes(currentValue)) {
                categorySelect.value = currentValue;
                currentCategoryFilter = currentValue;
            }
        }

        // Handle category filter change
        function onCategoryChange(event) {
            currentCategoryFilter = event.target.value;
            filterAndDisplayBooks();
        }

        // Filter books by search and category
        function filterAndDisplayBooks() {
            const searchInput = document.querySelector('input[name="search"]');
            const searchTerm = (searchInput?.value || '').toLowerCase();

            filteredBooks = allBooks.filter(book => {
                const matchSearch = !searchTerm || 
                    (book.title || '').toLowerCase().includes(searchTerm) || 
                    (book.author || '').toLowerCase().includes(searchTerm);
                const matchCategory = !currentCategoryFilter || book.category === currentCategoryFilter;
                return matchSearch && matchCategory;
            });

            updateBooksDisplay();
        }

        // Update books grid display
        function updateBooksDisplay() {
            const booksGrid = document.querySelector('.books-grid');
            if (!booksGrid) return;

            if (filteredBooks.length === 0) {
                booksGrid.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; padding: 60px 24px;">
                        <iconify-icon icon="mdi:book-search-outline" style="font-size: 48px; opacity: 0.3; margin-bottom: 12px; display: block;"></iconify-icon>
                        <h3 style="margin: 0 0 8px 0; color: var(--text);">Tidak ada buku</h3>
                        <p style="margin: 0; color: var(--text-muted);">Coba ubah filter atau pencarian Anda</p>
                    </div>
                `;
                return;
            }

            booksGrid.innerHTML = filteredBooks.map(book => `
                <div class="book-card" style="animation: fadeInScale 0.3s ease-out;">
                    <div class="book-card-cover" style="position: relative;">
                        ${book.cover_image ? 
                            `<img src="../img/covers/${book.cover_image}" alt="${book.title}" style="width: 100%; height: 100%; object-fit: cover;">` :
                            `<div style="width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white;"><iconify-icon icon="mdi:book-open-variant" width="48" height="48"></iconify-icon></div>`
                        }
                        <button class="btn-love ${favorites.has(book.id) ? 'loved' : ''}" onclick="toggleFavorite(event, ${book.id}, '${(book.title || '').replace(/'/g, "\\'")}')">
                            <iconify-icon icon="mdi:heart"></iconify-icon>
                        </button>
                    </div>
                    <div class="book-card-body">
                        <h3 class="book-card-title">${book.title}</h3>
                        <p class="book-card-author">${book.author || '-'}</p>
                        <p class="book-card-category">${book.category || 'Umum'}</p>
                        <div class="book-card-actions">
                            <button class="btn-borrow" onclick="borrowBook(${book.id}, '${(book.title || '').replace(/'/g, "\\'")}')">
                                <iconify-icon icon="mdi:cart-plus"></iconify-icon>
                                Pinjam
                            </button>
                            <button class="btn-detail" onclick="openBookModal({id: ${book.id}, title: '${(book.title || '').replace(/'/g, "\\'")}', author: '${(book.author || '').replace(/'/g, "\\'")}', category: '${(book.category || '').replace(/'/g, "\\'")}', isbn: '${(book.isbn || '').replace(/'/g, "\\'")}', copies: ${book.copies || 0}, cover_image: '${book.cover_image || ''}'})">
                                <iconify-icon icon="mdi:information"></iconify-icon>
                                Detail
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

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

        // Load favorites when page loads
        document.addEventListener('DOMContentLoaded', () => {
            loadFavorites();
            initCategoryFilter();

            // Add search event listener for real-time filtering
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) {
                searchInput.addEventListener('input', filterAndDisplayBooks);
            }

            // Add category change listener
            const categorySelect = document.getElementById('categorySelect');
            if (categorySelect) {
                categorySelect.addEventListener('change', onCategoryChange);
            }
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
            const isAvailable = bookData.copies > 0;
            const statusEl = document.getElementById('modalBookStatus');
            if (isAvailable) {
                statusEl.textContent = 'Tersedia';
                statusEl.className = 'modal-book-status available';
            } else {
                statusEl.textContent = 'HABIS';
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
    <script>
        // ----- backend-provided data for stats lists (full book objects) -----
        const BOOKS_AVAILABLE_SERVER = <?php echo json_encode(array_map(function($b){ return ['title' => $b['title'] ?? '', 'author' => $b['author'] ?? '-', 'category' => $b['category'] ?? '-', 'isbn' => $b['isbn'] ?? '-', 'copies' => $b['copies'] ?? 0]; }, $books_available ?? []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?> || [];
        const BOOKS_AVAILABLE_FALLBACK = <?php echo json_encode(array_map(function($b){ return ['title' => $b['title'] ?? '', 'author' => $b['author'] ?? '-', 'category' => $b['category'] ?? '-', 'isbn' => $b['isbn'] ?? '-', 'copies' => $b['copies'] ?? 0]; }, $books ?? []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?> || [];
        const BOOKS_AVAILABLE = (Array.isArray(BOOKS_AVAILABLE_SERVER) && BOOKS_AVAILABLE_SERVER.length > 0) ? BOOKS_AVAILABLE_SERVER : BOOKS_AVAILABLE_FALLBACK;
        const TOP_VIEWED_BOOKS = <?php echo json_encode(array_map(function($b){ return ['title' => $b['title'] ?? '', 'author' => $b['author'] ?? '-', 'category' => '-', 'isbn' => '-', 'copies' => 0]; }, $top_viewed_books ?? []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?> || [];
        // use $my_borrows (already fetched) and filter returned_at IS NULL
        const STUDENT_CURRENT_BORROWS = <?php echo json_encode(array_values(array_map(function($r){ return ['title' => $r['title'] ?? '', 'author' => $r['author'] ?? '-', 'category' => '-', 'isbn' => '-', 'copies' => 0]; }, array_filter($my_borrows, function($r){ return is_null($r['returned_at']); }))), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?> || [];

        function createListOverlay(title, itemsHtml) {
            const existing = document.getElementById('statsListOverlay');
            if (existing) existing.remove();

            const overlay = document.createElement('div');
            overlay.id = 'statsListOverlay';
            overlay.className = 'modal-overlay active';
            overlay.style.position = 'fixed';
            overlay.style.top = '0';
            overlay.style.left = '0';
            overlay.style.right = '0';
            overlay.style.bottom = '0';
            overlay.style.background = 'rgba(0, 0, 0, 0.6)';
            overlay.style.display = 'flex';
            overlay.style.alignItems = 'center';
            overlay.style.justifyContent = 'center';
            overlay.style.zIndex = '2200';
            overlay.style.opacity = '1';
            overlay.style.transition = 'opacity 0.3s ease';
            overlay.style.backdropFilter = 'blur(2px)';

            const container = document.createElement('div');
            container.className = 'modal-container';
            container.style.background = 'var(--card, #fff)';
            container.style.borderRadius = '14px';
            container.style.maxWidth = '900px';
            container.style.width = '90%';
            container.style.maxHeight = '75vh';
            container.style.display = 'flex';
            container.style.flexDirection = 'column';
            container.style.boxShadow = '0 20px 60px rgba(0, 0, 0, 0.2)';
            container.style.transform = 'scale(0.95)';
            container.style.transition = 'transform 0.3s ease';
            container.style.border = '1px solid var(--border, #e0e0e0)';
            container.style.animation = 'scaleIn 0.3s ease-out';

            // Header
            const header = document.createElement('div');
            header.className = 'modal-header';
            header.style.display = 'flex';
            header.style.justifyContent = 'space-between';
            header.style.alignItems = 'center';
            header.style.padding = '24px';
            header.style.borderBottom = '1px solid var(--border, #e0e0e0)';
            header.style.flexShrink = '0';
            header.style.background = 'linear-gradient(135deg, var(--muted, #f5f5f5) 0%, transparent 100%)';

            const h = document.createElement('h2');
            h.textContent = title;
            h.style.margin = '0';
            h.style.fontSize = '18px';
            h.style.fontWeight = '700';
            h.style.color = 'var(--text, #333)';
            h.style.letterSpacing = '0.5px';
            header.appendChild(h);

            const closeBtn = document.createElement('button');
            closeBtn.className = 'modal-close';
            closeBtn.textContent = '×';
            closeBtn.style.background = 'rgba(58, 127, 242, 0.1)';
            closeBtn.style.border = 'none';
            closeBtn.style.fontSize = '28px';
            closeBtn.style.cursor = 'pointer';
            closeBtn.style.color = 'var(--primary, #3a7ff2)';
            closeBtn.style.padding = '0';
            closeBtn.style.width = '36px';
            closeBtn.style.height = '36px';
            closeBtn.style.display = 'flex';
            closeBtn.style.alignItems = 'center';
            closeBtn.style.justifyContent = 'center';
            closeBtn.style.borderRadius = '8px';
            closeBtn.style.transition = 'all 0.2s ease';
            closeBtn.onmouseover = () => {
                closeBtn.style.background = 'rgba(58, 127, 242, 0.2)';
                closeBtn.style.transform = 'rotate(90deg)';
            };
            closeBtn.onmouseout = () => {
                closeBtn.style.background = 'rgba(58, 127, 242, 0.1)';
                closeBtn.style.transform = 'rotate(0deg)';
            };
            closeBtn.onclick = () => {
                overlay.style.opacity = '0';
                overlay.style.pointerEvents = 'none';
                setTimeout(() => overlay.remove(), 300);
            };
            header.appendChild(closeBtn);
            container.appendChild(header);

            // Body
            const body = document.createElement('div');
            body.className = 'modal-body';
            body.style.flex = '1';
            body.style.overflowY = 'auto';
            body.style.padding = '24px';
            body.style.fontSize = '14px';
            body.style.color = 'var(--text, #333)';
            body.innerHTML = itemsHtml;
            container.appendChild(body);

            overlay.appendChild(container);
            document.body.appendChild(overlay);
            
            // Trigger animation
            setTimeout(() => {
                container.style.transform = 'scale(1)';
            }, 10);
        }

        // Render detailed book list with complete information - styled as table
        function renderBooksListHtml(list) {
            if (!list || list.length === 0) return '<div style="text-align: center; color: var(--text-muted); padding: 40px 16px;"><iconify-icon icon="mdi:book-search-outline" width="48" height="48" style="opacity: 0.3; margin-bottom: 12px; display: block;"></iconify-icon><p style="margin: 0;">Tidak ada data</p></div>';
            
            let html = `
                <div style="overflow-x: auto; border-radius: 8px; border: 1px solid var(--border);">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <thead>
                            <tr style="background: linear-gradient(135deg, var(--muted) 0%, transparent 100%); border-bottom: 2px solid var(--border);">
                                <th style="padding: 14px 16px; text-align: left; font-weight: 700; color: var(--text); letter-spacing: 0.5px; white-space: nowrap;">Judul Buku</th>
                                <th style="padding: 14px 16px; text-align: left; font-weight: 700; color: var(--text); letter-spacing: 0.5px; white-space: nowrap;">Penulis</th>
                                <th style="padding: 14px 16px; text-align: left; font-weight: 700; color: var(--text); letter-spacing: 0.5px; white-space: nowrap;">Kategori</th>
                                <th style="padding: 14px 16px; text-align: center; font-weight: 700; color: var(--text); letter-spacing: 0.5px; white-space: nowrap;">Stok</th>
                                <th style="padding: 14px 16px; text-align: center; font-weight: 700; color: var(--text); letter-spacing: 0.5px; white-space: nowrap;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            list.forEach((item, idx) => {
                const title = (item.title || '-').toString();
                const author = (item.author || '-').toString();
                const category = (item.category || '-').toString();
                const copies = item.copies || 0;
                const statusClass = copies > 0 ? 'tersedia' : 'habis';
                const statusText = copies > 0 ? 'Tersedia' : 'HABIS';
                const bgColor = idx % 2 === 0 ? 'transparent' : 'rgba(58, 127, 242, 0.02)';
                
                html += `
                    <tr style="background: ${bgColor}; border-bottom: 1px solid var(--border); transition: all 0.2s ease;" 
                        onmouseover="this.style.backgroundColor='rgba(58, 127, 242, 0.08)'; this.style.boxShadow='inset 4px 0 0 var(--primary)';"
                        onmouseout="this.style.backgroundColor='${bgColor}'; this.style.boxShadow='none';">
                        <td style="padding: 12px 16px; color: var(--text); font-weight: 500;">${escapeHtml(title)}</td>
                        <td style="padding: 12px 16px; color: var(--text-muted);">${escapeHtml(author)}</td>
                        <td style="padding: 12px 16px; color: var(--text-muted);">${escapeHtml(category)}</td>
                        <td style="padding: 12px 16px; text-align: center; color: var(--text); font-weight: 600;">${copies}</td>
                        <td style="padding: 12px 16px; text-align: center;">
                            <span style="display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; background: ${copies > 0 ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)'}; color: ${copies > 0 ? '#059669' : '#dc2626'};">
                                ${statusText}
                            </span>
                        </td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            return html;
        }

        function escapeHtml(text) {
            if (!text) return '';
            return text
                .toString()
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // ===== CATEGORY DROPDOWN - COMPLETE IMPLEMENTATION =====
        
        /**
         * Toggle dropdown visibility
         * Buka/tutup menu dropdown kategori
         */
        function toggleCategoryDropdown(event) {
            event.preventDefault();
            event.stopPropagation();
            
            const btn = event.currentTarget;
            const dropdown = document.getElementById('categoryDropdown');
            const isOpen = dropdown.classList.contains('active');
            
            console.log('🔧 Dropdown toggle clicked - Current state:', isOpen);
            console.log('🔧 Dropdown element:', dropdown);
            console.log('🔧 Button element:', btn);
            
            if (isOpen) {
                // Tutup dropdown
                console.log('✅ Closing dropdown');
                dropdown.classList.remove('active');
                btn.classList.remove('open');
            } else {
                // Buka dropdown
                console.log('✅ Opening dropdown');
                dropdown.classList.add('active');
                btn.classList.add('open');
            }
            
            console.log('🔧 After toggle - dropdown classes:', dropdown.className);
        }

        /**
         * Handle kategori selection
         * 1. Update hidden input value
         * 2. Update button label text
         * 3. Set active class pada item yang dipilih
         * 4. Tutup dropdown
         * 5. Submit form untuk filter
         */
        function selectCategoryOption(event, categoryValue) {
            event.preventDefault();
            event.stopPropagation();
            
            // ✅ Step 1: Update hidden input
            const categoryInput = document.getElementById('categoryInput');
            categoryInput.value = categoryValue;
            
            // ✅ Step 2: Update button label
            const label = document.getElementById('categoryLabel');
            label.textContent = categoryValue === '' ? 'Kategori' : categoryValue;
            
            // ✅ Step 3: Update active class on dropdown items
            const items = document.querySelectorAll('.dropdown-item');
            items.forEach(item => {
                item.classList.remove('active');
                const itemText = item.textContent.trim();
                const expectedText = categoryValue === '' ? 'Semua Kategori' : categoryValue;
                if (itemText === expectedText) {
                    item.classList.add('active');
                }
            });
            
            // ✅ Step 4: Tutup dropdown
            closeAllDropdowns();
            
            // ✅ Step 5: Submit form (akan trigger filter dengan GET parameters: search, category, sort)
            const form = document.querySelector('.modern-search-bar-form');
            if (form) {
                form.submit();
            }
        }

        /**
         * Close all open dropdowns
         */
        function closeAllDropdowns() {
            const dropdown = document.getElementById('categoryDropdown');
            const btn = document.querySelector('.category-dropdown-btn');
            
            if (dropdown) {
                dropdown.classList.remove('active');
            }
            if (btn) {
                btn.classList.remove('open');
            }
        }

        /**
         * Close dropdown ketika user click di luar area dropdown
         * Gunakan DOMContentLoaded untuk pastikan element sudah di-load
         */
        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('click', function(event) {
                const wrapper = document.querySelector('.category-dropdown-wrapper');
                
                // Jika click di DALAM wrapper (button, dropdown menu), jangan close
                if (wrapper && wrapper.contains(event.target)) {
                    return;
                }
                
                // Jika click di LUAR wrapper, tutup dropdown
                closeAllDropdowns();
            });
        });

        // Category selection function (dari kategori pill)
        function selectCategory(e, category) {
            e.preventDefault();
            
            // Update pills
            document.querySelectorAll('.category-pill').forEach(pill => {
                pill.classList.remove('active');
            });
            e.target.closest('.category-pill').classList.add('active');
            
            // Update dropdown dan kategori input
            document.getElementById('categoryInput').value = category;
            document.getElementById('categoryLabel').textContent = category;
            
            // Update dropdown items
            const items = document.querySelectorAll('.dropdown-item');
            items.forEach(item => {
                item.classList.remove('active');
                if (item.textContent.trim() === category) {
                    item.classList.add('active');
                }
            });
            
            // Trigger filter
            const form = document.querySelector('.modern-search-bar-form');
            form.submit();
        }

        function toggleAllCategories() {
            // Placeholder for showing all categories modal/dropdown
            alert('Fitur melihat semua kategori akan ditampilkan di sini');
        }

        (function attachStatsHandlers(){
            const sections = Array.from(document.querySelectorAll('.sidebar-section'));
            const statSection = sections.find(s => (s.textContent||'').trim().startsWith('Statistik')) || sections[0];
            if (!statSection) return;

            const container = statSection.querySelector('div[style*="flex-direction: column"]') || statSection.querySelector('div');
            if (!container) return;

            const statBoxes = Array.from(container.children).filter(n => n.nodeType === 1);
            if (statBoxes.length < 1) return;

            // Hover -> subtle background only. Click -> show overlay with data.
            statBoxes.forEach((box, idx) => {
                box.classList.add('stat-box');
                box.style.cursor = 'pointer';

                box.addEventListener('mouseenter', () => {
                    box.classList.add('stat-hover');
                });

                box.addEventListener('mouseleave', () => {
                    box.classList.remove('stat-hover');
                });

                box.addEventListener('click', (e) => {
                    e.preventDefault();
                    // debug: print arrays to console to verify data
                    try {
                        console.debug('BOOKS_AVAILABLE length:', Array.isArray(BOOKS_AVAILABLE) ? BOOKS_AVAILABLE.length : typeof BOOKS_AVAILABLE, BOOKS_AVAILABLE);
                        console.debug('STUDENT_CURRENT_BORROWS length:', Array.isArray(STUDENT_CURRENT_BORROWS) ? STUDENT_CURRENT_BORROWS.length : typeof STUDENT_CURRENT_BORROWS, STUDENT_CURRENT_BORROWS);
                        console.debug('TOP_VIEWED_BOOKS length:', Array.isArray(TOP_VIEWED_BOOKS) ? TOP_VIEWED_BOOKS.length : typeof TOP_VIEWED_BOOKS, TOP_VIEWED_BOOKS);
                    } catch (err) {
                        console.error('Debug log error:', err);
                    }
                    if (idx === 0) createListOverlay('Semua Buku', renderBooksListHtml(BOOKS_AVAILABLE));
                    else if (idx === 1) createListOverlay('Buku yang Sedang Anda Pinjam', renderBooksListHtml(STUDENT_CURRENT_BORROWS));
                    else createListOverlay('Daftar', renderBooksListHtml(TOP_VIEWED_BOOKS));
                });
            });
        })();
    </script>
    <script src="../assets/js/sidebar.js"></script>
</body>

</html>