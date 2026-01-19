<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/maintenance/MaintenanceController.php';

$controller = new MaintenanceController($pdo);

// Get all records and books FIRST
$records = $controller->getAll();
$books = $controller->getBooks();
$totalRecords = $controller->getCount();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['action'])) {
  $controller->handleAjax();
  exit;
}

// Handle Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  header('Content-Type: application/vnd.ms-excel; charset=utf-8');
  header('Content-Disposition: attachment; filename="maintenance-' . date('Y-m-d-H-i-s') . '.xls"');

  echo '<!DOCTYPE html>';
  echo '<html>';
  echo '<head>';
  echo '<meta charset="UTF-8">';
  echo '<style>';
  echo 'table { border-collapse: collapse; width: 100%; }';
  echo 'th { background-color: #2563eb; color: white; padding: 12px; text-align: left; border: 1px solid #ddd; font-weight: bold; }';
  echo 'td { padding: 10px; border: 1px solid #ddd; }';
  echo 'tr:nth-child(even) { background-color: #f9fafb; }';
  echo 'col.id { width: 50px; }';
  echo 'col.title { width: 200px; }';
  echo 'col.author { width: 150px; }';
  echo 'col.status { width: 100px; }';
  echo 'col.priority { width: 100px; }';
  echo 'col.notes { width: 200px; }';
  echo 'col.followup { width: 120px; }';
  echo 'col.date { width: 120px; }';
  echo '</style>';
  echo '</head>';
  echo '<body>';
  echo '<table>';
  echo '<colgroup>';
  echo '<col class="id">';
  echo '<col class="title">';
  echo '<col class="author">';
  echo '<col class="status">';
  echo '<col class="priority">';
  echo '<col class="notes">';
  echo '<col class="followup">';
  echo '<col class="date">';
  echo '</colgroup>';
  echo '<thead>';
  echo '<tr>';
  echo '<th>ID</th>';
  echo '<th>Judul Buku</th>';
  echo '<th>Penulis</th>';
  echo '<th>Status</th>';
  echo '<th>Prioritas</th>';
  echo '<th>Catatan</th>';
  echo '<th>Follow-up</th>';
  echo '<th>Tanggal Update</th>';
  echo '</tr>';
  echo '</thead>';
  echo '<tbody>';

  foreach ($records as $r) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($r['id']) . '</td>';
    echo '<td>' . htmlspecialchars($r['book_title']) . '</td>';
    echo '<td>' . htmlspecialchars($r['book_author']) . '</td>';
    echo '<td>' . htmlspecialchars($r['status']) . '</td>';
    echo '<td>' . htmlspecialchars($r['priority'] ?? 'Normal') . '</td>';
    echo '<td>' . htmlspecialchars($r['notes'] ?? '') . '</td>';
    echo '<td>' . (isset($r['follow_up_date']) && $r['follow_up_date'] ? date('d-m-Y', strtotime($r['follow_up_date'])) : '') . '</td>';
    echo '<td>' . date('d-m-Y', strtotime($r['updated_at'])) . '</td>';
    echo '</tr>';
  }

  echo '</tbody>';
  echo '</table>';
  echo '</body>';
  echo '</html>';

  exit;
}

?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pemeliharaan Buku</title>
  <script src="../assets/js/theme-loader.js"></script>
  <script src="../assets/js/theme.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/animations.css">
  <link rel="stylesheet" href="../assets/css/book-maintenance.css">
</head>

<body>
  <?php require __DIR__ . '/partials/sidebar.php'; ?>

  <div class="app">

    <div class="topbar">
      <strong>Pemeliharaan Buku</strong>
      <div class="topbar-actions">
        <button class="btn primary" onclick="exportCSV()">📥 Export Excel</button>
        <button class="btn primary" onclick="openAddModal()">+ Tambah Catatan</button>
      </div>
    </div>

    <div class="content">
      <div class="main">

        <div>
          <div class="card">
            <h2>Filter & Statistik</h2>
            <div class="filter-bar">
              <input type="text" id="searchInput" placeholder="Cari judul buku atau penulis..." onkeyup="filterTable()">
              <select id="statusFilter" onchange="filterTable()">
                <option value="">-- Semua Status --</option>
                <option value="Good">Good (Bagus)</option>
                <option value="Worn Out">Worn Out (Aus)</option>
                <option value="Damaged">Damaged (Rusak)</option>
                <option value="Missing">Missing (Hilang)</option>
                <option value="Need Repair">Need Repair (Perlu Perbaikan)</option>
                <option value="Replaced">Replaced (Diganti)</option>
              </select>
              <select id="priorityFilter" onchange="filterTable()">
                <option value="">-- Semua Prioritas --</option>
                <option value="Low">Low</option>
                <option value="Normal">Normal</option>
                <option value="High">High</option>
                <option value="Urgent">Urgent</option>
              </select>
              <div class="filter-buttons">
                <button class="btn" onclick="resetFilter();"
                  style="background: var(--danger); color: #fff; border: none;">Reset</button>
              </div>
            </div>
            <div class="stats-container">
              <div class="stat-card">
                <div class="stat-label">Total Catatan</div>
                <div class="stat-value"><?= $totalRecords ?></div>
              </div>
            </div>
          </div>
        </div>

        <div>

          <?php if (empty($records)): ?>
            <p style="text-align: center; color: var(--muted); padding: 32px 0;">
              Belum ada catatan maintenance. <a href="#" onclick="openAddModal(); return false;"
                style="color: var(--accent); text-decoration: none; font-weight: 600;">Buat catatan pertama</a>
            </p>
          <?php else: ?>
            <div class="card">
              <h2>Daftar Catatan Maintenance (<?= count($records) ?>)</h2>
              <div class="table-wrap">
                <table>
                  <colgroup>
                    <col class="id">
                    <col class="title">
                    <col class="author">
                    <col class="status">
                    <col class="priority">
                    <col class="notes">
                    <col class="followup">
                    <col class="date">
                    <col class="action">
                  </colgroup>

                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Judul Buku</th>
                      <th>Penulis</th>
                      <th>Status</th>
                      <th>Prioritas</th>
                      <th>Catatan</th>
                      <th>Follow-up</th>
                      <th>Update</th>
                      <th class="text-center">Aksi</th>
                    </tr>
                  </thead>

                  <tbody>
                    <?php foreach ($records as $r): ?>
                      <tr>
                        <td>#<?= $r['id'] ?></td>
                        <td><strong><?= htmlspecialchars($r['book_title']) ?></strong></td>
                        <td><?= htmlspecialchars($r['book_author']) ?></td>
                        <td>
                          <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $r['status'])) ?>">
                            <?= htmlspecialchars($r['status']) ?>
                          </span>
                        </td>
                        <td>
                          <?php
                          $priority = $r['priority'] ?? 'Normal';
                          $priority_color = $priority === 'Urgent' ? '#dc2626' : ($priority === 'High' ? '#f59e0b' : '#6b7280');
                          ?>
                          <span
                            style="display: inline-block; padding: 4px 8px; background: rgba(220, 38, 38, 0.1); color: <?= $priority_color ?>; border-radius: 4px; font-size: 11px; font-weight: 600;">
                            <?= htmlspecialchars($priority) ?>
                          </span>
                        </td>
                        <td>
                          <?= $r['notes'] ? htmlspecialchars(substr($r['notes'], 0, 30)) . (strlen($r['notes']) > 30 ? '...' : '') : '-' ?>
                        </td>
                        <td style="font-size: 12px;">
                          <?php
                          $followup = $r['follow_up_date'] ?? null;
                          if ($followup) {
                            echo date('d M Y', strtotime($followup));
                          } else {
                            echo '-';
                          }
                          ?>
                        </td>
                        <td style="font-size: 12px;"><?= date('d M Y', strtotime($r['updated_at'])) ?></td>
                        <td class="text-center">
                          <div class="actions">
                            <button class="btn small" onclick="openEditModal(<?= $r['id'] ?>)">Edit</button>
                            <button class="btn small danger" onclick="deleteRecord(<?= $r['id'] ?>)">Hapus</button>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          <?php endif; ?>
          
          <div class="card">
            <h2>Pertanyaan Umum</h2>
            <div class="faq-item">
              <div class="faq-question">Bagaimana cara menambah catatan maintenance baru? <span>+</span></div>
              <div class="faq-answer">Klik tombol "+ Tambah Catatan" di pojok kanan atas, pilih buku dari dropdown, atur status dan prioritas, kemudian isi catatan keterangan. Tekan "Simpan" untuk menyimpan catatan.</div>
            </div>
          </div>
        </div>

      </div>

    </div>
  </div>

  <!-- Modal Add/Edit -->
  <div id="maintenanceModal" class="modal">
    <div class="modal-content">
      <div class="modal-header" id="modalTitle">Tambah Catatan Maintenance</div>
      <div class="modal-body">
        <form id="maintenanceForm">
          <input type="hidden" id="recordId" name="id" value="">

          <div class="form-group">
            <label for="bookId">Pilih Buku</label>
            <select id="bookId" name="book_id" required>
              <option value="">-- Pilih Buku --</option>
              <?php foreach ($books as $b): ?>
                <option value="<?= $b['id'] ?>">
                  <?= htmlspecialchars($b['title']) . ' - ' . htmlspecialchars($b['author']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status" required>
              <option value="">-- Pilih Status --</option>
              <option value="Good">Good (Bagus)</option>
              <option value="Worn Out">Worn Out (Aus)</option>
              <option value="Damaged">Damaged (Rusak)</option>
              <option value="Missing">Missing (Hilang)</option>
              <option value="Need Repair">Need Repair (Perlu Perbaikan)</option>
              <option value="Replaced">Replaced (Diganti)</option>
            </select>
          </div>

          <div class="form-group">
            <label for="priority">Prioritas</label>
            <select id="priority" name="priority">
              <option value="Normal">Normal</option>
              <option value="Low">Low</option>
              <option value="High">High</option>
              <option value="Urgent">Urgent</option>
            </select>
          </div>

          <div class="form-group">
            <label for="followUpDate">Tanggal Follow-up (Opsional)</label>
            <input type="date" id="followUpDate" name="follow_up_date">
          </div>

          <div class="form-group">
            <label for="notes">Catatan / Keterangan</label>
            <textarea id="notes" name="notes" placeholder="Deskripsikan kondisi buku..."></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn" onclick="closeModal()">Batal</button>
        <button class="btn primary" onclick="saveRecord()">Simpan</button>
      </div>
    </div>
  </div>

  <script>
    // Data untuk digunakan di book-maintenance.js
    window.recordsData = <?php echo json_encode($records); ?>;
  </script>
  <script src="../assets/js/book-maintenance.js"></script>

</body>

</html>