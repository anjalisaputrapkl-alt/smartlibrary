<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];

// Handle return confirmation
if (isset($_GET['action']) && $_GET['action'] === 'return' && isset($_GET['id'])) {
  $pdo->beginTransaction();
  try {
    // 1. Get book_id AND due_at
    $stmt = $pdo->prepare('SELECT book_id, due_at FROM borrows WHERE id=:id AND school_id=:sid');
    $stmt->execute(['id' => (int) $_GET['id'], 'sid' => $sid]);
    $borrowData = $stmt->fetch();
    
    if ($borrowData) {
      // Calculate final fine
      $schoolStmt = $pdo->prepare('SELECT late_fine FROM schools WHERE id = :sid');
      $schoolStmt->execute(['sid' => $sid]);
      $late_fine = (int) ($schoolStmt->fetchColumn() ?: 500);
      
      $fineAmount = 0;
      if ($borrowData['due_at']) {
        $dueDate = new DateTime($borrowData['due_at']);
        $now = new DateTime();
        if ($now > $dueDate) {
            $diff = $now->diff($dueDate);
            $daysLate = $diff->days;
            $fineAmount = $daysLate * $late_fine;
        }
      }

      // 2. Update borrows
      $stmt = $pdo->prepare(
        'UPDATE borrows SET returned_at=NOW(), status="returned", fine_amount=:fine
         WHERE id=:id AND school_id=:sid'
      );
      $stmt->execute(['id' => (int) $_GET['id'], 'sid' => $sid, 'fine' => $fineAmount]);

      // 3. Update stock
      $stmt = $pdo->prepare('UPDATE books SET copies = 1 WHERE id = :bid');
      $stmt->execute(['bid' => $borrowData['book_id']]);
      
      $pdo->commit();
    } else {
      $pdo->rollBack();
    }
  } catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
  }
  header('Location: borrows.php');
  exit;
}

// Update overdue status and Calculate Fines
$schoolStmt = $pdo->prepare('SELECT late_fine FROM schools WHERE id = :sid');
$schoolStmt->execute(['sid' => $sid]);
$late_fine = (int) ($schoolStmt->fetchColumn() ?: 500);

// 1. Mark overdue
$pdo->prepare(
  'UPDATE borrows SET status="overdue"
   WHERE school_id=:sid AND returned_at IS NULL AND due_at < NOW() AND status != "overdue"'
)->execute(['sid' => $sid]);

// 2. Calculate fines (Hanya untuk yang belum dikembalikan dan sudah lewat jatuh tempo)
// Fine = (Now - DueDate in Days) * late_fine
if ($late_fine > 0) {
    $pdo->prepare(
      'UPDATE borrows 
       SET fine_amount = GREATEST(0, DATEDIFF(NOW(), due_at)) * :fine
       WHERE school_id=:sid 
       AND returned_at IS NULL 
       AND due_at < NOW()'
    )->execute(['sid' => $sid, 'fine' => $late_fine]);
}

// Get all borrowing data
$stmt = $pdo->prepare(
  'SELECT b.*, bk.title, bk.cover_image, bk.isbn, bk.max_borrow_days, m.name AS member_name, m.nisn
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
</head>

<body>
  <?php require __DIR__ . '/partials/sidebar.php'; ?>

  <div class="app">

    <div class="topbar">
      <div class="topbar-title">
        <iconify-icon icon="mdi:book-clock-outline" style="font-size: 24px; color: var(--primary);"></iconify-icon>
        <strong>Manajemen Peminjaman</strong>
      </div>
      <div class="topbar-actions">
        <!-- Future notification/user icons can go here -->
      </div>
    </div>

    <div class="content">
      <div class="main">
        <div>
          <!-- Scanner Toggle Button -->
          <div class="scanner-toggle-wrap">
            <button onclick="toggleScanner()" class="btn-barcode-start">
              <iconify-icon icon="mdi:barcode-scan"></iconify-icon>
              <span id="scannerToggleText">Mulai Peminjaman Baru</span>
            </button>
          </div>

          <!-- Embedded Scanner Section -->
          <div id="scannerSection" class="card scanner-section">
              <div class="scanner-grid">
                  <!-- Left: Camera -->
                  <div>
                      <div id="reader"></div>
                      <div id="scanStatus"></div>
                      
                      <div class="scanner-controls">
                          <button id="btnModeBook" class="scanner-mode-btn active" onclick="setScanMode('book')">Mode Buku</button>
                          <button id="btnModeMember" class="scanner-mode-btn" onclick="setScanMode('member')">Mode Anggota</button>
                      </div>
                  </div>

                  <!-- Right: Transaction Details -->
                  <div>
                      <h2 class="flex-center gap-2">
                          <iconify-icon icon="mdi:basket-outline" style="font-size: 20px;"></iconify-icon>
                          Keranjang Peminjaman
                      </h2>

                      <!-- Member Info -->
                      <div id="scannedMemberInfo" class="scanned-info-card">
                          <div class="scanned-info-label">Peminjam</div>
                          <div class="scanned-info-value">
                              <span id="scannedMemberName"></span>
                          </div>
                          <div class="scanned-info-meta">NISN: <span id="scannedMemberNisn"></span></div>
                      </div>

                      <!-- Empty State -->
                      <div id="scanEmptyState" class="scanner-empty-state">
                          <iconify-icon icon="mdi:barcode"></iconify-icon>
                          <p>Scan buku atau anggota untuk memulai</p>
                      </div>

                      <!-- Book List -->
                      <div id="scannedBooksContainer" style="display: none;">
                          <div class="borrows-table-wrap mb-4">
                              <table class="borrows-table">
                                  <thead>
                                      <tr>
                                          <th style="width: 60px;">Cover</th>
                                          <th>Buku</th>
                                          <th style="width: 40px;"></th>
                                      </tr>
                                  </thead>
                                  <tbody id="scannedBooksList"></tbody>
                              </table>
                          </div>

                          <!-- Due Date -->
                          <div class="form-group">
                              <label>Tanggal Pengembalian</label>
                              <input type="date" id="borrowDueDate">
                          </div>

                          <!-- Actions -->
                          <div class="action-grid">
                              <button onclick="submitBorrow()" id="btnSubmitBorrow" class="btn primary" style="flex: 1; justify-content: center;">
                                  Konfirmasi Peminjaman
                              </button>
                             <button onclick="resetScannerSession()" class="btn" style="color: var(--danger); border-color: color-mix(in srgb, var(--danger), transparent 70%);">
                                  Batal
                              </button>
                          </div>
                      </div>
                  </div>
              </div>
          </div>

          <!-- Loading Overlay (Local) -->
          <div id="scannerLoading" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: var(--overlay); z-index: 9999; align-items: center; justify-content: center; flex-direction: column; color: white; backdrop-filter: blur(4px);">
              <div class="spinner" style="width: 40px; height: 40px; border: 3px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 10px;"></div>
              <p>Memproses...</p>
          </div>

          <script>
            let html5QrcodeScanner = null;
            let currentScanMode = 'book';
            let currentMember = null;
            let scannedBooks = [];
            let isScanning = false;
            let lastScanTime = 0;
            const SCAN_COOLDOWN = 1500;

            function toggleScanner() {
                // Robust mobile detection
                const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Mobile/i.test(navigator.userAgent) || window.innerWidth <= 1024;

                // If mobile, go to dedicated mobile scanner page
                if (isMobile) {
                    console.log('Detected mobile device, redirecting to scan-mobile.php');
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
                            
                             // VALIDATE EXISTING CART (Fix for Bug #816)
                             if (currentMember.role === 'student') {
                                const validBooks = [];
                                let removedCount = 0;
                                scannedBooks.forEach(b => {
                                    if (b.access_level === 'teacher_only') {
                                        removedCount++;
                                    } else {
                                        validBooks.push(b);
                                    }
                                });
                                
                                if (removedCount > 0) {
                                    scannedBooks = validBooks;
                                    alert(`Peringatan: ${removedCount} buku dihapus dari keranjang karena khusus Guru/Karyawan.`);
                                }
                            }

                            updateMemberDisplay();
                            setScanMode('book'); // Switch back to book to continue scanning books? Or maybe stay? Borrowing flow usually implies books first then member.
                            showScanStatus('Anggota terdeteksi: ' + item.name, 'success');
                        } else {
                            // Validate against current member if exists
                            if (currentMember && currentMember.role === 'student' && item.access_level === 'teacher_only') {
                                showScanStatus('Buku KHUSUS GURU! Tidak bisa dipinjam siswa.', 'error');
                                alert('PERINGATAN: Buku ini khusus untuk Guru & Karyawan!');
                                return;
                            }

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
                            
                            // VALIDATE EXISTING CART
                            if (currentMember.role === 'student') {
                                const validBooks = [];
                                let removedCount = 0;
                                scannedBooks.forEach(b => {
                                    if (b.access_level === 'teacher_only') {
                                        removedCount++;
                                    } else {
                                        validBooks.push(b);
                                    }
                                });
                                
                                if (removedCount > 0) {
                                    scannedBooks = validBooks;
                                    alert(`Peringatan: ${removedCount} buku dihapus dari keranjang karena khusus Guru/Karyawan.`);
                                }
                            }

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
                    
                    if (result.success && result.inserted > 0) {
                        let msg = result.message;
                        if (result.errors && result.errors.length > 0) {
                            msg += '\n\nPeringatan beberapa item gagal:\n' + result.errors.join('\n');
                        }
                        alert(msg);
                        location.reload(); 
                    } else if (result.success && result.inserted === 0) {
                         // All failed
                         const errorMsg = result.errors && result.errors.length > 0 ? result.errors.join('\n') : result.message;
                         alert('Gagal meminjam:\n' + errorMsg);
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
          <div class="stats-grid">
            <div class="stat-card clickable" data-stat-type="total" title="Klik untuk melihat detail">
              <div class="stat-icon blue">
                <iconify-icon icon="mdi:book-open-page-variant"></iconify-icon>
              </div>
              <div class="stat-content">
                <div class="stat-label">Total Peminjaman</div>
                <div class="stat-value"><?= number_format($totalBorrows) ?></div>
              </div>
            </div>

            <div class="stat-card clickable" data-stat-type="active" title="Klik untuk melihat detail">
              <div class="stat-icon blue">
                <iconify-icon icon="mdi:clock-outline"></iconify-icon>
              </div>
              <div class="stat-content">
                <div class="stat-label">Sedang Dipinjam</div>
                <div class="stat-value"><?= number_format($activeBorrows) ?></div>
              </div>
            </div>

            <div class="stat-card clickable" data-stat-type="overdue" title="Klik untuk melihat detail">
              <div class="stat-icon red">
                <iconify-icon icon="mdi:alert-circle-outline"></iconify-icon>
              </div>
              <div class="stat-content">
                <div class="stat-label">Terlambat</div>
                <div class="stat-value"><?= number_format($overdueBorrows) ?></div>
              </div>
            </div>

            <div class="stat-card clickable" data-stat-type="pending_confirmation" title="Klik untuk melihat detail">
              <div class="stat-icon orange">
                <iconify-icon icon="mdi:clipboard-text-outline"></iconify-icon>
              </div>
              <div class="stat-content">
                <div class="stat-label">Menunggu Konfirmasi</div>
                <div class="stat-value"><?= number_format($pendingConfirmation) ?></div>
              </div>
            </div>

            <div class="stat-card clickable" data-stat-type="pending_return" title="Klik untuk melihat detail">
              <div class="stat-icon orange">
                <iconify-icon icon="mdi:keyboard-return"></iconify-icon>
              </div>
              <div class="stat-content">
                <div class="stat-label">Menunggu Balik</div>
                <div class="stat-value"><?= number_format($pendingReturns) ?></div>
              </div>
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
                $groupedByMember[$b['member_id']]['books'][] = [
                    'id' => $b['id'],
                    'title' => $b['title'],
                    'isbn' => $b['isbn'],
                    'cover_image' => $b['cover_image'],
                    'max_borrow_days' => $b['max_borrow_days'],
                    'borrowed_at' => $b['borrowed_at']
                ];
              }
              ?>

              <?php foreach ($groupedByMember as $studentId => $studentData): ?>
                <div class="pending-borrow-card">
                  <!-- Header -->
                  <div class="pending-borrow-header">
                    <div class="pending-borrow-header-grid">
                      <div>
                        <div class="pending-label">
                          <iconify-icon icon="mdi:account" style="vertical-align: middle; margin-right: 6px;"></iconify-icon> Nama Siswa
                        </div>
                        <div class="pending-value">
                          <?= htmlspecialchars($studentData['member_name']) ?>
                        </div>
                      </div>
                      <div>
                        <div class="pending-label">
                          <iconify-icon icon="mdi:card-account-details" style="vertical-align: middle; margin-right: 6px;"></iconify-icon> NISN
                        </div>
                        <div class="pending-value"><?= htmlspecialchars($studentData['nisn']) ?></div>
                      </div>
                      <div>
                        <div class="pending-label">
                          <iconify-icon icon="mdi:book-multiple" style="vertical-align: middle; margin-right: 6px;"></iconify-icon> Total Buku
                        </div>
                        <div class="pending-value"><?= count($studentData['books']) ?> Buku</div>
                      </div>
                      <div>
                        <div class="pending-label">
                          <iconify-icon icon="mdi:calendar-clock" style="vertical-align: middle; margin-right: 6px;"></iconify-icon> Waktu Scan
                        </div>
                        <div class="pending-value">
                          <?= date('d/m H:i', strtotime($studentData['books'][0]['borrowed_at'])) ?>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Books List Section -->
                  <div style="padding: 24px;">
                    <h3 class="flex-center gap-2 mb-4" style="font-size: 13px; font-weight: 700; color: var(--text); text-transform: uppercase;">
                        <input type="checkbox" id="selectAll_<?= $studentId ?>" onchange="toggleSelectAll('<?= $studentId ?>', this)" style="width: 18px; height: 18px; cursor: pointer;" checked>
                        <iconify-icon icon="mdi:book-open-variant" style="color: var(--primary);"></iconify-icon>
                        Daftar Buku
                    </h3>
                    
                    <div class="borrows-table-wrap">
                      <table class="borrows-table">
                        <thead>
                          <tr>
                            <th style="width: 40px;"></th>
                            <th>Info Buku</th>
                            <th>ISBN</th>
                            <th style="width: 100px;">Batas</th>
                            <th style="width: 120px;">Status</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($studentData['books'] as $idx => $book): ?>
                            <tr>
                              <td>
                                <input type="checkbox" class="book-checkbox-<?= $studentId ?>" value="<?= $book['id'] ?>" style="width: 18px; height: 18px; cursor: pointer;" checked>
                              </td>
                              <td>
                                <div class="flex-center gap-2">
                                  <?php if (!empty($book['cover_image'])): ?>
                                      <img src="../img/covers/<?= htmlspecialchars($book['cover_image']) ?>" style="width: 32px; height: 48px; object-fit: cover; border-radius: 4px;">
                                  <?php else: ?>
                                      <div style="width: 32px; height: 48px; background: var(--bg); border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 8px; color: var(--muted);">No Img</div>
                                  <?php endif; ?>
                                  <div>
                                    <div style="font-weight: 700; color: var(--text);"><?= htmlspecialchars($book['title']) ?></div>
                                    <div style="font-size: 11px; color: var(--muted);">Buku #<?= $idx + 1 ?></div>
                                  </div>
                                </div>
                              </td>
                              <td style="font-weight: 600; color: var(--text);"><?= htmlspecialchars($book['isbn']) ?></td>
                              <td>
                                <?php if (!empty($book['max_borrow_days'])): ?>
                                    <span style="color: var(--danger); font-weight: 700; font-size: 11px;">
                                        <iconify-icon icon="mdi:alert-decagram"></iconify-icon> <?= $book['max_borrow_days'] ?> Hari
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 11px;">Default</span>
                                <?php endif; ?>
                              </td>
                              <td>
                                <span class="status-badge pending">Menunggu</span>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <!-- Action Buttons -->
                  <div class="pending-actions-bar">
                    <div class="due-date-control">
                      <label>
                        <iconify-icon icon="mdi:calendar-clock"></iconify-icon>
                        Tenggat Pengembalian
                      </label>
                      <div class="due-input-wrapper">
                        <iconify-icon icon="mdi:plus-minus-variant"></iconify-icon>
                        <input type="number" id="dueDays_<?= $studentId ?>" value="7" min="1" max="365">
                      </div>
                    </div>

                    <div class="pending-action-btns">
                      <?php
                      $bookIds = array_map(fn($b) => $b['id'], $studentData['books']);
                      $bookIdsJson = json_encode($bookIds);
                      $bookIdsHtml = htmlspecialchars($bookIdsJson);
                      ?>
                      <button type="button" onclick="rejectAllBorrow('<?= $bookIdsHtml ?>')" class="btn-premium reject">
                        <iconify-icon icon="mdi:close-circle-outline"></iconify-icon> Tolak
                      </button>
                      <?php $booksDataJson = htmlspecialchars(json_encode($studentData['books'])); ?>
                      <button type="button" onclick="approveSelectedBorrowsAdvanced('<?= $studentId ?>', '<?= $booksDataJson ?>')" class="btn-premium approve">
                        <iconify-icon icon="mdi:check-circle-outline"></iconify-icon> Terima
                      </button>
                    </div>
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
              <div class="borrows-table-wrap">
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
                        <td style="font-weight: 700; color: var(--text);"><?= htmlspecialchars($br['title']) ?></td>
                        <td><?= htmlspecialchars($br['member_name']) ?></td>
                        <td><?= date('d/m/Y', strtotime($br['borrowed_at'])) ?></td>
                        <td><?= $br['due_at'] ? date('d/m/Y', strtotime($br['due_at'])) : '-' ?></td>
                        <td>
                          <span class="status-badge pending">Konfirmasi Balik</span>
                        </td>
                        <td>
                          <button class="btn-sm btn-sm-info" onclick="confirmReturn(<?= $br['id'] ?>)">
                            <iconify-icon icon="mdi:check"></iconify-icon>
                            Terima Balik
                          </button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
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
              <div class="borrows-table-wrap">
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
                        <td style="font-weight: 700; color: var(--text);"><?= htmlspecialchars($br['title']) ?></td>
                        <td><?= htmlspecialchars($br['member_name']) ?></td>
                        <td><?= date('d/m/Y', strtotime($br['borrowed_at'])) ?></td>
                        <td><?= $br['due_at'] ? date('d/m/Y', strtotime($br['due_at'])) : '-' ?></td>
                        <td>
                          <?php if ($br['status'] === 'overdue'): ?>
                            <span class="status-badge overdue">Terlambat</span>
                          <?php else: ?>
                            <span class="status-badge borrowed">Dipinjam</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php if (!empty($br['fine_amount'])): ?>
                            <div class="flex-center gap-2" style="font-weight: 700; color: var(--danger);">
                              <iconify-icon icon="mdi:credit-card-outline"></iconify-icon>
                              Rp <?= number_format($br['fine_amount'], 0, ',', '.') ?>
                              <span style="font-size: 10px; opacity: 0.8;">(<?= $br['fine_status'] === 'paid' ? 'Paid' : 'Unpaid' ?>)</span>
                            </div>
                          <?php else: ?>
                            <span style="color: var(--muted);">-</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <div class="action-grid">
                            <button type="button"
                              onclick="extendDueDate(<?= $br['id'] ?>, '<?= htmlspecialchars($br['title']) ?>')"
                              class="btn-sm btn-sm-warning">
                              <iconify-icon icon="mdi:calendar-plus"></iconify-icon>
                              Perpanjang
                            </button>
                            <a href="borrows.php?action=return&id=<?= $br['id'] ?>" class="btn-sm btn-sm-success" style="text-decoration: none;">
                              <iconify-icon icon="mdi:check"></iconify-icon>
                              Kembali
                            </a>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
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
              <div class="borrows-table-wrap">
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
                        <td style="font-weight: 700; color: var(--text);"><?= htmlspecialchars($br['title']) ?></td>
                        <td><?= htmlspecialchars($br['member_name']) ?></td>
                        <td><?= date('d/m/Y', strtotime($br['borrowed_at'])) ?></td>
                        <td><?= $br['returned_at'] ? date('d/m/Y', strtotime($br['returned_at'])) : '-' ?></td>
                        <td>
                          <?php if (!empty($br['fine_amount'])): ?>
                            <div class="flex-center gap-2" style="font-weight: 700; color: var(--danger);">
                              Rp <?= number_format($br['fine_amount'], 0, ',', '.') ?>
                            </div>
                          <?php else: ?>
                            <span style="color: var(--muted);">-</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <span class="status-badge returned">Dikembalikan</span>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
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
    function approveSelectedBorrowsAdvanced(studentId, booksDataJson) {
        const checkboxes = document.querySelectorAll('.book-checkbox-' + studentId + ':checked');
        if (checkboxes.length === 0) {
            alert('Pilih setidaknya satu buku untuk disetujui.');
            return;
        }

        const allBooksData = JSON.parse(booksDataJson);
        const selectedIds = [];
        checkboxes.forEach((cb) => {
            selectedIds.push(parseInt(cb.value));
        });

        const inputElement = document.getElementById('dueDays_' + studentId);
        const globalDueDays = parseInt(inputElement.value, 10) || 7;

        if (!confirm(`Terima ${selectedIds.length} peminjaman terpilih?`)) {
            return;
        }

        let approved = 0;
        let failed = 0;
        const errors = [];

        // Map book limits for the selected IDs
        Promise.all(selectedIds.map(borrowId => {
            const bookInfo = allBooksData.find(b => b.id === borrowId);
            
            // Calculate due date: 
            // Priority 1: Book Limit
            // Priority 2: Admin Input
            const effectiveDays = (bookInfo && bookInfo.max_borrow_days) ? Math.min(bookInfo.max_borrow_days, globalDueDays) : globalDueDays;
            
            const dueDate = new Date();
            dueDate.setDate(dueDate.getDate() + effectiveDays);
            const dueString = dueDate.toISOString().slice(0, 10) + ' ' + dueDate.toTimeString().slice(0, 8);

            return fetch('api/approve-borrow.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'borrow_id=' + borrowId + '&due_at=' + encodeURIComponent(dueString)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) approved++;
                else {
                    failed++;
                    errors.push(data.message);
                }
            })
            .catch(err => {
                failed++;
                errors.push(err.message);
            });
        })).then(() => {
            if (failed === 0) {
                alert(`✓ ${approved} peminjaman telah diterima!`);
                location.reload();
            } else {
                alert(`${approved} diterima, ${failed} gagal.\n\nContoh error: ${errors[0]}`);
                location.reload();
            }
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