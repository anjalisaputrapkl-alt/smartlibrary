<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];

// Handle return confirmation
if (isset($_GET['action']) && $_GET['action'] === 'return' && isset($_GET['id'])) {
  $stmt = $pdo->prepare(
    'UPDATE borrows SET returned_at=NOW(), status="returned"
     WHERE id=:id AND school_id=:sid'
  );
  $stmt->execute([
    'id' => (int) $_GET['id'],
    'sid' => $sid
  ]);
  header('Location: borrows.php');
  exit;
}

// Update overdue status
$pdo->prepare(
  'UPDATE borrows SET status="overdue"
   WHERE school_id=:sid AND returned_at IS NULL AND due_at < NOW()'
)->execute(['sid' => $sid]);

// Get all borrowing data
$stmt = $pdo->prepare(
  'SELECT b.*, bk.title, m.name AS member_name
   FROM borrows b
   JOIN books bk ON b.book_id = bk.id
   JOIN members m ON b.member_id = m.id
   WHERE b.school_id = :sid
   ORDER BY b.borrowed_at DESC'
);
$stmt->execute(['sid' => $sid]);
$borrows = $stmt->fetchAll();

// Calculate statistics
$totalBorrows = count($borrows);
$activeBorrows = count(array_filter($borrows, fn($b) => $b['status'] !== 'returned' && $b['status'] !== 'pending_return'));
$overdueBorrows = count(array_filter($borrows, fn($b) => $b['status'] === 'overdue'));
$pendingReturns = count(array_filter($borrows, fn($b) => $b['status'] === 'pending_return'));
$withFines = count(array_filter($borrows, fn($b) => !empty($b['fine_amount'])));
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manajemen Peminjaman</title>
  <script src="../assets/js/theme-loader.js"></script>
  <script src="../assets/js/theme.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
  <link rel="stylesheet" href="../assets/css/animations.css">
  <link rel="stylesheet" href="../assets/css/borrows.css">
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
        --accent-light: #e0f2fe;
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

    .stats-section {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
    }

    .stat-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 20px;
      text-align: center;
      transition: all 0.3s ease;
    }

    .stat-card:hover {
      border-color: var(--accent);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .stat-label {
      font-size: 12px;
      color: var(--muted);
      margin-bottom: 12px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .stat-value {
      font-size: 28px;
      font-weight: 600;
      color: var(--text);
    }

    .borrows-table {
      width: 100%;
      border-collapse: collapse;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      overflow: hidden;
    }

    .borrows-table thead {
      background: #f9fafb;
      border-bottom: 2px solid var(--border);
    }

    .borrows-table th {
      padding: 16px 12px;
      text-align: left;
      font-size: 12px;
      font-weight: 600;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .borrows-table td {
      padding: 16px 12px;
      border-bottom: 1px solid var(--border);
      font-size: 13px;
    }

    .borrows-table tbody tr:hover {
      background: #fafbfc;
    }

    .borrows-table tbody tr:last-child td {
      border-bottom: none;
    }

    .table-no {
      color: var(--muted);
      font-weight: 500;
      width: 40px;
    }

    .status-badge {
      display: inline-block;
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      width: fit-content;
      margin-left: -10px;
    }

    .status-borrowed {
      background: #dbeafe;
      color: #1e40af;
      margin-left: -10px;
    }

    .status-overdue {
      background: #fee2e2;
      color: #991b1b;
      margin-left: -10px;
    }

    .status-returned {
      background: #dcfce7;
      color: #166534;
      margin-left: -10px;
    }

    .status-pending {
      background: #fef3c7;
      color: #92400e;
      margin-left: -10px;
    }

    .btn-return {
      display: inline-block;
      padding: 8px 16px;
      background: var(--success);
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.2s ease;
      white-space: nowrap;
      margin-left: -50px;
    }

    .btn-return:hover {
      background: #15803d;
      transform: translateY(-1px);
      box-shadow: 0 4px 8px rgba(22, 163, 74, 0.2);
    }

    .btn-return:active {
      transform: translateY(0);
    }

    .btn-confirm-return {
      display: inline-block;
      padding: 8px 16px;
      background: #06b6d4;
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.2s ease;
      white-space: nowrap;
      margin-left: -12px;
    }

    .btn-confirm-return:hover {
      background: #0891b2;
      transform: translateY(-1px);
      box-shadow: 0 4px 8px rgba(6, 182, 212, 0.2);
    }

    .btn-confirm-return:active {
      transform: translateY(0);
    }

    .btn-disabled {
      background: #d1d5db;
      color: #6b7280;
      cursor: not-allowed;
    }

    .btn-disabled:hover {
      background: #d1d5db;
      transform: none;
      box-shadow: none;
    }

    .empty-state {
      text-align: center;
      padding: 48px 24px;
      color: var(--muted);
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

    @media (max-width: 768px) {
      .stats-section {
        grid-template-columns: 1fr;
      }

      .borrows-table {
        font-size: 12px;
      }

      .borrows-table th,
      .borrows-table td {
        padding: 12px 8px;
      }
    }
  </style>
</head>

<body>
  <?php require __DIR__ . '/partials/sidebar.php'; ?>

  <div class="app">

    <div class="topbar">
      <strong>Manajemen Peminjaman</strong>
    </div>

    <div class="content">
      <div class="main">
        <div>
          <!-- Barcode Scanner Button -->
          <div style="display: flex; gap: 12px; margin-bottom: 24px; align-items: center;">
            <button id="btnStartBarcodeSession" class="btn-barcode-start"
              style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; font-size: 14px;">
              <iconify-icon icon="mdi:barcode-scan"></iconify-icon>
              Mulai Peminjaman Barcode
            </button>

            <div id="barcodeSessionDisplay"
              style="display: none; padding: 12px 16px; background: #e0f2fe; border: 1px solid #0284c7; border-radius: 8px; flex: 1;">
              <div
                style="font-size: 12px; color: #0369a1; margin-bottom: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                Kode Sesi Aktif</div>
              <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                <code id="sessionTokenDisplay"
                  style="font-family: 'Monaco', 'Courier New', monospace; font-weight: 600; font-size: 13px; color: #0369a1; background: white; padding: 8px 12px; border-radius: 4px; flex: 1; user-select: all;">---</code>
                <button id="btnCopySessionToken" class="btn-copy"
                  style="padding: 8px 12px; background: white; color: #0369a1; border: 1px solid #0284c7; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 600; transition: all 0.2s ease;">
                  Salin
                </button>
                <button id="btnEndBarcodeSession" class="btn-end"
                  style="padding: 8px 12px; background: white; color: #0369a1; border: 1px solid #0284c7; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 600; transition: all 0.2s ease;">
                  Selesai
                </button>
              </div>
            </div>
          </div>

          <!-- Barcode Session Live Data -->
          <div id="barcodeSessionPanel" style="display: none; margin-bottom: 24px;">
            <div class="card"
              style="background: linear-gradient(135deg, #f0f4ff 0%, #f8f1ff 100%); border: 2px solid #667eea;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h2 style="margin: 0;">📱 Sesi Pemindaian Aktif</h2>
                <span id="sessionStatus"
                  style="padding: 4px 12px; background: #10b981; color: white; border-radius: 20px; font-size: 12px; font-weight: 600;">AKTIF</span>
              </div>

              <div id="barcodeSessionContent">
                <div style="text-align: center; padding: 24px; color: #667eea;">
                  <p style="font-size: 14px; margin-bottom: 8px;">Tunggu pemindaian dari smartphone...</p>
                  <div
                    style="display: inline-block; width: 32px; height: 32px; border: 3px solid #667eea; border-top-color: transparent; border-radius: 50%; animation: spin 1s linear infinite;">
                  </div>
                </div>
              </div>

              <div style="margin-top: 16px; padding: 16px; background: white; border-radius: 8px;">
                <h3
                  style="font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; font-weight: 600;">
                  Info Sesi</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 13px;">
                  <div>
                    <span style="color: #999;">Anggota:</span>
                    <div id="sessionMemberInfo" style="font-weight: 600; color: #1a1a1a; margin-top: 4px;">-</div>
                  </div>
                  <div>
                    <span style="color: #999;">Buku Terscan:</span>
                    <div id="sessionBooksCount" style="font-weight: 600; color: #1a1a1a; margin-top: 4px;">0</div>
                  </div>
                </div>
              </div>

              <div id="sessionBooksDisplay" style="margin-top: 16px; display: none;">
                <h3
                  style="font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; font-weight: 600;">
                  Buku yang Dipindai</h3>
                <div id="sessionBooksList" style="display: flex; flex-direction: column; gap: 8px;"></div>
              </div>

              <div style="margin-top: 16px; display: flex; gap: 12px;">
                <input type="date" id="barcodeDueDate" class="barcode-input-due"
                  style="flex: 1; padding: 10px 12px; border: 1px solid #667eea; border-radius: 6px; font-size: 13px;"
                  placeholder="Pilih jatuh tempo">
                <button id="btnCompleteBarcodeSession" class="btn-complete"
                  style="padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 13px; transition: all 0.3s ease; white-space: nowrap;">
                  Simpan Peminjaman
                </button>
              </div>
            </div>
          </div>

          <!-- Statistics Section -->
          <div class="stats-section">
            <div class="stat-card">
              <div class="stat-label">Total Peminjaman</div>
              <div class="stat-value"><?= $totalBorrows ?></div>
            </div>
            <div class="stat-card">
              <div class="stat-label">Sedang Dipinjam</div>
              <div class="stat-value"><?= $activeBorrows ?></div>
            </div>
            <div class="stat-card">
              <div class="stat-label">Terlambat</div>
              <div class="stat-value"><?= $overdueBorrows ?></div>
            </div>
            <div class="stat-card">
              <div class="stat-label">Menunggu Konfirmasi</div>
              <div class="stat-value"><?= $pendingReturns ?></div>
            </div>
          </div>

          <!-- Pending Return Requests -->
          <div class="card">
            <h2>Permintaan Pengembalian Menunggu Konfirmasi</h2>
            <?php if (empty(array_filter($borrows, fn($b) => $b['status'] === 'pending_return'))): ?>
              <div class="empty-state">
                <iconify-icon icon="mdi:inbox-outline"></iconify-icon>
                <p>Tidak ada permintaan pengembalian</p>
              </div>
            <?php else: ?>
              <table class="borrows-table">
                <thead>
                  <tr>
                    <th class="table-no">No</th>
                    <th>Nama Buku</th>
                    <th>Nama Siswa</th>
                    <th>Tanggal Pinjam</th>
                    <th>Jatuh Tempo</th>
                    <th>Status</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $no = 1;
                  foreach ($borrows as $br):
                    if ($br['status'] !== 'pending_return')
                      continue;
                    ?>
                    <tr>
                      <td class="table-no"><?= $no++ ?></td>
                      <td><strong><?= htmlspecialchars($br['title']) ?></strong></td>
                      <td><?= htmlspecialchars($br['member_name']) ?></td>
                      <td><?= date('d/m/Y', strtotime($br['borrowed_at'])) ?></td>
                      <td><?= $br['due_at'] ? date('d/m/Y', strtotime($br['due_at'])) : '-' ?></td>
                      <td>
                        <span class="status-badge status-pending">Menunggu Konfirmasi</span>
                      </td>
                      <td>
                        <button class="btn-confirm-return" onclick="confirmReturn(<?= $br['id'] ?>)">
                          <iconify-icon icon="mdi:check" style="vertical-align: middle; margin-right: 4px;"></iconify-icon>
                          Konfirmasi Pengembalian
                        </button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>

          <!-- Borrowing List Table -->
          <div class="card">
            <h2>Daftar Peminjaman Aktif</h2>
            <?php if (empty(array_filter($borrows, fn($b) => $b['status'] !== 'returned' && $b['status'] !== 'pending_return'))): ?>
              <div class="empty-state">
                <iconify-icon icon="mdi:book-off-outline"></iconify-icon>
                <p>Tidak ada peminjaman aktif saat ini</p>
              </div>
            <?php else: ?>
              <table class="borrows-table">
                <thead>
                  <tr>
                    <th class="table-no">No</th>
                    <th>Nama Buku</th>
                    <th>Nama Siswa</th>
                    <th>Tanggal Pinjam</th>
                    <th>Jatuh Tempo</th>
                    <th>Status</th>
                    <th>Denda</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $no = 1;
                  foreach ($borrows as $br):
                    if ($br['status'] === 'returned' || $br['status'] === 'pending_return')
                      continue;
                    ?>
                    <tr>
                      <td class="table-no"><?= $no++ ?></td>
                      <td><strong><?= htmlspecialchars($br['title']) ?></strong></td>
                      <td><?= htmlspecialchars($br['member_name']) ?></td>
                      <td><?= date('d/m/Y', strtotime($br['borrowed_at'])) ?></td>
                      <td><?= $br['due_at'] ? date('d/m/Y', strtotime($br['due_at'])) : '-' ?></td>
                      <td>
                        <?php if ($br['status'] === 'overdue'): ?>
                          <span class="status-badge status-overdue">Terlambat</span>
                        <?php else: ?>
                          <span class="status-badge status-borrowed">Dipinjam</span>
                        <?php endif; ?>
                      </td>
                      <td style="text-align: center;">
                        <?php if (!empty($br['fine_amount'])): ?>
                          <span
                            style="background: #fee2e2; color: #991b1b; padding: 4px 8px; border-radius: 4px; font-weight: 600;">
                            💳 Rp <?= number_format($br['fine_amount'], 0, ',', '.') ?>
                            (<?= $br['fine_status'] === 'paid' ? '✅ Paid' : '⏳ Unpaid' ?>)
                          </span>
                        <?php else: ?>
                          <span style="color: #6b7280;">-</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <a href="borrows.php?action=return&id=<?= $br['id'] ?>" class="btn-return">
                          <iconify-icon icon="mdi:check" style="vertical-align: middle; margin-right: 4px;"></iconify-icon>
                          Konfirmasi Pengembalian
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>

          <!-- Returned Books History -->
          <div class="card">
            <h2>Riwayat Pengembalian Buku</h2>
            <?php if (empty(array_filter($borrows, fn($b) => $b['status'] === 'returned'))): ?>
              <div class="empty-state">
                <iconify-icon icon="mdi:history"></iconify-icon>
                <p>Belum ada riwayat pengembalian</p>
              </div>
            <?php else: ?>
              <table class="borrows-table">
                <thead>
                  <tr>
                    <th class="table-no">No</th>
                    <th>Nama Buku</th>
                    <th>Nama Siswa</th>
                    <th>Tanggal Pinjam</th>
                    <th>Tanggal Kembali</th>
                    <th>Denda</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $no = 1;
                  foreach ($borrows as $br):
                    if ($br['status'] !== 'returned')
                      continue;
                    ?>
                    <tr>
                      <td class="table-no"><?= $no++ ?></td>
                      <td><strong><?= htmlspecialchars($br['title']) ?></strong></td>
                      <td><?= htmlspecialchars($br['member_name']) ?></td>
                      <td><?= date('d/m/Y', strtotime($br['borrowed_at'])) ?></td>
                      <td><?= $br['returned_at'] ? date('d/m/Y', strtotime($br['returned_at'])) : '-' ?></td>
                      <td style="text-align: center;">
                        <?php if (!empty($br['fine_amount'])): ?>
                          <span
                            style="background: #fee2e2; color: #991b1b; padding: 4px 8px; border-radius: 4px; font-weight: 600;">
                            💳 Rp <?= number_format($br['fine_amount'], 0, ',', '.') ?>
                          </span>
                        <?php else: ?>
                          <span style="color: #6b7280;">-</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <span class="status-badge status-returned">Dikembalikan</span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

  </div>

  <script>
    // ========================================================================
    // Barcode Scanner Session Management
    // ========================================================================

    let currentBarcodeSessionId = null;
    let currentBarcodeToken = null;
    let pollingInterval = null;

    // localStorage functions
    function saveBarcodeSessionToStorage() {
      const sessionState = {
        sessionId: currentBarcodeSessionId,
        token: currentBarcodeToken,
        timestamp: Date.now()
      };
      localStorage.setItem('barcodeSession', JSON.stringify(sessionState));
    }

    function restoreBarcodeSessionFromStorage() {
      const stored = localStorage.getItem('barcodeSession');
      if (!stored) return false;

      try {
        const session = JSON.parse(stored);
        const ageMinutes = (Date.now() - session.timestamp) / 1000 / 60;

        // Session expired after 30 minutes
        if (ageMinutes > 30) {
          localStorage.removeItem('barcodeSession');
          return false;
        }

        // Restore session
        currentBarcodeSessionId = session.sessionId;
        currentBarcodeToken = session.token;
        return true;
      } catch (e) {
        console.error('Error restoring barcode session:', e);
        return false;
      }
    }

    function clearBarcodeSessionStorage() {
      localStorage.removeItem('barcodeSession');
    }

    const btnStartBarcodeSession = document.getElementById('btnStartBarcodeSession');
    const btnEndBarcodeSession = document.getElementById('btnEndBarcodeSession');
    const btnCopySessionToken = document.getElementById('btnCopySessionToken');
    const btnCompleteBarcodeSession = document.getElementById('btnCompleteBarcodeSession');
    const barcodeSessionDisplay = document.getElementById('barcodeSessionDisplay');
    const barcodeSessionPanel = document.getElementById('barcodeSessionPanel');
    const sessionTokenDisplay = document.getElementById('sessionTokenDisplay');
    const barcodeDueDate = document.getElementById('barcodeDueDate');

    // Try to restore barcode session from localStorage on page load
    if (restoreBarcodeSessionFromStorage()) {
      console.log('✓ Barcode session restored from localStorage');
      console.log('Session ID:', currentBarcodeSessionId);

      // Show session display
      sessionTokenDisplay.textContent = currentBarcodeToken;
      barcodeSessionDisplay.style.display = 'flex';
      barcodeSessionPanel.style.display = 'block';

      // Hide start button
      btnStartBarcodeSession.style.display = 'none';

      // Resume polling
      startPolling();
    }

    // Start Barcode Session
    btnStartBarcodeSession.addEventListener('click', async () => {
      btnStartBarcodeSession.disabled = true;
      btnStartBarcodeSession.style.opacity = '0.6';

      try {
        const response = await fetch('api/create-barcode-session.php', {
          method: 'POST'
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
          alert('Gagal membuat sesi barcode: ' + (data.message || 'Unknown error'));
          btnStartBarcodeSession.disabled = false;
          btnStartBarcodeSession.style.opacity = '1';
          return;
        }

        // Store session info
        currentBarcodeSessionId = data.data.session_id;
        currentBarcodeToken = data.data.token;

        // Save to localStorage
        saveBarcodeSessionToStorage();

        // Show session display
        sessionTokenDisplay.textContent = currentBarcodeToken;
        barcodeSessionDisplay.style.display = 'flex';
        barcodeSessionPanel.style.display = 'block';

        // Hide start button
        btnStartBarcodeSession.style.display = 'none';

        // Start polling for updates
        startPolling();

        // Set default due date (7 days from today)
        const today = new Date();
        const dueDate = new Date(today.getTime() + 7 * 24 * 60 * 60 * 1000);
        barcodeDueDate.valueAsDate = dueDate;

        alert('Sesi barcode dibuat!\n\nToken: ' + currentBarcodeToken + '\n\nBuka link berikut di smartphone:\nhttp://localhost/perpustakaan-online/public/barcode-scan.php');

      } catch (error) {
        console.error('Error:', error);
        alert('Terjadi kesalahan: ' + error.message);
        btnStartBarcodeSession.disabled = false;
        btnStartBarcodeSession.style.opacity = '1';
      }
    });

    // Copy Session Token
    btnCopySessionToken.addEventListener('click', () => {
      const token = sessionTokenDisplay.textContent;
      navigator.clipboard.writeText(token).then(() => {
        btnCopySessionToken.textContent = '✓ Tersalin';
        setTimeout(() => {
          btnCopySessionToken.textContent = 'Salin';
        }, 2000);
      });
    });

    // Start Polling
    function startPolling() {
      if (pollingInterval) clearInterval(pollingInterval);

      // Poll every 2 seconds
      pollingInterval = setInterval(pollSessionData, 2000);

      // Initial poll
      pollSessionData();
    }

    // Poll Session Data
    async function pollSessionData() {
      if (!currentBarcodeSessionId) return;

      try {
        const response = await fetch(`api/get-barcode-session-data.php?session_id=${currentBarcodeSessionId}`);
        const data = await response.json();

        if (!response.ok || !data.success) {
          return;
        }

        // Save session data to keep it alive
        saveBarcodeSessionToStorage();

        const sessionData = data.data;

        // Update member info
        const memberInfo = sessionData.member;
        if (memberInfo) {
          document.getElementById('sessionMemberInfo').textContent = memberInfo.name;
        }

        // Update books count
        const booksCount = sessionData.books_count || 0;
        document.getElementById('sessionBooksCount').textContent = booksCount;

        // Update books list
        const booksList = document.getElementById('sessionBooksList');
        if (sessionData.books_scanned && sessionData.books_scanned.length > 0) {
          booksList.innerHTML = '';
          sessionData.books_scanned.forEach((book, index) => {
            const bookItem = document.createElement('div');
            bookItem.style.cssText = 'padding: 8px 12px; background: white; border-left: 3px solid #667eea; border-radius: 4px; font-size: 13px;';
            bookItem.innerHTML = `
              <div style="font-weight: 600; color: #1a1a1a;">${escapeHtml(book.title)}</div>
              <div style="font-size: 11px; color: #999; margin-top: 2px;">ISBN: ${escapeHtml(book.isbn || '-')}</div>
            `;
            booksList.appendChild(bookItem);
          });
          document.getElementById('sessionBooksDisplay').style.display = 'block';
        }

      } catch (error) {
        console.error('Polling error:', error);
      }
    }

    // End Barcode Session
    btnEndBarcodeSession.addEventListener('click', () => {
      if (confirm('Akhiri sesi pemindaian?')) {
        stopPolling();
        resetBarcodeSession();
      }
    });

    // Complete Barcode Borrowing
    btnCompleteBarcodeSession.addEventListener('click', async () => {
      const dueDate = barcodeDueDate.value;

      if (!dueDate) {
        alert('Pilih tanggal jatuh tempo');
        return;
      }

      if (!confirm('Simpan peminjaman dengan tanggal jatuh tempo: ' + dueDate + '?')) {
        return;
      }

      btnCompleteBarcodeSession.disabled = true;
      btnCompleteBarcodeSession.style.opacity = '0.6';

      try {
        const response = await fetch('api/complete-barcode-borrowing.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            session_id: currentBarcodeSessionId,
            due_date: dueDate
          })
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
          alert('Gagal menyimpan peminjaman: ' + (data.message || 'Unknown error'));
          btnCompleteBarcodeSession.disabled = false;
          btnCompleteBarcodeSession.style.opacity = '1';
          return;
        }

        alert('✓ Peminjaman berhasil disimpan!\n\n' + data.data.borrows_created + ' buku telah dipinjam.');

        // Reset and reload
        stopPolling();
        resetBarcodeSession();
        setTimeout(() => {
          location.reload();
        }, 1500);

      } catch (error) {
        console.error('Error:', error);
        alert('Terjadi kesalahan: ' + error.message);
        btnCompleteBarcodeSession.disabled = false;
        btnCompleteBarcodeSession.style.opacity = '1';
      }
    });

    // Stop Polling
    function stopPolling() {
      if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
      }
    }

    // Reset Barcode Session UI
    function resetBarcodeSession() {
      currentBarcodeSessionId = null;
      currentBarcodeToken = null;

      // Clear storage
      clearBarcodeSessionStorage();

      barcodeSessionDisplay.style.display = 'none';
      barcodeSessionPanel.style.display = 'none';
      btnStartBarcodeSession.style.display = 'inline-flex';
      btnStartBarcodeSession.disabled = false;
      btnStartBarcodeSession.style.opacity = '1';

      document.getElementById('sessionMemberInfo').textContent = '-';
      document.getElementById('sessionBooksCount').textContent = '0';
      document.getElementById('sessionBooksList').innerHTML = '';
      document.getElementById('sessionBooksDisplay').style.display = 'none';
      barcodeDueDate.value = '';

      btnCompleteBarcodeSession.disabled = false;
      btnCompleteBarcodeSession.style.opacity = '1';
    }

    // Utility function
    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
      stopPolling();
    });

    // Original confirmReturn function
    function confirmReturn(borrowId) {
      if (!confirm('Konfirmasi pengembalian buku ini?')) {
        return;
      }

      fetch('api/admin-confirm-return.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'borrow_id=' + borrowId
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('Pengembalian buku telah dikonfirmasi!');
            location.reload();
          } else {
            alert(data.message || 'Gagal mengkonfirmasi pengembalian');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Terjadi kesalahan');
        });
    }

    // Add spin animation
    const style = document.createElement('style');
    style.textContent = `
      @keyframes spin {
        to { transform: rotate(360deg); }
      }
    `;
    document.head.appendChild(style);
  </script>

</body>

</html>