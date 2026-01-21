<?php
session_start();
$pdo = require __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/NotificationsService.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: /?login_required=1');
    exit;
}

$user = $_SESSION['user'];
$school_id = $user['school_id'];
$studentId = $user['id'];

// Initialize variables
$notifications = [];
$stats = [];
$errorMessage = '';
$successMessage = '';

try {
    $service = new NotificationsService($pdo);

    // Get sort filter from GET
    $sort = $_GET['sort'] ?? 'latest';
    $validSorts = ['latest', 'oldest'];
    $sort = in_array($sort, $validSorts) ? $sort : 'latest';

    // Get notifications
    $notifications = $service->getAllNotifications($studentId);

    // Sort
    if ($sort === 'oldest') {
        $notifications = array_reverse($notifications);
    }

    $stats = $service->getStatistics($studentId);

} catch (Exception $e) {
    $errorMessage = 'Error: ' . htmlspecialchars($e->getMessage());
}

$pageTitle = 'Notifikasi';

// Helper function untuk format tanggal
function formatDate($date)
{
    return NotificationsService::formatDate($date);
}

// Helper function untuk get icon
function getIcon($type)
{
    return NotificationsService::getIcon($type);
}

// Helper function untuk get badge class
function getBadgeClass($type)
{
    return NotificationsService::getBadgeClass($type);
}

// Helper function untuk get label
function getLabel($type)
{
    return NotificationsService::getLabel($type);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi - Perpustakaan Digital</title>
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--card);
            border-radius: 12px;
            padding: 20px;
            border-left: 4px solid var(--accent);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            animation: fadeInUp 0.6s ease-out;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .stat-card.overdue {
            border-left-color: var(--danger);
        }

        .stat-card.warning {
            border-left-color: var(--warning);
        }

        .stat-card.return {
            border-left-color: var(--accent);
        }

        .stat-card.info {
            border-left-color: var(--accent);
        }

        .stat-card.newbooks {
            border-left-color: #10b981;
        }

        .stat-card-label {
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stat-card-label iconify-icon {
            width: 16px;
            height: 16px;
        }

        .stat-card-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--text);
        }

        .stat-card.overdue .stat-card-value {
            color: var(--danger);
        }

        .stat-card.warning .stat-card-value {
            color: var(--warning);
        }

        .stat-card.return .stat-card-value {
            color: var(--accent);
        }

        .stat-card.info .stat-card-value {
            color: var(--accent);
        }

        /* Filter Bar */
        .filter-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 16px;
            background: var(--card);
            color: var(--text);
            border: 2px solid var(--border);
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }

        /* Notification Card */
        .notification-card {
            background: var(--card);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            animation: fadeInUp 0.6s ease-out;
            margin-bottom: 16px;
            transition: all 0.2s ease;
            border-left: 4px solid var(--border);
        }

        .notification-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .notification-card.unread {
            background: var(--accent-light);
            border-left-color: var(--accent);
        }

        .notification-card.telat {
            border-left-color: var(--danger);
        }

        .notification-card.peringatan {
            border-left-color: var(--warning);
        }

        .notification-card.pengembalian {
            border-left-color: var(--accent);
        }

        .notification-card.info {
            border-left-color: #0891b2;
        }

        .notification-card.sukses {
            border-left-color: var(--success);
        }

        .notification-card.buku {
            border-left-color: #10b981;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(16, 185, 129, 0.02) 100%);
        }

        .notification-card.buku .notification-card-icon {
            background: #d1fae5;
            color: #10b981;
        }

        .notification-card-content {
            padding: 20px;
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }

        .notification-card-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            flex-shrink: 0;
            font-size: 24px;
        }

        .notification-card.unread .notification-card-icon {
            background: var(--accent-light);
            color: var(--accent);
        }

        .notification-card.telat .notification-card-icon {
            background: #fee2e2;
            color: var(--danger);
        }

        .notification-card.peringatan .notification-card-icon {
            background: #fef3c7;
            color: var(--warning);
        }

        .notification-card.pengembalian .notification-card-icon,
        .notification-card.info .notification-card-icon {
            background: #cffafe;
            color: #0891b2;
        }

        .notification-card.sukses .notification-card-icon {
            background: #d1fae5;
            color: var(--success);
        }

        .notification-card.buku .notification-card-icon {
            background: #ede9fe;
            color: #8b5cf6;
        }

        .notification-card-body {
            flex: 1;
        }

        .notification-card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 8px;
            gap: 12px;
        }

        .notification-card-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--text);
            margin: 0;
        }

        .notification-card-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .notification-badge-overdue {
            background: #fee2e2;
            color: #991b1b;
        }

        .notification-badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .notification-badge-return {
            background: #cffafe;
            color: #164e63;
        }

        .notification-badge-info {
            background: #e0f2fe;
            color: #0c4a6e;
        }

        .notification-badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .notification-badge-book {
            background: #d1fae5;
            color: #065f46;
        }

        .notification-badge-default {
            background: var(--border);
            color: var(--text-muted);
        }

        .notification-card-message {
            font-size: 14px;
            color: var(--text-muted);
            margin: 0 0 8px 0;
            line-height: 1.5;
        }

        .notification-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: var(--text-muted);
        }

        .notification-card-date {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .notification-card-actions {
            display: flex;
            gap: 8px;
        }

        .notification-card-action {
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.2s ease;
            font-size: 12px;
        }

        .notification-card-action:hover {
            background: var(--border);
            color: var(--text);
        }

        .notification-card-action iconify-icon {
            width: 16px;
            height: 16px;
            vertical-align: middle;
            margin-right: 4px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 24px;
            color: var(--text-muted);
        }

        .empty-state-icon {
            font-size: 64px;
            color: var(--border);
            margin-bottom: 16px;
        }

        .empty-state h3 {
            font-size: 20px;
            color: var(--text);
            margin: 0 0 8px 0;
        }

        .empty-state p {
            font-size: 14px;
            margin: 0 0 16px 0;
        }

        /* Alert */
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
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

        .alert-info {
            background: #e0f2fe;
            color: #0c4a6e;
            border: 1px solid #bae6fd;
        }

        .alert iconify-icon {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        /* Responsive */
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

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .stat-card {
                padding: 16px;
            }

            .stat-card-value {
                font-size: 24px;
            }

            .page-header h1 {
                font-size: 20px;
            }

            .filter-bar {
                gap: 8px;
            }

            .filter-btn {
                padding: 6px 12px;
                font-size: 12px;
            }

            .notification-card-content {
                padding: 16px;
                gap: 12px;
            }

            .notification-card-icon {
                width: 40px;
                height: 40px;
                font-size: 20px;
            }

            .notification-card-title {
                font-size: 14px;
            }

            .notification-card-message {
                font-size: 13px;
            }

            .notification-card-header {
                flex-wrap: wrap;
            }

            .notification-card-badge {
                font-size: 10px;
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

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .notification-card-content {
                padding: 12px;
            }

            .notification-card-icon {
                width: 36px;
                height: 36px;
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
        <!-- Error Alert -->
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger">
                <iconify-icon icon="mdi:alert-circle" width="18" height="18"></iconify-icon>
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>

        <!-- Success Alert -->
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success">
                <iconify-icon icon="mdi:check-circle" width="18" height="18"></iconify-icon>
                <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="page-header">
            <h1>
                <iconify-icon icon="mdi:bell" width="28" height="28"></iconify-icon>
                Notifikasi
            </h1>
            <p>Pantau semua notifikasi penting dari sistem perpustakaan</p>
        </div>

        <!-- Stats Grid -->
        <?php if (!empty($stats)): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-label">
                        <iconify-icon icon="mdi:bell" width="16" height="16"></iconify-icon>
                        Total Notifikasi
                    </div>
                    <div class="stat-card-value"><?php echo (int) ($stats['total'] ?? 0); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-label">
                        <iconify-icon icon="mdi:email-multiple-outline" width="16" height="16"></iconify-icon>
                        Belum Dibaca
                    </div>
                    <div class="stat-card-value"><?php echo (int) ($stats['unread'] ?? 0); ?></div>
                </div>
                <div class="stat-card overdue">
                    <div class="stat-card-label">
                        <iconify-icon icon="mdi:alert-circle" width="16" height="16"></iconify-icon>
                        Keterlambatan
                    </div>
                    <div class="stat-card-value"><?php echo (int) ($stats['overdue'] ?? 0); ?></div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-card-label">
                        <iconify-icon icon="mdi:alert-triangle" width="16" height="16"></iconify-icon>
                        Peringatan
                    </div>
                    <div class="stat-card-value"><?php echo (int) ($stats['warning'] ?? 0); ?></div>
                </div>
                <div class="stat-card return">
                    <div class="stat-card-label">
                        <iconify-icon icon="mdi:package-variant-closed" width="16" height="16"></iconify-icon>
                        Pengembalian
                    </div>
                    <div class="stat-card-value"><?php echo (int) ($stats['return'] ?? 0); ?></div>
                </div>
                <div class="stat-card info">
                    <div class="stat-card-label">
                        <iconify-icon icon="mdi:information" width="16" height="16"></iconify-icon>
                        Informasi
                    </div>
                    <div class="stat-card-value"><?php echo (int) ($stats['info'] ?? 0); ?></div>
                </div>
                <div class="stat-card newbooks">
                    <div class="stat-card-label">
                        <iconify-icon icon="mdi:book-open-page-variant" width="16" height="16"></iconify-icon>
                        Buku Baru
                    </div>
                    <div class="stat-card-value"><?php echo (int) ($stats['newbooks'] ?? 0); ?></div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <a href="?sort=latest" class="filter-btn <?php echo $sort === 'latest' ? 'active' : ''; ?>">
                <iconify-icon icon="mdi:clock" width="16" height="16"></iconify-icon>
                Terbaru
            </a>
            <a href="?sort=oldest" class="filter-btn <?php echo $sort === 'oldest' ? 'active' : ''; ?>">
                <iconify-icon icon="mdi:archive" width="16" height="16"></iconify-icon>
                Terlama
            </a>
        </div>

        <!-- Notifications List -->
        <?php if (empty($notifications)): ?>
            <!-- Empty State -->
            <div class="empty-state">
                <div class="empty-state-icon">
                    <iconify-icon icon="mdi:inbox-multiple" width="64" height="64"></iconify-icon>
                </div>
                <h3>Belum Ada Notifikasi</h3>
                <p>Semua peminjaman Anda dalam kondisi baik. Tidak ada notifikasi penting saat ini.</p>
                <a href="student-dashboard.php"
                    style="display: inline-block; padding: 10px 20px; background: var(--accent); color: white; text-decoration: none; border-radius: 6px; font-weight: 600; transition: all 0.2s ease;">
                    <iconify-icon icon="mdi:arrow-left" width="16" height="16"></iconify-icon>
                    Kembali ke Dashboard
                </a>
            </div>
        <?php else: ?>
            <!-- Notifications Cards -->
            <?php foreach ($notifications as $notif): ?>
                <div
                    class="notification-card <?php echo !$notif['status_baca'] ? 'unread' : ''; ?> <?php echo htmlspecialchars($notif['jenis_notifikasi']); ?>">
                    <div class="notification-card-content">
                        <div class="notification-card-icon">
                            <iconify-icon icon="<?php echo getIcon($notif['jenis_notifikasi']); ?>" width="24"
                                height="24"></iconify-icon>
                        </div>
                        <div class="notification-card-body">
                            <div class="notification-card-header">
                                <h3 class="notification-card-title">
                                    <?php echo htmlspecialchars($notif['judul']); ?>
                                </h3>
                                <span class="notification-card-badge <?php echo getBadgeClass($notif['jenis_notifikasi']); ?>">
                                    <?php echo getLabel($notif['jenis_notifikasi']); ?>
                                </span>
                            </div>
                            <p class="notification-card-message">
                                <?php echo htmlspecialchars($notif['pesan']); ?>
                            </p>
                            <div class="notification-card-footer">
                                <div class="notification-card-date">
                                    <iconify-icon icon="mdi:clock-outline" width="16" height="16"></iconify-icon>
                                    <?php echo formatDate($notif['tanggal']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
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
    </script>
</body>

</html>