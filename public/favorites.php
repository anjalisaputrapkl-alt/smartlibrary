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
    <?php require_once __DIR__ . '/../theme-loader.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/school-profile.css">
    <link rel="stylesheet" href="../assets/css/student-dashboard.css">

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
            top: 4px;
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
            background: var(--accent);
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
        /* Reuse standard card layout from student-dashboard.css */






        /* Favorites Controls */
        .favorites-controls {
            display: flex;
            gap: 16px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
            padding: 16px;
            background: var(--card);
            border-radius: 12px;
            border: 1px solid var(--border);
            animation: slideDown 0.4s ease-out;
        }

        .control-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .search-group {
            flex: 1;
            min-width: 250px;
            position: relative;
        }

        .search-icon {
            position: absolute;
            left: 12px;
            color: var(--text-muted);
            width: 18px;
            height: 18px;
            pointer-events: none;
            z-index: 2;
        }

        .search-input {
            width: 100%;
            padding: 10px 12px 10px 40px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 13px;
            font-family: 'Inter', system-ui, sans-serif;
            color: var(--text);
            background: var(--muted);
            transition: all 0.2s ease;
        }

        .search-input::placeholder {
            color: var(--text-muted);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(58, 127, 242, 0.1);
        }

        .btn-clear-search {
            position: absolute;
            right: 10px;
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            z-index: 3;
        }

        .btn-clear-search:hover {
            color: var(--text);
            transform: scale(1.2);
        }

        .filter-sort-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .filter-select,
        .sort-select {
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

        .filter-select:hover,
        .sort-select:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(58, 127, 242, 0.15);
            border-color: var(--primary-2);
        }

        .filter-select:focus,
        .sort-select:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--accent);
            color: white;
            box-shadow: 0 0 0 3px rgba(58, 127, 242, 0.2);
            font-weight: 500;
        }

        .btn-clear-filters {
            padding: 10px 12px;
            border: 1px solid var(--danger);
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .btn-clear-filters:hover {
            background: var(--danger);
            color: white;
        }

        .filter-stats {
            margin-bottom: 20px;
            font-size: 13px;
            color: var(--text-muted);
            padding: 0 4px;
            animation: fadeInUp 0.4s ease-out;
        }

        .filter-stats span {
            font-weight: 600;
            color: var(--text);
        }

        /* Empty state dengan filter aktif */
        .empty-filtered-state {
            text-align: center;
            padding: 60px 24px;
            background: var(--card);
            border-radius: 12px;
            border: 1px solid var(--border);
        }

        .empty-filtered-state-icon {
            font-size: 48px;
            color: var(--border);
            margin-bottom: 12px;
        }

        .empty-filtered-state h3 {
            font-size: 16px;
            color: var(--text);
            margin: 0 0 6px 0;
        }

        .empty-filtered-state p {
            font-size: 13px;
            margin: 0 0 16px 0;
            color: var(--text-muted);
        }

        .empty-filtered-state .btn-reset {
            padding: 8px 16px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .empty-filtered-state .btn-reset:hover {
            background: #062d4a;
            transform: translateY(-1px);
        }

        /* Grid responsive */
        .favorites-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 20px;
            animation: fadeInUp 0.4s ease-out;
        }

        .book-card.hidden {
            display: none;
        }

        .book-card.fade-in {
            animation: fadeInScale 0.3s ease-out;
        }

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
            z-index: 10;
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
            min-width: 0;
            max-width: 200px;
        }

        .modal-book-cover {
            width: 200px;
            aspect-ratio: 2 / 3;
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
            line-height: 1.5;
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
            white-space: normal;
            margin: 0;
            min-width: 0;
            width: 100%;
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
            .favorites-controls {
                flex-direction: column;
                gap: 12px;
            }

            .search-group {
                width: 100%;
                min-width: auto;
            }

            .filter-sort-group {
                width: 100%;
                justify-content: space-between;
            }

            .filter-select,
            .sort-select {
                flex: 1;
                min-width: 0;
            }

            .modal-body {
                flex-direction: column;
            }

            .modal-book-left {
                width: 100%;
            }

            .modal-book-cover {
                width: 100%;
                aspect-ratio: 2 / 3;
            }

            .favorites-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 16px;
            }
        }

        @media (max-width: 480px) {
            .favorites-controls {
                padding: 12px;
                gap: 8px;
            }

            .filter-sort-group {
                width: 100%;
                flex-direction: column;
            }

            .filter-select,
            .sort-select,
            .btn-clear-filters {
                width: 100%;
            }

            .filter-stats {
                font-size: 12px;
            }

            .favorites-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 12px;
            }

            .favorites-grid .book-card-body {
                padding: 12px;
            }

            .favorites-grid .book-card-title {
                font-size: 13px;
            }

            .favorites-grid .btn-detail {
                font-size: 11px;
                padding: 8px 6px;
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
                aspect-ratio: 2 / 3;
            }
        }

        @media (max-width: 480px) {
            .nav-toggle {
                width: 40px;
                height: 40px;
                left: 10px;
                top: 4px;
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
                aspect-ratio: 2 / 3;
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
            <!-- Header dengan Stats -->


            <!-- Search, Filter, dan Sort Bar -->
            <?php if (!empty($favorites)): ?>
            <div class="favorites-controls">
                <!-- Search Bar -->
                <div class="control-group search-group">
                    <iconify-icon icon="mdi:magnify" class="search-icon"></iconify-icon>
                    <input type="text" id="searchInput" class="search-input" placeholder="Cari judul buku favorit...">
                    <button class="btn-clear-search" id="clearSearchBtn" style="display: none;" onclick="clearSearch()">
                        <iconify-icon icon="mdi:close" width="18" height="18"></iconify-icon>
                    </button>
                </div>

                <!-- Filter dan Sort Controls -->
                <div class="control-group filter-sort-group">
                    <select id="categoryFilter" class="filter-select" onchange="applyFilters()">
                        <option value="">Semua Kategori</option>
                    </select>

                    <select id="sortSelect" class="sort-select" onchange="applySorting()">
                        <option value="original">Urutan Awal</option>
                        <option value="a-z">A → Z</option>
                        <option value="z-a">Z → A</option>
                        <option value="newest">Terbaru</option>
                    </select>

                    <button class="btn-clear-filters" id="clearFiltersBtn" style="display: none;" onclick="clearAllFilters()">
                        <iconify-icon icon="mdi:filter-off" width="18" height="18"></iconify-icon>
                        Hapus Filter
                    </button>
                </div>
            </div>

            <!-- Stats Results -->
            <div class="filter-stats">
                <span id="resultsCount">Menampilkan <span id="activeCount"><?php echo count($favorites); ?></span> dari <span id="totalCount"><?php echo count($favorites); ?></span> buku</span>
            </div>
            <?php endif; ?>

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
                <div class="books-grid" id="favoritesList">
                    <?php foreach ($favorites as $fav): ?>
                        <div class="book-card-vertical" data-favorite-id="<?php echo $fav['id_favorit']; ?>" data-book-id="<?php echo $fav['id_buku']; ?>">
                            <div class="book-cover-container">
                                <?php if ($fav['cover']): ?>
                                    <img src="../img/covers/<?php echo htmlspecialchars($fav['cover']); ?>"
                                        alt="<?php echo htmlspecialchars($fav['judul']); ?>" loading="lazy">
                                <?php else: ?>
                                    <div class="no-image-placeholder">
                                        <iconify-icon icon="mdi:book-open-variant" style="font-size: 32px;"></iconify-icon>
                                    </div>
                                <?php endif; ?>

                                <button class="btn-love loved"
                                    onclick="toggleFavorite(event, <?php echo $fav['id_buku']; ?>, '<?php echo htmlspecialchars(str_replace("'", "\\'", $fav['judul'])); ?>')">
                                    <iconify-icon icon="mdi:heart"></iconify-icon>
                                </button>
                            </div>

                            <div class="book-card-body">
                                <div class="book-category"><?php echo htmlspecialchars($fav['buku_kategori'] ?? 'Umum'); ?></div>
                                <div class="book-title" title="<?php echo htmlspecialchars($fav['judul']); ?>"><?php echo htmlspecialchars($fav['judul']); ?></div>
                                <div class="book-author"><?php echo htmlspecialchars($fav['penulis'] ?? '-'); ?></div>
                                
                                <div class="book-card-footer">
                                    <div class="shelf-info">
                                        <iconify-icon icon="mdi:star" style="color: #FFD700;"></iconify-icon> 
                                        <span style="font-weight: 700;"><?php echo $fav['avg_rating'] ? round($fav['avg_rating'], 1) : '0'; ?></span>
                                        <span style="opacity: 0.6; margin-left: 2px;">(<?php echo (int)$fav['total_reviews']; ?>)</span>
                                    </div>
                                    
                                    <div class="action-buttons">
                                        <button class="btn-icon-sm" onclick="viewDetail(<?php echo $fav['id_buku']; ?>)" title="Detail">
                                            <iconify-icon icon="mdi:eye"></iconify-icon>
                                        </button>
                                    </div>
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
                            <span class="modal-book-item-label">Lokasi Rak</span>
                            <span class="modal-book-item-value" id="modalBookShelf">-</span>
                        </div>


                    </div>

                    <div class="modal-actions">
                        <button class="modal-btn modal-btn-close" onclick="closeBookModal()">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ============================================
        // DATA MANAGEMENT
        // ============================================
        
        let allFavorites = <?php echo json_encode($favorites); ?>;
        let filteredFavorites = [...allFavorites];
        let currentFilters = {
            search: '',
            category: '',
            sort: 'original'
        };

        // Extract unique categories
        function getUniqueCategories() {
            const categories = new Set();
            allFavorites.forEach(fav => {
                if (fav.buku_kategori) {
                    categories.add(fav.buku_kategori);
                }
            });
            return Array.from(categories).sort();
        }

        // Initialize category filter options
        function initializeCategoryFilter() {
            const select = document.getElementById('categoryFilter');
            const categories = getUniqueCategories();
            
            categories.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat;
                option.textContent = cat;
                select.appendChild(option);
            });
        }

        // ============================================
        // SEARCH FUNCTIONALITY
        // ============================================

        document.getElementById('searchInput')?.addEventListener('input', function(e) {
            currentFilters.search = e.target.value.toLowerCase();
            
            // Show/hide clear button
            const clearBtn = document.getElementById('clearSearchBtn');
            if (clearBtn) {
                clearBtn.style.display = currentFilters.search ? 'flex' : 'none';
            }
            
            applyFilters();
        });

        function clearSearch() {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.value = '';
                currentFilters.search = '';
                document.getElementById('clearSearchBtn').style.display = 'none';
                applyFilters();
            }
        }

        // ============================================
        // FILTER FUNCTIONALITY
        // ============================================

        function applyFilters() {
            // Start with all favorites
            filteredFavorites = [...allFavorites];

            // Apply search filter
            if (currentFilters.search) {
                filteredFavorites = filteredFavorites.filter(fav => {
                    const title = (fav.judul || '').toLowerCase();
                    const category = (fav.buku_kategori || '').toLowerCase();
                    return title.includes(currentFilters.search) || 
                           category.includes(currentFilters.search);
                });
            }

            // Apply category filter
            if (currentFilters.category) {
                filteredFavorites = filteredFavorites.filter(fav => 
                    fav.buku_kategori === currentFilters.category
                );
            }

            // Apply sorting
            applySorting();

            // Update UI
            updateFavoritesDisplay();
            updateFilterStats();
        }

        // ============================================
        // SORTING FUNCTIONALITY
        // ============================================

        document.getElementById('sortSelect')?.addEventListener('change', function(e) {
            currentFilters.sort = e.target.value;
            applySorting();
            updateFavoritesDisplay();
        });

        function applySorting() {
            const sortType = currentFilters.sort;
            
            if (sortType === 'a-z') {
                filteredFavorites.sort((a, b) => 
                    (a.judul || '').localeCompare(b.judul || '', 'id-ID')
                );
            } else if (sortType === 'z-a') {
                filteredFavorites.sort((a, b) => 
                    (b.judul || '').localeCompare(a.judul || '', 'id-ID')
                );
            } else if (sortType === 'newest') {
                filteredFavorites.sort((a, b) => (b.id_buku || 0) - (a.id_buku || 0));
            } else {
                // 'original' - maintain original order
                filteredFavorites = [...allFavorites];
                
                // Re-apply filters if needed
                if (currentFilters.search || currentFilters.category) {
                    filteredFavorites = filteredFavorites.filter(fav => {
                        let match = true;
                        
                        if (currentFilters.search) {
                            const title = (fav.judul || '').toLowerCase();
                            const category = (fav.buku_kategori || '').toLowerCase();
                            match = match && (title.includes(currentFilters.search) || 
                                            category.includes(currentFilters.search));
                        }
                        
                        if (currentFilters.category) {
                            match = match && fav.buku_kategori === currentFilters.category;
                        }
                        
                        return match;
                    });
                }
            }
        }

        // ============================================
        // CLEAR ALL FILTERS
        // ============================================

        function clearAllFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('categoryFilter').value = '';
            document.getElementById('sortSelect').value = 'original';
            document.getElementById('clearSearchBtn').style.display = 'none';
            document.getElementById('clearFiltersBtn').style.display = 'none';
            
            currentFilters = {
                search: '',
                category: '',
                sort: 'original'
            };
            
            filteredFavorites = [...allFavorites];
            updateFavoritesDisplay();
            updateFilterStats();
        }

        // ============================================
        // UI UPDATE
        // ============================================

        function updateFavoritesDisplay() {
            const favoritesList = document.getElementById('favoritesList');
            if (!favoritesList) return;

            // Clear existing items
            favoritesList.innerHTML = '';

            if (filteredFavorites.length === 0) {
                // Show empty state for filters
                const emptyDiv = document.createElement('div');
                emptyDiv.className = 'empty-filtered-state';
                emptyDiv.innerHTML = `
                    <div class="empty-filtered-state-icon">
                        <iconify-icon icon="mdi:magnify-off"></iconify-icon>
                    </div>
                    <h3>Tidak ada hasil</h3>
                    <p>Coba ubah filter atau pencarian Anda</p>
                    <button class="btn-reset" onclick="clearAllFilters()">Reset Filter</button>
                `;
                favoritesList.appendChild(emptyDiv);
                favoritesList.style.gridColumn = '1 / -1';
            } else {
                // Render books
                filteredFavorites.forEach((fav, index) => {
                    const card = createBookCard(fav);
                    card.classList.add('fade-in');
                    card.style.animationDelay = `${index * 50}ms`;
                    favoritesList.appendChild(card);
                });
                favoritesList.style.gridColumn = 'auto';
            }
        }

        function updateFilterStats() {
            const activeCount = filteredFavorites.length;
            const totalCount = allFavorites.length;

            // Update counter badge
            const countBadge = document.getElementById('favoritesCountBadge');
            if (countBadge) {
                countBadge.textContent = `${activeCount} Buku`;
            }

            // Update stats text
            const activeCountSpan = document.getElementById('activeCount');
            const totalCountSpan = document.getElementById('totalCount');
            if (activeCountSpan) activeCountSpan.textContent = activeCount;
            if (totalCountSpan) totalCountSpan.textContent = totalCount;

            // Show/hide clear filters button
            const hasActiveFilters = currentFilters.search || 
                                    currentFilters.category || 
                                    currentFilters.sort !== 'original';
            const clearBtn = document.getElementById('clearFiltersBtn');
            if (clearBtn) {
                clearBtn.style.display = hasActiveFilters ? 'flex' : 'none';
            }
        }

        // ============================================
        // CREATE BOOK CARD ELEMENT
        // ============================================

        function createBookCard(fav) {
            const card = document.createElement('div');
            card.className = 'book-card-vertical';
            card.setAttribute('data-favorite-id', fav.id_favorit);
            card.setAttribute('data-book-id', fav.id_buku);

            const avgRating = fav.avg_rating ? parseFloat(fav.avg_rating).toFixed(1) : '0';
            const totalReviews = parseInt(fav.total_reviews) || 0;

            card.innerHTML = `
                <div class="book-cover-container">
                    ${fav.cover ? 
                        `<img src="../img/covers/${fav.cover}" alt="${fav.judul}" loading="lazy">` :
                        `<div class="no-image-placeholder"><iconify-icon icon="mdi:book-open-variant" style="font-size: 32px;"></iconify-icon></div>`
                    }
                    <button class="btn-love loved" onclick="toggleFavorite(event, ${fav.id_buku}, '${fav.judul.replace(/'/g, "\\'")}')">
                        <iconify-icon icon="mdi:heart"></iconify-icon>
                    </button>
                </div>
                <div class="book-card-body">
                    <div class="book-category">${fav.buku_kategori || 'Umum'}</div>
                    <div class="book-title" title="${fav.judul}">${fav.judul}</div>
                    <div class="book-author">${fav.penulis || '-'}</div>
                    
                    <div class="book-card-footer">
                        <div class="shelf-info">
                            <iconify-icon icon="mdi:star" style="color: #FFD700;"></iconify-icon> 
                            <span style="font-weight: 700;">${avgRating}</span>
                            <span style="opacity: 0.6; margin-left: 2px;">(${totalReviews})</span>
                        </div>
                        
                        <div class="action-buttons">
                            <button class="btn-icon-sm" onclick="viewDetail(${fav.id_buku})" title="Detail">
                                <iconify-icon icon="mdi:eye"></iconify-icon>
                            </button>
                        </div>
                    </div>
                </div>
            `;

            return card;
        }

        // ============================================
        // EXISTING FUNCTIONS (PRESERVED)
        // ============================================

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
                        // Remove from list and update display
                        const card = btn.closest('.book-card-vertical');
                        if (card) {
                            card.style.opacity = '0.5';
                            setTimeout(() => {
                                allFavorites = allFavorites.filter(f => f.id_buku !== bookId);
                                applyFilters();
                            }, 300);
                        }
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
            document.getElementById('modalBookShelf').textContent = (bookData.shelf || '-') + (bookData.row_number ? ' (Baris ' + bookData.row_number + ')' : '');

            // Show modal
            document.getElementById('bookModal').classList.add('active');
        }

        function closeBookModal() {
            document.getElementById('bookModal').classList.remove('active');
            currentBookData = null;
        }


        // Close modal when clicking outside
        document.getElementById('bookModal')?.addEventListener('click', (e) => {
            if (e.target.id === 'bookModal') {
                closeBookModal();
            }
        });

        // ============================================
        // INITIALIZATION
        // ============================================

        document.addEventListener('DOMContentLoaded', function() {
            if (allFavorites.length > 0) {
                initializeCategoryFilter();
                updateFavoritesDisplay();
                updateFilterStats();
            }
        });
    </script>
    <script src="../assets/js/sidebar.js"></script>
</body>

</html>