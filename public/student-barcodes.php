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
    <link rel="stylesheet" href="../assets/css/sidebar.css">
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
            --success: #10B981;
            --warning: #f59e0b;
            --danger: #EF4444;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --primary: #3A7FF2;
                --primary-2: #7AB8F5;
                --primary-dark: #0A1A4F;
                --bg: #0f172a;
                --muted: #1e293b;
                --card: #1e293b;
                --surface: #1e293b;
                --muted-surface: #334155;
                --border: #334155;
                --text: #f1f5f9;
                --text-muted: #94a3b8;
                --accent: #3A7FF2;
                --success: #10B981;
                --warning: #f59e0b;
                --danger: #EF4444;
            }
        }

        .content {
            grid-template-columns: 1fr;
        }

        .main {
            grid-template-columns: 1fr;
        }

        .main>div {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .search-box {
            flex: 1;
            max-width: 400px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 12px 16px 12px 40px;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            background: var(--surface);
            color: var(--text);
            transition: all 0.2s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(58, 127, 242, 0.1);
        }

        .search-box iconify-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .students-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .student-card-item {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .student-card-item:hover {
            border-color: var(--primary);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .student-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }

        .student-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-2));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
            flex-shrink: 0;
        }

        .student-name {
            flex: 1;
        }

        .student-name-text {
            font-weight: 600;
            font-size: 14px;
            color: var(--text);
            margin-bottom: 4px;
        }

        .student-nisn {
            font-size: 12px;
            color: var(--text-muted);
        }

        .student-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .barcode-display {
            background: white;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            margin: 16px 0;
        }

        .barcode-display svg,
        .barcode-display img {
            max-width: 100%;
            height: auto;
            max-height: 80px;
            display: block;
            margin: 0 auto;
        }

        .student-info {
            font-size: 12px;
            color: var(--text-muted);
            margin: 12px 0;
            line-height: 1.6;
        }

        .student-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
        }

        .student-info-label {
            font-weight: 600;
        }

        .student-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        .btn-small {
            flex: 1;
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        .btn-primary-small {
            background: var(--primary);
            color: white;
        }

        .btn-primary-small:hover {
            background: var(--primary-dark);
        }

        .btn-secondary-small {
            background: var(--border);
            color: var(--text);
        }

        .btn-secondary-small:hover {
            background: var(--muted);
        }

        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: var(--text-muted);
        }

        .empty-state iconify-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-state p {
            margin: 0;
            font-size: 14px;
        }

        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }

        .stat-label {
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary);
        }

        @media (max-width: 768px) {
            .header-section {
                flex-direction: column;
                gap: 16px;
            }

            .search-box {
                max-width: 100%;
            }

            .students-grid {
                grid-template-columns: 1fr;
            }
        }

        @media print {
            .search-box,
            .header-section,
            .student-actions,
            .barcode-display {
                display: none;
            }

            .students-grid {
                grid-template-columns: 1fr;
            }

            .student-card-item {
                page-break-inside: avoid;
                border: none;
                box-shadow: none;
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
                <div>
                    <!-- Stats Section -->
                    <div class="stats-section">
                        <div class="stat-card">
                            <div class="stat-label">Total Siswa</div>
                            <div class="stat-value"><?= count($members) ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Siswa Aktif</div>
                            <div class="stat-value"><?= count(array_filter($members, fn($m) => $m['status'] === 'active')) ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Peminjam Aktif</div>
                            <div class="stat-value"><?= count(array_filter($members, fn($m) => $m['active_borrows'] > 0)) ?></div>
                        </div>
                    </div>

                    <!-- Header -->
                    <div class="header-section">
                        <h2 style="font-size: 20px; margin: 0;">Daftar Barcode Siswa <?= htmlspecialchars($school['name'] ?? '') ?></h2>
                        <div class="search-box">
                            <iconify-icon icon="mdi:magnify"></iconify-icon>
                            <input type="text" id="searchInput" placeholder="Cari nama atau NISN siswa..." onkeyup="filterStudents()">
                        </div>
                    </div>

                    <!-- Students Grid -->
                    <?php if (empty($members)): ?>
                        <div class="empty-state">
                            <iconify-icon icon="mdi:account-group"></iconify-icon>
                            <p>Tidak ada siswa di sekolah ini</p>
                        </div>
                    <?php else: ?>
                        <div class="students-grid" id="studentsGrid">
                            <?php foreach ($members as $member): ?>
                                <div class="student-card-item search-item" data-search="<?= strtolower($member['name'] . ' ' . $member['nisn']) ?>">
                                    <div class="student-header">
                                        <div class="student-avatar">
                                            <?= strtoupper(substr($member['name'], 0, 1)) ?>
                                        </div>
                                        <div class="student-name">
                                            <div class="student-name-text"><?= htmlspecialchars($member['name']) ?></div>
                                            <div class="student-nisn">NISN: <?= htmlspecialchars($member['nisn'] ?? '-') ?></div>
                                        </div>
                                        <span class="student-status <?= $member['status'] === 'active' ? 'status-active' : 'status-inactive' ?>">
                                            <?= $member['status'] === 'active' ? '✓ Aktif' : '✗ Nonaktif' ?>
                                        </span>
                                    </div>

                                    <div class="barcode-display">
                                        <object data="api/generate-student-barcode.php?member_id=<?= $member['id'] ?>" type="image/svg+xml" style="width: 100%; max-height: 70px;"></object>
                                    </div>

                                    <div class="student-info">
                                        <div class="student-info-row">
                                            <span class="student-info-label">Member ID:</span>
                                            <span><?= str_pad($member['id'], 6, '0', STR_PAD_LEFT) ?></span>
                                        </div>
                                        <div class="student-info-row">
                                            <span class="student-info-label">Peminjaman Aktif:</span>
                                            <span><?= $member['active_borrows'] ?> buku</span>
                                        </div>
                                        <div class="student-info-row">
                                            <span class="student-info-label">Bergabung:</span>
                                            <span><?= date('d/m/Y', strtotime($member['created_at'])) ?></span>
                                        </div>
                                    </div>

                                    <div class="student-actions">
                                        <button onclick="printBarcode(<?= $member['id'] ?>, '<?= htmlspecialchars($member['name']) ?>')" class="btn-small btn-primary-small">
                                            <iconify-icon icon="mdi:printer"></iconify-icon>
                                            Cetak
                                        </button>
                                        <a href="members.php?action=edit&id=<?= $member['id'] ?>" class="btn-small btn-secondary-small">
                                            <iconify-icon icon="mdi:pencil"></iconify-icon>
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
    </div>

    <script>
        function filterStudents() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const items = document.querySelectorAll('.search-item');

            items.forEach(item => {
                const searchText = item.getAttribute('data-search');
                if (searchText.includes(input)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function printBarcode(memberId, memberName) {
            const win = window.open(`api/generate-student-barcode.php?member_id=${memberId}`, '_blank');
            win.addEventListener('load', function() {
                setTimeout(() => {
                    win.print();
                }, 250);
            });
        }
    </script>
</body>

</html>
