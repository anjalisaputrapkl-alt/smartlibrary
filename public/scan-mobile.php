<?php
require __DIR__ . '/../src/auth.php';

// Handle Token-based access for scanners
if (isset($_GET['key']) && !empty($_GET['key'])) {
    if (loginByScanKey($_GET['key'])) {
        // Redirect to same page without key in URL for clean UI (optional)
        // header('Location: scan-mobile.php');
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
    <title>Scanner Mobile</title>
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <style>
        /* Modern Immersive Scanner Design */
        :root {
            --primary: #3A7FF2;
            --success: #00C853;
            --danger: #FF3D00;
            --dark-overlay: rgba(0, 0, 0, 0.6);
        }

        body {
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #121212;
            color: white;
            margin: 0;
            padding: 0;
            height: 100dvh; /* Dynamic viewport height */
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
            flex: 1;
        }

        /* Fullscreen Reader */
        #reader {
            width: 100% !important;
            height: 100% !important;
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

        /* UI Layer over camera */
        .ui-layer {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 20;
            display: flex;
            flex-direction: column;
            pointer-events: none;
        }

        /* Top Bar */
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

        .app-title {
            font-size: 18px;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Scanner Overlay & Laser */
        .scan-region {
            flex: 1;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Scan Target Box */
        .scan-target {
            position: absolute;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 250px;
            height: 250px;
            border: 2px solid rgba(255, 255, 255, 0.6);
            border-radius: 20px;
            box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);
            pointer-events: none;
            z-index: 5;
        }

        .scan-target::after {
            content: '';
            position: absolute;
            top: -2px; left: -2px; right: -2px; bottom: -2px;
            border: 2px solid transparent;
            border-radius: 20px;
            background: linear-gradient(to right, var(--primary), var(--primary)) top left / 30px 3px no-repeat,
                        linear-gradient(to down, var(--primary), var(--primary)) top left / 3px 30px no-repeat,
                        linear-gradient(to left, var(--primary), var(--primary)) top right / 30px 3px no-repeat,
                        linear-gradient(to down, var(--primary), var(--primary)) top right / 3px 30px no-repeat,
                        linear-gradient(to right, var(--primary), var(--primary)) bottom left / 30px 3px no-repeat,
                        linear-gradient(to up, var(--primary), var(--primary)) bottom left / 3px 30px no-repeat,
                        linear-gradient(to left, var(--primary), var(--primary)) bottom right / 30px 3px no-repeat,
                        linear-gradient(to up, var(--primary), var(--primary)) bottom right / 3px 30px no-repeat;
            opacity: 0.8;
            animation: pulse-border 2s infinite;
        }

        @keyframes pulse-border {
            0% { opacity: 0.5; }
            50% { opacity: 1; }
            100% { opacity: 0.5; }
        }

        /* Controls Area */
        .controls-area {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: #1e1e1e;
            padding: 24px;
            padding-bottom: max(24px, env(safe-area-inset-bottom));
            border-radius: 24px 24px 0 0;
            pointer-events: auto;
            max-height: 50dvh; /* Limit height */
            display: flex;
            flex-direction: column;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.5);
            z-index: 1000;
            box-sizing: border-box;
        }

        .mode-switch {
            display: flex;
            background: #333;
            border-radius: 100px;
            padding: 4px;
            margin-bottom: 16px;
            flex-shrink: 0;
        }

        .mode-btn {
            flex: 1;
            background: transparent;
            border: none;
            color: #aaa;
            padding: 10px;
            border-radius: 100px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .mode-btn.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 2px 8px rgba(58, 127, 242, 0.4);
        }

        /* Scanned List Mini-View */
        .scanned-list {
            flex: 1;
            overflow-y: auto;
            margin-bottom: 16px;
            min-height: 100px; /* Minimum height for list */
            scrollbar-width: thin;
            scrollbar-color: #444 transparent;
        }
        
        .empty-placeholder {
             text-align: center;
             color: #666;
             font-size: 13px;
             margin-top: 20px;
        }

        .scanned-item {
            display: flex;
            align-items: center;
            padding: 10px;
            background: #2a2a2a;
            border-radius: 12px;
            margin-bottom: 8px;
            animation: slideUp 0.3s ease;
            border: 1px solid #333;
        }

        .item-cover {
            width: 36px;
            height: 52px;
            background: #444;
            border-radius: 4px;
            object-fit: cover;
            margin-right: 12px;
        }

        .item-info {
            flex: 1;
            overflow: hidden;
        }

        .item-title {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 2px;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .item-meta {
            font-size: 11px;
            color: #aaa;
        }

        .item-remove {
            background: rgba(255, 82, 82, 0.1);
            border: none;
            color: #FF5252;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            margin-left: 8px;
        }

        .action-bar {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
            flex-shrink: 0; /* Prevent shrinking */
        }

        .btn-main {
            background: var(--success);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 200, 83, 0.3);
            transition: transform 0.1s;
        }
        
        .btn-main:active {
            transform: scale(0.98);
        }

        .btn-clear {
            background: #333;
            color: white;
            border: none;
            width: 48px;
            border-radius: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        /* Status Toast - Matched to Return Page */
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

        .toast.error { background: var(--danger); border: none; color: white; }
        .toast.success { background: var(--success); border: none; color: white; }

        .btn-back {
            background: #333;
            color: white;
            border: none;
            width: 100%;
            padding: 14px;
            border-radius: 14px;
            font-weight: 700;
            text-decoration: none;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 12px;
            box-sizing: border-box;
            font-size: 14px;
        }

        /* Loading Overlay - Matched to Return Page */
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
            z-index: 2000;
            flex-direction: column;
            gap: 16px;
        }
        
        .loading-overlay.show {
            display: flex;
        }
    </style>
    <style>
        @keyframes spin { 
            from { transform: rotate(0deg); } 
            to { transform: rotate(360deg); } 
        }
    </style>
    <?php require_once __DIR__ . '/../theme-loader.php'; ?>
</head>

<body>
    <div class="scanner-container">
        <!-- QR Reader -->
        <div id="reader"></div>
        <!-- Scan Target Box Removed to match Return Page -->

        <!-- UI Layer -->
        <div class="ui-layer">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="app-title">
                    <iconify-icon icon="mdi:barcode-scan" style="color: var(--primary);"></iconify-icon>
                    Scanner Peminjaman
                </div>
                <!-- Member Badge -->
                <div class="member-badge" id="memberBadge">
                    <iconify-icon icon="mdi:account"></iconify-icon>
                    <span id="badgeName"></span>
                </div>
            </div>

            <!-- Toast Message -->
            <div style="flex: 1; display: flex; align-items: center; justify-content: center; position: relative; pointer-events: none;">
                <div class="toast" id="toastMessage"></div>
            </div>

            <!-- Controls -->
            <div class="controls-area">
                <div class="mode-switch">
                    <button class="mode-btn active" id="btnModeBook" onclick="switchMode('book')">Scan Buku</button>
                    <button class="mode-btn" id="btnModeMember" onclick="switchMode('member')">Scan Anggota</button>
                </div>

                <div class="scanned-list" id="scannedListMini">
                    <div class="empty-placeholder">Belum ada buku discan</div>
                </div>

                <div class="action-bar" id="actionBar" style="display:none">
                    <button class="btn-main" id="btnSubmit" onclick="submitScannedBooks()">
                        <iconify-icon icon="mdi:check-circle-outline" style="font-size: 20px;"></iconify-icon>
                        Pinjam (<span id="btnCount">0</span>)
                    </button>
                    <button class="btn-clear" onclick="clearScannedBooks()">
                        <iconify-icon icon="mdi:delete-outline"></iconify-icon>
                    </button>
                </div>

                <a href="borrows.php" class="btn-back" id="backBtnContainer">
                    <iconify-icon icon="mdi:arrow-left"></iconify-icon>
                    Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Hidden logout form -->
    <form id="logoutForm" action="logout.php" method="POST" style="display: none;"></form>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <iconify-icon icon="mdi:loading" style="font-size: 40px; color: var(--primary); animation: spin 1s linear infinite;"></iconify-icon>
        <p style="margin-top: 16px; font-weight: 600;">Memproses...</p>
    </div>

    <!-- Audio Sounds -->
    <audio id="soundSuccess" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>
    <audio id="soundError" src="https://assets.mixkit.co/active_storage/sfx/2571/2571-preview.mp3" preload="auto"></audio>

    <!-- Html5 QRCode Library -->
    <script src="https://unpkg.com/html5-qrcode"></script>

    <script>
        // ============================================================================
        // Global Variables
        // ============================================================================

        let scanner = null;
        let scanMode = 'book'; // Default mode Book
        let currentMember = null;
        let scannedBooks = []; // Array to store scanned books
        let lastScannedTime = 0;
        const SCAN_DELAY = 1500;
        let toastTimeout = null;

        // ============================================================================
        // Initialize Scanner
        // ============================================================================

        function initScanner() {
            // Calculate best aspect ratio for camera
            const aspectRatio = window.innerWidth / window.innerHeight;
            
            scanner = new Html5Qrcode("reader");
            
            const config = { 
                fps: 10,
                qrbox: { width: 280, height: 130 },
                experimentalFeatures: {
                    useBarCodeDetectorIfSupported: true
                }
            };

            scanner.start(
                { facingMode: "environment" },
                config, 
                onScanSuccess,
                onScanError
            ).then(() => {
                showToast('Siap memindai', 'info');
            }).catch(err => {
                showToast('Gagal mengakses kamera', 'error');
            });
        }

        function onScanSuccess(text) {
            const now = Date.now();
            if (now - lastScannedTime < SCAN_DELAY) return;
            lastScannedTime = now;

            processBarcode(text.trim());
        }

        function onScanError(error) {
            // Silently ignore
        }

        // ============================================================================
        // Logic
        // ============================================================================

        function parseBarcode(rawBarcode) {
            const patterns = [
                /^(?:NISN|nisn|ID|id)[:=]?(.+)$/,
                /^(?:ISBN|isbn)[:=]?(.+)$/,
                /^[\*=](.+)[\*=]$/
            ];

            for (let pattern of patterns) {
                const match = rawBarcode.match(pattern);
                if (match && match[1]) return match[1].trim();
            }
            return rawBarcode.trim();
        }

        async function processBarcode(barcode) {
            const parsedBarcode = parseBarcode(barcode);
            
            showLoading(true);

            try {
                const response = await fetch('./api/process-barcode.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ barcode: parsedBarcode })
                });

                const data = await response.json();

                if (!data.success) {
                    playSound('error');
                    showToast('Data tidak ditemukan', 'error');
                    showLoading(false);
                    return;
                }

                // Handle member scan
                if (scanMode === 'member') {
                     if (data.data.type !== 'member') {
                        // Intelligent auto-switch
                        if (data.data.type === 'book') {
                            playSound('success');
                            showToast('Buku terdeteksi. Mode: Buku', 'info');
                            switchMode('book');
                            // Re-process as book immediately? Maybe risky for recursion/state. 
                            // Better to just let user scan again or handle it. 
                            // Let's call processBarcode recursively safely? 
                            // No, let's just switch mode and ask to scan again to be safe and clear.
                            // Actually, better UX: just handle it.
                            scanMode = 'book'; // Force update local var
                            processBarcode(barcode); // Retry with new mode
                            return;
                        }
                        playSound('error');
                        showToast('Bukan kartu anggota!', 'error');
                        showLoading(false);
                        return;
                    }
                    currentMember = data.data;
                    updateMemberUI();
                    playSound('success');
                    
                    if (scannedBooks.length > 0) {
                        showToast(`Anggota: ${currentMember.name}`, 'success');
                    } else {
                        showToast(`Hai ${currentMember.name.split(' ')[0]}. Scan buku sekarang.`, 'success');
                        switchMode('book');
                    }

                     // VALIDATE EXISTING CART (Fix for Restricted Books)
                     if (currentMember.role === 'student') {
                        const validBooks = [];
                        let removedCount = 0;
                        // scannedBooks structure in mobile is object { book_id, book_title, cover_image, access_level?? }
                        // Wait, data.data for book has access_level. We need to store it in scannedBooks first or check it?
                        // scan-mobile.php stores: { book_id: data.data.id, book_title: data.data.name, cover_image: data.data.cover_image }
                        // We need to add access_level to stored object to validate later.
                        
                        // REFACTOR: Check scannedBooks for access_level manually?
                        // We need to ensure access_level is saved during book scan.
                        const newScannedBooks = [];
                        scannedBooks.forEach(b => {
                            if (b.access_level === 'teacher_only') {
                                removedCount++;
                            } else {
                                newScannedBooks.push(b);
                            }
                        });

                        if (removedCount > 0) {
                            scannedBooks = newScannedBooks;
                            updateScannedList();
                            showToast(`${removedCount} buku dihapus (Khusus Guru)`, 'error');
                            playSound('error');
                        }
                    }
                }
                // Handle book scan
                else if (scanMode === 'book') {
                     if (data.data.type !== 'book') {
                         // Intelligent auto-switch
                        if (data.data.type === 'member') {
                            playSound('success');
                            showToast('Kartu anggota terdeteksi', 'info');
                            switchMode('member');
                            scanMode = 'member';
                            processBarcode(barcode);
                            return;
                        }
                        playSound('error');
                        showToast('Bukan kode buku!', 'error');
                        showLoading(false);
                        return;
                    }

                    if (scannedBooks.some(b => b.book_id === data.data.id)) {
                        playSound('error');
                        showToast('Buku sudah ada', 'error');
                    } else {
                        // CHECK ACCESS LEVEL
                        if (currentMember && currentMember.role === 'student' && data.data.access_level === 'teacher_only') {
                            playSound('error');
                            showToast('Buku Khusus GURU!', 'error');
                            showLoading(false); // Fix infinite loading
                            return;
                        }

                        scannedBooks.push({
                            book_id: data.data.id,
                            book_title: data.data.name,
                            cover_image: data.data.cover_image,
                            access_level: data.data.access_level // Store access_level
                        });
                        updateScannedList();
                        playSound('success');
                        showToast('Buku ditambahkan', 'success');
                    }
                }

            } catch (error) {
                console.error(error);
                playSound('error');
                showToast('Error koneksi', 'error');
            }

            showLoading(false);
        }

        async function submitScannedBooks() {
            if (scannedBooks.length === 0) return;

            if (!currentMember) {
                playSound('error');
                showToast('Scan kartu anggota dulu!', 'error');
                switchMode('member');
                return;
            }

            showLoading(true);

            try {
                const response = await fetch('./api/submit-borrow.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        borrows: scannedBooks.map(book => ({
                            member_id: currentMember.id,
                            book_id: book.book_id
                        }))
                    })
                });

                const data = await response.json();

                if (data.success) {
                    playSound('success');
                    showToast('Peminjaman Berhasil!', 'success');
                    // Small delay to let user see toast
                    setTimeout(() => {
                        window.location.href = 'borrows.php';
                    }, 1500);
                } else {
                    playSound('error');
                    showToast(data.message || 'Gagal', 'error');
                }
            } catch (error) {
                playSound('error');
                showToast('Error koneksi', 'error');
            }

            showLoading(false);
        }

        function playSound(type) {
            const audio = document.getElementById(type === 'success' ? 'soundSuccess' : 'soundError');
            if (audio) {
                audio.currentTime = 0;
                audio.play().catch(e => console.log('Audio play failed', e));
            }
        }

        // ============================================================================
        // UI Helpers
        // ============================================================================

        function switchMode(mode) {
            scanMode = mode;
            document.querySelectorAll('.mode-btn').forEach(b => b.classList.remove('active'));
            document.getElementById(mode === 'book' ? 'btnModeBook' : 'btnModeMember').classList.add('active');
        }

        function updateMemberUI() {
            const badge = document.getElementById('memberBadge');
            const nameEl = document.getElementById('badgeName');
            
            if (currentMember) {
                nameEl.textContent = currentMember.name;
                badge.classList.add('active');
            } else {
                badge.classList.remove('active');
            }
        }

        function updateScannedList() {
            const container = document.getElementById('scannedListMini');
            const actionBar = document.getElementById('actionBar');
            const backBtn = document.getElementById('backBtnContainer');
            
            if (scannedBooks.length === 0) {
                container.innerHTML = '<div class="empty-placeholder">Belum ada buku discan</div>';
                actionBar.style.display = 'none';
                backBtn.style.display = 'block'; // Show back button when empty
                return;
            }

            actionBar.style.display = 'grid';
            backBtn.style.display = 'none'; // Hide back button when active (or keep it? Return page always has it. Let's keep it but maybe below?)
            // Actually return page hides back button when card is up? No, return page always shows back button.
            // But here "Action Bar" replaces the space.
            // Let's put Back button inside action bar or below it?
            
            // Re-evaluating: Scan Return has "Return Card" (content) THEN "Action Bar" (Back Button).
            // Here we have "Scanned List" (content) THEN "Action Bar" (Submit/Clear).
            // We should add a "Back" button to the Action Bar or separate row.
            
            // Let's modify the HTML structure in a separate call to handle layout better. 
            // For now, let's just make sure list updates correctly.
             document.getElementById('btnCount').textContent = scannedBooks.length;

            container.innerHTML = scannedBooks.map((book, index) => `
                <div class="scanned-item">
                    ${book.cover_image 
                      ? `<img src="../img/covers/${escapeHtml(book.cover_image)}" class="item-cover">`
                      : `<div class="item-cover" style="display:flex;align-items:center;justify-content:center;font-size:10px;color:#888;">NoImg</div>`
                    }
                    <div class="item-info">
                        <div class="item-title">${escapeHtml(book.book_title)}</div>
                        <div class="item-meta">Tap hapus untuk membatalkan</div>
                    </div>
                    <button class="item-remove" onclick="removeBook(${index})">
                        <iconify-icon icon="mdi:close"></iconify-icon>
                    </button>
                </div>
            `).join('');
            
            container.scrollTop = container.scrollHeight;
        }

        function removeBook(index) {
            scannedBooks.splice(index, 1);
            updateScannedList();
        }

        function clearScannedBooks() {
            if(confirm('Hapus semua?')) {
                scannedBooks = [];
                updateScannedList();
            }
        }

        function showToast(msg, type) {
            const toast = document.getElementById('toastMessage');
            toast.textContent = msg;
            toast.className = 'toast show ' + type;
            
            if (toastTimeout) clearTimeout(toastTimeout);
            toastTimeout = setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        function showLoading(show) {
            const el = document.getElementById('loadingOverlay');
             if (show) {
                el.style.display = 'flex';
                // Trigger reflow
                el.offsetHeight; 
                el.style.opacity = '1';
            } else {
                el.style.opacity = '0';
                setTimeout(() => {
                    if (el.style.opacity === '0') el.style.display = 'none';
                }, 300);
            }
        }

        function escapeHtml(text) {
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Start
        window.addEventListener('load', initScanner);

    </script>
</body>
</html>