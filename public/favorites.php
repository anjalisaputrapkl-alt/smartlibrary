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

        .favorites-count {
            font-size: 13px;
            font-weight: 600;
            background: var(--accent-light);
            color: var(--accent);
            padding: 4px 12px;
            border-radius: 20px;
        }

        /* Favorites Grid */
        .favorites-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 20px;
        }

        .favorites-grid .book-card {
            background: var(--card);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            padding: 0;
            box-shadow: none;
            border-left: none;
            margin-bottom: 0;
            gap: 0;
        }

        .favorites-grid .book-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(58, 127, 242, 0.15);
            border-color: var(--primary);
        }

        .favorites-grid .book-card-cover {
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
            border-radius: 0;
        }

        .favorites-grid .book-card-cover .btn-love {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            background: rgba(255, 255, 255, 0.9);
            color: var(--text);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            font-size: 20px;
            padding: 0;
            z-index: 10;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .favorites-grid .book-card-cover .btn-love:hover {
            background: white;
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .favorites-grid .book-card-cover .btn-love.loved {
            color: var(--danger);
        }

        .favorites-grid .book-card-cover .btn-love iconify-icon {
            width: 20px;
            height: 20px;
        }

        .favorites-grid .book-card-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .favorites-grid .book-card-cover-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 48px;
        }

        .favorites-grid .book-card-body {
            flex: 1;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .favorites-grid .book-card-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
            margin: 0;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .favorites-grid .book-card-author {
            font-size: 12px;
            color: var(--text-muted);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .favorites-grid .book-card-category {
            font-size: 11px;
            color: var(--accent);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
            background: transparent;
            padding: 0;
            border-radius: 0;
            margin-bottom: 8px;
        }

        .favorites-grid .book-card-action {
            margin-top: auto;
        }

        .favorites-grid .btn-remove {
            width: 100%;
            padding: 10px;
            background: var(--danger);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .favorites-grid .btn-remove:hover {
            background: #dc2626;
        }

        .favorites-grid .book-card-actions {
            display: flex;
            gap: 8px;
            margin-top: auto;
        }

        .favorites-grid .btn-borrow,
        .favorites-grid .btn-detail {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        .favorites-grid .btn-borrow {
            background: var(--accent);
            color: white;
        }

        .favorites-grid .btn-borrow:hover {
            background: #062d4a;
            transform: translateY(-1px);
        }

        .favorites-grid .btn-detail {
            background: var(--bg);
            color: var(--accent);
            border: 1px solid var(--accent);
        }

        .favorites-grid .btn-detail:hover {
            background: var(--accent-light);
        }

        .favorites-grid .btn-borrow iconify-icon,
        .favorites-grid .btn-detail iconify-icon {
            width: 14px;
            height: 14px;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }

        .modal.active {
            display: flex;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-content {
            background: var(--card);
            border-radius: 16px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            background: var(--card);
        }

        .modal-header h2 {
            font-size: 18px;
            font-weight: 700;
            color: var(--text);
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: var(--text-muted);
            transition: color 0.2s;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            color: var(--text);
        }

        .modal-body {
            padding: 24px;
            display: flex;
            gap: 24px;
            align-items: flex-start;
        }

        .modal-book-left {
            display: flex;
            flex-direction: column;
            gap: 16px;
            flex-shrink: 0;
        }

        .modal-book-cover {
            width: 200px;
            height: 300px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .modal-book-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .modal-book-cover iconify-icon {
            width: 80px;
            height: 80px;
            color: white;
        }

        .modal-book-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text);
            line-height: 1.4;
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
            white-space: normal;
        }

        .modal-book-info {
            display: flex;
            flex-direction: column;
            gap: 16px;
            flex: 1;
        }

        .modal-book-meta {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .modal-book-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .modal-book-item-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .modal-book-item-value {
            font-size: 14px;
            color: var(--text);
        }

        .modal-book-status {
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            width: fit-content;
        }

        .modal-book-status.available {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .modal-book-status.unavailable {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            margin-top: 16px;
        }

        .modal-btn {
            flex: 1;
            padding: 12px 16px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .modal-btn-borrow {
            background: var(--accent);
            color: white;
        }

        .modal-btn-borrow:hover:not(:disabled) {
            background: #062d4a;
            transform: translateY(-1px);
        }

        .modal-btn-borrow:disabled {
            background: var(--border);
            color: var(--text-muted);
            cursor: not-allowed;
            opacity: 0.6;
        }

        .modal-btn-close {
            background: var(--bg);
            color: var(--text);
            border: 1px solid var(--border);
        }

        .modal-btn-close:hover {
            background: var(--muted);
        }

        /* Modal Responsive */
        @media (max-width: 768px) {
            .modal-body {
                flex-direction: column;
            }

            .modal-book-left {
                width: 100%;
            }

            .modal-book-cover {
                width: 100%;
                height: 250px;
            }
        }

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
        @media (max-width: 1024px) {}

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

            .favorites-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 16px;
            }

            .favorites-grid .book-card-cover {
                height: 180px;
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

            .favorites-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 12px;
            }

            .favorites-grid .book-card-cover {
                height: 160px;
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

        <!-- Favorites Grid Section -->
        <div>
            <div style="margin-bottom: 24px;">
                <h2
                    style="font-size: 18px; font-weight: 600; display: flex; align-items: center; gap: 8px; margin: 0 0 16px 0;">
                    <iconify-icon icon="mdi:heart" style="color: var(--success);"></iconify-icon>
                    Koleksi Favorit
                    <span class="favorites-count"><?php echo count($favorites); ?> Buku</span>
                </h2>
            </div>

            <?php if (empty($favorites)): ?>
                <div class="empty-state"
                    style="background: var(--card); border-radius: 12px; border: 1px solid var(--border); padding: 60px 40px; text-align: center;">
                    <div class="empty-state-icon">
                        <iconify-icon icon="mdi:heart-outline"></iconify-icon>
                    </div>
                    <h3>Belum ada favorit</h3>
                    <p>Mulai tambahkan buku favorit Anda sekarang!</p>
                </div>
            <?php else: ?>
                <div class="favorites-grid" id="favoritesList">
                    <?php foreach ($favorites as $fav): ?>
                        <div class="book-card" data-favorite-id="<?php echo $fav['id_favorit']; ?>"
                            data-book-id="<?php echo $fav['id_buku']; ?>">
                            <div class="book-card-cover">
                                <button class="btn-love loved"
                                    onclick="toggleFavorite(event, <?php echo $fav['id_buku']; ?>, '<?php echo htmlspecialchars(str_replace("'", "\\'", $fav['judul'])); ?>')">
                                    <iconify-icon icon="mdi:heart"></iconify-icon>
                                </button>
                                <?php if ($fav['cover']): ?>
                                    <img src="../img/covers/<?php echo htmlspecialchars($fav['cover']); ?>"
                                        alt="<?php echo htmlspecialchars($fav['judul']); ?>"
                                        style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <div class="book-card-cover-placeholder">
                                        <iconify-icon icon="mdi:book-open-variant" width="48" height="48"></iconify-icon>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="book-card-body">
                                <h3 class="book-card-title"><?php echo htmlspecialchars($fav['judul']); ?></h3>
                                <p class="book-card-author"><?php echo htmlspecialchars($fav['penulis'] ?? '-'); ?></p>
                                <p class="book-card-category"><?php echo htmlspecialchars($fav['buku_kategori'] ?? 'Umum'); ?>
                                </p>
                                <div class="book-card-actions">
                                    <button class="btn-borrow"
                                        onclick="borrowBook(<?php echo $fav['id_buku']; ?>, '<?php echo htmlspecialchars(str_replace("'", "\\'", $fav['judul'])); ?>')">
                                        Pinjam
                                    </button>
                                    <button class="btn-detail" onclick="viewDetail(<?php echo $fav['id_buku']; ?>)">
                                        Detail
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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
                    } else {
                        btn.classList.add('loved');
                        icon.setAttribute('icon', 'mdi:heart');
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Gagal mengubah favorite');
            }
        }

        let currentBookData = null;

        // Get book detail and open modal
        async function viewDetail(bookId) {
            try {
                const response = await fetch(`api/get-book.php?id=${bookId}`);
                const data = await response.json();

                if (data.success) {
                    openBookModal(data.data);
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Gagal memuat detail buku');
            }
        }

        // Open book modal
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

        // Borrow book
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
    </script>
</body>

</html>