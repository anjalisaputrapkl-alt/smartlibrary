<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];

// Get all members for this school
$stmt = $pdo->prepare(
    'SELECT m.*, 
            (SELECT COUNT(*) FROM borrows WHERE member_id = m.id AND status != "returned") as active_borrows
     FROM members m
     WHERE m.school_id = :sid
     ORDER BY m.name ASC'
);
$stmt->execute(['sid' => $sid]);
$members = $stmt->fetchAll();

// Get school info
$stmt = $pdo->prepare('SELECT * FROM schools WHERE id = :sid');
$stmt->execute(['sid' => $sid]);
$school = $stmt->fetch();
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Barcode Siswa - Perpustakaan Online</title>
    <script src="../assets/js/theme-loader.js"></script>
    <script src="../assets/js/theme.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../assets/css/animations.css">
    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="../assets/css/index.css">
    <style>
        /* Layout Structure */
        body {
            margin: 0;
            padding: 0;
            background: var(--bg);
        }

        .app {
            margin-left: 240px;
            display: grid;
            grid-template-columns: 1fr;
            grid-template-rows: auto 1fr;
            min-height: 100vh;
        }

        .topbar {
            padding: 20px 34px;
            font-size: 16px;
            font-weight: 600;
            border-bottom: 1px solid var(--border);
            grid-column: 1;
        }

        .topbar strong {
            margin-top: 3px;
            font-size: 16px;
            font-weight: 700;
        }

        .content {
            grid-column: 1;
            padding: 0;
            margin: 0;
            display: block;
        }

        .main {
            padding: 32px;
            max-width: 1600px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 28px;
        }

        /* Page Header */
        .page-header {
            margin: 0;
            padding-bottom: 8px;
        }

        .page-title {
            font-size: 32px;
            font-weight: 700;
            margin: 0 0 8px 0;
            color: var(--text);
            letter-spacing: -0.02em;
        }

        .page-subtitle {
            font-size: 15px;
            margin: 0;
            color: var(--text-muted);
            font-weight: 500;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin: 0;
        }

        .stat-card {
            background: var(--card);
            border: 1.5px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary);
            background: var(--card);
        }

        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .stat-icon {
            flex-shrink: 0;
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .stat-icon::before {
            content: '';
            position: absolute;
            inset: 0;
            background: inherit;
            opacity: 0.1;
            filter: blur(20px);
        }

        .stat-icon.blue {
            background: linear-gradient(135deg, #3B82F6, #2563EB);
        }

        .stat-icon.green {
            background: linear-gradient(135deg, #10B981, #059669);
        }

        .stat-icon.orange {
            background: linear-gradient(135deg, #F59E0B, #D97706);
        }

        .stat-content {
            flex: 1;
        }

        .stat-label {
            font-size: 13px;
            margin: 0 0 6px 0;
            color: var(--text-muted);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-value {
            font-size: 32px;
            margin: 0;
            line-height: 1;
            font-weight: 700;
            color: var(--text);
        }

        /* Toolbar */
        .toolbar {
            background: var(--card);
            border: 1.5px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            box-shadow: var(--shadow-sm);
        }

        .search-box {
            flex: 1;
            min-width: 280px;
            position: relative;
        }

        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            color: var(--text-muted);
            pointer-events: none;
        }

        .search-input {
            width: 100%;
            padding: 13px 16px 13px 48px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            background: var(--surface);
            color: var(--text);
            transition: all 0.2s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--surface);
            box-shadow: 0 0 0 3px rgba(58, 127, 242, 0.1);
        }

        .toolbar-actions {
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s ease;
            white-space: nowrap;
            font-family: inherit;
        }

        .btn-secondary {
            background: var(--muted-surface);
            color: var(--text);
            border: 1.5px solid var(--border);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .btn-secondary:hover {
            background: var(--border);
            color: var(--text);
            border-color: var(--border);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .btn-secondary:active {
            transform: translateY(0);
        }

        /* Students Grid */
        .students-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 24px;
            margin: 0;
        }

        .student-card {
            background: var(--card);
            border: 1.5px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow-sm);
        }

        .student-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }

        .student-card:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-md);
            transform: translateY(-6px);
        }

        .student-card:hover::before {
            transform: scaleX(1);
        }

        .student-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 6px;
            z-index: 1;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .badge-active {
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
        }

        .badge-inactive {
            background: linear-gradient(135deg, #EF4444, #DC2626);
            color: white;
        }

        .student-header {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1.5px solid var(--border);
        }

        .student-avatar {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--primary), var(--primary-2));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 24px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(58, 127, 242, 0.2);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .student-avatar::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.3), transparent);
        }

        .student-info-header {
            flex: 1;
            min-width: 0;
            padding-right: 80px;
        }

        .student-name {
            font-size: 17px;
            font-weight: 700;
            margin: 0 0 6px 0;
            line-height: 1.3;
            color: var(--text);
            word-break: break-word;
        }

        .student-nisn {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 500;
            margin: 0;
            font-family: 'Courier New', monospace;
        }

        .barcode-section {
            background: var(--muted-surface);
            border: 2px dashed var(--border);
            border-radius: 12px;
            padding: 20px;
            margin: 0 0 20px 0;
            text-align: center;
            transition: all 0.3s ease;
        }

        .student-card:hover .barcode-section {
            background: var(--muted-surface);
            border-color: var(--primary);
            box-shadow: 0 0 0 transparent;
        }

        .barcode-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 80px;
            margin-bottom: 12px;
        }

        .barcode-display {
            max-width: 100%;
            height: auto;
            max-height: 70px;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }

        .barcode-id {
            font-size: 12px;
            font-weight: 700;
            color: var(--text);
            font-family: 'Courier New', monospace;
            background: var(--surface);
            display: inline-block;
            padding: 6px 16px;
            border-radius: 6px;
            border: 1.5px solid var(--border);
        }

        .student-details {
            margin: 0 0 20px 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: var(--surface);
            border-radius: 8px;
            font-size: 13px;
            transition: all 0.2s ease;
            border: 1px solid var(--border);
        }

        .detail-row:hover {
            background: var(--muted-surface);
            border-color: var(--border);
        }

        .detail-label {
            font-weight: 500;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .detail-value {
            font-weight: 700;
            color: var(--text);
        }

        .detail-icon {
            font-size: 18px;
            color: var(--primary);
        }

        .student-actions {
            display: flex;
            gap: 10px;
            margin-top: auto;
            padding-top: 16px;
            border-top: 1.5px solid var(--border);
        }

        .btn-action {
            flex: 1;
            padding: 12px 16px;
            border: none;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.2s ease;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-print {
            background: linear-gradient(135deg, var(--primary), var(--primary-2));
            color: white;
            box-shadow: 0 2px 8px rgba(58, 127, 242, 0.2);
            border: none;
        }

        .btn-print:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-print:active {
            transform: translateY(0);
        }

        .btn-edit {
            background: var(--surface);
            color: var(--text);
            border: 1.5px solid var(--border);
        }

        .btn-edit:hover {
            border-color: var(--primary);
            background: var(--muted-surface);
            color: var(--primary);
            box-shadow: 0 0 0 transparent;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 24px;
            grid-column: 1 / -1;
            background: var(--muted-surface);
            border: 2px dashed var(--border);
            border-radius: 16px;
        }

        .empty-icon {
            font-size: 64px;
            opacity: 0.3;
            margin-bottom: 20px;
            color: var(--text-muted);
        }

        .empty-title {
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 8px 0;
            color: var(--text);
        }

        .empty-text {
            font-size: 15px;
            margin: 0;
            color: var(--text-muted);
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .students-grid {
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .app {
                margin-left: 0;
            }

            .main {
                padding: 20px;
                gap: 20px;
            }

            .page-title {
                font-size: 26px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .stat-card {
                padding: 20px;
            }

            .stat-icon {
                width: 48px;
                height: 48px;
                font-size: 24px;
            }

            .stat-value {
                font-size: 28px;
            }

            .toolbar {
                padding: 16px;
            }

            .search-box {
                width: 100%;
                min-width: unset;
            }

            .toolbar-actions {
                width: 100%;
            }

            .toolbar-actions .btn {
                flex: 1;
            }

            .students-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .student-badge {
                position: static;
                margin-bottom: 12px;
                width: fit-content;
            }

            .student-info-header {
                padding-right: 0;
            }

            .student-header {
                flex-wrap: wrap;
            }
        }

        @media (max-width: 480px) {
            .main {
                padding: 16px;
                gap: 16px;
            }

            .page-title {
                font-size: 22px;
            }

            .page-subtitle {
                font-size: 14px;
            }

            .stat-card {
                padding: 16px;
                gap: 16px;
            }

            .stat-icon {
                width: 44px;
                height: 44px;
                font-size: 22px;
            }

            .stat-value {
                font-size: 24px;
            }

            .student-card {
                padding: 20px;
            }

            .student-avatar {
                width: 48px;
                height: 48px;
                font-size: 20px;
            }

            .student-name {
                font-size: 15px;
            }

            .btn-action {
                font-size: 12px;
                padding: 10px 12px;
            }
        }

        /* Dark Mode Support */
        @media (prefers-color-scheme: dark) {
            .stat-card {
                background: var(--card) !important;
                border-color: var(--border) !important;
            }

            .student-card {
                background: var(--card) !important;
                border-color: var(--border) !important;
            }

            .toolbar {
                background: var(--card) !important;
                border-color: var(--border) !important;
            }

            .search-input {
                background: var(--surface) !important;
                color: var(--text) !important;
                border-color: var(--border) !important;
            }

            .detail-row {
                background: var(--surface) !important;
                border-color: var(--border) !important;
            }

            .barcode-section {
                background: var(--muted-surface) !important;
                border-color: var(--border) !important;
            }

            .barcode-id {
                background: var(--surface) !important;
                border-color: var(--border) !important;
                color: var(--text) !important;
            }
        }

        /* Print Styles */
        @media print {

            .toolbar,
            .student-actions,
            .student-badge {
                display: none !important;
            }

            .students-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }

            .student-card {
                page-break-inside: avoid;
                border: 2px solid #000;
                box-shadow: none;
                transform: none !important;
            }

            .barcode-section {
                border: 1px solid #000;
                background: white;
            }

            body {
                background: white;
            }
        }
    </style>
</head>

<body>
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <div class="app">

        <div class="topbar">
            <strong>Barcode Siswa</strong>
        </div>

        <div class="content">
            <div class="main">
                <!-- Page Header -->
                <div class="page-header">
                    <h1 class="page-title">Barcode Siswa</h1>
                </div>

                <!-- Stats Section -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <iconify-icon icon="solar:users-group-rounded-bold"></iconify-icon>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Total Siswa</div>
                            <div class="stat-value"><?= count($members) ?></div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon green">
                            <iconify-icon icon="solar:user-check-rounded-bold"></iconify-icon>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Siswa Aktif</div>
                            <div class="stat-value">
                                <?= count(array_filter($members, fn($m) => $m['status'] === 'active')) ?>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <iconify-icon icon="solar:book-bookmark-bold"></iconify-icon>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Peminjam Aktif</div>
                            <div class="stat-value">
                                <?= count(array_filter($members, fn($m) => $m['active_borrows'] > 0)) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Toolbar -->
                <div class="toolbar">
                    <div class="search-box">
                        <iconify-icon icon="solar:magnifer-bold" class="search-icon"></iconify-icon>
                        <input type="text" id="searchInput" class="search-input"
                            placeholder="Cari nama atau NISN siswa..." onkeyup="filterStudents()">
                    </div>
                    <div class="toolbar-actions">
                        <button onclick="window.print()" class="btn btn-secondary">
                            <iconify-icon icon="solar:printer-bold"></iconify-icon>
                            Cetak Semua
                        </button>
                    </div>
                </div>

                <!-- Students Grid -->
                <?php if (empty($members)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <iconify-icon icon="solar:users-group-rounded-broken"></iconify-icon>
                        </div>
                        <h3 class="empty-title">Tidak Ada Siswa</h3>
                        <p class="empty-text">Belum ada siswa yang terdaftar di sekolah ini</p>
                    </div>
                <?php else: ?>
                    <div class="students-grid" id="studentsGrid">
                        <?php foreach ($members as $member): ?>
                            <div class="student-card search-item"
                                data-search="<?= strtolower($member['name'] . ' ' . $member['nisn']) ?>">
                                <span
                                    class="student-badge <?= $member['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                                    <iconify-icon
                                        icon="<?= $member['status'] === 'active' ? 'solar:check-circle-bold' : 'solar:close-circle-bold' ?>"></iconify-icon>
                                    <?= $member['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?>
                                </span>

                                <div class="student-header">
                                    <div class="student-avatar">
                                        <?= strtoupper(mb_substr($member['name'], 0, 1)) ?>
                                    </div>
                                    <div class="student-info-header">
                                        <h3 class="student-name"><?= htmlspecialchars($member['name']) ?></h3>
                                        <div class="student-nisn">NISN: <?= htmlspecialchars($member['nisn'] ?? '-') ?></div>
                                    </div>
                                </div>

                                <div class="barcode-section">
                                    <div class="barcode-wrapper">
                                        <svg class="barcode-display barcode-render"
                                            jsbarcode-format="CODE128"
                                            jsbarcode-value="<?= htmlspecialchars($member['nisn'] ?? $member['id']) ?>"
                                            jsbarcode-displayValue="true"
                                            jsbarcode-fontSize="12"
                                            jsbarcode-width="1.5"
                                            jsbarcode-height="50"
                                            jsbarcode-margin="5"></svg>
                                    </div>
                                    <div class="barcode-id">NISN: <?= htmlspecialchars($member['nisn'] ?? '-') ?></div>
                                </div>

                                <div class="student-details">
                                    <div class="detail-row">
                                        <span class="detail-label">
                                            <iconify-icon icon="solar:card-bold" class="detail-icon"></iconify-icon>
                                            Member ID
                                        </span>
                                        <span class="detail-value"><?= str_pad($member['id'], 6, '0', STR_PAD_LEFT) ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">
                                            <iconify-icon icon="solar:book-2-bold" class="detail-icon"></iconify-icon>
                                            Peminjaman Aktif
                                        </span>
                                        <span class="detail-value"><?= $member['active_borrows'] ?> buku</span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">
                                            <iconify-icon icon="solar:calendar-mark-bold" class="detail-icon"></iconify-icon>
                                            Bergabung
                                        </span>
                                        <span class="detail-value"><?= date('d/m/Y', strtotime($member['created_at'])) ?></span>
                                    </div>
                                </div>

                                <div class="student-actions">
                                    <button
                                        onclick="printBarcode(<?= $member['id'] ?>, '<?= htmlspecialchars($member['name']) ?>')"
                                        class="btn-action btn-print">
                                        <iconify-icon icon="solar:printer-bold"></iconify-icon>
                                        Cetak
                                    </button>
                                    <a href="members.php?action=edit&id=<?= $member['id'] ?>" class="btn-action btn-edit">
                                        <iconify-icon icon="solar:pen-bold"></iconify-icon>
                                        Edit
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function filterStudents() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const items = document.querySelectorAll('.search-item');
            let visibleCount = 0;

            items.forEach(item => {
                const searchText = item.getAttribute('data-search');
                if (searchText.includes(input)) {
                    item.style.display = '';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function printBarcode(memberId, memberName) {
            const win = window.open(`api/generate-student-barcode.php?member_id=${memberId}`, '_blank');
            if (win) {
                win.addEventListener('load', function () {
                    setTimeout(() => {
                        win.print();
                    }, 250);
                });
            }
        }

        // Keyboard shortcuts
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('searchInput');

            // Clear search on Escape
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    this.value = '';
                    filterStudents();
                    this.blur();
                }
            });

            // Focus search on Ctrl/Cmd + K
            document.addEventListener('keydown', function (e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    searchInput.focus();
                    searchInput.select();
                }
            });
        });
    </script>

    <!-- JsBarcode CDN -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script>
        // Initialize all barcodes after page load
        document.addEventListener('DOMContentLoaded', function() {
            try {
                JsBarcode(".barcode-render").init();
            } catch (e) {
                console.error("Barcode rendering failed", e);
            }
        });
    </script>
</body>

</html>