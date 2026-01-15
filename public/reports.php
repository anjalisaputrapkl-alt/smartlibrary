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
  <script src="../assets/js/theme.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

  <style>
    :root {
      --bg: #f1f4f8;
      --surface: #ffffff;
      --text: #1f2937;
      --muted: #6b7280;
      --border: #e5e7eb;
      --accent: #2563eb;
      --danger: #dc2626;
      --success: #16a34a;
    }

    * {
      box-sizing: border-box;
    }

    html,
    body {
      margin: 0;
    }

    body {
      font-family: Inter, system-ui, sans-serif;
      background: var(--bg);
      color: var(--text);
    }

    a {
      text-decoration: none;
      color: inherit;
    }

    /* Layout */
    .app {
      min-height: 100vh;
      display: grid;
      grid-template-rows: 64px 1fr;
      margin-left: 260px;
    }

    /* Topbar */
    .topbar {
      background: var(--surface);
      border-bottom: 1px solid var(--border);
      padding: 22px 32px;
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

    /* Card */
    .card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 24px;
    }

    .card h2 {
      font-size: 16px;
      margin: 0 0 20px;
      font-weight: 600;
    }

    .card h3 {
      font-size: 14px;
      margin: 20px 0 12px;
      font-weight: 600;
    }

    .card h4 {
      font-size: 13px;
      margin: 16px 0 12px;
      font-weight: 600;
    }

    /* Form */
    .form-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
      margin-bottom: 16px;
    }

    label {
      font-size: 12px;
      color: var(--muted);
      font-weight: 500;
    }

    input,
    select {
      padding: 10px 12px;
      border: 1px solid var(--border);
      border-radius: 8px;
      font-size: 13px;
      font-family: inherit;
    }

    small {
      font-size: 12px;
      color: var(--muted);
    }

    /* Button */
    .btn {
      padding: 8px 14px;
      border: 1px solid var(--border);
      border-radius: 6px;
      background: white;
      font-size: 13px;
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn:hover {
      background: #f9fafb;
    }

    .btn.primary {
      background: var(--accent);
      color: white;
      border: none;
    }

    .btn.primary:hover {
      opacity: 0.9;
    }

    /* Filter Panel */
    .filter-panel {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 16px;
      align-items: flex-end;
    }

    .filter-panel .form-group {
      margin-bottom: 0;
    }

    /* KPI Grid */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 16px;
      margin-bottom: 24px;
    }

    .kpi-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 20px;
      display: flex;
      gap: 16px;
      align-items: center;
    }

    .kpi-icon {
      font-size: 32px;
    }

    .kpi-title {
      font-size: 12px;
      color: var(--muted);
      margin-bottom: 4px;
    }

    .kpi-value {
      font-size: 24px;
      font-weight: 600;
      color: var(--text);
    }

    /* Chart Grid */
    .chart-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 16px;
      margin-bottom: 24px;
    }

    .chart-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 20px;
    }

    .chart-body {
      position: relative;
      height: 300px;
    }

    .chart-empty {
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100%;
      color: var(--muted);
      font-size: 14px;
      text-align: center;
      padding: 20px;
    }

    /* Heatmap */
    .heatmap-grid {
      display: grid;
      grid-template-columns: repeat(6, 1fr);
      gap: 8px;
      margin-bottom: 12px;
    }

    .heatmap-cell {
      padding: 12px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 500;
      text-align: center;
    }

    /* DataTables */
    .datatable {
      width: 100% !important;
      border-collapse: collapse;
      margin-top: 12px;
    }

    .datatable thead {
      background: #f9fafb;
      border-bottom: 1px solid var(--border);
    }

    .datatable th {
      padding: 12px;
      text-align: left;
      font-size: 12px;
      font-weight: 600;
      color: var(--muted);
    }

    .datatable td {
      padding: 12px;
      border-bottom: 1px solid var(--border);
      font-size: 13px;
    }

    .datatable tbody tr:hover {
      background: #f9fafb;
    }

    .datatable td,
    .datatable th {
      white-space: normal !important;
      word-wrap: break-word !important;
      overflow-wrap: break-word !important;
    }

    /* Info Section */
    .info-section {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
      margin-bottom: 24px;
    }

    .info-section .card {
      text-align: center;
    }

    @media (max-width: 1024px) {
      .app {
        margin-left: 0;
      }

      .topbar {
        left: 0;
      }

      .filter-panel {
        grid-template-columns: 1fr;
      }

      .chart-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body>
  <?php require __DIR__ . '/partials/sidebar.php'; ?>

  <div class="app">
    <div class="topbar">
      <strong>📊 Laporan Perpustakaan</strong>
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
            <button id="btn-apply" class="btn primary">Filter</button>
            <button id="btn-export-excel" class="btn" style="margin-left: 8px;">Export Excel</button>
          </div>
        </div>
      </div>

      <!-- KPI Cards -->
      <div class="kpi-grid">
        <div class="kpi-card">
          <div class="kpi-icon">📚</div>
          <div>
            <div class="kpi-title">Total Buku</div>
            <div class="kpi-value"><?php echo number_format($tot_books); ?></div>
          </div>
        </div>

        <div class="kpi-card">
          <div class="kpi-icon">🔄</div>
          <div>
            <div class="kpi-title">Peminjaman Bulan Ini</div>
            <div class="kpi-value"><?php echo number_format($tot_borrows_month); ?></div>
          </div>
        </div>

        <div class="kpi-card">
          <div class="kpi-icon">📥</div>
          <div>
            <div class="kpi-title">Pengembalian Bulan Ini</div>
            <div class="kpi-value"><?php echo number_format($tot_returns_month); ?></div>
          </div>
        </div>

        <div class="kpi-card">
          <div class="kpi-icon">👥</div>
          <div>
            <div class="kpi-title">Anggota Aktif (90 hari)</div>
            <div class="kpi-value"><?php echo number_format($active_members); ?></div>
          </div>
        </div>

        <div class="kpi-card">
          <div class="kpi-icon">💰</div>
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
    console.log('🟢 Dashboard script starting...');
    window.dashboardReady = false;

    function waitForDependencies(callback) {
      if (typeof jQuery !== 'undefined' && typeof Chart !== 'undefined') {
        callback();
      } else {
        setTimeout(() => waitForDependencies(callback), 50);
      }
    }

    waitForDependencies(function () {
      console.log('🟢 Dependencies ready, initializing...');
      initDashboard();
    });

    function initDashboard() {
      console.log('🟢 initDashboard() started');

      const trendLabels = <?php echo json_encode($trend_labels); ?>;
      const trendData = <?php echo json_encode($trend_data); ?>;
      const categoryLabels = <?php echo json_encode($category_labels); ?>;
      const categoryData = <?php echo json_encode($category_data); ?>;
      const memLabels = <?php echo json_encode($mem_labels); ?>;
      const memData = <?php echo json_encode($mem_data); ?>;

      // Init charts
      console.log('Rendering trend chart...');
      const ctxTrend = document.getElementById('chart-trend');
      const trendChart = new Chart(ctxTrend.getContext('2d'), {
        type: 'line',
        data: { labels: trendLabels, datasets: [{ label: 'Peminjaman', data: trendData, borderColor: '#0ea5e9', backgroundColor: 'rgba(14,165,233,0.12)', tension: 0.25, fill: true }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
      });
      window.trendChartGlobal = trendChart;

      if (categoryLabels.length > 0) {
        console.log('Rendering category chart...');
        const ctxCat = document.getElementById('chart-category');
        if (ctxCat) {
          const catChart = new Chart(ctxCat.getContext('2d'), {
            type: 'pie',
            data: { labels: categoryLabels, datasets: [{ data: categoryData, backgroundColor: ['#60a5fa', '#34d399', '#f59e0b', '#f97316', '#a78bfa', '#fb7185', '#94a3b8'] }] },
            options: { responsive: true, maintainAspectRatio: false }
          });
          window.globalCategoryChart = catChart;
        }
      }

      console.log('Rendering members chart...');
      const ctxMem = document.getElementById('chart-members');
      const memChart = new Chart(ctxMem.getContext('2d'), {
        type: 'bar',
        data: { labels: memLabels, datasets: [{ label: 'Anggota Baru', data: memData, backgroundColor: '#8b5cf6' }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
      });
      window.memChartGlobal = memChart;

      // Init DataTables
      console.log('Initializing DataTables...');
      const tables = jQuery('.datatable');

      if (tables.length >= 1) {
        window.borrowsDataTable = jQuery(tables[0]).DataTable({
          pageLength: 10,
          order: [[1, 'desc']],
          responsive: true
        });
      }
      if (tables.length >= 2) {
        window.returnsDataTable = jQuery(tables[1]).DataTable({
          pageLength: 10,
          order: [[1, 'desc']],
          responsive: true
        });
      }
      if (tables.length >= 3) {
        window.booksDataTable = jQuery(tables[2]).DataTable({
          pageLength: 10,
          order: [[1, 'asc']],
          responsive: true
        });
      }

      console.log('🟢 Charts and tables ready');

      // Helper functions
      window.showToast = function (msg) {
        const t = document.createElement('div');
        t.style.cssText = 'position:fixed;right:18px;bottom:18px;background:#333;color:#fff;padding:12px 16px;border-radius:8px;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.15);';
        t.innerText = msg;
        document.body.appendChild(t);
        setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity 0.3s'; setTimeout(() => t.remove(), 300); }, 2500);
      };

      window.numberFormat = function (num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
      };

      window.escapeHtml = function (text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
      };

      // Export function
      window.exportToExcel = function () {
        if (typeof XLSX === 'undefined') {
          showToast('XLSX library belum loaded');
          return;
        }

        const wb = XLSX.utils.book_new();

        const tables = [
          { id: 'tbl-borrows', name: 'Borrows' },
          { id: 'tbl-returns', name: 'Returns' },
          { id: 'tbl-books', name: 'Books' }
        ];

        tables.forEach(config => {
          const el = document.getElementById(config.id);
          if (!el) return;

          const headers = [];
          el.querySelectorAll('thead th').forEach(th => {
            headers.push(th.innerText.trim());
          });

          const rows = [];
          el.querySelectorAll('tbody tr').forEach(tr => {
            const row = [];
            tr.querySelectorAll('td').forEach(td => {
              row.push(td.innerText.trim());
            });
            if (row.length > 0) {
              rows.push(row);
            }
          });

          const jsonData = rows.map(row => {
            const obj = {};
            headers.forEach((h, i) => {
              obj[h] = row[i] || '';
            });
            return obj;
          });

          const ws = XLSX.utils.json_to_sheet(jsonData, { header: headers });
          const wscols = headers.map((h, i) => {
            if (i === 1 || i === 5 || i === 6) {
              return { wch: 35 };
            }
            return { wch: 20 };
          });
          ws['!cols'] = wscols;

          XLSX.utils.book_append_sheet(wb, ws, config.name);
        });

        const fname = 'perpustakaan-report-' + new Date().toISOString().slice(0, 10) + '.xlsx';
        try {
          XLSX.writeFile(wb, fname);
          showToast('✅ Export berhasil: ' + fname);
        } catch (err) {
          console.error('Export error:', err);
          showToast('❌ Export gagal: ' + err.message);
        }
      };

      // Filter handler
      console.log('🟢 Setting up filter button...');

      window.applyFilter = function () {
        console.log('🔵 applyFilter() called');
        const startDate = document.getElementById('filter-start').value;
        const endDate = document.getElementById('filter-end').value;
        const category = document.getElementById('filter-category').value;

        console.log('Filter params:', { startDate, endDate, category });

        if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
          showToast('Tanggal awal harus lebih kecil dari tanggal akhir');
          return;
        }

        showToast('Memproses filter...');

        const params = new URLSearchParams();
        if (startDate) params.append('start_date', startDate);
        if (endDate) params.append('end_date', endDate);
        if (category) params.append('category', category);

        const url = './reports-filter.php?' + params.toString();
        console.log('Fetching:', url);

        fetch(url)
          .then(r => r.json())
          .then(data => {
            console.log('Response:', data);
            if (data.error) {
              showToast('Error: ' + data.error);
              return;
            }

            // Update charts
            if (window.trendChartGlobal) {
              window.trendChartGlobal.data.labels = data.trend.labels;
              window.trendChartGlobal.data.datasets[0].data = data.trend.data;
              window.trendChartGlobal.update();
            }

            if (window.globalCategoryChart && data.category.labels.length > 0) {
              window.globalCategoryChart.data.labels = data.category.labels;
              window.globalCategoryChart.data.datasets[0].data = data.category.data;
              window.globalCategoryChart.update();
            }

            if (window.memChartGlobal) {
              window.memChartGlobal.data.labels = data.members.labels;
              window.memChartGlobal.data.datasets[0].data = data.members.data;
              window.memChartGlobal.update();
            }

            // Update KPI
            const kpiCards = document.querySelectorAll('.kpi-card');
            if (kpiCards.length >= 5) {
              kpiCards[0].querySelector('.kpi-value').innerText = window.numberFormat(data.stats.tot_books);
              kpiCards[1].querySelector('.kpi-value').innerText = window.numberFormat(data.stats.borrows_month);
              kpiCards[2].querySelector('.kpi-value').innerText = window.numberFormat(data.stats.returns_month);
              kpiCards[3].querySelector('.kpi-value').innerText = window.numberFormat(data.stats.active_members);
              kpiCards[4].querySelector('.kpi-value').innerText = 'Rp ' + window.numberFormat(data.stats.fines);
            }

            showToast('✅ Filter berhasil diterapkan');
          })
          .catch(err => {
            console.error('Fetch error:', err);
            showToast('❌ Error: ' + err.message);
          });
      };

      // Attach button listeners
      const btnFilter = document.getElementById('btn-apply');
      if (btnFilter) {
        console.log('✅ btn-apply found');
        btnFilter.addEventListener('click', (e) => {
          e.preventDefault();
          window.applyFilter();
        });
      } else {
        console.error('❌ btn-apply NOT FOUND');
      }

      const btnExport = document.getElementById('btn-export-excel');
      if (btnExport) {
        btnExport.addEventListener('click', (e) => {
          e.preventDefault();
          window.exportToExcel();
        });
      }

      window.dashboardReady = true;
      console.log('✅ Dashboard ready!');
    }
  </script>
</body>

</html>