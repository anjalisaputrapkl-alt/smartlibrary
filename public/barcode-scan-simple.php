<?php
require __DIR__ . '/../src/auth.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemindai Barcode</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
        }

        .container {
            width: 100%;
            max-width: 500px;
        }

        .header {
            text-align: center;
            color: #1a1a1a;
            margin-bottom: 24px;
            padding: 16px;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 4px;
            font-weight: 700;
            color: #2c3e50;
        }

        .header p {
            font-size: 14px;
            color: #7f8c8d;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 28px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid #e8ecf1;
        }

        .card h2 {
            font-size: 20px;
            margin-bottom: 20px;
            color: #2c3e50;
            font-weight: 700;
        }

        .reader {
            width: 100%;
            aspect-ratio: 1 / 1;
            position: relative;
            background: #000;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 2px solid #e8ecf1;
            overflow: hidden;
        }

        /* Force scanner video to be square */
        #reader video {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
        }

        #reader__scan_region {
            width: 100% !important;
            height: 100% !important;
        }

        #reader__dashboard_section {
            display: none !important;
        }

        #reader__dashboard_section_csr {
            width: 100% !important;
            height: 100% !important;
        }

        .info-text {
            font-size: 13px;
            color: #555;
            margin-bottom: 20px;
            padding: 12px 14px;
            background: #f0f5fa;
            border-radius: 6px;
            border-left: 3px solid #3498db;
        }

        .status-message {
            font-size: 13px;
            padding: 12px 14px;
            border-radius: 6px;
            margin-bottom: 16px;
            text-align: center;
            font-weight: 500;
        }

        .status-message.success {
            background: #ecf9f3;
            color: #1e7e5f;
            border: 1px solid #d5ede7;
        }

        .status-message.error {
            background: #fef3f2;
            color: #c4302b;
            border: 1px solid #f9d8d6;
        }

        .status-message.info {
            background: #f0f5fa;
            color: #2c5aa0;
            border: 1px solid #dce7f0;
        }

        .btn {
            width: 100%;
            padding: 12px 16px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 12px;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .mode-indicator {
            margin-bottom: 20px;
            padding: 14px;
            background: #f8fbfe;
            border-radius: 6px;
            border-left: 3px solid #3498db;
        }

        .mode-text {
            font-size: 12px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .mode-buttons {
            display: flex;
            gap: 8px;
        }

        .mode-btn {
            flex: 1;
            padding: 9px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
            background: white;
            color: #555;
        }

        .mode-btn.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }

        .mode-btn.inactive {
            background: white;
            color: #888;
            border-color: #ddd;
        }

        .member-display {
            display: none;
            margin-bottom: 20px;
            padding: 14px;
            background: #ecf9f3;
            border-radius: 6px;
            border-left: 3px solid #27ae60;
        }

        .member-display.show {
            display: block;
        }

        .member-label {
            font-size: 12px;
            font-weight: 700;
            color: #1e7e5f;
            margin-bottom: 8px;
        }

        .member-info {
            font-size: 14px;
            font-weight: 600;
            color: #2c3e50;
        }

        .scan-count {
            margin-bottom: 20px;
            padding: 14px;
            background: #ecf9f3;
            border-radius: 6px;
            border-left: 3px solid #27ae60;
            font-size: 13px;
            color: #1e7e5f;
            font-weight: 500;
        }

        .scanned-items {
            margin-bottom: 20px;
            background: white;
            border-radius: 6px;
            border: 1px solid #e8ecf1;
            overflow: hidden;
        }

        .scanned-items table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .scanned-items th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 700;
            color: #2c3e50;
            border-bottom: 1px solid #e8ecf1;
        }

        .scanned-items td {
            padding: 12px;
            border-bottom: 1px solid #e8ecf1;
            color: #555;
        }

        .scanned-items tr:last-child td {
            border-bottom: none;
        }

        .scanned-items tbody tr:hover {
            background: #f8fbfe;
        }

        .btn-remove {
            padding: 5px 10px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-remove:hover {
            background: #c0392b;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-primary {
            flex: 1;
            padding: 12px 16px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background: #229954;
        }

        .btn-primary:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }

        .btn-secondary {
            flex: 1;
            padding: 12px 16px;
            background: #95a5a6;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
        }

        .instruction {
            margin-bottom: 20px;
            padding: 14px;
            background: #fef9e7;
            border-radius: 6px;
            border-left: 3px solid #f39c12;
            font-size: 13px;
            color: #7d6608;
        }

        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .loading-overlay.show {
            display: flex;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f0f0f0;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 16px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .loading-overlay p {
            color: white;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="container">

        <div class="info-text">
            Arahkan kamera ke barcode untuk memulai pemindaian
        </div>

        <!-- QR Reader -->
        <div id="reader" class="reader"></div>

        <!-- Status Message -->
        <div id="statusMessage" class="status-message info" style="display: none;"></div>

        <!-- Mode Indicator -->
        <div class="mode-indicator">
            <div class="mode-text">Mode Pemindaian</div>
            <div class="mode-buttons">
                <button class="mode-btn active" id="btnModeMember">Scan Anggota</button>
                <button class="mode-btn inactive" id="btnModeBook">Scan Buku</button>
            </div>
        </div>

        <!-- Member Display -->
        <div class="member-display" id="memberDisplay">
            <div class="member-label">Anggota Terpilih</div>
            <div class="member-info">
                <span id="memberName"></span> (NISN: <span id="memberNisn"></span>)
            </div>
        </div>

        <!-- Scan Count -->
        <div class="scan-count" id="scanCount" style="display: none;">
            Buku yang di-scan: <strong id="bookCount">0</strong>
        </div>

        <!-- Scanned Items List -->
        <div class="scanned-items" id="scannedItems" style="display: none;">
            <table>
                <thead>
                    <tr>
                        <th>Buku</th>
                        <th style="width: 50px;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="scannedItemsList">
                </tbody>
            </table>
        </div>

        <!-- Action Buttons -->
        <div class="btn-group" id="actionButtons" style="display: none;">
            <button class="btn-primary" id="btnSubmit" onclick="submitScannedBooks()">
                Kirim Data
            </button>
            <button class="btn-secondary" id="btnClear" onclick="clearScannedBooks()">
                Hapus Semua
            </button>
        </div>

        <!-- Instruction -->
        <div class="instruction">
            Silakan scan NISN Anda terlebih dahulu, kemudian scan barcode buku yang ingin dipinjam. Data akan
            tampil di sistem admin secara realtime.
        </div>

        <!-- Logout Button -->
        <div style="margin-top: 24px;">
            <button class="btn btn-danger" id="btnLogout">
                Logout
            </button>
        </div>
    </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="spinner"></div>
        <p>Memproses...</p>
    </div>

    <!-- Html5 QRCode Library -->
    <script src="https://unpkg.com/html5-qrcode"></script>

    <script>
        // ============================================================================
        // Global Variables
        // ============================================================================

        let scanner = null;
        let scanMode = 'member';
        let currentMember = null;
        let scannedBooks = []; // Array to store scanned books
        let lastScannedTime = 0;
        const SCAN_DELAY = 1000;
        let statusTimeoutId = null;

        // ============================================================================
        // Initialize Scanner
        // ============================================================================

        function initScanner() {
            console.log('[INIT] Starting scanner...');
            scanner = new Html5Qrcode("reader");

            scanner.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: 250 },
                onScanSuccess,
                onScanError
            ).then(() => {
                console.log('[SCANNER] Started');
                showStatus('Kamera aktif - siap memindai barcode', 'info');
            }).catch(err => {
                console.error('[SCANNER] Error:', err);
                showStatus('Gagal mengakses kamera', 'error');
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
        // Parse Barcode
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

        // ============================================================================
        // Process Barcode
        // ============================================================================

        async function processBarcode(barcode) {
            const parsedBarcode = parseBarcode(barcode);
            console.log('[PROCESS] Barcode:', parsedBarcode, 'Mode:', scanMode);

            showLoading(true);
            showStatus(`Memproses barcode ${scanMode}...`, 'info');

            try {
                const response = await fetch('./api/process-barcode.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ barcode: parsedBarcode })
                });

                const data = await response.json();
                console.log('[API] Response:', data);

                if (!data.success) {
                    showStatus('Barcode tidak ditemukan', 'error');
                    showLoading(false);
                    return;
                }

                // Handle member scan
                if (scanMode === 'member') {
                    if (data.data.type !== 'member') {
                        showStatus('Ini buku, bukan anggota!', 'error');
                        showLoading(false);
                        return;
                    }
                    currentMember = data.data;
                    displayMember();
                    switchMode('book');
                    scannedBooks = []; // Reset books list
                    updateScannedList();
                    showStatus('Anggota dipilih. Sekarang scan buku', 'success');
                }
                // Handle book scan
                else if (scanMode === 'book') {
                    if (!currentMember) {
                        showStatus('Scan anggota dulu!', 'error');
                        switchMode('member');
                        showLoading(false);
                        return;
                    }
                    if (data.data.type !== 'book') {
                        showStatus('Ini anggota, bukan buku!', 'error');
                        showLoading(false);
                        return;
                    }

                    // Add to local array
                    scannedBooks.push({
                        member_id: currentMember.id,
                        member_name: currentMember.name,
                        book_id: data.data.id,
                        book_title: data.data.name
                    });

                    updateScannedList();
                    showStatus(data.data.name + ' ditambahkan', 'success');
                }

            } catch (error) {
                console.error('[ERROR]', error);
                showStatus('Error: ' + error.message, 'error');
            }

            showLoading(false);
        }

        // ============================================================================
        // Handle Scanned Books List
        // ============================================================================

        function updateScannedList() {
            const container = document.getElementById('scannedItems');
            const tbody = document.getElementById('scannedItemsList');
            const actionButtons = document.getElementById('actionButtons');
            const scanCount = document.getElementById('scanCount');

            if (scannedBooks.length === 0) {
                container.style.display = 'none';
                actionButtons.style.display = 'none';
                scanCount.style.display = 'none';
            } else {
                container.style.display = 'block';
                actionButtons.style.display = 'flex';
                scanCount.style.display = 'block';

                document.getElementById('bookCount').textContent = scannedBooks.length;

                tbody.innerHTML = scannedBooks.map((book, index) => `
                    <tr>
                        <td>${escapeHtml(book.book_title)}</td>
                        <td>
                            <button class="btn-remove" onclick="removeBook(${index})">Hapus</button>
                        </td>
                    </tr>
                `).join('');
            }
        }

        function removeBook(index) {
            scannedBooks.splice(index, 1);
            updateScannedList();
        }

        function clearScannedBooks() {
            if (confirm('Hapus semua buku yang sudah di-scan?')) {
                scannedBooks = [];
                updateScannedList();
                showStatus('Daftar buku telah dihapus', 'info');
            }
        }

        async function submitScannedBooks() {
            if (scannedBooks.length === 0) {
                showStatus('Tidak ada buku untuk dikirim', 'error');
                return;
            }

            const btnSubmit = document.getElementById('btnSubmit');
            const btnClear = document.getElementById('btnClear');
            btnSubmit.disabled = true;
            btnClear.disabled = true;

            showLoading(true);
            showStatus('Mengirim data...', 'info');

            try {
                const response = await fetch('./api/submit-borrow.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        borrows: scannedBooks.map(book => ({
                            member_id: book.member_id,
                            book_id: book.book_id
                        }))
                    })
                });

                const data = await response.json();
                console.log('[SUBMIT] Response:', data);

                if (data.success) {
                    showStatus(`${data.inserted} peminjaman berhasil disimpan! Tunggu konfirmasi admin.`, 'success');
                    scannedBooks = [];
                    updateScannedList();
                    currentMember = null;
                    document.getElementById('memberDisplay').classList.remove('show');
                    switchMode('member');
                } else {
                    showStatus('Error: ' + (data.message || 'Gagal menyimpan'), 'error');
                    btnSubmit.disabled = false;
                    btnClear.disabled = false;
                }
            } catch (error) {
                console.error('[SUBMIT] Error:', error);
                showStatus('Error: ' + error.message, 'error');
                btnSubmit.disabled = false;
                btnClear.disabled = false;
            }

            showLoading(false);
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        // ============================================================================
        // UI Management
        // ============================================================================

        function switchMode(mode) {
            scanMode = mode;
            const btnMember = document.getElementById('btnModeMember');
            const btnBook = document.getElementById('btnModeBook');

            if (mode === 'member') {
                btnMember.classList.add('active');
                btnMember.classList.remove('inactive');
                btnBook.classList.remove('active');
                btnBook.classList.add('inactive');
            } else {
                btnMember.classList.remove('active');
                btnMember.classList.add('inactive');
                btnBook.classList.add('active');
                btnBook.classList.remove('inactive');
            }
        }

        function displayMember() {
            document.getElementById('memberName').textContent = currentMember.name;
            document.getElementById('memberNisn').textContent = currentMember.barcode;
            document.getElementById('memberDisplay').classList.add('show');
        }

        function showStatus(message, type = 'info') {
            const div = document.getElementById('statusMessage');
            if (statusTimeoutId) clearTimeout(statusTimeoutId);

            div.textContent = message;
            div.className = 'status-message ' + type;
            div.style.display = 'block';

            if (type === 'success') {
                statusTimeoutId = setTimeout(() => {
                    div.style.display = 'none';
                }, 4000);
            }
        }

        function showLoading(show) {
            const overlay = document.getElementById('loadingOverlay');
            if (show) {
                overlay.classList.add('show');
            } else {
                overlay.classList.remove('show');
            }
        }

        // ============================================================================
        // Event Listeners
        // ============================================================================

        document.getElementById('btnLogout').addEventListener('click', () => {
            if (confirm('Logout?')) {
                if (scanner) scanner.stop();
                location.href = './logout.php';
            }
        });

        document.getElementById('btnModeMember').addEventListener('click', () => {
            switchMode('member');
        });

        document.getElementById('btnModeBook').addEventListener('click', () => {
            if (!currentMember) {
                showStatus('Scan anggota dulu!', 'error');
            } else {
                switchMode('book');
            }
        });

        // ============================================================================
        // Initialization
        // ============================================================================

        window.addEventListener('load', () => {
            console.log('[PAGE] Load complete');
            initScanner();
        });

        window.addEventListener('beforeunload', () => {
            if (scanner) scanner.stop();
        });
    </script>
</body>

</html>