<?php
session_start();
$pdo = require __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/FavoriteModel.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: /?login_required=1');
    exit;
}

$user = $_SESSION['user'];
$studentId = $user['id'];

// Initialize variables
$categories = [];
$books = [];
$favorites = [];
$selectedCategory = '';
$errorMessage = '';
$successMessage = '';

try {
    $model = new FavoriteModel($pdo);

    // Get all categories
    $categories = $model->getCategories();

    // Get all books (default)
    $books = $model->getBooksByCategory(null);

    // Get favorites
    $favorites = $model->getFavorites($studentId);

} catch (Exception $e) {
    $errorMessage = 'Error: ' . htmlspecialchars($e->getMessage());
}

$pageTitle = 'Koleksi Favorit';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Koleksi Favorit - Perpustakaan Digital</title>
    <script src="../assets/js/db-theme-loader.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../assets/css/school-profile.css">

    <style>
        :root {
            --primary: #3A7FF2;
            --primary-2: #7AB8F5;
            --primary-dark: #0A1A4F;
            --bg: #F6F9FF;
            --muted: #F3F7FB;
            --card: #FFFFFF;
            --surface: #FFFFFF;
            --muted-surface: #F7FAFF;
            --border: #E6EEF8;
            --text: #0F172A;
            --text-muted: #50607A;
            --accent: #3A7FF2;
            --accent-light: #e0f2fe;
            --success: #10B981;
            --warning: #f59e0b;
            --danger: #EF4444;
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

        /* Navigation Sidebar */
        .nav-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 240px;
            background: linear-gradient(135deg, #0b3d61 0%, #062d4a 100%);
            color: white;
            padding: 24px 0;
            z-index: 1002;
            overflow-y: auto;
            animation: slideInLeft 0.6s ease-out;
        }

        .nav-sidebar-header {
            padding: 0 24px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: white;
        }

        .nav-sidebar-header-icon {
            font-size: 32px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
        }

        .nav-sidebar-header-icon iconify-icon {
            width: 32px;
            height: 32px;
            color: white;
        }

        .nav-sidebar-header h2 {
            font-size: 14px;
            font-weight: 700;
            margin: 0;
        }

        .nav-sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-sidebar-menu li {
            margin: 0;
        }

        .nav-sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 13px;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
            position: relative;
        }

        .nav-sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .nav-sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border-left-color: white;
            font-weight: 600;
        }

        .nav-sidebar-menu iconify-icon {
            font-size: 18px;
            width: 24px;
            height: 24px;
            color: rgba(255, 255, 255, 0.8);
        }

        .nav-sidebar-menu a:hover iconify-icon,
        .nav-sidebar-menu a.active iconify-icon {
            color: white;
        }

        .nav-sidebar-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
            margin: 16px 0;
        }

        /* Hamburger Menu Button */
        .nav-toggle {
            display: none;
            position: fixed;
            top: 6px;
            left: 12px;
            z-index: 999;
            background: var(--card);
            color: var(--text);
            cursor: pointer;
            width: 44px;
            height: 44px;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            padding: 0;
            transition: all 0.2s ease;
            border: none;
        }

        .nav-toggle:hover {
            background: var(--bg);
        }

        .nav-toggle:active {
            transform: scale(0.95);
        }

        .nav-toggle iconify-icon {
            width: 24px;
            height: 24px;
            color: var(--accent);
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
            margin-left: 240px;
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

        .header-brand-icon iconify-icon {
            width: 32px;
            height: 32px;
            color: var(--accent);
        }

        .header-brand-text h2 {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
        }

        .header-brand-text p {
            font-size: 12px;
            color: var(--text-muted);
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
            color: var(--text-muted);
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
        .container-main {
            margin-left: 240px;
            padding: 24px;
            max-width: 1400px;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 24px;
            animation: fadeInUp 0.6s ease-out;
        }

        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--section-header-text, var(--text));
            margin: 0 0 8px 0;
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--section-header, transparent);
            padding: 16px 20px;
            border-radius: 12px;
        }

        .page-header p {
            color: var(--text-muted);
            font-size: 14px;
            margin: 0;
        }

        /* Two Column Layout */
        .layout-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        /* Form Card */
        .form-card {
            background: var(--card);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            animation: fadeInUp 0.6s ease-out;
        }

        .form-card h2 {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 16px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-card h2 iconify-icon {
            width: 20px;
            height: 20px;
            color: var(--accent);
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 6px;
            color: var(--text);
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 13px;
            font-family: 'Inter', system-ui, sans-serif;
            transition: all 0.2s ease;
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(11, 61, 97, 0.1);
        }

        .btn {
            padding: 10px 16px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            width: 100%;
            justify-content: center;
        }

        .btn:hover {
            background: #0a2c52;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(11, 61, 97, 0.2);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn iconify-icon {
            width: 16px;
            height: 16px;
        }

        .btn:disabled {
            background: var(--muted);
            cursor: not-allowed;
            opacity: 0.5;
        }

        /* Favorites List */
        .favorites-list {
            animation: fadeInUp 0.6s ease-out;
        }

        .favorites-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .favorites-header h2 {
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }

        .favorites-header h2 iconify-icon {
            width: 20px;
            height: 20px;
            color: var(--success);
        }

        .favorites-count {
            font-size: 13px;
            font-weight: 600;
            background: var(--accent-light);
            color: var(--accent);
            padding: 4px 12px;
            border-radius: 20px;
        }

        /* Book Card */
        .book-card {
            background: var(--card);
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 12px;
            transition: all 0.2s ease;
            border-left: 4px solid var(--accent);
            display: flex;
            gap: 12px;
        }

        .book-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .book-card-cover {
            width: 60px;
            height: 80px;
            background: var(--bg);
            border-radius: 6px;
            overflow: hidden;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .book-card-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .book-card-cover-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--accent-light) 0%, var(--bg) 100%);
            color: var(--accent);
            font-size: 28px;
        }

        .book-card-body {
            flex: 1;
        }

        .book-card-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
            margin: 0 0 4px 0;
            line-height: 1.4;
        }

        .book-card-author {
            font-size: 12px;
            color: var(--text-muted);
            margin: 0 0 6px 0;
        }

        .book-card-category {
            display: inline-block;
            font-size: 11px;
            font-weight: 600;
            background: var(--accent-light);
            color: var(--accent);
            padding: 2px 8px;
            border-radius: 4px;
            margin-bottom: 8px;
        }

        .book-card-action {
            margin-top: 8px;
        }

        .btn-remove {
            padding: 6px 12px;
            background: var(--danger);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            width: 100%;
            justify-content: center;
        }

        .btn-remove:hover {
            background: #dc2626;
        }

        .btn-remove iconify-icon {
            width: 14px;
            height: 14px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 24px;
            color: var(--text-muted);
        }

        .empty-state-icon {
            font-size: 48px;
            color: var(--border);
            margin-bottom: 12px;
        }

        .empty-state h3 {
            font-size: 16px;
            color: var(--text);
            margin: 0 0 6px 0;
        }

        .empty-state p {
            font-size: 13px;
            margin: 0;
        }

        /* Alert */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert iconify-icon {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        /* Loading State */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .layout-container {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .nav-toggle {
                display: flex;
            }

            .nav-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                width: 240px;
                box-shadow: 2px 0 8px rgba(0, 0, 0, 0.15);
            }

            .nav-sidebar.active {
                transform: translateX(0);
            }

            .header {
                margin-left: 0;
                padding: 12px 0;
                padding-left: 12px;
            }

            .header-container {
                flex-wrap: wrap;
                padding: 0 16px 0 60px;
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

            .container-main {
                margin-left: 0;
                padding: 16px;
            }

            .page-header h1 {
                font-size: 20px;
            }

            .layout-container {
                gap: 16px;
            }

            .form-card {
                padding: 16px;
            }

            .book-card {
                padding: 12px;
            }

            .book-card-cover {
                width: 50px;
                height: 70px;
            }
        }

        @media (max-width: 480px) {
            .nav-toggle {
                width: 40px;
                height: 40px;
                left: 10px;
                top: 6px;
            }

            .nav-toggle iconify-icon {
                width: 20px;
                height: 20px;
            }

            .nav-sidebar {
                width: 200px;
            }

            .header {
                padding: 10px 0;
                padding-left: 10px;
            }

            .header-container {
                padding: 0 12px 0 50px;
                gap: 8px;
            }

            .header-brand {
                flex: 0;
                min-width: auto;
            }

            .header-brand-icon {
                font-size: 20px;
                width: 28px;
                height: 28px;
            }

            .header-brand-text {
                display: none;
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

            .container-main {
                padding: 12px;
            }

            .page-header h1 {
                font-size: 20px;
            }

            .layout-container {
                flex-direction: column;
            }

            .form-card {
                padding: 12px;
            }

            .book-card {
                padding: 10px;
            }
        }

        iconify-icon {
            display: inline-block;
            vertical-align: middle;
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
    <div class="container-main">
        <!-- Page Header -->
        <div class="page-header">
            <h1>
                <iconify-icon icon="mdi:heart" width="28" height="28"></iconify-icon>
                Koleksi Favorit
            </h1>
            <p>Simpan dan kelola buku-buku pilihan Anda</p>
        </div>

        <!-- Error Alert -->
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger">
                <iconify-icon icon="mdi:alert-circle" width="16" height="16"></iconify-icon>
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>

        <!-- Success Alert -->
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success">
                <iconify-icon icon="mdi:check-circle" width="16" height="16"></iconify-icon>
                <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>

        <!-- Two Column Layout -->
        <div class="layout-container">
            <!-- Form Section -->
            <div>
                <div class="form-card">
                    <h2>
                        <iconify-icon icon="mdi:plus-circle-outline"></iconify-icon>
                        Tambah Buku Favorit
                    </h2>

                    <form id="favoriteForm">
                        <!-- Kategori Dropdown -->
                        <div class="form-group">
                            <label for="categorySelect">Pilih Kategori</label>
                            <select id="categorySelect" name="kategori">
                                <option value="">-- Semua Kategori --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>">
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Buku Dropdown -->
                        <div class="form-group">
                            <label for="bookSelect">Pilih Buku</label>
                            <select id="bookSelect" name="id_buku" required>
                                <option value="">-- Pilih Buku --</option>
                                <?php foreach ($books as $book): ?>
                                    <option value="<?php echo $book['id_buku']; ?>">
                                        <?php echo htmlspecialchars($book['judul']); ?>
                                        <?php if ($book['kategori']): ?>
                                            (<?php echo htmlspecialchars($book['kategori']); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn">
                            <iconify-icon icon="mdi:plus"></iconify-icon>
                            Tambah ke Favorit
                        </button>
                    </form>
                </div>
            </div>

            <!-- Favorites List Section -->
            <div class="favorites-list">
                <div class="form-card">
                    <div class="favorites-header">
                        <h2>
                            <iconify-icon icon="mdi:heart"></iconify-icon>
                            Koleksi Favorit
                        </h2>
                        <span class="favorites-count"><?php echo count($favorites); ?> Buku</span>
                    </div>

                    <?php if (empty($favorites)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <iconify-icon icon="mdi:heart-outline"></iconify-icon>
                            </div>
                            <h3>Belum ada favorit</h3>
                            <p>Mulai tambahkan buku favorit Anda sekarang!</p>
                        </div>
                    <?php else: ?>
                        <div id="favoritesList">
                            <?php foreach ($favorites as $fav): ?>
                                <div class="book-card" data-favorite-id="<?php echo $fav['id_favorit']; ?>">
                                    <div class="book-card-cover">
                                        <?php if ($fav['cover']): ?>
                                            <img src="<?php echo htmlspecialchars($fav['cover']); ?>" alt="Cover">
                                        <?php else: ?>
                                            <div class="book-card-cover-placeholder">
                                                <iconify-icon icon="mdi:book"></iconify-icon>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="book-card-body">
                                        <p class="book-card-title"><?php echo htmlspecialchars($fav['judul']); ?></p>
                                        <p class="book-card-author">
                                            <iconify-icon icon="mdi:pencil" style="width: 12px; height: 12px;"></iconify-icon>
                                            <?php echo htmlspecialchars($fav['penulis'] ?? 'Unknown'); ?>
                                        </p>
                                        <?php if ($fav['kategori']): ?>
                                            <span class="book-card-category">
                                                <?php echo htmlspecialchars($fav['kategori']); ?>
                                            </span>
                                        <?php endif; ?>
                                        <div class="book-card-action">
                                            <button class="btn-remove"
                                                onclick="removeFavorite(<?php echo $fav['id_favorit']; ?>)">
                                                <iconify-icon icon="mdi:trash-can-outline"></iconify-icon>
                                                Hapus
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar on mobile
        const navToggle = document.getElementById('navToggle');
        const navSidebar = document.getElementById('navSidebar');

        if (navToggle) {
            navToggle.addEventListener('click', () => {
                navSidebar.classList.toggle('active');
            });

            document.addEventListener('click', (e) => {
                if (!navSidebar.contains(e.target) && !navToggle.contains(e.target)) {
                    navSidebar.classList.remove('active');
                }
            });
        }

        // Handle category change
        document.getElementById('categorySelect').addEventListener('change', async function () {
            const category = this.value;
            const bookSelect = document.getElementById('bookSelect');

            try {
                const url = category
                    ? `/perpustakaan-online/public/api/favorites.php?action=books_by_category&category=${encodeURIComponent(category)}`
                    : `/perpustakaan-online/public/api/favorites.php?action=books_by_category`;

                const response = await fetch(url);
                const data = await response.json();

                if (data.success) {
                    bookSelect.innerHTML = '<option value="">-- Pilih Buku --</option>';
                    data.data.forEach(book => {
                        const option = document.createElement('option');
                        option.value = book.id_buku;
                        option.textContent = `${book.judul} (${book.kategori || 'Tanpa Kategori'})`;
                        bookSelect.appendChild(option);
                    });
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Gagal memuat daftar buku');
            }
        });

        // Handle form submission
        document.getElementById('favoriteForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const bookId = document.getElementById('bookSelect').value;
            const category = document.getElementById('categorySelect').value;

            if (!bookId) {
                alert('Silakan pilih buku terlebih dahulu');
                return;
            }

            const formData = new FormData();
            formData.append('id_buku', bookId);
            if (category) {
                formData.append('kategori', category);
            }

            try {
                const response = await fetch('/perpustakaan-online/public/api/favorites.php?action=add', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert('Buku berhasil ditambahkan ke favorit!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Gagal menambahkan buku ke favorit');
            }
        });

        // Handle remove favorite
        function removeFavorite(favoriteId) {
            if (!confirm('Yakin ingin menghapus buku ini dari favorit?')) {
                return;
            }

            const formData = new FormData();
            formData.append('id_favorit', favoriteId);

            fetch('/perpustakaan-online/public/api/favorites.php?action=remove', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector(`[data-favorite-id="${favoriteId}"]`).remove();
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Gagal menghapus dari favorit');
                });
        }
    </script>
</body>

</html>