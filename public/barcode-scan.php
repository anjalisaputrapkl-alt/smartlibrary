<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemindai Barcode</title>
    <link rel="stylesheet" href="../assets/css/barcode-scan.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>📖 Pemindai Barcode</h1>
            <p>Sistem Perpustakaan Sekolah</p>
        </div>

        <!-- Step 1: Input Session Token -->
        <div id="step-session" class="step active">
            <div class="card">
                <h2>Masukkan Kode Sesi</h2>
                <p class="info-text">Minta kode sesi dari petugas perpustakaan</p>

                <input type="text" id="sessionToken" class="input-field" placeholder="Masukkan kode sesi 32 karakter"
                    maxlength="32" autocomplete="off">

                <button id="btnVerifySession" class="btn btn-primary">
                    <span class="btn-text">Verifikasi Sesi</span>
                </button>

                <p class="error-message" id="sessionError"></p>
            </div>
        </div>

        <!-- Step 2: Scanner -->
        <div id="step-scanner" class="step" style="display: none;">
            <div class="card">
                <div class="scanner-header">
                    <h2>Pemindai</h2>
                    <button class="btn-close" id="btnCloseScanner">✕</button>
                </div>

                <div class="scan-info">
                    <div class="info-badge member-badge" id="memberDisplay" style="display: none;">
                        <span class="badge-icon">👤</span>
                        <span id="memberName"></span>
                    </div>

                    <div class="scan-type-selector">
                        <button class="scan-type-btn active" data-type="member" id="btnScanMember">
                            Scan Anggota
                        </button>
                        <button class="scan-type-btn" data-type="book" id="btnScanBook">
                            Scan Buku
                        </button>
                    </div>
                </div>

                <!-- QR Scanner -->
                <div id="qr-reader" class="qr-reader"></div>
                <p class="scan-instruction" id="scanInstruction">Arahkan kamera ke barcode</p>

                <div class="scanned-list">
                    <h3>Hasil Pemindaian</h3>
                    <div id="scannedItems">
                        <p class="empty-message">Belum ada hasil pemindaian</p>
                    </div>
                </div>

                <div class="action-buttons">
                    <button class="btn btn-secondary" id="btnClearScans">Hapus Semua</button>
                    <button class="btn btn-success" id="btnFinishScanning">Selesai Pemindaian</button>
                </div>

                <p class="error-message" id="scanError"></p>
            </div>
        </div>

        <!-- Step 3: Completion -->
        <div id="step-completion" class="step" style="display: none;">
            <div class="card">
                <div class="completion-icon">✓</div>
                <h2>Pemindaian Selesai!</h2>

                <div class="completion-summary">
                    <div class="summary-item">
                        <span class="summary-label">Anggota:</span>
                        <span class="summary-value" id="summaryMember">-</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Jumlah Buku:</span>
                        <span class="summary-value" id="summaryBooks">0</span>
                    </div>
                </div>

                <p class="info-text">Data telah dikirim ke petugas perpustakaan. Silakan tunggu persetujuan.</p>

                <button class="btn btn-primary" id="btnNewSession">Sesi Baru</button>
            </div>
        </div>
    </div>

    <!-- Loading indicator -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="spinner"></div>
        <p>Memproses...</p>
    </div>

    <!-- Include html5-qrcode library with multiple fallbacks -->
    <script>
        // Track library load attempts
        let libraryLoaded = false;
        let libraryLoadAttempts = 0;
        const maxAttempts = 3;

        function onLibraryLoaded() {
            libraryLoaded = true;
            console.log('[LIBRARY] ✓ Html5Qrcode loaded successfully');
            document.dispatchEvent(new Event('libraryReady'));
        }

        function tryLoadLibrary() {
            libraryLoadAttempts++;
            console.log(`[LIBRARY] Load attempt ${libraryLoadAttempts}/${maxAttempts}`);

            if (libraryLoadAttempts > maxAttempts) {
                console.error('[LIBRARY] ✗ Failed to load from all CDN sources');
                return;
            }

            const cdnUrls = [
                'https://unpkg.com/html5-qrcode@2.2.0/minified/html5-qrcode.min.js',
                'https://cdn.jsdelivr.net/npm/html5-qrcode@2.2.0/minified/html5-qrcode.min.js',
                'https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.2.0/html5-qrcode.min.js'
            ];

            const script = document.createElement('script');
            script.src = cdnUrls[libraryLoadAttempts - 1];
            script.onload = onLibraryLoaded;
            script.onerror = () => {
                console.warn(`[LIBRARY] CDN ${libraryLoadAttempts} failed: ${script.src}`);
                tryLoadLibrary();
            };
            script.timeout = 10000;

            console.log(`[LIBRARY] Trying CDN ${libraryLoadAttempts}: ${script.src}`);
            document.head.appendChild(script);
        }

        // Start loading library immediately
        tryLoadLibrary();
    </script>

    <!-- Main barcode scanner script -->
    <script src="../assets/js/barcode-scan.js" defer></script>
</body>

</html>