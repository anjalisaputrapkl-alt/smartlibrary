<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

$is_authenticated = !empty($_SESSION['user']);

if ($is_authenticated) {
    $pdo = require __DIR__ . '/../src/db.php';
    $user = $_SESSION['user'];
    $school_id = $user['school_id'];

    function countData($pdo, $sql, $sid)
    {
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['sid' => $sid]);
        return $stmt->fetchColumn();
    }

    $total_books = countData($pdo, "SELECT COUNT(*) FROM books WHERE school_id = :sid", $school_id);
    $total_members = countData($pdo, "SELECT COUNT(*) FROM members WHERE school_id = :sid", $school_id);
    $total_borrowed = countData($pdo, "SELECT COUNT(*) FROM borrows WHERE school_id = :sid AND returned_at IS NULL", $school_id);
    $total_overdue = countData($pdo, "SELECT COUNT(*) FROM borrows WHERE school_id = :sid AND status='overdue'", $school_id);

    // Recent borrows
    $stmt = $pdo->prepare("SELECT b.title, m.name, br.borrowed_at as timestamp, 'borrow' as type FROM borrows br 
        JOIN books b ON br.book_id = b.id 
        JOIN members m ON br.member_id = m.id 
        WHERE br.school_id = :sid AND br.returned_at IS NULL 
        ORDER BY br.borrowed_at DESC LIMIT 10");
    $stmt->execute(['sid' => $school_id]);
    $recent_borrows = $stmt->fetchAll();

    // Recent returns
    $stmt = $pdo->prepare("SELECT b.title, m.name, br.returned_at as timestamp, 'return' as type FROM borrows br 
        JOIN books b ON br.book_id = b.id 
        JOIN members m ON br.member_id = m.id 
        WHERE br.school_id = :sid AND br.returned_at IS NOT NULL 
        ORDER BY br.returned_at DESC LIMIT 10");
    $stmt->execute(['sid' => $school_id]);
    $recent_returns = $stmt->fetchAll();

    // New members
    $stmt = $pdo->prepare("SELECT name as title, '' as name, created_at as timestamp, 'member' as type FROM members 
        WHERE school_id = :sid 
        ORDER BY created_at DESC LIMIT 10");
    $stmt->execute(['sid' => $school_id]);
    $new_members = $stmt->fetchAll();

    // New books
    $stmt = $pdo->prepare("SELECT title, '' as name, created_at as timestamp, 'book' as type FROM books 
        WHERE school_id = :sid 
        ORDER BY created_at DESC LIMIT 10");
    $stmt->execute(['sid' => $school_id]);
    $new_books = $stmt->fetchAll();

    // Merge and sort all activities
    $all_activities = array_merge($recent_borrows, $recent_returns, $new_members, $new_books);
    usort($all_activities, function ($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });

    $monthly_borrows = [12, 18, 25, 20, 30, 28, 35, 40, 38, 32, 26, 22];
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Perpustakaan</title>
    <script src="../assets/js/theme.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --bg: #f1f4f8;
            --surface: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --border: #e5e7eb;
            --accent: #2563eb;
            --danger: #dc2626;
        }

        * {
            box-sizing: border-box
        }

        html,
        body {
            height: 100%;
            margin: 0;
        }

        body {
            font-family: Inter, system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        a {
            text-decoration: none;
            color: inherit
        }

        /* Layout */
        .app {
            min-height: 100vh;
            display: grid;
            grid-template-rows: 64px 1fr;
        }

        /* Topbar */
        .topbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 23px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: fixed;
            top: 0;
            left: 260px;
            right: 0;
            z-index: 999;
        }

        .topbar strong {
            font-size: 15px;
        }

        /* Content */
        .content {
            padding: 32px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 32px;
            margin-top: 64px;
        }

        /* Main */
        .main {
            display: flex;
            flex-direction: column;
            gap: 32px;
        }

        /* Stats */
        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .stat {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
        }

        .stat small {
            color: var(--muted)
        }

        .stat strong {
            display: block;
            font-size: 30px;
            margin-top: 6px;
        }

        .stat.alert strong {
            color: var(--danger)
        }

        /* Charts */
        .charts {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }

        .chart-box {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
        }

        .chart-box h2 {
            font-size: 14px;
            margin: 0 0 16px;
        }

        /* Actions */
        .actions {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .action {
            padding: 14px;
            border: 1px dashed var(--border);
            border-radius: 10px;
            font-size: 13px;
            transition: all 0.2s;
        }

        .action:hover {
            background: #f9fafb;
            border-color: var(--accent);
        }

        /* Activity Section */
        .activity-section {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
        }

        .activity-section h2 {
            font-size: 14px;
            margin: 0 0 16px;
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .activity-item {
            padding: 12px;
            background: #f9fafb;
            border-radius: 8px;
            border-left: 3px solid var(--accent);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
        }

        .activity-item .details {
            flex: 1;
        }

        .activity-item .book-title {
            font-weight: 500;
            color: var(--text);
        }

        .activity-item .member-name {
            color: var(--muted);
            font-size: 12px;
        }

        .activity-item .time {
            color: var(--muted);
            font-size: 12px;
            white-space: nowrap;
            margin-left: 12px;
        }

        .empty-activity {
            color: var(--muted);
            text-align: center;
            padding: 24px 16px;
            font-size: 13px;
        }

        /* Activity Tabs */
        .activity-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }

        .activity-tab {
            padding: 8px 12px;
            border: none;
            background: none;
            color: var(--muted);
            font-size: 13px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }

        .activity-tab:hover {
            color: var(--text);
        }

        .activity-tab.active {
            color: var(--accent);
            border-bottom-color: var(--accent);
        }

        .activity-content {
            display: none;
        }

        .activity-content.active {
            display: block;
        }

        /* Scrollable Activity List */
        .activity-scroll-container {
            max-height: 400px;
            overflow-y: auto;
            padding-right: 8px;
        }

        .activity-scroll-container::-webkit-scrollbar {
            width: 6px;
        }

        .activity-scroll-container::-webkit-scrollbar-track {
            background: transparent;
        }

        .activity-scroll-container::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 3px;
        }

        .activity-scroll-container::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }

        /* Panel */
        .panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
        }

        .list-item {
            font-size: 12px;
            padding: 10px;
            background: #f9fafb;
            border-radius: 8px;
            margin-top: 8px;
        }

        /* Button */
        .btn {
            padding: 6px 14px;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: white;
            font-size: 13px;
        }

        .faq-item {
            border-bottom: 1px solid var(--border);
            padding: 10px 0;
        }

        .faq-question {
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .faq-question span {
            color: var(--muted);
            font-size: 12px;
        }

        .faq-answer {
            font-size: 12px;
            color: var(--muted);
            margin-top: 8px;
            display: none;
            line-height: 1.6;
        }

        .faq-item.active .faq-answer {
            display: block;
        }
    </style>
</head>

<body>

    <?php if ($is_authenticated): ?>
        <?php require __DIR__ . '/partials/sidebar.php'; ?>

        <div class="app">

            <div class="topbar">
                <strong>Dashboard Perpustakaan</strong>
            </div>

            <div class="content">

                <div class="main">

                    <div class="stats">
                        <div class="stat"><small>Total Buku</small><strong><?= $total_books ?></strong></div>
                        <div class="stat"><small>Total Anggota</small><strong><?= $total_members ?></strong></div>
                        <div class="stat"><small>Dipinjam</small><strong><?= $total_borrowed ?></strong></div>
                        <div class="stat alert"><small>Terlambat</small><strong><?= $total_overdue ?></strong></div>
                    </div>

                    <div class="charts">
                        <div class="chart-box">
                            <h2>Peminjaman per Bulan</h2>
                            <canvas id="borrowChart"></canvas>
                        </div>
                        <div class="chart-box">
                            <h2>Status Buku</h2>
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>

                    <div class="activity-section">
                        <h2>📋 Aktivitas Terbaru</h2>

                        <div class="activity-tabs">
                            <button class="activity-tab active" data-tab="all">🔀 Semua</button>
                            <button class="activity-tab" data-tab="borrows">📖 Peminjaman</button>
                            <button class="activity-tab" data-tab="returns">📥 Pengembalian</button>
                            <button class="activity-tab" data-tab="members">👥 Anggota Baru</button>
                            <button class="activity-tab" data-tab="books">📚 Buku Baru</button>
                        </div>

                        <!-- All Activities Tab -->
                        <div class="activity-content active" id="all-content">
                            <div class="activity-scroll-container">
                                <div class="activity-list">
                                    <?php if (!empty($all_activities)): ?>
                                        <?php foreach ($all_activities as $activity): ?>
                                            <div class="activity-item">
                                                <div class="details">
                                                    <div class="book-title"><?= htmlspecialchars($activity['title']) ?></div>
                                                    <div class="member-name">
                                                        <?php
                                                        switch ($activity['type']) {
                                                            case 'borrow':
                                                                echo '📖 Dipinjam oleh ' . htmlspecialchars($activity['name']);
                                                                break;
                                                            case 'return':
                                                                echo '📥 Dikembalikan oleh ' . htmlspecialchars($activity['name']);
                                                                break;
                                                            case 'member':
                                                                echo '👥 Anggota baru terdaftar';
                                                                break;
                                                            case 'book':
                                                                echo '📚 Buku baru ditambahkan';
                                                                break;
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                                <div class="time"><?= date('d M', strtotime($activity['timestamp'])) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="empty-activity">Tidak ada aktivitas terbaru</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Borrows Tab -->
                        <div class="activity-content" id="borrows-content">
                            <div class="activity-scroll-container">
                                <div class="activity-list">
                                    <?php if (!empty($recent_borrows)): ?>
                                        <?php foreach ($recent_borrows as $activity): ?>
                                            <div class="activity-item">
                                                <div class="details">
                                                    <div class="book-title"><?= htmlspecialchars($activity['title']) ?></div>
                                                    <div class="member-name">Dipinjam oleh
                                                        <?= htmlspecialchars($activity['name']) ?></div>
                                                </div>
                                                <div class="time"><?= date('d M', strtotime($activity['timestamp'])) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="empty-activity">Tidak ada peminjaman terbaru</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Returns Tab -->
                        <div class="activity-content" id="returns-content">
                            <div class="activity-scroll-container">
                                <div class="activity-list">
                                    <?php if (!empty($recent_returns)): ?>
                                        <?php foreach ($recent_returns as $activity): ?>
                                            <div class="activity-item">
                                                <div class="details">
                                                    <div class="book-title"><?= htmlspecialchars($activity['title']) ?></div>
                                                    <div class="member-name">Dikembalikan oleh
                                                        <?= htmlspecialchars($activity['name']) ?></div>
                                                </div>
                                                <div class="time"><?= date('d M', strtotime($activity['timestamp'])) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="empty-activity">Tidak ada pengembalian terbaru</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Members Tab -->
                        <div class="activity-content" id="members-content">
                            <div class="activity-scroll-container">
                                <div class="activity-list">
                                    <?php if (!empty($new_members)): ?>
                                        <?php foreach ($new_members as $activity): ?>
                                            <div class="activity-item">
                                                <div class="details">
                                                    <div class="book-title"><?= htmlspecialchars($activity['title']) ?></div>
                                                    <div class="member-name">Anggota baru terdaftar</div>
                                                </div>
                                                <div class="time"><?= date('d M', strtotime($activity['timestamp'])) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="empty-activity">Tidak ada anggota baru</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Books Tab -->
                        <div class="activity-content" id="books-content">
                            <div class="activity-scroll-container">
                                <div class="activity-list">
                                    <?php if (!empty($new_books)): ?>
                                        <?php foreach ($new_books as $activity): ?>
                                            <div class="activity-item">
                                                <div class="details">
                                                    <div class="book-title"><?= htmlspecialchars($activity['title']) ?></div>
                                                    <div class="member-name">Buku baru ditambahkan</div>
                                                </div>
                                                <div class="time"><?= date('d M', strtotime($activity['timestamp'])) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="empty-activity">Tidak ada buku baru</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <script>
            // Activity tabs functionality
            document.querySelectorAll('.activity-tab').forEach(tab => {
                tab.addEventListener('click', () => {
                    const tabName = tab.getAttribute('data-tab');

                    // Remove active class from all tabs and contents
                    document.querySelectorAll('.activity-tab').forEach(t => t.classList.remove('active'));
                    document.querySelectorAll('.activity-content').forEach(c => c.classList.remove('active'));

                    // Add active class to clicked tab and its content
                    tab.classList.add('active');
                    document.getElementById(tabName + '-content').classList.add('active');
                });
            });

            document.querySelectorAll('.faq-question').forEach(item => {
                item.addEventListener('click', () => {
                    const parent = item.parentElement;
                    parent.classList.toggle('active');
                    item.querySelector('span').textContent =
                        parent.classList.contains('active') ? '−' : '+';
                });
            });
            new Chart(document.getElementById('borrowChart'), {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                    datasets: [{
                        data: <?= json_encode($monthly_borrows) ?>,
                        borderColor: '#2563eb',
                        tension: .3
                    }]
                },
                options: { plugins: { legend: { display: false } } }
            });

            new Chart(document.getElementById('statusChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Tersedia', 'Dipinjam', 'Terlambat'],
                    datasets: [{
                        data: [
                            <?= $total_books - $total_borrowed ?>,
                            <?= $total_borrowed ?>,
                            <?= $total_overdue ?>
                        ],
                        backgroundColor: ['#16a34a', '#2563eb', '#dc2626']
                    }]
                },
                options: { plugins: { legend: { position: 'bottom' } } }
            });
        </script>
    <?php endif; ?>

</body>

</html>