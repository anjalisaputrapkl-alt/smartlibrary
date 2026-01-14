<?php
require_once __DIR__ . '/../src/db.php';
include __DIR__ . '/partials/header.php';
// Prepare data for dashboard
$pdo = $pdo; // from src/db.php

// Summary stats
$tot_books = (int) $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
$tot_borrows_month = (int) $pdo->query("SELECT COUNT(*) FROM borrows WHERE MONTH(borrowed_at) = MONTH(CURRENT_DATE()) AND YEAR(borrowed_at)=YEAR(CURRENT_DATE())")->fetchColumn();
$tot_returns_month = (int) $pdo->query("SELECT COUNT(*) FROM borrows WHERE returned_at IS NOT NULL AND MONTH(returned_at)=MONTH(CURRENT_DATE()) AND YEAR(returned_at)=YEAR(CURRENT_DATE())")->fetchColumn();
$active_members = (int) $pdo->query("SELECT COUNT(DISTINCT member_id) FROM borrows WHERE borrowed_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY)")->fetchColumn();

// Total fines estimate (if due_at/returned_at available) - assume 1000 (Rp) per day late
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

// Borrow trend (last 30 days)
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

// Category most borrowed - fallback if `category` column exists on books
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

// Members per month (last 12 months)
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

// Heatmap: borrows per hour (0-23) last 30 days
$hourStmt = $pdo->prepare("SELECT HOUR(borrowed_at) h, COUNT(*) c FROM borrows WHERE borrowed_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 29 DAY) GROUP BY h");
$hourStmt->execute();
$hours = array_fill(0, 24, 0);
foreach ($hourStmt->fetchAll() as $r)
  $hours[(int) $r['h']] = (int) $r['c'];

// Table data (limited)
$borrowTable = $pdo->query("SELECT br.id, br.borrowed_at, b.title as book_title, m.name as member_name, br.status, br.due_at, br.returned_at FROM borrows br JOIN books b ON br.book_id=b.id JOIN members m ON br.member_id=m.id ORDER BY br.borrowed_at DESC LIMIT 500")->fetchAll();
$returnsTable = $pdo->query("SELECT br.id, br.borrowed_at, br.returned_at, DATEDIFF(br.returned_at, br.due_at) as days_late, b.title as book_title, m.name as member_name FROM borrows br JOIN books b ON br.book_id=b.id JOIN members m ON br.member_id=m.id WHERE br.returned_at IS NOT NULL ORDER BY br.returned_at DESC LIMIT 500")->fetchAll();
$booksTable = $pdo->query("SELECT id, title, author, copies, created_at FROM books ORDER BY title LIMIT 1000")->fetchAll();

// Additional small stats
$new_members_30 = (int) $pdo->query("SELECT COUNT(*) FROM members WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)")->fetchColumn();
$new_books_30 = (int) $pdo->query("SELECT COUNT(*) FROM books WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)")->fetchColumn();

?>
<script src="../assets/js/theme.js"></script>
<link rel="stylesheet" href="../assets/css/theme.css">
<link rel="stylesheet" href="../assets/css/styles.css">
<main class="container dashboard">
  <header class="page-header">
    <div class="inner">
      <div class="title">
        <div class="icon">📊</div>
        <div>
          <h1>Laporan SmartLibrary</h1>
          <p>Dashboard analisis data perpustakaan</p>
        </div>
      </div>
      <div class="actions">
        <a class="btn btn-small" href="/perpustakaan-online/public/index.php">← Dashboard</a>
      </div>
    </div>

    <div class="filter-panel card">
      <div>
        <label for="filter-start">Date Start</label>
        <input id="filter-start" type="date" />
      </div>
      <div>
        <label for="filter-end">Date End</label>
        <input id="filter-end" type="date" />
      </div>
      <div>
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
      <div style="display:flex; gap:8px; align-items:center;">
        <button id="btn-apply" class="btn btn-primary">Filter</button>
        <button id="btn-export-excel" class="btn btn-secondary">Export Excel</button>
      </div>
    </div>
  </header>

  <section class="kpi-section">
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-icon">📚</div>
        <div class="kpi-body">
          <div class="kpi-title">Total Buku</div>
          <div class="kpi-value"><?php echo number_format($tot_books); ?></div>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon">🔄</div>
        <div class="kpi-body">
          <div class="kpi-title">Peminjaman Bulan Ini</div>
          <div class="kpi-value"><?php echo number_format($tot_borrows_month); ?></div>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon">📥</div>
        <div class="kpi-body">
          <div class="kpi-title">Pengembalian Bulan Ini</div>
          <div class="kpi-value"><?php echo number_format($tot_returns_month); ?></div>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon">👥</div>
        <div class="kpi-body">
          <div class="kpi-title">Anggota Aktif (90 hari)</div>
          <div class="kpi-value"><?php echo number_format($active_members); ?></div>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon">💰</div>
        <div class="kpi-body">
          <div class="kpi-title">Total Denda</div>
          <div class="kpi-value">Rp <?php echo number_format($fines); ?></div>
        </div>
      </div>
    </div>
  </section>

  <div class="content-grid">

    <div class="chart-grid">
      <div class="chart-card">
        <h3>Tren Peminjaman (30 hari)</h3>
        <div class="chart-body">
          <canvas id="chart-trend" height="160"></canvas>
          <div class="chart-empty" id="trend-empty" style="display:none;">Belum ada data untuk periode ini.</div>
        </div>
      </div>

      <div>
        <div class="chart-card">
          <h3>Kategori Paling Sering Dipinjam</h3>
          <?php if ($hasCategory): ?>
            <div class="chart-body">
              <canvas id="chart-category" height="200"></canvas>
              <div class="chart-empty" id="category-empty" style="display:none;">Belum ada data kategori untuk periode
                ini.</div>
            </div>
          <?php else: ?>
            <div class="chart-empty">Kolom `category` tidak ditemukan di tabel
              `books`.<br><code>ALTER TABLE books ADD COLUMN category VARCHAR(100) NULL;</code></div>
          <?php endif; ?>
        </div>

        <div class="chart-card" style="margin-top:12px;">
          <h4>Anggota Baru per Bulan</h4>
          <div class="chart-body"><canvas id="chart-members" height="120"></canvas></div>
        </div>
      </div>
    </div>

    <div class="info-section">
      <div class="card chart-card">
        <h4>Anggota Baru (30d)</h4>
        <div class="kpi-value"><?php echo number_format($new_members_30); ?></div>
      </div>
      <div class="card chart-card">
        <h4>Buku Baru (30d)</h4>
        <div class="kpi-value"><?php echo number_format($new_books_30); ?></div>
      </div>
    </div>

    <div class="card" style="margin-bottom:18px;">
      <h3>Heatmap Jam Peminjaman (last 30d)</h3>
      <div id="heatmap" class="heatmap-grid">
        <?php for ($h = 0; $h < 24; $h++): ?>
          <?php $v = $hours[$h];
          $intensity = min(1, $v / max(1, max($hours))); ?>
          <div class="heatmap-cell" style="background:rgba(14,165,233,<?php echo 0.12 + $intensity * 0.6; ?>);">
            <div style="font-size:12px; color:var(--text-muted);"><?php echo sprintf('%02d:00', $h); ?></div>
          </div>
        <?php endfor; ?>
      </div>
      <p style="margin-top:8px; color:var(--text-muted); font-size:13px;">Warna lebih gelap menunjukkan volume
        peminjaman yang lebih tinggi.</p>
    </div>

    <div class="card" style="margin-bottom:18px;">
      <h3>Tabel Laporan Detail</h3>
      <div style="margin-top:12px;">
        <h4>Laporan Peminjaman</h4>
        <table id="tbl-borrows" class="datatable" style="width:100%;">
          <thead>
            <tr>
              <th>ID</th>
              <th>Tanggal</th>
              <th>Buku</th>
              <th>Anggota</th>
              <th>Status</th>
              <th>Due</th>
              <th>Returned</th>
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

        <h4 style="margin-top:16px;">Laporan Pengembalian</h4>
        <table id="tbl-returns" class="datatable" style="width:100%;">
          <thead>
            <tr>
              <th>ID</th>
              <th>Tanggal Pinjam</th>
              <th>Tanggal Kembali</th>
              <th>Keterlambatan (hari)</th>
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

        <h4 style="margin-top:16px;">Laporan Buku</h4>
        <table id="tbl-books" class="datatable" style="width:100%;">
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

</main>

<?php include __DIR__ . '/partials/footer.php'; ?>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
  // Data for charts
  const trendLabels = <?php echo json_encode($trend_labels); ?>;
  const trendData = <?php echo json_encode($trend_data); ?>;
  const categoryLabels = <?php echo json_encode($category_labels); ?>;
  const categoryData = <?php echo json_encode($category_data); ?>;
  const memLabels = <?php echo json_encode($mem_labels); ?>;
  const memData = <?php echo json_encode($mem_data); ?>;

  // Render charts
  const ctxTrend = document.getElementById('chart-trend').getContext('2d');
  const trendChart = new Chart(ctxTrend, {
    type: 'line',
    data: { labels: trendLabels, datasets: [{ label: 'Peminjaman', data: trendData, borderColor: '#0ea5e9', backgroundColor: 'rgba(14,165,233,0.12)', tension: 0.25, fill: true }] },
    options: { responsive: true, plugins: { legend: { display: false } } }
  });
  // show empty state when no trend data
  if (Array.isArray(trendData) && trendData.reduce((a, b) => a + b, 0) === 0) {
    var te = document.getElementById('trend-empty'); if (te) { te.style.display = 'flex'; document.getElementById('chart-trend').style.display = 'none'; }
  } else { var te = document.getElementById('trend-empty'); if (te) { te.style.display = 'none'; document.getElementById('chart-trend').style.display = 'block'; } }

  if (categoryLabels.length) {
    const ctxCat = document.getElementById('chart-category').getContext('2d');
    const catChart = new Chart(ctxCat, { type: 'pie', data: { labels: categoryLabels, datasets: [{ data: categoryData, backgroundColor: ['#60a5fa', '#34d399', '#f59e0b', '#f97316', '#a78bfa', '#fb7185', '#94a3b8'] }] }, options: { responsive: true } });
    if (Array.isArray(categoryData) && categoryData.reduce((a, b) => a + b, 0) === 0) { var ce = document.getElementById('category-empty'); if (ce) ce.style.display = 'flex'; document.getElementById('chart-category').style.display = 'none'; } else { var ce = document.getElementById('category-empty'); if (ce) ce.style.display = 'none'; document.getElementById('chart-category').style.display = 'block'; }
  }

  const ctxMem = document.getElementById('chart-members').getContext('2d');
  const memChart = new Chart(ctxMem, { type: 'bar', data: { labels: memLabels, datasets: [{ label: 'Anggota Baru', data: memData, backgroundColor: '#8b5cf6' }] }, options: { responsive: true, plugins: { legend: { display: false } } } });
  if (Array.isArray(memData) && memData.reduce((a, b) => a + b, 0) === 0) { /* optionally show empty */ }


  // DataTables
  $(document).ready(function () {
    $('.datatable').DataTable({ pageLength: 10, order: [[1, 'desc']] });

    // Export to Excel using SheetJS (robust) — exports DataTables filtered data and falls back gracefully
    function exportToExcel() {
      function doExport() {
        try {
          if (typeof XLSX === 'undefined') { throw new Error('Library not loaded'); }
          var wb = XLSX.utils.book_new();
          var tables = [{ id: 'tbl-borrows', name: 'Borrows' }, { id: 'tbl-returns', name: 'Returns' }, { id: 'tbl-books', name: 'Books' }];
          tables.forEach(function (t) {
            var el = document.getElementById(t.id);
            if (!el) return;
            var ws;
            // If DataTables is initialized, get all rows (filtered) via API to preserve full dataset
            var dt;
            try { dt = $(el).DataTable && $(el).DataTable(); } catch (e) { dt = null; }
            if (dt && dt.rows().count() > 0) {
              var headers = $(el).find('thead th').map(function (i, th) { return $(th).text().trim(); }).get();
              var nodes = dt.rows({ search: 'applied' }).nodes().toArray();
              var rows = nodes.map(function (rowNode) { var cols = Array.from(rowNode.querySelectorAll('td')).map(td => td.innerText.trim()); return cols; });
              var json = rows.map(function (r) { var obj = {}; headers.forEach(function (h, i) { obj[h] = r[i] || ''; }); return obj; });
              ws = XLSX.utils.json_to_sheet(json, { header: headers });
            } else {
              // Fallback: use table_to_sheet on clone
              var clone = el.cloneNode(true);
              ws = XLSX.utils.table_to_sheet(clone);
            }
            XLSX.utils.book_append_sheet(wb, ws, t.name);
          });
          var fname = 'perpustakaan-report-' + (new Date()).toISOString().slice(0, 10) + '.xlsx';
          XLSX.writeFile(wb, fname);
          showToast('Export berhasil — file: ' + fname);
        } catch (err) {
          console.error('Export error:', err);
          showToast('Export gagal: ' + (err.message || 'unknown error'));
        }
      }

      if (typeof XLSX === 'undefined') {
        // Try to dynamically load SheetJS then export
        var s = document.createElement('script');
        s.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
        s.onload = doExport;
        s.onerror = function () { showToast('Gagal memuat library export (SheetJS).'); };
        document.head.appendChild(s);
      } else {
        doExport();
      }
    }

    function showToast(msg) {
      var t = document.createElement('div');
      t.className = 'toast';
      t.style.cssText = 'position:fixed;right:18px;bottom:18px;background:var(--primary-dark);color:#fff;padding:10px 14px;border-radius:8px;box-shadow:var(--shadow-md);z-index:9999;';
      t.innerText = msg; document.body.appendChild(t);
      setTimeout(function () { t.style.opacity = 0; setTimeout(function () { t.remove(); }, 300); }, 2200);
    }

    var btnX = document.getElementById('btn-export-excel');
    if (btnX) btnX.addEventListener('click', function (e) { e.preventDefault(); exportToExcel(); });

    // CSV export removed — use Excel export for richer, multi-sheet output
  });