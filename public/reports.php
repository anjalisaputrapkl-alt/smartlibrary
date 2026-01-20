<?php
require_once __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/auth.php';
requireAuth();

$pdo = $pdo;

// Summary stats
$tot_books = (int) $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
$tot_borrows_month = (int) $pdo->query("SELECT COUNT(*) FROM borrows WHERE MONTH(borrowed_at) = MONTH(CURRENT_DATE()) AND YEAR(borrowed_at)=YEAR(CURRENT_DATE())")->fetchColumn();
$tot_returns_month = (int) $pdo->query("SELECT COUNT(*) FROM borrows WHERE returned_at IS NOT NULL AND MONTH(returned_at)=MONTH(CURRENT_DATE()) AND YEAR(returned_at)=YEAR(CURRENT_DATE())")->fetchColumn();
$active_members = (int) $pdo->query("SELECT COUNT(DISTINCT member_id) FROM borrows WHERE borrowed_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY)")->fetchColumn();

// Total fines
$per_day = 1000;
$fines = 0;
$rows = $pdo->query("SELECT due_at, returned_at FROM borrows WHERE due_at IS NOT NULL AND (returned_at IS NOT NULL OR CURRENT_DATE() > due_at)")->fetchAll();
foreach ($rows as $r) {
  $due = new DateTime($r['due_at']);
  $returned = $r['returned_at'] ? new DateTime($r['returned_at']) : new DateTime();
  $diff = (int) $due->diff($returned)->format('%r%a');
  if ($diff > 0)
    $fines += $diff * $per_day;
}

// Trend
$trendStmt = $pdo->prepare("SELECT DATE(borrowed_at) as d, COUNT(*) as c FROM borrows WHERE borrowed_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 29 DAY) GROUP BY DATE(borrowed_at) ORDER BY d ASC");
$trendStmt->execute();
$trend = $trendStmt->fetchAll();
$trend_labels = [];
$trend_data = [];
$start = new DateTime('-29 days');
$period = new DatePeriod($start, new DateInterval('P1D'), 30);
$map = [];
foreach ($trend as $t)
  $map[$t['d']] = (int) $t['c'];
foreach ($period as $day) {
  $k = $day->format('Y-m-d');
  $trend_labels[] = $k;
  $trend_data[] = $map[$k] ?? 0;
}

// Category
$category_labels = [];
$category_data = [];
$hasCategory = (bool) $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='books' AND COLUMN_NAME='category'")->fetchColumn();
if ($hasCategory) {
  $catStmt = $pdo->prepare("SELECT b.category, COUNT(*) as c FROM borrows br JOIN books b ON br.book_id = b.id GROUP BY b.category ORDER BY c DESC LIMIT 10");
  $catStmt->execute();
  foreach ($catStmt->fetchAll() as $r) {
    $category_labels[] = $r['category'] ?: 'Uncategorized';
    $category_data[] = (int) $r['c'];
  }
}

// Members
$memStmt = $pdo->prepare("SELECT DATE_FORMAT(created_at,'%Y-%m') month, COUNT(*) c FROM members WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 11 MONTH) GROUP BY month ORDER BY month ASC");
$memStmt->execute();
$mem = $memStmt->fetchAll();
$mem_labels = [];
$mem_data = [];
$start = new DateTime('first day of -11 months');
$period = new DatePeriod($start, new DateInterval('P1M'), 12);
$map = [];
foreach ($mem as $m)
  $map[$m['month']] = (int) $m['c'];
foreach ($period as $d) {
  $k = $d->format('Y-m');
  $mem_labels[] = $k;
  $mem_data[] = $map[$k] ?? 0;
}

// Heatmap
$hourStmt = $pdo->prepare("SELECT HOUR(borrowed_at) h, COUNT(*) c FROM borrows WHERE borrowed_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 29 DAY) GROUP BY h");
$hourStmt->execute();
$hours = array_fill(0, 24, 0);
foreach ($hourStmt->fetchAll() as $r)
  $hours[(int) $r['h']] = (int) $r['c'];

// Tables
$borrowTable = $pdo->query("SELECT br.id, br.borrowed_at, b.title as book_title, m.name as member_name, br.status, br.due_at, br.returned_at FROM borrows br JOIN books b ON br.book_id=b.id JOIN members m ON br.member_id=m.id ORDER BY br.borrowed_at DESC LIMIT 500")->fetchAll();
$returnsTable = $pdo->query("SELECT br.id, br.borrowed_at, br.returned_at, DATEDIFF(br.returned_at, br.due_at) as days_late, b.title as book_title, m.name as member_name FROM borrows br JOIN books b ON br.book_id=b.id JOIN members m ON br.member_id=m.id WHERE br.returned_at IS NOT NULL ORDER BY br.returned_at DESC LIMIT 500")->fetchAll();
$booksTable = $pdo->query("SELECT id, title, author, copies, created_at FROM books ORDER BY title LIMIT 1000")->fetchAll();

$new_members_30 = (int) $pdo->query("SELECT COUNT(*) FROM members WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)")->fetchColumn();
$new_books_30 = (int) $pdo->query("SELECT COUNT(*) FROM books WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Laporan - Perpustakaan Online</title>
  <script src="../assets/js/theme-loader.js"></script>
  <script src="../assets/js/theme.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
  <link rel="stylesheet" href="../assets/css/animations.css">
  <link rel="stylesheet" href="../assets/css/reports.css">
</head>

<body>
  <?php require __DIR__ . '/partials/sidebar.php'; ?>

  <div class="app">
    <div class="topbar">
      <strong><iconify-icon icon="mdi:chart-box-outline" style="vertical-align: middle; margin-right: 8px;"></iconify-icon>Laporan Perpustakaan</strong>
    </div>

    <div class="content">
      <!-- Filter Panel -->
      <div class="card">
        <h3>Filter Data</h3>
        <div class="filter-panel">
          <div class="form-group">
            <label for="filter-start">Tanggal Mulai</label>
            <input id="filter-start" type="date" />
          </div>
          <div class="form-group">
            <label for="filter-end">Tanggal Akhir</label>
            <input id="filter-end" type="date" />
          </div>
          <div class="form-group">
            <label for="filter-category">Kategori</label>
            <select id="filter-category">
              <option value="">Semua Kategori</option>
              <?php if ($hasCategory): ?>
                <?php foreach ($category_labels as $c): ?>
                  <option><?php echo htmlspecialchars($c); ?></option>
                <?php endforeach; ?>
              <?php else: ?>
                <option disabled>-- kategori tidak tersedia --</option>
              <?php endif; ?>
            </select>
          </div>
          <div>
            <button id="btn-apply" class="btn"><iconify-icon icon="mdi:filter" style="vertical-align: middle;"></iconify-icon> Filter</button>
            <button id="btn-export-excel" class="btn btn-secondary" style="margin-left: 8px;"><iconify-icon icon="mdi:file-excel" style="vertical-align: middle;"></iconify-icon> Export Excel</button>
          </div>
        </div>
      </div>

      <!-- KPI Cards -->
      <div class="kpi-grid">
        <div class="kpi-card">
          <div class="kpi-icon"><iconify-icon icon="mdi:library"></iconify-icon></div>
          <div>
            <div class="kpi-title">Total Buku</div>
            <div class="kpi-value"><?php echo number_format($tot_books); ?></div>
          </div>
        </div>

        <div class="kpi-card">
          <div class="kpi-icon"><iconify-icon icon="mdi:sync"></iconify-icon></div>
          <div>
            <div class="kpi-title">Peminjaman Bulan Ini</div>
            <div class="kpi-value"><?php echo number_format($tot_borrows_month); ?></div>
          </div>
        </div>

        <div class="kpi-card">
          <div class="kpi-icon"><iconify-icon icon="mdi:inbox"></iconify-icon></div>
          <div>
            <div class="kpi-title">Pengembalian Bulan Ini</div>
            <div class="kpi-value"><?php echo number_format($tot_returns_month); ?></div>
          </div>
        </div>

        <div class="kpi-card">
          <div class="kpi-icon"><iconify-icon icon="mdi:account-multiple"></iconify-icon></div>
          <div>
            <div class="kpi-title">Anggota Aktif (90 hari)</div>
            <div class="kpi-value"><?php echo number_format($active_members); ?></div>
          </div>
        </div>

        <div class="kpi-card">
          <div class="kpi-icon"><iconify-icon icon="mdi:cash-multiple"></iconify-icon></div>
          <div>
            <div class="kpi-title">Total Denda</div>
            <div class="kpi-value">Rp <?php echo number_format($fines); ?></div>
          </div>
        </div>
      </div>

      <!-- Charts -->
      <div class="chart-grid">
        <div class="chart-card">
          <h3>Tren Peminjaman (30 hari)</h3>
          <div class="chart-body">
            <canvas id="chart-trend"></canvas>
          </div>
        </div>

        <div class="chart-card">
          <h3>Kategori Paling Sering Dipinjam</h3>
          <div class="chart-body">
            <?php if ($hasCategory): ?>
              <canvas id="chart-category"></canvas>
            <?php else: ?>
              <div class="chart-empty">Kolom kategori tidak tersedia</div>
            <?php endif; ?>
          </div>
        </div>

        <div class="chart-card">
          <h3>Anggota Baru per Bulan</h3>
          <div class="chart-body">
            <canvas id="chart-members"></canvas>
          </div>
        </div>
      </div>

      <!-- Info Section -->
      <div class="info-section">
        <div class="card">
          <h4>Anggota Baru (30 hari)</h4>
          <div class="kpi-value"><?php echo number_format($new_members_30); ?></div>
        </div>
        <div class="card">
          <h4>Buku Baru (30 hari)</h4>
          <div class="kpi-value"><?php echo number_format($new_books_30); ?></div>
        </div>
      </div>

      <!-- Heatmap -->
      <div class="card">
        <h3>Heatmap Jam Peminjaman (30 hari terakhir)</h3>
        <div id="heatmap" class="heatmap-grid">
          <?php for ($h = 0; $h < 24; $h++): ?>
            <?php $v = $hours[$h];
            $intensity = min(1, $v / max(1, max($hours))); ?>
            <div class="heatmap-cell" style="background:rgba(14,165,233,<?php echo 0.12 + $intensity * 0.6; ?>);">
              <div style="font-size:12px; color:var(--muted);"><?php echo sprintf('%02d:00', $h); ?></div>
            </div>
          <?php endfor; ?>
        </div>
        <small style="display: block; margin-top: 12px;">Warna lebih gelap menunjukkan volume peminjaman lebih
          tinggi</small>
      </div>

      <!-- Tables -->
      <div class="card">
        <h3>Laporan Detail</h3>

        <h4 style="margin-top: 24px;">Laporan Peminjaman</h4>
        <table id="tbl-borrows" class="datatable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Tanggal</th>
              <th>Buku</th>
              <th>Anggota</th>
              <th>Status</th>
              <th>Due</th>
              <th>Kembali</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($borrowTable as $r): ?>
              <tr>
                <td><?php echo $r['id']; ?></td>
                <td><?php echo $r['borrowed_at']; ?></td>
                <td><?php echo htmlspecialchars($r['book_title']); ?></td>
                <td><?php echo htmlspecialchars($r['member_name']); ?></td>
                <td><?php echo $r['status']; ?></td>
                <td><?php echo $r['due_at']; ?></td>
                <td><?php echo $r['returned_at']; ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <h4 style="margin-top: 24px;">Laporan Pengembalian</h4>
        <table id="tbl-returns" class="datatable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Pinjam</th>
              <th>Kembali</th>
              <th>Terlambat (hari)</th>
              <th>Buku</th>
              <th>Anggota</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($returnsTable as $r): ?>
              <tr>
                <td><?php echo $r['id']; ?></td>
                <td><?php echo $r['borrowed_at']; ?></td>
                <td><?php echo $r['returned_at']; ?></td>
                <td><?php echo max(0, (int) $r['days_late']); ?></td>
                <td><?php echo htmlspecialchars($r['book_title']); ?></td>
                <td><?php echo htmlspecialchars($r['member_name']); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <h4 style="margin-top: 24px;">Laporan Buku</h4>
        <table id="tbl-books" class="datatable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Judul</th>
              <th>Penulis</th>
              <th>Stok</th>
              <th>Ditambahkan</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($booksTable as $r): ?>
              <tr>
                <td><?php echo $r['id']; ?></td>
                <td><?php echo htmlspecialchars($r['title']); ?></td>
                <td><?php echo htmlspecialchars($r['author']); ?></td>
                <td><?php echo (int) $r['copies']; ?></td>
                <td><?php echo $r['created_at']; ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/partials/footer.php'; ?>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

  <script>
    // Pass chart data to reports.js
    window.chartData = {
      trendLabels: <?php echo json_encode($trend_labels); ?>,
      trendData: <?php echo json_encode($trend_data); ?>,
      categoryLabels: <?php echo json_encode($category_labels); ?>,
      categoryData: <?php echo json_encode($category_data); ?>,
      memLabels: <?php echo json_encode($mem_labels); ?>,
      memData: <?php echo json_encode($mem_data); ?>
    };
  </script>
  <script src="../assets/js/reports.js"></script>
</body>

</html>