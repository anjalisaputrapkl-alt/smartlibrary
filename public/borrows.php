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
  'SELECT b.*, bk.title, bk.cover_image, bk.isbn, m.name AS member_name, m.nisn
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
$activeBorrows = count(array_filter($borrows, fn($b) => $b['status'] !== 'returned' && $b['status'] !== 'pending_return' && $b['status'] !== 'pending_confirmation'));
$overdueBorrows = count(array_filter($borrows, fn($b) => $b['status'] === 'overdue'));
$pendingReturns = count(array_filter($borrows, fn($b) => $b['status'] === 'pending_return'));
$pendingConfirmation = count(array_filter($borrows, fn($b) => $b['status'] === 'pending_confirmation'));
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
      grid-template-columns: repeat(5, 1fr);
      gap: 20px;
    }

    .stat-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 20px;
      text-align: center;
      transition: all 0.3s ease;
      position: relative;
    }

    .stat-card.clickable {
      cursor: pointer;
    }

    .stat-card.clickable:hover {
      border-color: var(--accent);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      transform: translateY(-4px);
    }

    /* Modal Styles */
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 10000;
      backdrop-filter: blur(4px);
    }

    .modal-container {
      background: var(--surface);
      width: 90%;
      max-width: 800px;
      max-height: 80vh;
      border-radius: 16px;
      display: flex;
      flex-direction: column;
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
      overflow: hidden;
    }

    .modal-header {
      padding: 20px 24px;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .modal-header h2 {
      margin: 0;
      font-size: 18px;
      font-weight: 700;
      color: var(--text);
    }

    .modal-close {
      background: none;
      border: none;
      font-size: 24px;
      color: var(--text-muted);
      cursor: pointer;
      line-height:1;
    }

    .modal-body {
      padding: 24px;
      overflow-y: auto;
    }

    .modal-loading {
      text-align: center;
      padding: 40px;
      color: var(--text-muted);
    }

    .student-badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 600;
    }

    .badge-active { background: #d1fae5; color: #065f46; }
    .badge-inactive { background: #fee2e2; color: #991b1b; }

    .modal-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 14px;
    }

    .modal-table th {
      text-align: left;
      padding: 12px;
      border-bottom: 2px solid var(--border);
      color: var(--text-muted);
      font-weight: 600;
      text-transform: uppercase;
      font-size: 11px;
      letter-spacing: 0.5px;
    }

    .modal-table td {
      padding: 12px;
      border-bottom: 1px solid var(--border);
      color: var(--text);
    }

    .modal-table tr:last-child td { border-bottom: none; }
    
    @media (max-width: 600px) {
        .col-hide-mobile { display: none; }
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
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 8px 12px;
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
      margin-left: 0;
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

    .btn-extend-due {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 8px 12px;
      background: #f59e0b;
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.2s ease;
      white-space: nowrap;
    }

    .btn-extend-due:hover {
      background: #d97706;
      transform: translateY(-1px);
      box-shadow: 0 4px 8px rgba(217, 119, 6, 0.2);
    }

    .btn-extend-due:active {
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
          <!-- Scanner Toggle Button -->
          <div style="display: flex; gap: 12px; margin-bottom: 24px;">
            <button onclick="toggleScanner()" class="btn-barcode-start"
              style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; font-size: 14px; text-decoration: none;">
              <iconify-icon icon="mdi:barcode-scan"></iconify-icon>
              <span id="scannerToggleText">Mulai Peminjaman Baru</span>
            </button>
          </div>

          <!-- Embedded Scanner Section -->
          <div id="scannerSection" class="card" style="display: none; border: 2px solid #667eea; background: #f8fbfe;">
              <div style="display: grid; grid-template-columns: 350px 1fr; gap: 24px;">
                  <!-- Left: Camera -->
                  <div>
                      <div id="reader" style="width: 100%; border-radius: 8px; overflow: hidden; background: #000;"></div>
                      <div id="scanStatus" style="margin-top: 10px; padding: 10px; border-radius: 6px; font-size: 13px; text-align: center; display: none;"></div>
                      
                      <div style="margin-top: 15px; display: flex; gap: 8px;">
                          <button id="btnModeBook" class="scanner-mode-btn active" onclick="setScanMode('book')">Mode Buku</button>
                          <button id="btnModeMember" class="scanner-mode-btn" onclick="setScanMode('member')">Mode Anggota</button>
                      </div>
                  </div>

                  <!-- Right: Transaction Details -->
                  <div>
                      <h3 style="margin-top: 0; display: flex; align-items: center; gap: 8px;">
                          <iconify-icon icon="mdi:basket-outline"></iconify-icon>
                          Keranjang Peminjaman
                      </h3>

                      <!-- Member Info -->
                      <div id="scannedMemberInfo" style="display: none; background: #ecf9f3; padding: 12px; border-radius: 6px; border-left: 4px solid #10B981; margin-bottom: 15px;">
                          <div style="font-size: 11px; color: #10B981; font-weight: 700; text-transform: uppercase;">Peminjam</div>
                          <div style="font-size: 15px; font-weight: 700; color: #333;">
                              <span id="scannedMemberName"></span>
                          </div>
                          <div style="font-size: 12px; color: #666;">NISN: <span id="scannedMemberNisn"></span></div>
                      </div>

                      <!-- Empty State -->
                      <div id="scanEmptyState" style="text-align: center; padding: 30px; color: #999; border: 2px dashed #ddd; border-radius: 8px;">
                          <iconify-icon icon="mdi:barcode" style="font-size: 32px; opacity: 0.5;"></iconify-icon>
                          <p style="margin: 10px 0 0; font-size: 13px;">Scan buku terlebih dahulu</p>
                      </div>

                      <!-- Book List -->
                      <div id="scannedBooksContainer" style="display: none;">
                          <table class="borrows-table" style="margin-bottom: 20px;">
                              <thead>
                                  <tr>
                                      <th style="width: 50px;">Cover</th>
                                      <th>Buku</th>
                                      <th style="width: 40px;"></th>
                                  </tr>
                              </thead>
                              <tbody id="scannedBooksList"></tbody>
                          </table>

                          <!-- Due Date -->
                          <div style="margin-bottom: 20px;">
                              <label style="display: block; font-size: 12px; font-weight: 600; color: #666; margin-bottom: 6px;">
                                  Tanggal Pengembalian
                              </label>
                              <input type="date" id="borrowDueDate" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                          </div>

                          <!-- Actions -->
                          <div style="display: flex; gap: 10px;">
                              <button onclick="submitBorrow()" id="btnSubmitBorrow" style="flex: 1; padding: 12px; background: #10B981; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">
                                  Konfirmasi Peminjaman
                              </button>
                             <button onclick="resetScannerSession()" style="padding: 12px; background: #fee2e2; color: #ef4444; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">
                                  Batal
                              </button>
                          </div>
                      </div>
                  </div>
              </div>
          </div>

          <!-- Loading Overlay (Local) -->
          <div id="scannerLoading" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; flex-direction: column; color: white;">
              <div class="spinner" style="width: 40px; height: 40px; border: 3px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 10px;"></div>
              <p>Memproses...</p>
          </div>

          <!-- Html5Qrcode Library -->
          <script src="https://unpkg.com/html5-qrcode"></script>

          <style>
              .scanner-mode-btn {
                  flex: 1;
                  padding: 8px;
                  border: 1px solid #ddd;
                  background: white;
                  border-radius: 4px;
                  cursor: pointer;
                  font-size: 12px;
                  font-weight: 600;
                  color: #666;
              }
              .scanner-mode-btn.active {
                  background: #3A7FF2;
                  color: white;
                  border-color: #3A7FF2;
              }
              .status-success { background: #ecf9f3; color: #10B981; }
              .status-error { background: #fee2e2; color: #ca2d2d; }
              .status-info { background: #e0f2fe; color: #0284c7; }
              
              @media (max-width: 768px) {
                  #scannerSection > div { grid-template-columns: 1fr; }
              }
          </style>

          <script>
            let html5QrcodeScanner = null;
            let currentScanMode = 'book';
            let currentMember = null;
            let scannedBooks = [];
            let isScanning = false;
            let lastScanTime = 0;
            const SCAN_COOLDOWN = 1500;

            function toggleScanner() {
                // If mobile, go to dedicated mobile scanner page
                if (window.innerWidth <= 768) {
                    window.location.href = 'scan-mobile.php';
                    return;
                }

                const section = document.getElementById('scannerSection');
                const btnText = document.getElementById('scannerToggleText');
                
                if (section.style.display === 'none') {
                    section.style.display = 'block';
                    btnText.textContent = 'Tutup Peminjaman';
                    initScanner();
                } else {
                    stopScanner();
                    section.style.display = 'none';
                    btnText.textContent = 'Mulai Peminjaman Baru';
                }
            }

            function initScanner() {
                if (html5QrcodeScanner) return;
                
                html5QrcodeScanner = new Html5Qrcode("reader");
                html5QrcodeScanner.start(
                    { facingMode: "environment" },
                    { fps: 10, qrbox: { width: 250, height: 150 } },
                    onScanSuccess,
                    (error) => {}
                ).catch(err => {
                    console.error("Error starting scanner", err);
                    showScanStatus("Gagal membuka kamera: " + err, 'error');
                });
                
                // Initialize Due Date (+7 days default)
                const date = new Date();
                date.setDate(date.getDate() + 7);
                document.getElementById('borrowDueDate').valueAsDate = date;
                
                setScanMode('book');
            }

            function stopScanner() {
                if (html5QrcodeScanner) {
                    html5QrcodeScanner.stop().then(() => {
                        html5QrcodeScanner.clear();
                        html5QrcodeScanner = null;
                    }).catch(err => console.error(err));
                }
            }

            function setScanMode(mode) {
                currentScanMode = mode;
                document.getElementById('btnModeBook').className = mode === 'book' ? 'scanner-mode-btn active' : 'scanner-mode-btn';
                document.getElementById('btnModeMember').className = mode === 'member' ? 'scanner-mode-btn active' : 'scanner-mode-btn';
                
                const status = mode === 'book' ? 'Mode: Scan Buku' : 'Mode: Scan Anggota';
                showScanStatus(status, 'info');
            }

            function showScanStatus(msg, type) {
                const el = document.getElementById('scanStatus');
                el.textContent = msg;
                el.className = 'status-' + type;
                el.style.display = 'block';
                
                if (type === 'success') {
                    setTimeout(() => { el.style.display = 'none'; }, 3000);
                }
            }

            function onScanSuccess(decodedText, decodedResult) {
                const now = Date.now();
                if (now - lastScanTime < SCAN_COOLDOWN) return;
                lastScanTime = now;

                processScannedCode(decodedText.trim());
            }

            function parseBarcode(raw) {
                // Common cleaning
                const patterns = [/^(?:NISN|nisn|ID|id)[:=]?(.+)$/, /^(?:ISBN|isbn)[:=]?(.+)$/];
                for (let p of patterns) {
                    const m = raw.match(p);
                    if (m && m[1]) return m[1].trim();
                }
                return raw;
            }

            async function processScannedCode(rawCode) {
                const code = parseBarcode(rawCode);
                
                document.getElementById('scannerLoading').style.display = 'flex';
                
                try {
                    const res = await fetch('api/process-barcode.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({barcode: code})
                    });
                    const data = await res.json();
                    
                    if (!data.success) {
                        showScanStatus('Data tidak ditemukan', 'error');
                        document.getElementById('scannerLoading').style.display = 'none';
                        return;
                    }

                    const item = data.data;

                    if (currentScanMode === 'book') {
                        if (item.type === 'member') {
                            // Auto switch to member mode
                            currentMember = item;
                            updateMemberDisplay();
                            setScanMode('book'); // Switch back to book to continue scanning books? Or maybe stay? Borrowing flow usually implies books first then member.
                            showScanStatus('Anggota terdeteksi: ' + item.name, 'success');
                        } else {
                            addBookToCart(item);
                            showScanStatus('Buku ditambahkan: ' + item.name, 'success');
                        }
                    } else { // Member mode
                        if (item.type === 'book') {
                            addBookToCart(item);
                            showScanStatus('Buku ditambahkan (Auto switch to Book Mode)', 'info');
                            setScanMode('book');
                        } else {
                            currentMember = item;
                            updateMemberDisplay();
                            showScanStatus('Anggota di-set: ' + item.name, 'success');
                            setScanMode('book');
                        }
                    }

                } catch (e) {
                    showScanStatus('Error: ' + e.message, 'error');
                }
                
                document.getElementById('scannerLoading').style.display = 'none';
            }

            function addBookToCart(book) {
                if (scannedBooks.some(b => b.id === book.id)) {
                    showScanStatus('Buku sudah ada di keranjang', 'error');
                    return;
                }
                scannedBooks.push(book);
                updateCartDisplay();
            }

            function removeBookFromCart(index) {
                scannedBooks.splice(index, 1);
                updateCartDisplay();
            }

            function updateMemberDisplay() {
                const el = document.getElementById('scannedMemberInfo');
                if (currentMember) {
                    el.style.display = 'block';
                    document.getElementById('scannedMemberName').textContent = currentMember.name;
                    document.getElementById('scannedMemberNisn').textContent = currentMember.barcode;
                    
                    // Highlight logic if needed
                } else {
                    el.style.display = 'none';
                }
                updateCartDisplay(); // To check submit button state
            }

            function escapeHtml(text) {
                if (!text) return text;
                return text
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }

            function updateCartDisplay() {
                const list = document.getElementById('scannedBooksList');
                const empty = document.getElementById('scanEmptyState');
                const container = document.getElementById('scannedBooksContainer');
                
                if (scannedBooks.length === 0) {
                    empty.style.display = 'block';
                    container.style.display = 'none';
                } else {
                    empty.style.display = 'none';
                    container.style.display = 'block';
                    
                    list.innerHTML = scannedBooks.map((b, i) => `
                        <tr>
                            <td>
                                ${b.cover_image 
                                  ? `<img src="../img/covers/${escapeHtml(b.cover_image)}" style="width: 40px; height: 60px; object-fit: cover; border-radius: 4px;">`
                                  : `<div style="width: 40px; height: 60px; background: #eee; display: flex; align-items: center; justify-content: center; border-radius: 4px;"><small>No Img</small></div>`
                                }
                            </td>
                            <td>
                                <div style="font-weight: 600; font-size: 13px;">${escapeHtml(b.name)}</div>
                                <div style="font-size: 11px; color: #888;">${escapeHtml(b.barcode)}</div>
                            </td>
                            <td>
                                <button onclick="removeBookFromCart(${i})" style="color: #ef4444; background: none; border: none; cursor: pointer;">
                                    <iconify-icon icon="mdi:close-circle" style="font-size: 18px;"></iconify-icon>
                                </button>
                            </td>
                        </tr>
                    `).join('');
                }
                
                // validate submit button
                 const btn = document.getElementById('btnSubmitBorrow');
                 if (scannedBooks.length > 0 && currentMember) {
                     btn.disabled = false;
                     btn.style.opacity = 1;
                     btn.textContent = `Pinjam ${scannedBooks.length} Buku`;
                 } else {
                     btn.disabled = true;
                     btn.style.opacity = 0.6;
                     if (!currentMember) btn.textContent = 'Scan Anggota Dulu';
                     else btn.textContent = 'Scan Buku Dulu';
                 }
            }

            function resetScannerSession() {
                scannedBooks = [];
                currentMember = null;
                updateMemberDisplay();
                updateCartDisplay();
                showScanStatus('Sesi direset', 'info');
            }

            async function submitBorrow() {
                if (!currentMember || scannedBooks.length === 0) return;
                
                const dueDate = document.getElementById('borrowDueDate').value;
                if (!dueDate) {
                    alert('Pilih tanggal pengembalian');
                    return;
                }

                document.getElementById('scannerLoading').style.display = 'flex';

                try {
                    const payload = {
                        borrows: scannedBooks.map(b => ({
                            member_id: currentMember.id,
                            book_id: b.id
                        })),
                        due_date: dueDate + ' 23:59:59'
                    };

                    const res = await fetch('api/submit-borrow.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(payload)
                    });
                    
                    const result = await res.json();
                    
                    if (result.success) {
                        alert('Peminjaman berhasil dicatat!');
                        location.reload(); 
                    } else {
                        alert('Gagal: ' + (result.message || 'Error saving'));
                    }

                } catch (e) {
                    console.error(e);
                    alert('Error submitting borrow');
                }
                
                document.getElementById('scannerLoading').style.display = 'none';
            }
          </script>
    <script>
        function filterStudents() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const items = document.querySelectorAll('.search-item');
            let visibleCount = 0;

            items.forEach(item => {
                const searchText = item.getAttribute('data-search');
                if (searchText.includes(input)) {
                    item.style.display = '';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            // Optional: Show a message if no results found
            // You can add this functionality if needed
        }

        function printBarcode(memberId, memberName) {
            const win = window.open(`api/generate-student-barcode.php?member_id=${memberId}`, '_blank');
            if (win) {
                win.addEventListener('load', function () {
                    setTimeout(() => {
                        win.print();
                    }, 250);
                });
            }
        }

        // Add keyboard shortcut for search
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('searchInput').addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    this.value = '';
                    filterStudents();
                }
            });

            // Focus search on Ctrl/Cmd + K
            document.addEventListener('keydown', function (e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    document.getElementById('searchInput').focus();
                }
            });
        });
    </script>
    
    <!-- Stats Modal -->
    <div class="modal-overlay" id="statsModal">
        <div class="modal-container">
            <div class="modal-header">
                <h2>Detail Data</h2>
                <button class="modal-close" type="button">×</button>
            </div>
            <div class="modal-body">
                <div class="modal-loading">Memuat data...</div>
            </div>
        </div>
    </div>

    <script src="../assets/js/borrows-stats.js"></script>
          <!-- Statistics Section -->
          <div class="stats-section">
            <div class="stat-card clickable" data-stat-type="total" title="Klik untuk melihat detail">
              <div class="stat-label">Total Peminjaman</div>
              <div class="stat-value"><?= $totalBorrows ?></div>
            </div>
            <div class="stat-card clickable" data-stat-type="active" title="Klik untuk melihat detail">
              <div class="stat-label">Sedang Dipinjam</div>
              <div class="stat-value"><?= $activeBorrows ?></div>
            </div>
            <div class="stat-card clickable" data-stat-type="overdue" title="Klik untuk melihat detail">
              <div class="stat-label">Terlambat</div>
              <div class="stat-value"><?= $overdueBorrows ?></div>
            </div>
            <div class="stat-card clickable" data-stat-type="pending_confirmation" title="Klik untuk melihat detail">
              <div class="stat-label">Form Menunggu Konfirmasi</div>
              <div class="stat-value"><?= $pendingConfirmation ?></div>
            </div>
            <div class="stat-card clickable" data-stat-type="pending_return" title="Klik untuk melihat detail">
              <div class="stat-label">Pengembalian Menunggu Konfirmasi</div>
              <div class="stat-value"><?= $pendingReturns ?></div>
            </div>
          </div>

          <!-- Realtime Scan Form -->
          <div class="card">
            <h2>Form Peminjaman Menunggu Konfirmasi</h2>
            <p style="color: var(--text-muted); margin-bottom: 20px; font-size: 14px;">
              Data peminjaman dari siswa yang menunggu konfirmasi admin
            </p>

            <?php $pendingConfirm = array_filter($borrows, fn($b) => $b['status'] === 'pending_confirmation'); ?>

            <?php if (empty($pendingConfirm)): ?>
              <div class="empty-state">
                <iconify-icon icon="mdi:inbox-outline"></iconify-icon>
                <p>Tidak ada peminjaman yang menunggu konfirmasi</p>
              </div>
            <?php else: ?>
              <?php
              // Group by member_id
              $groupedByMember = [];
              foreach ($pendingConfirm as $b) {
                if (!isset($groupedByMember[$b['member_id']])) {
                  $groupedByMember[$b['member_id']] = [
                    'member_name' => $b['member_name'],
                    'member_id' => $b['member_id'],
                    'nisn' => $b['nisn'],
                    'books' => []
                  ];
                }
                $groupedByMember[$b['member_id']]['books'][] = $b;
              }
              ?>

              <?php foreach ($groupedByMember as $studentId => $studentData): ?>
                <div
                  style="background: white; border: 2px solid #E0EFF9; border-radius: 12px; padding: 0; margin-bottom: 24px; overflow: hidden; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);">

                  <!-- Header dengan Warna Biru Langit -->
                  <div style="background: #3A7FF2; padding: 20px 24px; color: white;">
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px;">
                      <div>
                        <div
                          style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.95; margin-bottom: 10px;">
                          <iconify-icon icon="mdi:account"
                            style="vertical-align: middle; margin-right: 6px;"></iconify-icon> Nama Siswa
                        </div>
                        <div style="font-size: 17px; font-weight: 700; word-break: break-word;">
                          <?= htmlspecialchars($studentData['member_name']) ?>
                        </div>
                      </div>
                      <div>
                        <div
                          style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.95; margin-bottom: 10px;">
                          <iconify-icon icon="mdi:card-account-details"
                            style="vertical-align: middle; margin-right: 6px;"></iconify-icon> NISN
                        </div>
                        <div style="font-size: 17px; font-weight: 700;"><?= htmlspecialchars($studentData['nisn']) ?></div>
                      </div>
                      <div>
                        <div
                          style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.95; margin-bottom: 10px;">
                          <iconify-icon icon="mdi:book-multiple"
                            style="vertical-align: middle; margin-right: 6px;"></iconify-icon> Total Buku
                        </div>
                        <div style="font-size: 17px; font-weight: 700;"><?= count($studentData['books']) ?> Buku</div>
                      </div>
                      <div>
                        <div
                          style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.95; margin-bottom: 10px;">
                          <iconify-icon icon="mdi:calendar-clock"
                            style="vertical-align: middle; margin-right: 6px;"></iconify-icon> Waktu Scan
                        </div>
                        <div style="font-size: 17px; font-weight: 700;">
                          <?= date('d/m H:i', strtotime($studentData['books'][0]['borrowed_at'])) ?>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Books List Section -->
                  <div style="padding: 24px;">
                    <h3
                      style="font-size: 13px; font-weight: 700; color: #333; margin: 0 0 20px 0; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center;">
                      <div style="display: flex; align-items: center;">
                        <input type="checkbox" id="selectAll_<?= $studentId ?>" onchange="toggleSelectAll('<?= $studentId ?>', this)" style="margin-right: 8px; width: 16px; height: 16px; cursor: pointer;" checked>
                        <iconify-icon icon="mdi:book-open" style="margin-right: 8px; color: #5BA3F5;"></iconify-icon>
                        Daftar Buku yang Dipinjam
                      </div>
                    </h3>
                    <div style="display: grid; grid-template-columns: 1fr; gap: 14px;">
                      <?php foreach ($studentData['books'] as $idx => $book): ?>
                        <div
                          style="background: white; border: 1px solid #E0EFF9; border-left: 4px solid #5BA3F5; border-radius: 8px; padding: 16px; display: grid; grid-template-columns: 1fr 140px 100px; gap: 16px; align-items: center;">
                          <div style="display: flex; align-items: center; gap: 12px;">
                            <input type="checkbox" class="book-checkbox-<?= $studentId ?>" value="<?= $book['id'] ?>" style="width: 18px; height: 18px; cursor: pointer;" checked>
                            <div>
                                <div
                                style="font-size: 11px; color: #5BA3F5; font-weight: 700; margin-bottom: 8px; text-transform: uppercase;">
                                Buku #<?= $idx + 1 ?></div>
                                <div style="font-size: 14px; font-weight: 600; color: #333;">
                                <?= htmlspecialchars($book['title']) ?>
                                </div>
                            </div>
                          </div>
                          <div style="text-align: center;">
                            <div
                              style="font-size: 11px; color: #999; margin-bottom: 6px; text-transform: uppercase; font-weight: 600;">
                              ISBN</div>
                            <div style="font-size: 14px; font-weight: 700; color: #333;">
                              <?= htmlspecialchars($book['isbn']) ?>
                            </div>
                          </div>
                          <div style="text-align: center;">
                            <?php if (!empty($book['cover_image'])): ?>
                                <img src="../img/covers/<?= htmlspecialchars($book['cover_image']) ?>" style="width: 40px; height: 60px; object-fit: cover; border-radius: 4px; display: inline-block;">
                            <?php else: ?>
                                <span style="display: inline-block; width: 40px; height: 60px; background: #eee; border-radius: 4px; text-align: center; line-height: 60px; font-size: 10px; color: #999;">No Img</span>
                            <?php endif; ?>
                            <div style="margin-top: 5px;">
                                <span
                                style="display: inline-block; background: #FFF3E0; color: #F57C00; padding: 6px 12px; border-radius: 4px; font-size: 11px; font-weight: 700;">Menunggu</span>
                            </div>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>

                  <!-- Action Buttons -->
                  <div
                    style="padding: 20px 24px; background: #F8FBFF; border-top: 1px solid #E0EFF9; display: grid; grid-template-columns: 200px 1fr auto auto; gap: 16px; align-items: end;">
                    <div>
                      <label
                        style="display: block; font-size: 11px; font-weight: 700; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
                        <iconify-icon icon="mdi:calendar" style="vertical-align: middle; margin-right: 4px;"></iconify-icon>
                        Tenggat (Hari)
                      </label>
                      <input type="number" id="dueDays_<?= $studentId ?>" value="7" min="1" max="365"
                        style="width: 100%; padding: 10px 12px; border: 2px solid #5BA3F5; border-radius: 6px; font-size: 14px; font-weight: 600; color: #5BA3F5; box-sizing: border-box;">
                    </div>
                    <div></div>
                    <?php
                    $bookIds = array_map(fn($b) => $b['id'], $studentData['books']);
                    $bookIdsJson = json_encode($bookIds);
                    $bookIdsHtml = htmlspecialchars($bookIdsJson);
                    ?>
                    <button type="button"
                      onclick="approveSelectedBorrows('<?= $studentId ?>')"
                      style="background: #5BA3F5; padding: 12px 20px; border: none; border-radius: 6px; color: white; font-weight: 700; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px; font-size: 14px;">
                      <iconify-icon icon="mdi:check-circle"></iconify-icon> Terima
                    </button>
                    <button type="button" onclick="rejectAllBorrow('<?= $bookIdsHtml ?>')"
                      style="background: #FF6B6B; padding: 12px 20px; border: none; border-radius: 6px; color: white; font-weight: 700; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px; font-size: 14px;">
                      <iconify-icon icon="mdi:close-circle"></iconify-icon> Tolak
                    </button>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
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
            <?php if (empty(array_filter($borrows, fn($b) => $b['status'] !== 'returned' && $b['status'] !== 'pending_return' && $b['status'] !== 'pending_confirmation'))): ?>
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
                    if ($br['status'] === 'returned' || $br['status'] === 'pending_return' || $br['status'] === 'pending_confirmation')
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
                        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                          <button type="button"
                            onclick="extendDueDate(<?= $br['id'] ?>, '<?= htmlspecialchars($br['title']) ?>')"
                            class="btn-extend-due"
                            style="display: inline-flex; align-items: center; gap: 4px; padding: 8px 12px; background: #f59e0b; color: white; border: none; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; white-space: nowrap;">
                            <iconify-icon icon="mdi:calendar-plus" style="vertical-align: middle;"></iconify-icon>
                            Perpanjang
                          </button>
                          <a href="borrows.php?action=return&id=<?= $br['id'] ?>" class="btn-return" style="display: inline-flex; align-items: center; gap: 4px; padding: 8px 12px; background: var(--success); color: white; border: none; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: none; transition: all 0.2s ease; white-space: nowrap; margin-left: 0;">
                            <iconify-icon icon="mdi:check" style="vertical-align: middle;"></iconify-icon>
                            Kembali
                          </a>
                        </div>
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
    // Approve/Reject Borrow
    // ========================================================================

    function approveBorrow(borrowId) {
      if (!confirm('Terima peminjaman ini?')) {
        return;
      }

      fetch('api/approve-borrow.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'borrow_id=' + borrowId
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('Peminjaman telah diterima!');
            location.reload();
          } else {
            alert(data.message || 'Gagal menerima peminjaman');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Terjadi kesalahan');
        });
    }

    function approveAllBorrow(borrowIds) {
      if (!confirm('Terima SEMUA peminjaman siswa ini?')) {
        return;
      }

      const ids = JSON.parse(borrowIds);
      let approved = 0;
      let failed = 0;

      // Approve each borrow sequentially
      Promise.all(ids.map(id =>
        fetch('api/approve-borrow.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'borrow_id=' + id
        })
          .then(r => r.json())
          .then(data => {
            if (data.success) approved++;
            else failed++;
          })
          .catch(() => failed++)
      )).then(() => {
        if (failed === 0) {
          alert(`${approved} peminjaman telah diterima!`);
          location.reload();
        } else {
          alert(`${approved} diterima, ${failed} gagal`);
          location.reload();
        }
      });
    }

    function approveAllBorrowWithDue(borrowIds, inputId) {
      console.log('[APPROVE] Starting with borrowIds:', borrowIds, 'inputId:', inputId);

      let ids;

      // Handle both string and object
      if (typeof borrowIds === 'string') {
        try {
          // Check if it's already a JSON string
          ids = JSON.parse(borrowIds);
          console.log('[APPROVE] Parsed JSON string:', ids);
        } catch (e) {
          console.error('[APPROVE] JSON parse error:', e.message);
          alert('Error: Format data peminjaman tidak valid.\n\nDetail: ' + e.message);
          return;
        }
      } else if (Array.isArray(borrowIds)) {
        ids = borrowIds;
        console.log('[APPROVE] Already an array:', ids);
      } else {
        console.error('[APPROVE] Invalid borrowIds type:', typeof borrowIds, borrowIds);
        alert('Error: Tipe data peminjaman tidak valid (expected string atau array)');
        return;
      }

      if (!Array.isArray(ids) || ids.length === 0) {
        console.error('[APPROVE] IDs is not valid array or empty:', ids);
        alert('Error: Data peminjaman kosong. Silakan refresh halaman.');
        return;
      }

      const inputElement = document.getElementById(inputId);
      console.log('[APPROVE] Input element:', inputElement, 'Value:', inputElement?.value);

      if (!inputElement) {
        console.error('[APPROVE] Input element not found with ID:', inputId);
        alert('Error: Input tenggat tidak ditemukan dengan ID ' + inputId);
        return;
      }

      const dueDays = parseInt(inputElement.value, 10) || 7;
      console.log('[APPROVE] Parsed dueDays:', dueDays);

      if (dueDays < 1 || dueDays > 365) {
        alert('Tenggat harus antara 1-365 hari');
        return;
      }

      if (!confirm(`Terima SEMUA peminjaman siswa ini dengan tenggat ${dueDays} hari?`)) {
        console.log('[APPROVE] User cancelled');
        return;
      }

      const dueDate = new Date();
      dueDate.setDate(dueDate.getDate() + dueDays);

      // Format: YYYY-MM-DD HH:MM:SS
      const dueString = dueDate.toISOString().slice(0, 10) + ' ' + dueDate.toTimeString().slice(0, 8);

      console.log('[APPROVE] Due date calculated:', dueString, 'for', dueDays, 'days');

      let approved = 0;
      let failed = 0;
      const errors = [];

      // Approve each borrow with custom due date
      Promise.all(ids.map(id => {
        console.log('[APPROVE] Processing ID:', id);
        return fetch('api/approve-borrow.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'borrow_id=' + id + '&due_at=' + encodeURIComponent(dueString)
        })
          .then(r => {
            console.log('[APPROVE] Response status:', r.status, 'for ID:', id);
            return r.json().then(data => ({
              status: r.status,
              data: data,
              id: id
            }));
          })
          .then(result => {
            console.log('[APPROVE] Response data for ID', result.id, ':', result.data);
            if (result.data.success) {
              approved++;
            } else {
              failed++;
              errors.push(`ID ${result.id}: ${result.data.message}`);
            }
          })
          .catch(err => {
            console.error('[APPROVE] Error for ID', id, ':', err);
            failed++;
            errors.push(`ID ${id}: ${err.message}`);
          })
      })).then(() => {
        console.log('[APPROVE] Complete - Approved:', approved, 'Failed:', failed);
        if (failed === 0) {
          alert(`✓ ${approved} peminjaman telah diterima!\nTenggat: ${dueString}`);
          location.reload();
        } else {
          const errorMsg = errors.length > 0 ? errors.slice(0, 3).join('\n') : 'Unknown error';
          alert(`${approved} diterima, ${failed} gagal\n\n${errorMsg}`);
          location.reload();
        }
      });
    }

    function rejectAllBorrow(borrowIds) {
      console.log('[REJECT] Starting with borrowIds:', borrowIds);

      let ids;

      // Handle both string and object
      if (typeof borrowIds === 'string') {
        try {
          ids = JSON.parse(borrowIds);
          console.log('[REJECT] Parsed JSON string:', ids);
        } catch (e) {
          console.error('[REJECT] JSON parse error:', e.message);
          alert('Error: Format data peminjaman tidak valid.\n\nDetail: ' + e.message);
          return;
        }
      } else if (Array.isArray(borrowIds)) {
        ids = borrowIds;
        console.log('[REJECT] Already an array:', ids);
      } else {
        console.error('[REJECT] Invalid borrowIds type:', typeof borrowIds, borrowIds);
        alert('Error: Tipe data peminjaman tidak valid (expected string atau array)');
        return;
      }

      if (!Array.isArray(ids) || ids.length === 0) {
        console.error('[REJECT] IDs is not valid array or empty:', ids);
        alert('Error: Data peminjaman kosong. Silakan refresh halaman.');
        return;
      }

      if (!confirm(`Tolak SEMUA ${ids.length} peminjaman siswa ini?`)) {
        console.log('[REJECT] User cancelled');
        return;
      }

      console.log('[REJECT] IDs:', ids);

      let rejected = 0;
      let failed = 0;
      const errors = [];

      // Reject each borrow
      Promise.all(ids.map(id => {
        console.log('[REJECT] Processing ID:', id);
        return fetch('api/reject-borrow.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'borrow_id=' + id
        })
          .then(r => {
            console.log('[REJECT] Response status:', r.status, 'for ID:', id);
            return r.json().then(data => ({
              status: r.status,
              data: data,
              id: id
            }));
          })
          .then(result => {
            console.log('[REJECT] Response data for ID', result.id, ':', result.data);
            if (result.data.success) {
              rejected++;
            } else {
              failed++;
              errors.push(`ID ${result.id}: ${result.data.message}`);
            }
          })
          .catch(err => {
            console.error('[REJECT] Error for ID', id, ':', err);
            failed++;
            errors.push(`ID ${id}: ${err.message}`);
          })
      })).then(() => {
        console.log('[REJECT] Complete - Rejected:', rejected, 'Failed:', failed);
        if (failed === 0) {
          alert(`✓ ${rejected} peminjaman telah ditolak!`);
          location.reload();
        } else {
          const errorMsg = errors.length > 0 ? errors.slice(0, 3).join('\n') : 'Unknown error';
          alert(`${rejected} ditolak, ${failed} gagal\n\n${errorMsg}`);
          location.reload();
        }
      });
    }

    // ========================================================================
    // Extend Due Date Function
    // ========================================================================

    function extendDueDate(borrowId, bookTitle) {
      const days = prompt(`Perpanjang tenggat untuk "${bookTitle}":\n\nMasukkan jumlah hari perpanjangan (1-365):`, '7');

      if (days === null) {
        return; // User cancelled
      }

      const daysInt = parseInt(days, 10);

      if (isNaN(daysInt) || daysInt < 1 || daysInt > 365) {
        alert('Jumlah hari harus antara 1-365');
        return;
      }

      // Send request to extend due date
      fetch('api/extend-due-date.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'borrow_id=' + borrowId + '&extend_days=' + daysInt
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert(`✓ Tenggat waktu diperpanjang!\n\nBuku: ${data.book_title}\nSiswa: ${data.member_name}\nTenggat Baru: ${data.new_due_date}`);
            location.reload();
          } else {
            alert('❌ Gagal memperpanjang tenggat:\n' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Terjadi kesalahan saat memperpanjang tenggat');
        });
    }

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
    function toggleSelectAll(studentId, source) {
        const checkboxes = document.querySelectorAll('.book-checkbox-' + studentId);
        for(let i=0; i<checkboxes.length; i++) {
            checkboxes[i].checked = source.checked;
        }
    }

    function approveSelectedBorrows(studentId) {
        const checkboxes = document.querySelectorAll('.book-checkbox-' + studentId + ':checked');
        if (checkboxes.length === 0) {
            alert('Pilih setidaknya satu buku untuk disetujui.');
            return;
        }

        const selectedIds = [];
        checkboxes.forEach((cb) => {
            selectedIds.push(cb.value);
        });

        // Call the original function with selected IDs
        approveAllBorrowWithDue(JSON.stringify(selectedIds), 'dueDays_' + studentId);
    }
  </script>

</body>

</html>