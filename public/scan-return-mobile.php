<?php
require __DIR__ . '/../src/auth.php';

// Handle Token-based access for scanners
if (isset($_GET['key']) && !empty($_GET['key'])) {
    if (loginByScanKey($_GET['key'])) {
        // Redirect to same page without key in URL for clean UI (optional)
        // header('Location: scan-return-mobile.php');
        // exit;
    }
}

requireAuth();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Scanner Pengembalian</title>
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <style>
        :root {
            --primary: #3A7FF2;
            --success: #00C853;
            --danger: #FF3D00;
            --warning: #FFAB00;
        }

        body {
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #121212;
            color: white;
            margin: 0;
            padding: 0;
            height: 100dvh; /* Force dynamic viewport height */
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .scanner-container {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background: #000;
        }

        #reader {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 1;
        }
        
        #reader video {
            object-fit: cover !important;
            width: 100% !important;
            height: 100% !important;
        }

        .ui-layer {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 20; /* Higher than scanner */
            pointer-events: none;
        }

        .top-bar {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            padding: 20px;
            padding-top: max(20px, env(safe-area-inset-top));
            background: linear-gradient(180deg, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0) 100%);
            display: flex;
            justify-content: space-between;
            align-items: center;
            pointer-events: auto;
            z-index: 30;
            box-sizing: border-box;
        }

        /* ... app-title styles remain the same ... */

        .controls-area {
            position: fixed; /* Stick to bottom */
            bottom: 0;
            left: 0;
            width: 100%;
            background: #1e1e1e;
            padding: 24px;
            padding-bottom: max(24px, env(safe-area-inset-bottom));
            border-radius: 24px 24px 0 0;
            pointer-events: auto; /* Active interactions */
            z-index: 1000; /* Very high z-index to avoid overlap */
            box-shadow: 0 -4px 20px rgba(0,0,0,0.5);
            box-sizing: border-box;
        }

        .return-card {
            background: #2a2a2a;
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 20px;
            animation: slideUp 0.3s ease;
            display: none;
            border-left: 4px solid var(--success);
        }

        .return-card.late {
            border-left-color: var(--danger);
        }

        .card-header {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            color: var(--success);
            margin-bottom: 8px;
        }

        .return-card.late .card-header {
            color: var(--danger);
        }

        .book-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .member-info {
            font-size: 13px;
            color: #aaa;
        }

        .fine-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: rgba(255, 61, 0, 0.2);
            color: #ff5252;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            margin-top: 8px;
        }

        .action-bar {
            display: flex;
            gap: 12px;
        }

        .btn-back {
            background: #333;
            color: white;
            border: none;
            flex: 1;
            padding: 14px;
            border-radius: 14px;
            font-weight: 700;
            text-decoration: none;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        /* Status Toast */
        .toast {
            position: absolute;
            top: 100px;
            left: 50%;
            transform: translateX(-50%) translateY(-20px);
            background: rgba(0,0,0,0.85);
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            font-size: 14px;
            opacity: 0;
            transition: all 0.3s;
            z-index: 10;
        }
        
        .toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        .toast.error { background: var(--danger); }
        .toast.success { background: var(--success); }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 100;
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
    <?php require_once __DIR__ . '/../theme-loader.php'; ?>
</head>
<body>
    <div class="scanner-container">
        <div id="reader"></div>

        <div class="ui-layer">
            <div class="top-bar">
                <div class="app-title">
                    <iconify-icon icon="mdi:keyboard-return"></iconify-icon>
                    Return Mode
                </div>
            </div>

            <div style="flex: 1; display: flex; align-items: center; justify-content: center; position: relative;">
                <div class="toast" id="toastMessage"></div>
            </div>

            <div class="controls-area">
                <div class="return-card" id="returnCard">
                    <div class="card-header" id="cardHeader">Buku Kembali</div>
                    <div class="book-title" id="bookTitle">-</div>
                    <div class="member-info" id="memberInfo">-</div>
                    <div id="fineArea"></div>
                </div>

                <div class="action-bar">
                    <a href="returns.php" class="btn-back">
                        <iconify-icon icon="mdi:arrow-left"></iconify-icon>
                        Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div id="loadingOverlay" class="loading-overlay">
        <iconify-icon icon="mdi:loading" style="font-size: 40px; animation: spin 1s linear infinite;"></iconify-icon>
    </div>

    <audio id="soundSuccess" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>
    <audio id="soundError" src="https://assets.mixkit.co/active_storage/sfx/2571/2571-preview.mp3" preload="auto"></audio>
    <audio id="soundWarning" src="https://assets.mixkit.co/active_storage/sfx/2857/2857-preview.mp3" preload="auto"></audio>

    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        let scanner = null;
        let lastScannedTime = 0;
        const SCAN_DELAY = 2500;
        let toastTimeout = null;

        function initScanner() {
            scanner = new Html5Qrcode("reader");
            scanner.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: { width: 280, height: 130 } },
                onScanSuccess,
                (err) => {}
            ).catch(err => showToast('Gagal akses kamera', 'error'));
        }

        function onScanSuccess(text) {
            const now = Date.now();
            if (now - lastScannedTime < SCAN_DELAY) return;
            lastScannedTime = now;
            processReturn(text.trim());
        }

        async function processReturn(barcode) {
             document.getElementById('loadingOverlay').style.display = 'flex';
             try {
                const res = await fetch('api/process-return.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({barcode: barcode})
                });
                const result = await res.json();
                
                if (result.success) {
                    displayReturn(result.data);
                    showToast('Sukses!', 'success');
                    document.getElementById('soundSuccess').play();
                } else {
                    showToast(result.message, 'error');
                    document.getElementById('soundError').play();
                }
             } catch (e) {
                showToast('Error koneksi', 'error');
             }
             document.getElementById('loadingOverlay').style.display = 'none';
        }

        function displayReturn(data) {
            const card = document.getElementById('returnCard');
            const header = document.getElementById('cardHeader');
            const fineArea = document.getElementById('fineArea');
            
            card.style.display = 'block';
            card.className = data.fine_amount > 0 ? 'return-card late' : 'return-card';
            
            header.textContent = data.fine_amount > 0 ? 'KEMBALI TERLAMBAT' : 'BUKU KEMBALI';
            document.getElementById('bookTitle').textContent = data.book_title;
            document.getElementById('memberInfo').textContent = data.member_name + ' (' + data.member_nisn + ')';
            
            if (data.fine_amount > 0) {
                document.getElementById('soundWarning').play();
                fineArea.innerHTML = `
                    <div class="fine-badge">
                        <iconify-icon icon="mdi:alert-circle"></iconify-icon>
                        Denda: Rp ${data.fine_amount.toLocaleString('id-ID')}
                    </div>
                `;
            } else {
                fineArea.innerHTML = '';
            }
        }

        function showToast(msg, type) {
            const toast = document.getElementById('toastMessage');
            toast.textContent = msg;
            toast.className = 'toast show ' + type;
            if (toastTimeout) clearTimeout(toastTimeout);
            toastTimeout = setTimeout(() => toast.classList.remove('show'), 3000);
        }

        window.addEventListener('load', initScanner);
    </script>
</body>
</html>
