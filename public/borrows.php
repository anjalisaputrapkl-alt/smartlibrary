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
          <div style="display: flex; gap: 12px; margin-bottom: 24px;">
            <a href="barcode-scan-simple.php" class="btn-barcode-start"
              style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; font-size: 14px; text-decoration: none;">
              <iconify-icon icon="mdi:barcode-scan"></iconify-icon>
              Buka Pemindai Barcode
            </a>
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
    // Confirm Return Function (Admin)
    // ========================================================================

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
  </script>

</body>

</html>