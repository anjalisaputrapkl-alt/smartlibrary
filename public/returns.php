<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];

// Get some basic stats for the return page
$stmt = $pdo->prepare(
  'SELECT COUNT(*) FROM borrows WHERE school_id = :sid AND status IN ("borrowed", "overdue")'
);
$stmt->execute(['sid' => $sid]);
$activeBorrowsCount = $stmt->fetchColumn();

// Get recent returns
$stmt = $pdo->prepare(
  'SELECT b.*, bk.title, m.name as member_name 
   FROM borrows b
   JOIN books bk ON b.book_id = bk.id
   JOIN members m ON b.member_id = m.id
   WHERE b.school_id = :sid AND b.status = "returned"
   ORDER BY b.returned_at DESC LIMIT 5'
);
$stmt->execute(['sid' => $sid]);
$recentReturns = $stmt->fetchAll();
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pengembalian Buku</title>
  <script src="../assets/js/theme-loader.js"></script>
  <script src="../assets/js/theme.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
  <link rel="stylesheet" href="../assets/css/animations.css">
  <link rel="stylesheet" href="../assets/css/borrows.css">
  <style>
    .return-scanner-section {
      background: var(--primary-soft);
      border: 2px solid var(--primary);
      border-radius: 16px;
      padding: 32px;
      text-align: center;
    }
    
    .manual-input-wrap {
      max-width: 500px;
      margin: 0 auto 24px;
      position: relative;
    }
    
    .manual-input-wrap input {
      width: 100%;
      padding: 16px 20px 16px 50px;
      font-size: 18px;
      font-weight: 700;
      border: 2px solid var(--border);
      border-radius: 12px;
      background: var(--surface);
      color: var(--text);
      transition: all 0.3s;
    }
    
    .manual-input-wrap input:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 4px var(--primary-soft);
      outline: none;
    }
    
    .manual-input-wrap iconify-icon {
      position: absolute;
      left: 18px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 24px;
      color: var(--primary);
    }
    
    .last-return-card {
      display: none;
      animation: slideUp 0.4s ease-out;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 24px;
      margin-top: 24px;
      text-align: left;
      box-shadow: var(--card-shadow);
    }
    
    .success-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 12px;
      background: var(--success-soft);
      color: var(--success);
      border-radius: 50px;
      font-size: 12px;
      font-weight: 700;
      margin-bottom: 16px;
    }
    
    .fine-alert {
      background: var(--danger-soft);
      color: var(--danger);
      padding: 12px 16px;
      border-radius: 8px;
      margin-top: 12px;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    #reader {
        width: 100%;
        max-width: 400px;
        margin: 0 auto 20px;
        border-radius: 12px;
        overflow: hidden;
        border: 4px solid var(--surface);
    }
  </style>
</head>

<body>
  <?php require __DIR__ . '/partials/sidebar.php'; ?>

  <div class="app">
    <div class="topbar">
      <div class="topbar-title">
        <iconify-icon icon="mdi:keyboard-return" style="font-size: 24px; color: var(--primary);"></iconify-icon>
        <strong>Pengembalian Buku</strong>
      </div>
    </div>

    <div class="content">
      <div class="main">
        
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon blue">
              <iconify-icon icon="mdi:book-clock"></iconify-icon>
            </div>
            <div class="stat-content">
              <div class="stat-label">Buku Belum Kembali</div>
              <div class="stat-value"><?= number_format($activeBorrowsCount) ?></div>
            </div>
          </div>
          <div class="stat-card">
             <div class="stat-icon green">
              <iconify-icon icon="mdi:check-circle"></iconify-icon>
            </div>
            <div class="stat-content">
              <div class="stat-label">Sesi Ini</div>
              <div class="stat-value" id="sessionCount">0</div>
            </div>
          </div>
        </div>

        <div class="return-scanner-section">
          <h2>Scan Barcode Buku</h2>
          <p style="color: var(--muted); margin-bottom: 24px;">Gunakan scanner barcode atau arahkan kamera ke barcode buku</p>
          
          <div id="reader" style="display: none;"></div>

          <div class="manual-input-wrap">
            <iconify-icon icon="mdi:barcode"></iconify-icon>
            <input type="text" id="barcodeInput" placeholder="Scan atau ketik ISBN/ID Buku..." autofocus autocomplete="off">
          </div>

          <div class="action-grid" style="justify-content: center;">
            <button class="btn-barcode-start" id="btnToggleCamera" onclick="toggleCamera()">
              <iconify-icon icon="mdi:camera"></iconify-icon>
              Gunakan Kamera
            </button>
            <button class="btn-sm btn-sm-info" onclick="document.getElementById('barcodeInput').focus()">
              <iconify-icon icon="mdi:keyboard-outline"></iconify-icon>
              Fokus Input
            </button>
          </div>

          <div id="lastReturnCard" class="last-return-card">
            <div class="success-badge">
              <iconify-icon icon="mdi:check-circle"></iconify-icon>
              BERHASIL DIKEMBALIKAN
            </div>
            <div style="display: grid; grid-template-columns: 1fr auto; gap: 20px;">
              <div>
                <div style="font-size: 20px; font-weight: 800; color: var(--text);" id="resBookTitle">-</div>
                <div style="font-size: 14px; color: var(--muted); margin-top: 4px;">
                  Peminjam: <span style="font-weight: 700; color: var(--primary);" id="resMemberName">-</span>
                </div>
                </div>
              </div>
              <div style="text-align: right;">
                <div style="font-size: 12px; color: var(--muted);" id="resTime">-</div>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <h2>Aktivitas Pengembalian Terbaru</h2>
          <div class="borrows-table-wrap">
            <table class="borrows-table">
              <thead>
                <tr>
                  <th>Buku</th>
                  <th>Peminjam</th>
                  <th>Waktu Kembali</th>
                  <th>Denda</th>
                </tr>
              </thead>
              <tbody id="recentReturnsList">
                <?php foreach($recentReturns as $r): ?>
                <tr>
                  <td style="font-weight: 700;"><?= htmlspecialchars($r['title']) ?></td>
                  <td><?= htmlspecialchars($r['member_name']) ?></td>
                  <td><?= date('d/m/Y H:i', strtotime($r['returned_at'])) ?></td>
                  <td>
                    <?php if($r['fine_amount'] > 0): ?>
                      <span style="color: var(--danger); font-weight: 700;">Rp <?= number_format($r['fine_amount'], 0, ',', '.') ?></span>
                    <?php else: ?>
                      <span style="color: var(--success);">Nihil</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>

  <audio id="soundSuccess" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>
  <audio id="soundError" src="https://assets.mixkit.co/active_storage/sfx/2571/2571-preview.mp3" preload="auto"></audio>
  <audio id="soundWarning" src="https://assets.mixkit.co/active_storage/sfx/2857/2857-preview.mp3" preload="auto"></audio>

  <script src="https://unpkg.com/html5-qrcode"></script>
  <script>
    const barcodeInput = document.getElementById('barcodeInput');
    const lastReturnCard = document.getElementById('lastReturnCard');
    const sessionCountEl = document.getElementById('sessionCount');
    let sessionCount = 0;
    let cameraActive = false;
    let html5QrcodeScanner = null;

    // Auto-focus input
    document.addEventListener('keydown', (e) => {
      // Don't focus if we are already in an input
      if (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'SELECT' || document.activeElement.tagName === 'TEXTAREA') {
        return;
      }
      barcodeInput.focus();
    });

    barcodeInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        const val = barcodeInput.value.trim();
        if (val) {
          processReturn(val);
          barcodeInput.value = '';
        }
      }
    });

    async function processReturn(barcode) {
      try {
        const res = await fetch('api/process-return.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({barcode: barcode})
        });
        
        const result = await res.json();
        
        if (result.success) {
          handleSuccess(result.data);
        } else {
          handleError(result.message);
        }
      } catch (e) {
        handleError('Koneksi terputus atau server error');
      }
    }

    function handleSuccess(data) {
      document.getElementById('soundSuccess').play();
      
      sessionCount++;
      sessionCountEl.textContent = sessionCount;
      
      lastReturnCard.style.display = 'block';
      document.getElementById('resBookTitle').textContent = data.book_title;
      document.getElementById('resMemberName').textContent = data.member_name;
      document.getElementById('resTime').textContent = new Date().toLocaleTimeString('id-ID');
      
      const fineEl = document.getElementById('fineDisplay');
      if (data.fine_amount > 0) {
        document.getElementById('soundWarning').play();
        fineEl.innerHTML = `
          <div class="fine-alert">
            <iconify-icon icon="mdi:alert-circle"></iconify-icon>
            TERLAMBAT ${data.late_days} HARI | DENDA: Rp ${data.fine_amount.toLocaleString('id-ID')}
          </div>
        `;
      } else {
        fineEl.innerHTML = '';
      }

      // Add to list
      const list = document.getElementById('recentReturnsList');
      const row = document.createElement('tr');
      row.style.animation = 'fadeIn 0.5s ease';
      row.innerHTML = `
        <td style="font-weight: 700;">${data.book_title}</td>
        <td>${data.member_name}</td>
        <td>${new Date().toLocaleDateString('id-ID')} ${new Date().toLocaleTimeString('id-ID')}</td>
        <td>${data.fine_amount > 0 ? `<span style="color: var(--danger); font-weight: 700;">Rp ${data.fine_amount.toLocaleString('id-ID')}</span>` : `<span style="color: var(--success);">Nihil</span>`}</td>
      `;
      list.insertBefore(row, list.firstChild);
    }

    function handleError(msg) {
      document.getElementById('soundError').play();
      alert(msg);
    }

    function toggleCamera() {
        // If mobile, redirect to specialized mobile return page
        if (window.innerWidth <= 768) {
            window.location.href = 'scan-return-mobile.php';
            return;
        }

        const reader = document.getElementById('reader');
        const btn = document.getElementById('btnToggleCamera');

        if (!cameraActive) {
            reader.style.display = 'block';
            cameraActive = true;
            btn.innerHTML = '<iconify-icon icon="mdi:camera-off"></iconify-icon> Matikan Kamera';
            
            html5QrcodeScanner = new Html5Qrcode("reader");
            html5QrcodeScanner.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: { width: 250, height: 150 } },
                (decodedText) => {
                    processReturn(decodedText);
                    // Add cool-down
                    html5QrcodeScanner.pause();
                    setTimeout(() => html5QrcodeScanner.resume(), 2000);
                },
                (error) => {}
            );
        } else {
            html5QrcodeScanner.stop().then(() => {
                reader.style.display = 'none';
                cameraActive = false;
                btn.innerHTML = '<iconify-icon icon="mdi:camera"></iconify-icon> Gunakan Kamera';
            });
        }
    }
  </script>
</body>
</html>
