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
$memberId = $user['id'];

// Inisialisasi variabel
$borrowingHistory = [];
$totalBooks = 0;
$borrowedBooks = 0;
$returnedBooks = 0;
$overdueBooks = 0;
$errorMessage = '';

try {
    /**
     * Query untuk mengambil riwayat peminjaman dengan informasi buku
     * 
     * JOIN antara tabel:
     * - borrows: data peminjaman
     * - books: informasi buku (judul, penulis, cover)
     * 
     * Filter berdasarkan member_id dan sortir dari tanggal pinjam terbaru
     */
    $query = "
        SELECT 
            b.id AS borrow_id,
            b.member_id,
            b.book_id,
            b.borrowed_at,
            b.due_at,
            b.returned_at,
            b.status,
            bk.title AS book_title,
            bk.author,
            bk.cover_image,
            CASE 
                WHEN b.status = 'returned' THEN 'Dikembalikan'
                WHEN b.status = 'overdue' THEN 'Telat'
                WHEN b.status = 'borrowed' THEN 'Dipinjam'
                ELSE b.status
            END AS status_text,
            DATEDIFF(b.due_at, NOW()) AS hari_sisa
        FROM borrows b
        LEFT JOIN books bk ON b.book_id = bk.id
        WHERE b.member_id = ?
        ORDER BY b.borrowed_at DESC
    ";

    $stmt = $pdo->prepare($query);
    
    // Sanitasi input
    if (!$stmt->execute([$memberId])) {
        throw new Exception('Gagal mengambil data peminjaman');
    }

    $borrowingHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalBooks = count($borrowingHistory);

    // Hitung statistik
    foreach ($borrowingHistory as $item) {
        switch ($item['status']) {
            case 'borrowed':
                $borrowedBooks++;
                break;
            case 'returned':
                $returnedBooks++;
                break;
            case 'overdue':
                $overdueBooks++;
                break;
        }
    }

} catch (PDOException $e) {
    $errorMessage = 'Error Database: ' . htmlspecialchars($e->getMessage());
} catch (Exception $e) {
    $errorMessage = 'Error: ' . htmlspecialchars($e->getMessage());
}

// Helper function untuk format tanggal
function formatDate($date) {
    if (empty($date) || $date === '0000-00-00 00:00:00') {
        return '-';
    }
    return date('d M Y H:i', strtotime($date));
}

// Helper function untuk format status
function getStatusBadge($status) {
    switch ($status) {
        case 'borrowed':
            return '<span class="badge badge-primary">Dipinjam</span>';
        case 'returned':
            return '<span class="badge badge-success">Dikembalikan</span>';
        case 'overdue':
            return '<span class="badge badge-danger">Telat</span>';
        default:
            return '<span class="badge badge-secondary">' . htmlspecialchars($status) . '</span>';
    }
}

// Helper function untuk status warna
function getStatusColor($status) {
    switch ($status) {
        case 'borrowed':
            return 'warning';
        case 'returned':
            return 'success';
        case 'overdue':
            return 'danger';
        default:
            return 'secondary';
    }
}

$pageTitle = 'Riwayat Peminjaman';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman Buku - Perpustakaan Digital</title>
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
            margin-left: 7px;
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
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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

        .stat-card.borrowed {
            border-left-color: var(--warning);
        }

        .stat-card.returned {
            border-left-color: var(--success);
        }

        .stat-card.overdue {
            border-left-color: var(--danger);
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

        .stat-card.borrowed .stat-card-value {
            color: var(--warning);
        }

        .stat-card.returned .stat-card-value {
            color: var(--success);
        }

        .stat-card.overdue .stat-card-value {
            color: var(--danger);
        }

        /* History Card */
        .history-card {
            background: var(--card);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            animation: fadeInUp 0.6s ease-out 0.1s both;
        }

        .history-card-header {
            background: linear-gradient(135deg, var(--accent) 0%, #062d4a 100%);
            color: white;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .history-card-header h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
        }

        .history-card-header iconify-icon {
            width: 24px;
            height: 24px;
        }

        /* Table */
        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .table thead {
            background: var(--bg);
            border-bottom: 2px solid var(--border);
        }

        .table th {
            padding: 16px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table td {
            padding: 16px;
            border-bottom: 1px solid var(--border);
        }

        .table tbody tr:hover {
            background: var(--bg);
        }

        /* Book Info */
        .book-info {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .book-cover {
            width: 50px;
            height: 70px;
            background: linear-gradient(135deg, var(--accent) 0%, #062d4a 100%);
            border-radius: 6px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .book-details h6 {
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
            margin: 0 0 4px 0;
        }

        .book-details small {
            font-size: 12px;
            color: var(--text-muted);
        }

        /* Badge */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-borrowed {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-returned {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-overdue {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Date Info */
        .date-cell {
            font-size: 13px;
        }

        .date-label {
            display: block;
            font-size: 11px;
            color: var(--text-muted);
            margin-bottom: 2px;
        }

        .date-value {
            color: var(--text);
            font-weight: 500;
        }

        .date-hint {
            font-size: 11px;
            margin-top: 4px;
            font-weight: 500;
        }

        .date-hint.success {
            color: var(--success);
        }

        .date-hint.danger {
            color: var(--danger);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 24px;
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

        .empty-state-btn {
            display: inline-block;
            padding: 10px 20px;
            background: var(--accent);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .empty-state-btn:hover {
            background: #062d4a;
        }

        /* Alert */
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
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

            .table {
                font-size: 12px;
            }

            .table th,
            .table td {
                padding: 12px;
            }

            .book-info {
                flex-direction: column;
                align-items: center;
            }

            .book-cover {
                width: 40px;
                height: 60px;
            }

            .page-header h1 {
                font-size: 20px;
            }

            .history-card-header h2 {
                font-size: 16px;
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
                grid-template-columns: 1fr;
            }

            .table {
                font-size: 11px;
            }

            .table th,
            .table td {
                padding: 8px;
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

        <!-- Page Header -->
        <div class="page-header">
            <h1>
                <iconify-icon icon="mdi:history" width="28" height="28"></iconify-icon>
                Riwayat Peminjaman Buku
            </h1>
            <p>Lihat semua buku yang pernah Anda pinjam dan status pengembaliannya</p>
        </div>

        <!-- Statistik Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-label">
                    <iconify-icon icon="mdi:book-open-variant" width="16" height="16"></iconify-icon>
                    Total Peminjaman
                </div>
                <div class="stat-card-value"><?php echo $totalBooks; ?></div>
            </div>
            <div class="stat-card borrowed">
                <div class="stat-card-label">
                    <iconify-icon icon="mdi:hourglass-half" width="16" height="16"></iconify-icon>
                    Sedang Dipinjam
                </div>
                <div class="stat-card-value"><?php echo $borrowedBooks; ?></div>
            </div>
            <div class="stat-card returned">
                <div class="stat-card-label">
                    <iconify-icon icon="mdi:check-circle" width="16" height="16"></iconify-icon>
                    Sudah Dikembalikan
                </div>
                <div class="stat-card-value"><?php echo $returnedBooks; ?></div>
            </div>
            <div class="stat-card overdue">
                <div class="stat-card-label">
                    <iconify-icon icon="mdi:alert-triangle" width="16" height="16"></iconify-icon>
                    Telat Dikembalikan
                </div>
                <div class="stat-card-value"><?php echo $overdueBooks; ?></div>
            </div>
        </div>

        <!-- History Card -->
        <div class="history-card">
            <div class="history-card-header">
                <iconify-icon icon="mdi:list-box" width="24" height="24"></iconify-icon>
                <h2>Detail Riwayat Peminjaman</h2>
            </div>

            <?php if (empty($borrowingHistory)): ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <iconify-icon icon="mdi:inbox-multiple" width="64" height="64"></iconify-icon>
                    </div>
                    <h3>Belum Ada Riwayat Peminjaman</h3>
                    <p>Anda belum pernah meminjam buku. Silakan kunjungi katalog buku untuk memulai.</p>
                    <a href="student-dashboard.php" class="empty-state-btn">
                        <iconify-icon icon="mdi:arrow-left" width="16" height="16"></iconify-icon>
                        Kembali ke Dashboard
                    </a>
                </div>
            <?php else: ?>
                <!-- Tabel Responsif -->
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Cover</th>
                                <th>Judul & Penulis</th>
                                <th>Tanggal Pinjam</th>
                                <th>Tenggat Kembali</th>
                                <th>Tanggal Kembali</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($borrowingHistory as $item): ?>
                                <tr>
                                    <!-- Cover -->
                                    <td>
                                        <?php if (!empty($item['cover_image'])): ?>
                                            <img 
                                                src="<?php echo htmlspecialchars('../img/covers/' . $item['cover_image']); ?>" 
                                                alt="<?php echo htmlspecialchars($item['book_title']); ?>"
                                                class="book-cover"
                                            >
                                        <?php else: ?>
                                            <div style="width: 50px; height: 70px; background: linear-gradient(135deg, var(--accent) 0%, #062d4a 100%); border-radius: 6px; display: flex; align-items: center; justify-content: center; color: white;">
                                                <iconify-icon icon="mdi:book" width="24" height="24"></iconify-icon>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Info Buku -->
                                    <td>
                                        <div class="book-details">
                                            <h6><?php echo htmlspecialchars($item['book_title'] ?? 'Buku Tidak Ditemukan'); ?></h6>
                                            <small>
                                                <iconify-icon icon="mdi:pen" width="14" height="14"></iconify-icon>
                                                <?php echo htmlspecialchars($item['author'] ?? '-'); ?>
                                            </small>
                                        </div>
                                    </td>

                                    <!-- Tanggal Pinjam -->
                                    <td class="date-cell">
                                        <div class="date-label">Pinjam</div>
                                        <div class="date-value"><?php echo formatDate($item['borrowed_at']); ?></div>
                                    </td>

                                    <!-- Tenggat Kembali -->
                                    <td class="date-cell">
                                        <div class="date-label">Tenggat</div>
                                        <div class="date-value"><?php echo formatDate($item['due_at']); ?></div>
                                        <?php if ($item['status'] === 'borrowed' && $item['hari_sisa'] >= 0): ?>
                                            <div class="date-hint success">
                                                <iconify-icon icon="mdi:check-circle" width="12" height="12"></iconify-icon>
                                                <?php echo $item['hari_sisa']; ?> hari tersisa
                                            </div>
                                        <?php elseif ($item['status'] === 'borrowed'): ?>
                                            <div class="date-hint danger">
                                                <iconify-icon icon="mdi:alert-circle" width="12" height="12"></iconify-icon>
                                                <?php echo abs($item['hari_sisa']); ?> hari telat
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Tanggal Kembali -->
                                    <td class="date-cell">
                                        <div class="date-label">Dikembalikan</div>
                                        <div class="date-value"><?php echo formatDate($item['returned_at']); ?></div>
                                    </td>

                                    <!-- Status -->
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        $statusText = '';
                                        switch ($item['status']) {
                                            case 'borrowed':
                                                $statusClass = 'badge-borrowed';
                                                $statusText = 'Dipinjam';
                                                break;
                                            case 'returned':
                                                $statusClass = 'badge-returned';
                                                $statusText = 'Dikembalikan';
                                                break;
                                            case 'overdue':
                                                $statusClass = 'badge-overdue';
                                                $statusText = 'Telat';
                                                break;
                                            default:
                                                $statusClass = '';
                                                $statusText = htmlspecialchars($item['status']);
                                        }
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer Info -->
        <div style="margin-top: 24px; padding: 16px; background: var(--card); border-radius: 8px; text-align: center; color: var(--text-muted); font-size: 13px; border-left: 4px solid var(--accent);">
            <p style="margin: 0; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <iconify-icon icon="mdi:information" width="16" height="16"></iconify-icon>
                Harap kembalikan buku sebelum tenggat waktu untuk menghindari denda. Hubungi pustakawan jika ada pertanyaan.
            </p>
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

            // Close sidebar when clicking outside
            document.addEventListener('click', (e) => {
                if (!navSidebar.contains(e.target) && !navToggle.contains(e.target)) {
                    navSidebar.classList.remove('active');
                }
            });
        }
    </script>
</body>
</html>
