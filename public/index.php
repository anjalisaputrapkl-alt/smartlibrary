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
            height: 100%
        }

        body {
            margin: 0;
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
            padding: 0 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .topbar strong {
            font-size: 15px;
        }

        /* Content */
        .content {
            padding: 32px;
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 32px;
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
        }

        /* Sidebar */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

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

        /* Menu */
        .menu {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-top: 12px;
        }

        .menu a {
            font-size: 13px;
            padding: 10px 12px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .menu a:hover {
            background: #f3f4f6;
        }

        .menu a.active {
            background: rgba(37, 99, 235, .1);
            color: var(--accent);
            font-weight: 500;
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
        <div class="app">

            <div class="topbar">
                <strong>Dashboard Perpustakaan</strong>
                <a href="logout.php" class="btn">Logout</a>
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

                    <div class="actions">
                        <h2 style="font-size:14px;margin-bottom:16px">Tindakan Cepat</h2>
                        <div class="action-grid">
                            <a href="books.php" class="action">📚 Kelola Buku</a>
                            <a href="members.php" class="action">👥 Kelola Anggota</a>
                            <a href="borrows.php" class="action">📖 Peminjaman</a>
                            <a href="reports.php" class="action">📈 Laporan</a>
                        </div>
                    </div>

                </div>

                <div class="sidebar">

                    <div class="panel">
                        <h3 style="font-size:14px">Ringkasan</h3>
                        <div class="list-item"><?= $total_borrowed ?> buku masih dipinjam</div>
                        <div class="list-item"><?= $total_overdue ?> buku terlambat</div>
                    </div>

                    <div class="panel">
                        <h3 style="font-size:14px">Menu</h3>
                        <div class="menu">
                            <a href="index.php" class="active">📊 Dashboard</a>
                            <a href="books.php">📚 Buku</a>
                            <a href="members.php">👥 Anggota</a>
                            <a href="borrows.php">📖 Peminjaman</a>
                            <a href="reports.php">📈 Laporan</a>
                            <a href="settings.php">⚙️ Pengaturan</a>
                        </div>
                    </div>

                    <div class="panel">
                        <h3 style="font-size:14px">FAQ</h3>

                        <div class="faq-item">
                            <div class="faq-question">
                                Bagaimana cara menambah buku?
                                <span>+</span>
                            </div>
                            <div class="faq-answer">
                                Masuk ke menu <b>Buku</b>, lalu klik tombol tambah untuk menambahkan data buku baru.
                            </div>
                        </div>

                        <div class="faq-item">
                            <div class="faq-question">
                                Bagaimana proses peminjaman buku?
                                <span>+</span>
                            </div>
                            <div class="faq-answer">
                                Peminjaman dilakukan melalui menu <b>Peminjaman</b> dengan memilih anggota dan buku yang
                                tersedia.
                            </div>
                        </div>

                        <div class="faq-item">
                            <div class="faq-question">
                                Apa arti status terlambat?
                                <span>+</span>
                            </div>
                            <div class="faq-answer">
                                Status terlambat muncul jika buku belum dikembalikan melewati tanggal jatuh tempo.
                            </div>
                        </div>

                    </div>



                </div>

            </div>
        </div>

        <script>
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