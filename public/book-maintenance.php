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
  <script src="../assets/js/theme.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

  <style>
    :root {
      --bg: #f1f4f8;
      --surface: #fff;
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
      margin: 0;
    }

    body {
      font-family: Inter, sans-serif;
      background: var(--bg);
      color: var(--text)
    }

    .app {
      min-height: 100vh;
      display: grid;
      grid-template-rows: 64px 1fr;
      margin-left: 260px;
    }

    .topbar {
      background: var(--surface);
      border-bottom: 1px solid var(--border);
      padding: 18px 32px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: fixed;
      top: 0;
      left: 260px;
      right: 0;
      z-index: 999;
    }

    .content {
      padding: 32px;
      display: grid;
      grid-template-columns: 1fr;
      gap: 32px;
      margin-top: 64px;
    }

    .main {
      display: grid;
      grid-template-columns: 1fr;
      gap: 32px
    }

    .card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 24px
    }

    .card h2 {
      font-size: 14px;
      margin: 0 0 16px
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
      margin-bottom: 16px
    }

    label {
      font-size: 12px;
      color: var(--muted)
    }

    input,
    select,
    textarea {
      padding: 12px 14px;
      border: 1px solid var(--border);
      border-radius: 8px;
      font-size: 13px;
      font-family: Inter, sans-serif;
    }

    select {
      padding-right: 30px;
      background: var(--surface);
      color: var(--text);
      cursor: pointer;
      background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
      background-repeat: no-repeat;
      background-position: right 8px center;
      background-size: 16px;
      appearance: none;
    }

    textarea {
      resize: vertical;
      min-height: 80px;
    }

    .btn {
      padding: 7px 14px;
      border-radius: 6px;
      border: 1px solid var(--border);
      background: #fff;
      font-size: 13px;
      cursor: pointer;
    }

    .btn.primary {
      background: var(--accent);
      color: #fff;
      border: none
    }

    .btn.danger {
      background: #fee2e2;
      color: var(--danger);
      border: 1px solid #fecaca
    }

    .table-wrap {
      overflow-x: auto;
      max-height: 380px;
      overflow-y: auto;
      border: 1px solid var(--border);
      border-radius: 8px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 12.5px;
      table-layout: fixed;
    }

    col.id {
      width: 5%
    }

    col.title {
      width: 22%
    }

    col.author {
      width: 18%
    }

    col.status {
      width: 10%
    }

    col.priority {
      width: 10%
    }

    col.notes {
      width: 15%
    }

    col.followup {
      width: 10%
    }

    col.date {
      width: 10%
    }

    col.action {
      width: 12%
    }

    th,
    td {
      padding: 10px 8px;
      border-bottom: 1px solid var(--border);
      vertical-align: middle;
      word-wrap: break-word;
      overflow-wrap: break-word;
    }

    th {
      color: var(--muted);
      font-weight: 500;
      text-align: left
    }

    .text-center {
      text-align: center
    }

    .actions {
      display: flex;
      gap: 6px;
      justify-content: center
    }

    .filter-bar {
      display: flex;
      gap: 12px;
      margin-bottom: 16px;
      flex-wrap: wrap;
      align-items: center;
    }

    .filter-bar input {
      flex: 1;
      min-width: 200px;
      padding: 10px 12px;
      border: 1px solid var(--border);
      border-radius: 6px;
      font-size: 13px;
    }

    .filter-bar select {
      padding: 10px 12px;
      border: 1px solid var(--border);
      border-radius: 6px;
      font-size: 13px;
      min-width: 140px;
    }

    .filter-buttons {
      display: flex;
      gap: 8px;
    }

    .filter-buttons .btn {
      padding: 10px 14px;
      font-size: 13px;
    }

    .sidebar {
      display: none;
    }

    .status-badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 11px;
      font-weight: 600;
      white-space: nowrap;
    }

    .status-good {
      background: #dcfce7;
      color: #166534;
    }

    .status-worn {
      background: #fef3c7;
      color: #92400e;
    }

    .status-damaged {
      background: #fee2e2;
      color: #991b1b;
    }

    .status-missing {
      background: #f3f4f6;
      color: #374151;
    }

    .status-need-repair {
      background: #dbeafe;
      color: #1e40af;
    }

    .status-replaced {
      background: #cffafe;
      color: #164e63;
    }

    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 9999;
      align-items: center;
      justify-content: center;
    }

    .modal.active {
      display: flex !important;
    }

    .modal-content {
      background: var(--surface);
      padding: 24px;
      border-radius: 8px;
      width: 90%;
      max-width: 500px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
      max-height: 90vh;
      overflow-y: auto;
    }

    .modal-header {
      font-size: 14px;
      font-weight: 700;
      margin-bottom: 16px;
    }

    .modal-body {
      margin-bottom: 16px;
    }

    .modal-footer {
      display: flex;
      gap: 8px;
      justify-content: flex-end;
    }

    .toast {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 12px 16px;
      border-radius: 6px;
      color: white;
      z-index: 2000;
      animation: slideIn 0.3s;
      font-size: 13px;
      font-weight: 500;
    }

    .toast-success {
      background: #10b981;
    }

    .toast-error {
      background: var(--danger);
    }

    @keyframes slideIn {
      from {
        transform: translateX(400px);
        opacity: 0;
      }

      to {
        transform: translateX(0);
        opacity: 1;
      }
    }
  </style>
</head>

<body>
  <?php require __DIR__ . '/partials/sidebar.php'; ?>

  <div class="app">

    <div class="topbar">
      <strong>Pemeliharaan Buku</strong>
      <div style="display: flex; gap: 12px;">
        <button class="btn primary" onclick="exportCSV()">📥 Export Excel</button>
        <button class="btn primary" onclick="openAddModal()">+ Tambah Catatan</button>
      </div>
    </div>

    <div class="content">
      <div class="main">

        <div class="card">
          <h2>Daftar Catatan Maintenance (<?= $totalRecords ?>)</h2>

          <!-- Filter Bar -->
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

          <?php if (empty($records)): ?>
            <p style="text-align: center; color: var(--muted); padding: 32px 0;">
              Belum ada catatan maintenance. <a href="#" onclick="openAddModal(); return false;"
                style="color: var(--accent); text-decoration: none; font-weight: 600;">Buat catatan pertama</a>
            </p>
          <?php else: ?>
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
                          <button class="btn" onclick="openEditModal(<?= $r['id'] ?>)">Edit</button>
                          <button class="btn danger" onclick="deleteRecord(<?= $r['id'] ?>)">Hapus</button>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

        <div class="card" style="grid-column: 1/-1">
          <h2>Statistik Maintenance</h2>
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px">
            <div style="padding: 16px; background: rgba(37, 99, 235, .05); border-radius: 8px">
              <div style="font-size: 12px; color: var(--muted); margin-bottom: 6px">Total Catatan</div>
              <div style="font-size: 24px; font-weight: 600"><?= $totalRecords ?></div>
            </div>
            <div style="padding: 16px; background: rgba(37, 99, 235, .05); border-radius: 8px">
              <div style="font-size: 12px; color: var(--muted); margin-bottom: 6px">Status Baik</div>
              <div style="font-size: 24px; font-weight: 600" id="goodCount">0</div>
            </div>
            <div style="padding: 16px; background: rgba(37, 99, 235, .05); border-radius: 8px">
              <div style="font-size: 12px; color: var(--muted); margin-bottom: 6px">Rusak/Perbaikan</div>
              <div style="font-size: 24px; font-weight: 600" id="damagedCount">0</div>
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
    function showToast(msg, type = 'success') {
      const toast = document.createElement('div');
      toast.className = `toast ${type === 'success' ? 'toast-success' : 'toast-error'}`;
      toast.innerText = msg;
      document.body.appendChild(toast);
      setTimeout(() => {
        toast.style.animation = 'slideIn 0.3s reverse';
        setTimeout(() => toast.remove(), 300);
      }, 2500);
    }

    function exportCSV() {
      window.location.href = '?export=csv';
    }

    function filterTable() {
      const searchText = document.getElementById('searchInput').value.toLowerCase();
      const statusFilter = document.getElementById('statusFilter').value;
      const priorityFilter = document.getElementById('priorityFilter').value;
      const rows = document.querySelectorAll('tbody tr');

      rows.forEach(row => {
        const title = row.cells[1].textContent.toLowerCase();
        const author = row.cells[2].textContent.toLowerCase();
        const status = row.cells[3].textContent.trim();
        const priority = row.cells[4].textContent.trim();

        const matchSearch = title.includes(searchText) || author.includes(searchText);
        const matchStatus = !statusFilter || status.includes(statusFilter);
        const matchPriority = !priorityFilter || priority.includes(priorityFilter);

        row.style.display = matchSearch && matchStatus && matchPriority ? '' : 'none';
      });
    }

    function resetFilter() {
      document.getElementById('searchInput').value = '';
      document.getElementById('statusFilter').value = '';
      document.getElementById('priorityFilter').value = '';
      filterTable();
    }

    function openAddModal() {
      document.getElementById('recordId').value = '';
      document.getElementById('maintenanceForm').reset();
      document.getElementById('modalTitle').innerText = 'Tambah Catatan Maintenance';
      document.getElementById('maintenanceModal').classList.add('active');
    }

    function openEditModal(id) {
      fetch('?action=get&id=' + id)
        .then(r => r.json())
        .then(data => {
          if (data.success && data.data) {
            const record = data.data;
            document.getElementById('recordId').value = record.id;
            document.getElementById('bookId').value = record.book_id;
            document.getElementById('status').value = record.status;
            document.getElementById('priority').value = record.priority || 'Normal';
            document.getElementById('followUpDate').value = record.follow_up_date || '';
            document.getElementById('notes').value = record.notes || '';
            document.getElementById('modalTitle').innerText = 'Edit Catatan Maintenance';
            document.getElementById('maintenanceModal').classList.add('active');
          }
        });
    }

    function closeModal() {
      document.getElementById('maintenanceModal').classList.remove('active');
    }

    function saveRecord() {
      const id = document.getElementById('recordId').value;
      const bookId = document.getElementById('bookId').value;
      const status = document.getElementById('status').value;
      const priority = document.getElementById('priority').value;
      const followUpDate = document.getElementById('followUpDate').value;
      const notes = document.getElementById('notes').value;

      if (!bookId || !status) {
        showToast('Buku dan Status harus dipilih!', 'error');
        return;
      }

      const formData = new FormData();
      formData.append('action', id ? 'update' : 'add');
      formData.append('book_id', bookId);
      formData.append('status', status);
      formData.append('priority', priority);
      formData.append('follow_up_date', followUpDate);
      formData.append('notes', notes);
      if (id) formData.append('id', id);

      fetch(window.location.pathname, { method: 'POST', body: formData })
        .then(r => r.text().then(text => {
          try {
            return JSON.parse(text);
          } catch (e) {
            throw new Error('Invalid JSON');
          }
        }))
        .then(data => {
          if (data.success) {
            showToast(data.message);
            closeModal();
            setTimeout(() => location.reload(), 800);
          } else {
            showToast(data.message || 'Terjadi kesalahan', 'error');
          }
        })
        .catch(err => {
          showToast('Error: ' + err.message, 'error');
        });
    }

    function deleteRecord(id) {
      if (!confirm('Yakin hapus catatan ini?')) return;

      const formData = new FormData();
      formData.append('action', 'delete');
      formData.append('id', id);

      fetch(window.location.pathname, { method: 'POST', body: formData })
        .then(r => r.text().then(text => {
          try {
            return JSON.parse(text);
          } catch (e) {
            throw new Error('Invalid JSON');
          }
        }))
        .then(data => {
          if (data.success) {
            showToast(data.message);
            setTimeout(() => location.reload(), 800);
          } else {
            showToast(data.message || 'Terjadi kesalahan', 'error');
          }
        })
        .catch(err => {
          showToast('Error: ' + err.message, 'error');
        });
    }

    // Update stats on page load
    document.addEventListener('DOMContentLoaded', () => {
      const records = <?php echo json_encode($records); ?>;
      const good = records.filter(r => r.status === 'Good').length;
      const damaged = records.filter(r =>
        ['Damaged', 'Need Repair', 'Missing'].includes(r.status)
      ).length;

      document.getElementById('goodCount').innerText = good;
      document.getElementById('damagedCount').innerText = damaged;
    });

    // Close modal on outside click
    document.getElementById('maintenanceModal').addEventListener('click', (e) => {
      if (e.target.id === 'maintenanceModal') closeModal();
    });
  </script>

</body>

</html>