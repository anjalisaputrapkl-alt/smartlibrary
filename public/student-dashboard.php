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

        .nav-sidebar-menu-icon {
            font-size: 18px;
            width: 24px;
            text-align: center;
            flex-shrink: 0;
        }

        iconify-icon {
            display: inline-block;
            vertical-align: middle;
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
            margin-left: 240px;
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
            color: var(--section-header-text, var(--text));
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--section-header, transparent);
            padding: 12px 16px;
            margin: -20px -20px 16px -20px;
            border-radius: 12px 12px 0 0;
        }

        .sidebar-section h3 iconify-icon {
            width: 16px;
            height: 16px;
            color: var(--accent);
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
            background: var(--surface);
            color: var(--text);
            transition: 0.2s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(58, 127, 242, 0.1);
        }

        .sort-select {
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 13px;
            font-family: inherit;
            cursor: pointer;
            background: var(--surface);
            color: var(--text);
            transition: 0.2s ease;
        }

        .sort-select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .btn-search {
            padding: 10px 20px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .btn-search:hover {
            background: var(--primary-dark);
            opacity: 0.9;
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
        .book-card:nth-child(1) {
            animation-delay: 0.45s;
        }

        .book-card:nth-child(2) {
            animation-delay: 0.50s;
        }

        .book-card:nth-child(3) {
            animation-delay: 0.55s;
        }

        .book-card:nth-child(4) {
            animation-delay: 0.60s;
        }

        .book-card:nth-child(5) {
            animation-delay: 0.65s;
        }

        .book-card:nth-child(6) {
            animation-delay: 0.70s;
        }

        .book-card:nth-child(7) {
            animation-delay: 0.75s;
        }

        .book-card:nth-child(8) {
            animation-delay: 0.80s;
        }

        .book-card:nth-child(n+9) {
            animation-delay: 0.85s;
        }

        .book-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(58, 127, 242, 0.15);
            border-color: var(--primary);
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

        .book-cover iconify-icon {
            width: 48px;
            height: 48px;
            color: white;
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
            color: var(--text-muted);
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
            color: var(--text-muted);
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
            color: var(--text-muted);
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
            color: var(--text-muted);
            font-size: 14px;
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
                transform: translateY(40px);
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
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid var(--border);
        }

        .modal-btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .modal-btn-borrow {
            background: var(--accent);
            color: white;
        }

        .modal-btn-borrow:hover:not(:disabled) {
            background: #062d4a;
        }

        .modal-btn-borrow:disabled {
            background: var(--border);
            color: var(--text-muted);
            cursor: not-allowed;
        }

        .modal-btn-close {
            background: var(--bg);
            color: var(--text);
            border: 1px solid var(--border);
        }

        .modal-btn-close:hover {
            background: var(--border);
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

            .modal-content {
                max-width: 90%;
                width: 100%;
            }
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .header {
                margin-left: 240px;
            }

            .container {
                margin-left: 240px;
            }

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

            .container {
                padding: 16px;
                margin-left: 0;
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
            .nav-toggle {
                display: flex;
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
                margin-left: 0;
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

            .container {
                padding: 12px;
                margin-left: 0;
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
                            <div class="book-card">
                                <div class="book-cover">
                                    <?php if (!empty($book['cover_image'])): ?>
                                        <img src="../img/covers/<?php echo htmlspecialchars($book['cover_image']); ?>"
                                            alt="<?php echo htmlspecialchars($book['title']); ?>"
                                            style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <iconify-icon icon="mdi:book-open-variant" width="48" height="48"></iconify-icon>
                                    <?php endif; ?>
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
                            <div class="empty-state-icon">📚</div>
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
    </script>
</body>

</html>