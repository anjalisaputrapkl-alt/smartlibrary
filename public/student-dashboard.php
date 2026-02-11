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

// 4. Get student's loan limit (max_pinjam)
try {
    $maxPinjamStmt = $pdo->prepare('SELECT max_pinjam FROM members WHERE id = :member_id');
    $maxPinjamStmt->execute(['member_id' => $member_id]);
    $maxPinjam = (int) ($maxPinjamStmt->fetch(PDO::FETCH_ASSOC)['max_pinjam'] ?? 2);
} catch (Exception $e) {
    $maxPinjam = 2;
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

// Build query to get books with availability status and current borrower
$query = 'SELECT bk.*, 
                 curr_b.id as current_borrow_id, curr_b.due_at as borrower_due_at,
                 m.name as borrower_name,
                 (SELECT AVG(rating) FROM rating_buku WHERE id_buku = bk.id) as avg_rating,
                 (SELECT COUNT(*) FROM rating_buku WHERE id_buku = bk.id) as total_reviews
          FROM books bk
          LEFT JOIN borrows curr_b ON bk.id = curr_b.book_id AND curr_b.returned_at IS NULL
          LEFT JOIN members m ON curr_b.member_id = m.id
          WHERE bk.school_id = :school_id';
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
    $booksAvailStmt = $pdo->prepare('SELECT bk.*, 
                                            curr_b.id as current_borrow_id, curr_b.due_at as borrower_due_at,
                                            m.name as borrower_name,
                                            (SELECT AVG(rating) FROM rating_buku WHERE id_buku = bk.id) as avg_rating,
                                            (SELECT COUNT(*) FROM rating_buku WHERE id_buku = bk.id) as total_reviews
                                     FROM books bk
                                     LEFT JOIN borrows curr_b ON bk.id = curr_b.book_id AND curr_b.returned_at IS NULL
                                     LEFT JOIN members m ON curr_b.member_id = m.id
                                     WHERE bk.school_id = :school_id 
                                     ORDER BY bk.created_at DESC');
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

// Set dynamic page title
$userRole = $_SESSION['user']['role'] ?? 'student';
$roleLabel = 'Siswa';
if ($userRole === 'teacher') $roleLabel = 'Guru';
elseif ($userRole === 'employee') $roleLabel = 'Karyawan';

$pageTitle = 'Dashboard ' . $roleLabel;
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Perpustakaan - <?php echo $pageTitle; ?></title>
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
        .kpi-grid { display:grid; grid-template-columns: repeat(3,1fr); gap:16px; margin: 18px 0 24px; }
        .kpi-card { 
            display:flex; 
            align-items:center; 
            justify-content:space-between; 
            background: var(--card); 
            padding: 20px; 
            border-radius: 16px; 
            text-decoration: none; 
            color: var(--text); 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid var(--border); 
            transition: all 0.2s ease;
        }
        .kpi-card:hover { 
            transform: translateY(-4px); 
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            border-color: var(--primary);
        }
        .kpi-left { display:flex; flex-direction:column }
        .kpi-title { font-size:11px; color:var(--text-muted); font-weight:700; text-transform:uppercase; letter-spacing:0.05em }
        .kpi-value { font-size:24px; font-weight:800; margin-top:4px; color:var(--text); line-height: 1; }
        .kpi-icon { 
            width:48px; 
            height:48px; 
            border-radius: 12px; 
            display:flex; 
            align-items:center; 
            justify-content:center; 
            background: var(--bg); 
            color: var(--primary); 
            flex-shrink:0;
            transition: all 0.2s ease;
        }
        .kpi-card:hover .kpi-icon {
            background: var(--primary);
            color: white;
        }
        @media (max-width:900px){ .kpi-grid{ grid-template-columns: repeat(2,1fr) } }
        @media (max-width:480px){ .kpi-grid{ grid-template-columns: 1fr } .kpi-card{ padding:16px } }
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
                        style="padding: 12px; background-color: <?php echo $pendingMemberDenda > 0 ? 'color-mix(in srgb, var(--danger) 5%, transparent)' : 'color-mix(in srgb, var(--success) 5%, transparent)'; ?>; border-radius: 8px; border-left: 4px solid <?php echo $pendingMemberDenda > 0 ? 'var(--danger)' : 'var(--success)'; ?>; border: 1px solid <?php echo $pendingMemberDenda > 0 ? 'color-mix(in srgb, var(--danger) 15%, transparent)' : 'color-mix(in srgb, var(--success) 15%, transparent)'; ?>;">
                        <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 6px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Denda Tertunda</div>
                        <div
                            style="font-size: 18px; font-weight: 700; color: <?php echo $pendingMemberDenda > 0 ? 'var(--danger)' : 'var(--success)'; ?>; margin-bottom: 8px;">
                            Rp <?php echo number_format($pendingMemberDenda, 0, ',', '.'); ?></div>
                        <?php if ($pendingMemberDenda > 0): ?>
                            <p style="font-size: 11px; color: var(--text-muted); margin: 0; line-height: 1.5;">Denda dari
                                kerusakan buku saat peminjaman. Silakan hubungi admin untuk detail.</p>
                        <?php else: ?>
                            <p style="font-size: 11px; color: var(--success); margin: 0; font-weight: 500;">✓ Tidak ada denda tertunda</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Category Filter -->
                <?php if (!empty($categories)): ?>
                <?php endif; ?>

                <!-- Library News -->
                <div class="sidebar-section" style="animation: fadeInSlideUp 0.5s ease-out 0.1s both;">
                    <h3><iconify-icon icon="mdi:bullhorn-variant" width="16" height="16"></iconify-icon> Info Perpus</h3>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <div style="padding: 10px; background: var(--bg); border: 1px solid var(--border); border-radius: 10px;">
                            <div style="font-size: 10px; color: var(--primary); font-weight: 700; margin-bottom: 2px;">BARU DATANG</div>
                            <div style="font-size: 12px; font-weight: 600; color: var(--text);">5 Koleksi buku fiksi baru bulan Februari!</div>
                        </div>
                        <div style="padding: 10px; background: var(--bg); border: 1px solid var(--border); border-radius: 10px;">
                            <div style="font-size: 10px; color: var(--text-muted); font-weight: 700; margin-bottom: 2px;">PENGUMUMAN</div>
                            <div style="font-size: 12px; font-weight: 500; color: var(--text-muted);">Kembalikan buku tepat waktu untuk menghindari denda.</div>
                        </div>
                    </div>
                </div>

                <!-- Trending Books -->
                <?php if (!empty($top_viewed_books)): ?>
                <div class="sidebar-section" style="animation: fadeInSlideUp 0.5s ease-out 0.2s both;">
                    <h3><iconify-icon icon="mdi:trending-up" width="16" height="16"></iconify-icon> Buku Terpopuler</h3>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach (array_slice($top_viewed_books, 0, 3) as $pop_book): ?>
                            <div style="display: flex; gap: 12px; align-items: center; cursor: pointer;" onclick="openBookModal(<?php echo htmlspecialchars(json_encode($pop_book)); ?>)">
                                <div style="width: 45px; height: 60px; border-radius: 6px; overflow: hidden; flex-shrink: 0; background: var(--bg); border: 1px solid var(--border);">
                                    <?php if (!empty($pop_book['cover_image'])): ?>
                                        <img src="../img/covers/<?php echo htmlspecialchars($pop_book['cover_image']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--text-muted);"><iconify-icon icon="mdi:book" width="20"></iconify-icon></div>
                                    <?php endif; ?>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-size: 13px; font-weight: 600; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($pop_book['title']); ?></div>
                                    <div style="font-size: 11px; color: var(--text-muted);"><?php echo (int)$pop_book['view_count']; ?> pembaca</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quick Access -->
                <div class="sidebar-section" style="animation: fadeInSlideUp 0.5s ease-out 0.3s both;">
                    <h3><iconify-icon icon="mdi:link-variant" width="16" height="16"></iconify-icon> Akses Cepat</h3>
                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <a href="favorites.php" style="display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 10px; color: var(--text); text-decoration: none; font-size: 13px; font-weight: 500; transition: all 0.2s;" onmouseover="this.style.background='var(--bg)'; this.style.color='var(--primary)';" onmouseout="this.style.background='transparent'; this.style.color='var(--text)';">
                            <iconify-icon icon="mdi:heart-outline" width="18"></iconify-icon> Buku Favorit Saya
                        </a>
                        <a href="student-borrowing-history.php" style="display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 10px; color: var(--text); text-decoration: none; font-size: 13px; font-weight: 500; transition: all 0.2s;" onmouseover="this.style.background='var(--bg)'; this.style.color='var(--primary)';" onmouseout="this.style.background='transparent'; this.style.color='var(--text)';">
                            <iconify-icon icon="mdi:history" width="18"></iconify-icon> Riwayat Pinjam
                        </a>
                    </div>
                </div>

                <!-- Jam Operasional -->
                <div class="sidebar-section" style="animation: fadeInSlideUp 0.5s ease-out 0.4s both;">
                    <div style="padding: 15px; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-2) 100%); border-radius: 15px; color: white;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                            <iconify-icon icon="mdi:clock-time-four-outline" width="20"></iconify-icon>
                            <span style="font-size: 13px; font-weight: 700;">Jam Operasional</span>
                        </div>
                        <div style="font-size: 12px; opacity: 0.9; line-height: 1.6;">
                            Senin - Jumat: 07:30 - 15:30<br>
                            Sabtu & Libur: Tutup
                        </div>
                        <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.2); display: flex; align-items: center; gap: 5px;">
                            <div style="width: 8px; height: 8px; background: #4ade80; border-radius: 50%;"></div>
                            <span style="font-size: 11px; font-weight: 600;">Sedang Buka</span>
                        </div>
                    </div>
                </div>

            </aside>

            <!-- Main Content -->
            <div class="main-content">
                <!-- Modern Search Bar with Category Dropdown -->
                <!-- KPI Cards -->
                <div class="kpi-grid" role="list">
                    <a class="kpi-card" href="javascript:void(0)" onclick="showTotalBooksModal()" role="listitem">
                        <div class="kpi-left">
                            <div class="kpi-title">Total Buku</div>
                            <div class="kpi-value"><?php echo $totalBooks; ?></div>
                        </div>
                        <div class="kpi-icon"><iconify-icon icon="mdi:book-open-variant" width="20" height="20"></iconify-icon></div>
                    </a>

                    <a class="kpi-card" href="javascript:void(0)" onclick="showCurrentBorrowsModal()" role="listitem">
                        <div class="kpi-left">
                            <div class="kpi-title">Sedang Dipinjam</div>
                            <div class="kpi-value"><?php echo $borrowCount; ?></div>
                        </div>
                        <div class="kpi-icon"><iconify-icon icon="mdi:clock-outline" width="20" height="20"></iconify-icon></div>
                    </a>

                    <a class="kpi-card" href="javascript:void(0)" onclick="showOverdueBorrowsModal()" role="listitem">
                        <div class="kpi-left">
                            <div class="kpi-title">Terlambat / Overdue</div>
                            <div class="kpi-value" style="color: var(--danger);"><?php echo $overdueCount ?? $overdue_count ?? $overdue_borrows ?? 0; ?></div>
                        </div>
                        <div class="kpi-icon" style="background: color-mix(in srgb, var(--danger) 10%, transparent); color: var(--danger);">
                            <iconify-icon icon="mdi:alert-circle-outline" width="20" height="20"></iconify-icon>
                        </div>
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
                                $is_available = empty($book['current_borrow_id']); 
                                $is_teacher_only = ($book['access_level'] ?? 'all') === 'teacher_only';
                            ?>
                            <div class="book-card-vertical" data-book-id="<?php echo $book['id']; ?>">
                                <div class="book-cover-container">
                                    <?php if (!empty($book['cover_image'])): ?>
                                        <img src="../img/covers/<?php echo htmlspecialchars($book['cover_image']); ?>"
                                            alt="<?php echo htmlspecialchars($book['title']); ?>" loading="lazy">
                                    <?php else: ?>
                                        <div class="no-image-placeholder">
                                            <iconify-icon icon="mdi:book-open-variant" style="font-size: 32px;"></iconify-icon>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($is_teacher_only): ?>
                                        <div class="stock-badge-overlay" style="
                                            background: color-mix(in srgb, var(--warning) 15%, transparent);
                                            color: var(--warning);
                                            border: 1px solid color-mix(in srgb, var(--warning) 30%, transparent);
                                        ">
                                            Khusus Guru
                                        </div>
                                    <?php else: ?>
                                        <div class="stock-badge-overlay" style="
                                            background: <?= $is_available ? 'color-mix(in srgb, var(--success) 15%, transparent)' : 'color-mix(in srgb, var(--danger) 15%, transparent)' ?>;
                                            color: <?= $is_available ? 'var(--success)' : 'var(--danger)' ?>;
                                            border: 1px solid <?= $is_available ? 'color-mix(in srgb, var(--success) 30%, transparent)' : 'color-mix(in srgb, var(--danger) 30%, transparent)' ?>;
                                        ">
                                            <?= $is_available ? 'Tersedia' : 'Dipinjam' ?>
                                        </div>
                                    <?php endif; ?>


                                    <button class="btn-love"
                                        onclick="toggleFavorite(event, <?php echo $book['id']; ?>, '<?php echo htmlspecialchars($book['title']); ?>')">
                                        <iconify-icon icon="mdi:heart-outline"></iconify-icon>
                                    </button>
                                </div>

                                <div class="book-card-body">
                                    <div class="book-category"><?php echo htmlspecialchars($book['category'] ?? 'Umum'); ?></div>
                                    <div class="book-title" title="<?php echo htmlspecialchars($book['title']); ?>"><?php echo htmlspecialchars($book['title']); ?></div>
                                    <div class="book-author"><?php echo htmlspecialchars($book['author'] ?? '-'); ?></div>
                                    
                                    <?php if (!$is_available): ?>
                                        <p style="font-size: 10px; color: var(--danger); margin: -8px 0 8px 0;">Oleh: <?php echo htmlspecialchars($book['borrower_name']); ?></p>
                                    <?php endif; ?>

                                    <div class="book-card-footer">
                                        <div class="shelf-info">
                                            <iconify-icon icon="mdi:star" style="color: #FFD700;"></iconify-icon> 
                                            <span style="font-weight: 700;"><?php echo $book['avg_rating'] ? round($book['avg_rating'], 1) : '0'; ?></span>
                                            <span style="opacity: 0.6; margin-left: 2px;">(<?php echo (int)$book['total_reviews']; ?>)</span>
                                        </div>
                                        
                                        <div class="action-buttons">
                                          <button class="btn-icon-sm" onclick="openBookModal(<?php echo htmlspecialchars(json_encode($book)); ?>)" title="Detail">
                                             <iconify-icon icon="mdi:eye"></iconify-icon>
                                          </button>
                                          <a href="book-rating.php?id=<?php echo $book['id']; ?>" class="btn-icon-sm" title="Rating & Review" style="color: var(--primary);">
                                             <iconify-icon icon="mdi:star-outline"></iconify-icon>
                                          </a>
                                        </div>
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
                            <span class="modal-book-item-label">Lokasi Rak</span>
                            <span class="modal-book-item-value" id="modalBookShelf">-</span>
                        </div>


                    </div>

                    <div class="modal-actions">
                        <a id="modalRatingBtn" href="#" class="modal-btn modal-btn-borrow" style="display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; background: rgba(58, 127, 242, 0.1); border: 1px solid var(--primary); color: var(--primary);">
                            <iconify-icon icon="mdi:star-outline"></iconify-icon> Rating & Komentar
                        </a>
                        <button class="modal-btn modal-btn-close" onclick="closeBookModal()">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentBookData = null;
        let favorites = new Set();
        let currentBorrowCount = <?php echo (int)$borrowCount; ?>;

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

            booksGrid.innerHTML = filteredBooks.map(book => {
                const is_available = !book.current_borrow_id;
                const avgRating = book.avg_rating ? parseFloat(book.avg_rating).toFixed(1) : '0';
                const totalReviews = parseInt(book.total_reviews) || 0;
                
                return `
                <div class="book-card-vertical" style="animation: fadeInScale 0.3s ease-out;">
                    <div class="book-cover-container">
                        ${book.cover_image ? 
                            `<img src="../img/covers/${book.cover_image}" alt="${book.title}" loading="lazy">` :
                            `<div class="no-image-placeholder"><iconify-icon icon="mdi:book-open-variant" style="font-size: 32px;"></iconify-icon></div>`
                        }
                        <div class="stock-badge-overlay" style="
                            background: ${is_available ? 'color-mix(in srgb, var(--success) 15%, transparent)' : 'color-mix(in srgb, var(--danger) 15%, transparent)'};
                            color: ${is_available ? 'var(--success)' : 'var(--danger)'};
                            border: 1px solid ${is_available ? 'color-mix(in srgb, var(--success) 30%, transparent)' : 'color-mix(in srgb, var(--danger) 30%, transparent)'};
                        ">
                            ${is_available ? 'Tersedia' : 'Dipinjam'}
                        </div>
                        <button class="btn-love ${favorites.has(parseInt(book.id)) ? 'loved' : ''}" onclick="toggleFavorite(event, ${book.id}, '${(book.title || '').replace(/'/g, "\\'")}')">
                            <iconify-icon icon="mdi:heart${favorites.has(parseInt(book.id)) ? '' : '-outline'}"></iconify-icon>
                        </button>
                    </div>
                    <div class="book-card-body">
                        <div class="book-category">${book.category || 'Umum'}</div>
                        <div class="book-title" title="${book.title}">${book.title}</div>
                        <div class="book-author">${book.author || '-'}</div>
                        ${!is_available ? `<p style="font-size: 10px; color: var(--danger); margin: -8px 0 8px 0;">Oleh: ${book.borrower_name}</p>` : ''}
                        
                        <div class="book-card-footer">
                            <div class="shelf-info">
                                <iconify-icon icon="mdi:star" style="color: #FFD700;"></iconify-icon>
                                <span style="font-weight: 700;">${avgRating}</span>
                                <span style="opacity: 0.6; margin-left: 2px;">(${totalReviews})</span>
                            </div>
                            <div class="action-buttons">
                                <button class="btn-icon-sm" onclick="openBookModal(${JSON.stringify(book).replace(/"/g, '&quot;')})" title="Detail">
                                    <iconify-icon icon="mdi:eye"></iconify-icon>
                                </button>
                                <a href="book-rating.php?id=${book.id}" class="btn-icon-sm" title="Rating & Review" style="color: var(--primary);">
                                    <iconify-icon icon="mdi:star-outline"></iconify-icon>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `;}).join('');
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
        // Toggle favorite (Optimistic UI Update)
        async function toggleFavorite(e, bookId, bookTitle) {
            e.preventDefault();
            e.stopPropagation();

            const btn = e.currentTarget;
            const icon = btn.querySelector('iconify-icon');     
            const wasLoved = btn.classList.contains('loved');

            // 1. Optimistic UI Update (Langsung update tampilan)
            if (wasLoved) {
                btn.classList.remove('loved');
                icon.setAttribute('icon', 'mdi:heart-outline');
                favorites.delete(bookId);
            } else {
                btn.classList.add('loved');
                icon.setAttribute('icon', 'mdi:heart');
                favorites.add(bookId);
            }

            try {
                const formData = new FormData();
                formData.append('id_buku', bookId);

                const action = wasLoved ? 'remove' : 'add';
                
                // 2. Kirim request di background
                const response = await fetch(`/perpustakaan-online/public/api/favorites.php?action=${action}`, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                // 3. Revert jika gagal
                if (!data.success) {
                    throw new Error(data.message || 'Gagal mengubah status');
                }
            } catch (error) {
                console.error('Error:', error);
                
                // Revert UI ke state awal
                if (wasLoved) {
                    btn.classList.add('loved');
                    icon.setAttribute('icon', 'mdi:heart');
                    favorites.add(bookId);
                } else {
                    btn.classList.remove('loved');
                    icon.setAttribute('icon', 'mdi:heart-outline');
                    favorites.delete(bookId);
                }
                
                // Gunakan Toast atau alert kecil daripada alert() yang blocking
                // Tapi untuk sekarang alert cukup sebagai fallback
                // alert('Gagal: ' + error.message); 
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

            // Set modal info
            document.getElementById('modalBookTitle').textContent = bookData.title || '-';
            document.getElementById('modalBookAuthor').textContent = bookData.author || '-';
            document.getElementById('modalBookCategory').textContent = bookData.category || 'Umum';
            document.getElementById('modalBookISBN').textContent = bookData.isbn || '-';
            document.getElementById('modalBookShelf').textContent = (bookData.shelf || '-') + (bookData.row_number ? ' (Baris ' + bookData.row_number + ')' : '');
            
            // Set rating link
            document.getElementById('modalRatingBtn').href = 'book-rating.php?id=' + bookData.id;

            // Show modal
            document.getElementById('bookModal').classList.add('active');

            // Add borrower info if not available
            let borrowerInfoDiv = document.getElementById('modalBorrowerInfo');
            if (!borrowerInfoDiv) {
                borrowerInfoDiv = document.createElement('div');
                borrowerInfoDiv.id = 'modalBorrowerInfo';
                borrowerInfoDiv.className = 'modal-book-item';
                borrowerInfoDiv.style.marginTop = '12px';
                borrowerInfoDiv.style.padding = '12px';
                borrowerInfoDiv.style.borderRadius = '8px';
                document.querySelector('.modal-book-meta').appendChild(borrowerInfoDiv);
            }

            if (bookData.current_borrow_id) {
                const dueDate = new Date(bookData.borrower_due_at).toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'});
                borrowerInfoDiv.style.display = 'block';
                borrowerInfoDiv.style.background = 'rgba(239, 68, 68, 0.05)';
                borrowerInfoDiv.style.border = '1px solid rgba(239, 68, 68, 0.2)';
                borrowerInfoDiv.innerHTML = `
                    <p style="margin: 0 0 4px 0; font-size: 11px; color: #dc2626; font-weight: 600; text-transform: uppercase;">Sedang Dipinjam</p>
                    <p style="margin: 0; font-size: 13px; font-weight: 500;">Peminjam: <span style="color: var(--text);">${bookData.borrower_name}</span></p>
                    <p style="margin: 4px 0 0 0; font-size: 12px; color: var(--text-muted);">Tenggat: ${dueDate}</p>
                `;
            } else {
                borrowerInfoDiv.style.display = 'block';
                borrowerInfoDiv.style.background = 'rgba(16, 185, 129, 0.05)';
                borrowerInfoDiv.style.border = '1px solid rgba(16, 185, 129, 0.2)';
                borrowerInfoDiv.innerHTML = `
                    <p style="margin: 0; font-size: 11px; color: #059669; font-weight: 600; text-transform: uppercase;">✓ Buku Tersedia</p>
                `;
            }

            // Show modal
            document.getElementById('bookModal').classList.add('active');
        }

        function closeBookModal() {
            document.getElementById('bookModal').classList.remove('active');
            currentBookData = null;
        }



        // Close modal when clicking outside
        document.getElementById('bookModal').addEventListener('click', (e) => {
            if (e.target.id === 'bookModal') {
                closeBookModal();
            }
        });



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
        const BOOKS_AVAILABLE_SERVER = <?php echo json_encode(array_values(array_map(function($b){ 
            return [
                'id' => $b['id'],
                'title' => $b['title'] ?? '', 
                'author' => $b['author'] ?? '-', 
                'category' => $b['category'] ?? '-', 
                'current_borrow_id' => $b['current_borrow_id'],
                'borrower_name' => $b['borrower_name'],
                'borrower_due_at' => $b['borrower_due_at']
            ]; 
        }, $books_available ?? [])), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?> || [];
        
        const BOOKS_AVAILABLE_FALLBACK = <?php echo json_encode(array_values(array_map(function($b){ 
            return [
                'id' => $b['id'],
                'title' => $b['title'] ?? '', 
                'author' => $b['author'] ?? '-', 
                'category' => $b['category'] ?? '-', 
                'current_borrow_id' => $b['current_borrow_id'],
                'borrower_name' => $b['borrower_name'],
                'borrower_due_at' => $b['borrower_due_at']
            ]; 
        }, $books ?? [])), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?> || [];
        
        const BOOKS_AVAILABLE = (Array.isArray(BOOKS_AVAILABLE_SERVER) && BOOKS_AVAILABLE_SERVER.length > 0) ? BOOKS_AVAILABLE_SERVER : BOOKS_AVAILABLE_FALLBACK;
        
        // Borrowing data mappings
        const STUDENT_CURRENT_BORROWS = <?php echo json_encode(array_values(array_map(function($r){ 
            return [
                'title' => $r['title'] ?? '', 
                'borrowed_at' => $r['borrowed_at'], 
                'due_at' => $r['due_at'], 
                'status' => $r['status']
            ]; 
        }, array_filter($my_borrows, function($r){ return is_null($r['returned_at']); }))), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?> || [];
        
        const STUDENT_ACTIVE_BORROWS = <?php echo json_encode(array_values(array_map(function($r){ 
            return [
                'title' => $r['title'] ?? '', 
                'borrowed_at' => $r['borrowed_at'], 
                'due_at' => $r['due_at'], 
                'status' => $r['status']
            ]; 
        }, array_filter($my_borrows, function($r){ return is_null($r['returned_at']) && $r['status'] !== 'overdue'; }))), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?> || [];
        
        const STUDENT_OVERDUE_BORROWS = <?php echo json_encode(array_values(array_map(function($r){ 
            return [
                'title' => $r['title'] ?? '', 
                'borrowed_at' => $r['borrowed_at'], 
                'due_at' => $r['due_at'], 
                'status' => $r['status']
            ]; 
        }, array_filter($my_borrows, function($r){ return $r['status'] === 'overdue'; }))), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?> || [];

        const TOP_VIEWED_BOOKS = <?php echo json_encode(array_map(function($b){ return ['title' => $b['title'] ?? '', 'author' => $b['author'] ?? '-', 'category' => '-', 'isbn' => '-', 'copies' => 0]; }, $top_viewed_books ?? []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?> || [];


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
            overlay.style.background = 'rgba(15, 23, 42, 0.4)'; // Subtle slate overlay
            overlay.style.display = 'flex';
            overlay.style.alignItems = 'center';
            overlay.style.justifyContent = 'center';
            overlay.style.zIndex = '2200';
            overlay.style.opacity = '0';
            overlay.style.transition = 'opacity 0.25s ease';
            overlay.style.backdropFilter = 'blur(6px)';

            const container = document.createElement('div');
            container.className = 'modal-container';
            container.style.background = 'var(--card)';
            container.style.borderRadius = '20px'; // Matching professional cards
            container.style.maxWidth = '1000px';
            container.style.width = '90%';
            container.style.maxHeight = '85vh';
            container.style.display = 'flex';
            container.style.flexDirection = 'column';
            container.style.boxShadow = '0 25px 50px -12px rgba(0, 0, 0, 0.25)';
            container.style.transform = 'translateY(20px)';
            container.style.transition = 'all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1)';
            container.style.border = '1px solid var(--border)';
            container.style.overflow = 'hidden';

            // Header
            const header = document.createElement('div');
            header.className = 'modal-header';
            header.style.display = 'flex';
            header.style.justifyContent = 'space-between';
            header.style.alignItems = 'center';
            header.style.padding = '20px 24px';
            header.style.borderBottom = '1px solid var(--border)';
            header.style.flexShrink = '0';
            header.style.background = 'var(--card)';

            const h = document.createElement('h2');
            h.textContent = title;
            h.style.margin = '0';
            h.style.fontSize = '18px';
            h.style.fontWeight = '700';
            h.style.color = 'var(--text)';
            h.style.letterSpacing = '-0.025em';
            header.appendChild(h);

            const closeBtn = document.createElement('button');
            closeBtn.className = 'modal-close';
            closeBtn.innerHTML = '<iconify-icon icon="mdi:close" width="20" height="20"></iconify-icon>';
            closeBtn.style.background = 'var(--bg)';
            closeBtn.style.border = '1px solid var(--border)';
            closeBtn.style.cursor = 'pointer';
            closeBtn.style.color = 'var(--text-muted)';
            closeBtn.style.width = '32px';
            closeBtn.style.height = '32px';
            closeBtn.style.display = 'flex';
            closeBtn.style.alignItems = 'center';
            closeBtn.style.justifyContent = 'center';
            closeBtn.style.borderRadius = '8px';
            closeBtn.style.transition = 'all 0.2s ease';
            
            closeBtn.onmouseover = () => {
                closeBtn.style.background = 'var(--border)';
                closeBtn.style.color = 'var(--text)';
            };
            closeBtn.onmouseout = () => {
                closeBtn.style.background = 'var(--bg)';
                closeBtn.style.color = 'var(--text-muted)';
            };
            closeBtn.onclick = () => {
                overlay.style.opacity = '0';
                container.style.transform = 'translateY(20px)';
                setTimeout(() => overlay.remove(), 250);
            };
            header.appendChild(closeBtn);
            container.appendChild(header);

            // Body
            const body = document.createElement('div');
            body.className = 'modal-body';
            body.style.flex = '1';
            body.style.overflowY = 'auto';
            body.style.padding = '0';
            body.innerHTML = itemsHtml;
            container.appendChild(body);

            overlay.appendChild(container);
            document.body.appendChild(overlay);
            
            // Trigger animation
            requestAnimationFrame(() => {
                overlay.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            });
        }

        function renderBooksListHtml(list) {
            if (!list || list.length === 0) return '<div style="text-align: center; color: var(--text-muted); padding: 60px 24px;"><iconify-icon icon="mdi:book-search-outline" width="48" height="48" style="opacity: 0.2; margin-bottom: 12px; display: block;"></iconify-icon><p style="margin: 0; font-weight: 500;">Tidak ada data buku ditemukan</p></div>';
            
            let html = `
                <div style="overflow-x: auto; width: 100%;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px; margin: 0;">
                        <thead>
                            <tr style="background: var(--bg); border-bottom: 2px solid var(--border);">
                                <th style="padding: 16px 24px; text-align: left; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; font-size: 11px;">Judul Buku</th>
                                <th style="padding: 16px 24px; text-align: left; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; font-size: 11px;">Penulis</th>
                                <th style="padding: 16px 24px; text-align: center; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; font-size: 11px;">Status</th>
                                <th style="padding: 16px 24px; text-align: left; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; font-size: 11px;">Peminjam & Tenggat</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            list.forEach((item, idx) => {
                const title = (item.title || '-').toString();
                const author = (item.author || '-').toString();
                const is_available = !item.current_borrow_id;
                const statusText = is_available ? 'Tersedia' : 'Dipinjam';
                const statusClass = is_available ? 'badge-available' : 'badge-borrowed';
                
                let borrowerInfo = '<span style="color: var(--text-muted);">Tersedia di rak</span>';
                if (!is_available) {
                    const dueDate = item.borrower_due_at ? new Date(item.borrower_due_at).toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'}) : '-';
                    borrowerInfo = `<div style="font-weight: 600; color: var(--text);">${item.borrower_name}</div><div style="color: var(--text-muted); font-size: 11px;">Hingga: ${dueDate}</div>`;
                }

                html += `
                    <tr style="border-bottom: 1px solid var(--border); transition: all 0.2s ease;" 
                        onmouseover="this.style.backgroundColor='var(--bg)';"
                        onmouseout="this.style.backgroundColor='transparent';">
                        <td style="padding: 16px 24px; color: var(--text); font-weight: 600; max-width: 300px;">${escapeHtml(title)}</td>
                        <td style="padding: 16px 24px; color: var(--text-muted);">${escapeHtml(author)}</td>
                        <td style="padding: 16px 24px; text-align: center;">
                            <span class="cover-status-badge ${statusClass}" style="position: static; box-shadow: none; padding: 4px 10px; font-size: 10px;">
                                ${statusText}
                            </span>
                        </td>
                        <td style="padding: 16px 24px; color: var(--text);">${borrowerInfo}</td>
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

        function renderBorrowsListHtml(list) {
            if (!list || list.length === 0) return '<div style="text-align: center; color: var(--text-muted); padding: 60px 24px;"><iconify-icon icon="mdi:clock-check-outline" width="48" height="48" style="opacity: 0.2; margin-bottom: 12px; display: block;"></iconify-icon><p style="margin: 0; font-weight: 500;">Tidak ada aktivitas peminjaman</p></div>';
            
            let html = `
                <div style="overflow-x: auto; width: 100%;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px; margin: 0;">
                        <thead>
                            <tr style="background: var(--bg); border-bottom: 2px solid var(--border);">
                                <th style="padding: 16px 24px; text-align: left; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; font-size: 11px;">Judul Buku</th>
                                <th style="padding: 16px 24px; text-align: center; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; font-size: 11px;">Tgl Pinjam</th>
                                <th style="padding: 16px 24px; text-align: center; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; font-size: 11px;">Batas Kembali</th>
                                <th style="padding: 16px 24px; text-align: center; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; font-size: 11px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            list.forEach((item, idx) => {
                const title = (item.title || '-').toString();
                const borrowedAt = item.borrowed_at ? new Date(item.borrowed_at).toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'}) : '-';
                const dueAt = item.due_at ? new Date(item.due_at).toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'}) : '-';
                const status = (item.status || 'borrowed').toLowerCase();
                
                let statusBadge = '';
                if (status === 'overdue') {
                    statusBadge = `<span class="cover-status-badge badge-borrowed" style="position: static; box-shadow: none; padding: 4px 10px; font-size: 10px; background: var(--danger);">TERLAMBAT</span>`;
                } else if (status === 'returned') {
                    statusBadge = `<span class="cover-status-badge badge-available" style="position: static; box-shadow: none; padding: 4px 10px; font-size: 10px;">KEMBALI</span>`;
                } else {
                    statusBadge = `<span class="cover-status-badge" style="position: static; box-shadow: none; padding: 4px 10px; font-size: 10px; background: var(--primary); color: white;">DIPINJAM</span>`;
                }
                
                html += `
                    <tr style="border-bottom: 1px solid var(--border); transition: all 0.2s ease;" 
                        onmouseover="this.style.backgroundColor='var(--bg)';"
                        onmouseout="this.style.backgroundColor='transparent';">
                        <td style="padding: 16px 24px; color: var(--text); font-weight: 600;">${escapeHtml(title)}</td>
                        <td style="padding: 16px 24px; text-align: center; color: var(--text-muted);">${borrowedAt}</td>
                        <td style="padding: 16px 24px; text-align: center; color: var(--text-muted);">${dueAt}</td>
                        <td style="padding: 16px 24px; text-align: center;">${statusBadge}</td>
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

        // KPI Modal Handlers
        function showTotalBooksModal() {
            createListOverlay('Daftar Semua Buku', renderBooksListHtml(BOOKS_AVAILABLE));
        }

        function showCurrentBorrowsModal() {
            createListOverlay('Buku yang Sedang Dipinjam', renderBorrowsListHtml(STUDENT_CURRENT_BORROWS));
        }

        function showActiveBorrowsModal() {
            createListOverlay('Buku yang Aktif Dipinjam', renderBorrowsListHtml(STUDENT_ACTIVE_BORROWS));
        }

        function showOverdueBorrowsModal() {
            createListOverlay('Buku yang Terlambat (Overdue)', renderBorrowsListHtml(STUDENT_OVERDUE_BORROWS));
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


    </script>
    <script src="../assets/js/sidebar.js"></script>
</body>

</html>