<?php
session_start();
$pdo = require __DIR__ . '/../src/db.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: /?login_required=1');
    exit;
}

$user = $_SESSION['user'];
$school_id = $user['school_id'];

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
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Perpustakaan Siswa - Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #0f1724;
            --muted: #6b7280;
            --accent: #0b3d61;
            --accent-light: #e0f2fe;
            --border: #e2e8f0;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }

        /* ===== ANIMATIONS ===== */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-40px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(40px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Header */
        .header {
            background: var(--card);
            border-bottom: 1px solid var(--border);
            padding: 16px 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            animation: slideDown 0.6s ease-out;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text);
        }

        .header-brand-icon {
            font-size: 32px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--accent-light);
            border-radius: 8px;
        }

        .header-brand-text h2 {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
        }

        .header-brand-text p {
            font-size: 12px;
            color: var(--muted);
            margin: 2px 0 0 0;
        }

        .header-user {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header-user-info {
            text-align: right;
        }

        .header-user-info p {
            font-size: 13px;
            margin: 0;
        }

        .header-user-info .name {
            font-weight: 600;
            color: var(--text);
        }

        .header-user-info .role {
            color: var(--muted);
            font-size: 12px;
        }

        .header-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--accent), #2563eb);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 16px;
        }

        .header-logout {
            padding: 8px 16px;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: var(--bg);
            color: var(--text);
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }

        .header-logout:hover {
            background: #f0f0f0;
            border-color: var(--text);
        }

        /* Main Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px 24px;
        }

        .content-wrapper {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 32px;
        }

        /* Sidebar */
        .sidebar {
            position: sticky;
            top: 100px;
            height: fit-content;
            animation: slideInLeft 0.7s ease-out 0.2s both;
        }

        .sidebar-section {
            background: var(--card);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid var(--border);
            animation: fadeInUp 0.5s ease-out backwards;
        }

        .sidebar-section:nth-child(1) {
            animation-delay: 0.25s;
        }

        .sidebar-section:nth-child(2) {
            animation-delay: 0.35s;
        }

        .sidebar-section:nth-child(3) {
            animation-delay: 0.45s;
        }

        .sidebar-section h3 {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 16px;
            color: var(--text);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .filter-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: var(--accent);
        }

        .filter-item label {
            font-size: 13px;
            cursor: pointer;
            color: var(--text);
            flex: 1;
        }

        /* Main Content */
        .main-content {
            display: flex;
            flex-direction: column;
            gap: 24px;
            animation: slideInRight 0.7s ease-out 0.2s both;
        }

        .search-sort-bar {
            background: var(--card);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--border);
            display: flex;
            gap: 16px;
            align-items: center;
            animation: fadeInUp 0.6s ease-out 0.3s both;
        }

        .search-input {
            flex: 1;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 13px;
            font-family: inherit;
            transition: 0.2s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(11, 61, 97, 0.1);
        }

        .sort-select {
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 13px;
            font-family: inherit;
            cursor: pointer;
            background: var(--bg);
            color: var(--text);
            transition: 0.2s ease;
        }

        .sort-select:focus {
            outline: none;
            border-color: var(--accent);
        }

        .btn-search {
            padding: 10px 20px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .btn-search:hover {
            background: #062d4a;
        }

        /* Books Grid */
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 20px;
            animation: fadeInUp 0.6s ease-out 0.4s both;
        }

        .book-card {
            background: var(--card);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            animation: scaleIn 0.5s ease-out backwards;
        }

        /* Stagger animation untuk setiap book card */
        .book-card:nth-child(1) { animation-delay: 0.45s; }
        .book-card:nth-child(2) { animation-delay: 0.50s; }
        .book-card:nth-child(3) { animation-delay: 0.55s; }
        .book-card:nth-child(4) { animation-delay: 0.60s; }
        .book-card:nth-child(5) { animation-delay: 0.65s; }
        .book-card:nth-child(6) { animation-delay: 0.70s; }
        .book-card:nth-child(7) { animation-delay: 0.75s; }
        .book-card:nth-child(8) { animation-delay: 0.80s; }
        .book-card:nth-child(n+9) { animation-delay: 0.85s; }

        .book-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
            border-color: var(--accent);
        }

        .book-cover {
            width: 100%;
            height: 220px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .book-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .book-status {
            position: absolute;
            top: 8px;
            right: 8px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .book-status.available {
            background: var(--success);
            color: white;
        }

        .book-status.unavailable {
            background: var(--danger);
            color: white;
        }

        .book-status.limited {
            background: var(--warning);
            color: white;
        }

        .book-info {
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
        }

        .book-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .book-author {
            font-size: 12px;
            color: var(--muted);
        }

        .book-category {
            font-size: 11px;
            color: var(--accent);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .book-rating {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            color: var(--muted);
        }

        .book-actions {
            display: flex;
            gap: 8px;
            margin-top: auto;
        }

        .btn-borrow {
            flex: 1;
            padding: 10px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .btn-borrow:hover {
            background: #062d4a;
        }

        .btn-borrow:disabled {
            background: var(--border);
            color: var(--muted);
            cursor: not-allowed;
        }

        .btn-detail {
            flex: 1;
            padding: 10px;
            background: var(--bg);
            color: var(--accent);
            border: 1px solid var(--accent);
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-detail:hover {
            background: var(--accent-light);
        }

        /* Empty State */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 40px;
            background: var(--card);
            border-radius: 12px;
            border: 1px solid var(--border);
            animation: fadeInUp 0.6s ease-out 0.45s both;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 16px;
            animation: scaleIn 0.6s ease-out;
        }

        .empty-state h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: var(--muted);
            font-size: 14px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .content-wrapper {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: relative;
                top: 0;
            }

            .books-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 12px 0;
            }

            .header-container {
                flex-wrap: wrap;
                padding: 12px 16px;
                gap: 12px;
            }

            .header-brand {
                flex: 0 1 auto;
                min-width: auto;
            }

            .header-brand-icon {
                font-size: 24px;
                width: 32px;
                height: 32px;
            }

            .header-brand-text h2 {
                font-size: 14px;
            }

            .header-brand-text p {
                font-size: 11px;
            }

            .header-user {
                flex: 1;
                justify-content: flex-end;
                gap: 12px;
                order: 3;
                width: 100%;
            }

            .header-user-info {
                display: none;
            }

            .header-user-avatar {
                width: 36px;
                height: 36px;
                font-size: 14px;
            }

            .header-logout {
                padding: 6px 12px;
                font-size: 12px;
            }

            .container {
                padding: 16px;
            }

            .content-wrapper {
                gap: 16px;
                display: flex;
                flex-direction: column;
            }

            .sidebar {
                display: grid;
                grid-template-columns: 1fr;
                gap: 12px;
                order: -1;
            }

            .sidebar-section {
                background: var(--card);
                border-radius: 12px;
                padding: 16px;
                border: 1px solid var(--border);
                margin-bottom: 0;
            }

            .sidebar-section h3 {
                font-size: 13px;
                margin-bottom: 12px;
            }

            .filter-group {
                display: grid;
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .filter-item {
                display: flex;
                align-items: center;
                gap: 6px;
                font-size: 12px;
            }

            .filter-item input[type="radio"],
            .filter-item input[type="checkbox"] {
                width: 14px;
                height: 14px;
                cursor: pointer;
            }

            .filter-item label {
                font-size: 12px;
                cursor: pointer;
            }

            .search-sort-bar {
                flex-direction: column;
                padding: 16px;
                gap: 12px;
            }

            .sort-select {
                width: 100%;
            }

            .btn-search {
                width: 100%;
            }

            .books-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .book-cover {
                height: 160px;
                font-size: 36px;
            }

            .book-info {
                padding: 12px;
                gap: 6px;
            }

            .book-title {
                font-size: 12px;
            }

            .book-author {
                font-size: 11px;
            }

            .book-actions {
                gap: 6px;
            }

            .btn-borrow,
            .btn-detail {
                padding: 8px;
                font-size: 11px;
            }

            .sidebar {
                display: block;
                position: relative;
                top: 0;
                order: -1;
                animation: fadeInUp 0.6s ease-out 0.2s both;
            }

            .sidebar-section {
                margin-bottom: 16px;
                padding: 16px;
            }

            .sidebar-section h3 {
                font-size: 12px;
                margin-bottom: 12px;
            }

            .filter-group {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .filter-item {
                display: flex;
                align-items: center;
                gap: 6px;
            }

            .filter-item input[type="radio"],
            .filter-item input[type="checkbox"] {
                width: 14px;
                height: 14px;
            }

            .filter-item label {
                font-size: 12px;
            }

            .empty-state {
                padding: 40px 20px;
            }

            .empty-state-icon {
                font-size: 48px;
                margin-bottom: 12px;
            }

            .empty-state h3 {
                font-size: 16px;
            }

            .empty-state p {
                font-size: 13px;
            }
        }

        /* Extra small devices (< 480px) */
        @media (max-width: 480px) {
            .header-container {
                padding: 10px 12px;
                gap: 8px;
            }

            .header-brand-icon {
                font-size: 20px;
                width: 28px;
                height: 28px;
            }

            .header-brand-text h2 {
                font-size: 13px;
            }

            .header-brand-text p {
                font-size: 10px;
            }

            .header-user-avatar {
                width: 32px;
                height: 32px;
                font-size: 12px;
            }

            .header-logout {
                padding: 5px 10px;
                font-size: 11px;
            }

            .container {
                padding: 12px;
            }

            .search-sort-bar {
                padding: 12px;
                gap: 10px;
            }

            .search-input {
                padding: 8px 10px;
                font-size: 12px;
            }

            .sort-select {
                padding: 8px 8px;
                font-size: 12px;
            }

            .btn-search {
                padding: 8px 12px;
                font-size: 11px;
            }

            .books-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .book-cover {
                height: 140px;
                font-size: 32px;
            }

            .book-info {
                padding: 10px;
                gap: 5px;
            }

            .book-title {
                font-size: 11px;
            }

            .book-author {
                font-size: 10px;
            }

            .book-category {
                font-size: 10px;
            }

            .book-rating {
                font-size: 11px;
            }

            .btn-borrow,
            .btn-detail {
                padding: 6px;
                font-size: 10px;
            }

            .empty-state {
                padding: 30px 16px;
            }

            .empty-state-icon {
                font-size: 40px;
            }

            .empty-state h3 {
                font-size: 14px;
            }

            .empty-state p {
                font-size: 12px;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <a href="student-dashboard.php" class="header-brand">
                <div class="header-brand-icon">📚</div>
                <div class="header-brand-text">
                    <h2>AS Library</h2>
                    <p>Dashboard Siswa</p>
                </div>
            </a>

            <div class="header-user">
                <div class="header-user-info">
                    <p class="name"><?php echo htmlspecialchars($user['name'] ?? 'Siswa'); ?></p>
                    <p class="role">Siswa</p>
                </div>
                <div class="header-user-avatar">
                    <?php echo strtoupper(substr($user['name'] ?? 'S', 0, 1)); ?>
                </div>
                <a href="logout.php" class="header-logout">Logout</a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <div class="container">
        <div class="content-wrapper">
            <!-- Sidebar -->
            <aside class="sidebar">
                <!-- Search Tips -->
                <div class="sidebar-section">
                    <h3>💡 Tips</h3>
                    <p style="font-size: 12px; color: var(--muted); line-height: 1.6;">
                        Gunakan search untuk mencari buku berdasarkan judul atau pengarang. Filter kategori membantu
                        Anda menemukan buku yang Anda inginkan.
                    </p>
                </div>

                <!-- Category Filter -->
                <?php if (!empty($categories)): ?>
                    <div class="sidebar-section">
                        <h3>Kategori</h3>
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
                    <h3>📊 Statistik</h3>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <div>
                            <p style="font-size: 11px; color: var(--muted); margin-bottom: 4px;">Total Buku</p>
                            <p style="font-size: 20px; font-weight: 700; color: var(--accent);">
                                <?php echo count($books); ?></p>
                        </div>
                        <div>
                            <p style="font-size: 11px; color: var(--muted); margin-bottom: 4px;">Sedang Dipinjam</p>
                            <p style="font-size: 20px; font-weight: 700; color: var(--warning);">-</p>
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
                            <div class="book-card">
                                <div class="book-cover">
                                    📖
                                    <span class="book-status available">Tersedia</span>
                                </div>
                                <div class="book-info">
                                    <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                                    <p class="book-author"><?php echo htmlspecialchars($book['author']); ?></p>
                                    <p class="book-category"><?php echo htmlspecialchars($book['category'] ?? 'Umum'); ?></p>
                                    <div class="book-rating">
                                        ⭐ 4.5 (12 reviews)
                                    </div>
                                    <div class="book-actions">
                                        <button class="btn-borrow"
                                            onclick="borrowBook(<?php echo $book['id']; ?>)">Pinjam</button>
                                        <a href="book-detail.php?id=<?php echo $book['id']; ?>" class="btn-detail">Detail</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📚</div>
                            <h3>Buku Tidak Ditemukan</h3>
                            <p>Coba ubah filter atau cari dengan kata kunci yang berbeda.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function borrowBook(bookId) {
            if (!confirm('Apakah Anda ingin meminjam buku ini?')) {
                return;
            }

            // TODO: Implement borrow functionality
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
    </script>
</body>

</html>