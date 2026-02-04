<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/BarcodeModel.php';

$school_id = $_SESSION['user']['school_id'] ?? null;
$user_id = $_SESSION['user']['id'] ?? null;

if (!$school_id) {
    header('Location: index.php');
    exit;
}

?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Generate Barcode - Perpustakaan Online</title>
    <script src="../assets/js/theme-loader.js"></script>
    <script src="../assets/js/theme.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../assets/css/animations.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/index.css">
    <style>
        :root {
            --admin-blue: #3a7afe;
            --admin-blue-dark: #2f66d9;
            --card-bg: #f7f9fc;
            --card-border: #dce3ef;
            --title-color: #1b1f3b;
            --muted-text: #5b627a;
            --value-color: #333333;
            --shadow-1: 0 4px 10px rgba(0,0,0,0.06);
            --modal-shadow: 0 10px 30px rgba(0,0,0,0.1);
            font-family: 'Poppins', 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial;
        }

        .topbar strong {
            margin-left: 20px;
            font-size: 16px;
        }

        .search-container {
            margin-bottom: 20px;
        }

        .search-input-wrapper {
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 1px solid var(--card-border);
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            transition: box-shadow 0.18s ease, transform 0.12s ease;
            background: #ffffff;
            color: var(--title-color);
            box-shadow: 0 2px 6px rgba(19, 35, 58, 0.03);
        }

        .search-input:focus {
            outline: none;
            box-shadow: 0 6px 18px rgba(58, 122, 255, 0.12);
            transform: translateY(-1px);
            border-color: var(--admin-blue);
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #99a0b3;
            font-size: 18px;
        }

        .search-results {
            max-height: 600px;
            overflow-y: auto;
            display: none;
            margin-top: 12px;
        }

        .search-results.active {
            display: block;
        }

        .result-item {
            padding: 12px 16px;
            display: flex;
            gap: 12px;
            align-items: center;
            transition: transform 0.12s ease, box-shadow 0.12s ease;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            box-shadow: var(--shadow-1);
            margin-bottom: 12px;
        }

        .result-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 22px rgba(32,45,88,0.06);
        }

        .result-thumb {
            width: 48px;
            height: 68px;
            object-fit: cover;
            border-radius: 6px;
            flex-shrink: 0;
            border: 1px solid rgba(0,0,0,0.04);
            background: #fff;
        }

        .result-info {
            flex: 1;
        }

        .result-title {
            font-weight: 700;
            color: var(--title-color);
            margin-bottom: 6px;
            font-size: 14px;
        }

        .result-meta {
            font-size: 13px;
            color: var(--muted-text);
        }

        .result-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .result-right .result-meta {
            font-weight: 600;
            color: var(--title-color);
            font-size: 13px;
        }

        .btn-generate {
            padding: 8px 14px;
            background: var(--admin-blue);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.12s ease, box-shadow 0.12s ease, transform 0.08s ease;
            box-shadow: 0 6px 18px rgba(58,122,254,0.12);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-generate:hover {
            background: var(--admin-blue-dark);
            transform: translateY(-1px);
        }

        .btn-generate:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .empty-state { text-align: center; padding: 40px 20px; color: #94a0b5; }
        .loading { text-align: center; padding: 20px; color: #94a0b5; }

        /* Modal Styles */
        .modal { display: none; position: fixed; inset:0; background: rgba(0,0,0,0.45); z-index:1000; align-items: center; justify-content:center; }
        .modal.active { display:flex; }

        .modal-content {
            background: #ffffff;
            border-radius: 16px;
            padding: 36px;
            max-width: 680px;
            width: 94%;
            max-height: 86vh;
            overflow-y: auto;
            position: relative;
            box-shadow: var(--modal-shadow);
        }

        .modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
        .modal-title { font-size:20px; font-weight:800; color:var(--title-color); }
        .modal-close { background:none; border:none; font-size:24px; cursor:pointer; color:#98a0b3; }

        .barcode-preview { display:flex; flex-direction:column; gap:18px; }

        .book-info { padding: 8px 0; }
        .book-info-row { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:6px 0; }
        .book-info-label { color: var(--admin-blue); font-weight:700; font-size:13px; }
        .book-info-value { color: var(--value-color); font-weight:600; font-size:13px; }

        .barcode-section { display:flex; flex-direction:column; align-items:center; padding:14px; border-radius:10px; background: #f9fbff; }
        .barcode-label { color: var(--admin-blue); font-weight:800; margin-bottom:12px; font-size:12px; letter-spacing:0.6px; }
        .barcode-image { max-width:100%; height:auto; border-radius:6px; background:#fff; padding:8px; }

        .modal-actions { display:flex; gap:12px; margin-top:20px; }
        .btn-modal { flex:1; padding:12px; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:8px; }
        .btn-download { background:var(--admin-blue); color:#fff; box-shadow: 0 8px 22px rgba(58,122,254,0.12); }
        .btn-download:hover { background:var(--admin-blue-dark); }
        .btn-print { background:#fff; color:var(--title-color); border:1px solid var(--card-border); }

        @media (max-width: 768px) {
            .modal-content { padding: 20px; }
            .result-item { flex-direction: row; }
        }

        @media print {
            body { background: white; }
            .search-container, .search-results, .modal-header, .modal-actions, .topbar { display: none; }
            .modal-content { max-width:100%; padding:0; box-shadow:none; border:none; }
            .barcode-section { page-break-inside: avoid; }
        }
    </style>
</head>

<body>
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <div class="app">
        <div class="topbar" style="margin-left: -20px;">
            <strong>Generate Barcode Buku</strong>
            <div class="topbar-actions">
                <!-- Empty for now -->
            </div>
        </div>

        <div class="content">
            <!-- Search Section -->
            <div class="card" style="margin-bottom: 24px;">
                <div class="search-container">
                    <h3 style="margin-bottom: 16px; color: var(--text);">Cari Buku</h3>
                    <div class="search-input-wrapper">
                        <iconify-icon icon="mdi:magnify" class="search-icon"></iconify-icon>
                        <input 
                            type="text" 
                            id="searchInput" 
                            class="search-input"
                            placeholder="Cari berdasarkan judul, kode buku, atau penulis..."
                            autocomplete="off"
                        >
                    </div>
                </div>

                <!-- Search Results -->
                <div id="searchResults" class="search-results">
                    <!-- Results will be populated here -->
                </div>

                <!-- Empty State -->
                <div id="emptyState" class="empty-state" style="display: none;">
                    <div class="empty-state-icon">📚</div>
                    <p>Mulai mengetik untuk mencari buku</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div id="barcodeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Preview Barcode</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>

            <div class="barcode-preview">
                <!-- Book Info -->
                <div class="book-info">
                    <div class="book-info-row">
                        <span class="book-info-label">Judul Buku</span>
                        <span class="book-info-value" id="modalTitle">-</span>
                    </div>
                    <div class="book-info-row">
                        <span class="book-info-label">Kode Buku</span>
                        <span class="book-info-value" id="modalCode">-</span>
                    </div>
                    <div class="book-info-row">
                        <span class="book-info-label">Penulis</span>
                        <span class="book-info-value" id="modalAuthor">-</span>
                    </div>
                    <div class="book-info-row">
                        <span class="book-info-label">Stok</span>
                        <span class="book-info-value" id="modalStock">-</span>
                    </div>
                </div>

                <!-- Barcode -->
                <div class="barcode-section">
                    <div class="barcode-label">Barcode Code128</div>
                    <img id="barcodeImage" class="barcode-image" src="" alt="Barcode">
                </div>
            </div>

            <div class="modal-actions">
                <button class="btn-modal btn-download" onclick="downloadBarcodes()">
                    <iconify-icon icon="mdi:download"></iconify-icon>
                    Download PNG
                </button>
                <button class="btn-modal btn-print" onclick="printBarcodes()">
                    <iconify-icon icon="mdi:printer"></iconify-icon>
                    Cetak
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentBookData = null;
        let searchTimeout;

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function (e) {
            const query = e.target.value.trim();
            const emptyState = document.getElementById('emptyState');
            const searchResults = document.getElementById('searchResults');

            clearTimeout(searchTimeout);

            if (query.length < 2) {
                searchResults.classList.remove('active');
                emptyState.style.display = 'block';
                return;
            }

            emptyState.style.display = 'none';
            searchResults.innerHTML = '<div class="loading"><iconify-icon icon="mdi:loading" style="animation: spin 1s linear infinite;"></iconify-icon> Mencari...</div>';
            searchResults.classList.add('active');

            searchTimeout = setTimeout(() => {
                fetch(`api/barcode-api-inline.php?action=search&q=${encodeURIComponent(query)}`)
                    .then(res => {
                        if (!res.ok) throw new Error(`HTTP ${res.status}`);
                        return res.text();
                    })
                    .then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('Invalid JSON response:', text);
                            throw new Error('Server returned invalid JSON. Check server logs.');
                        }
                    })
                            .then(data => {
                        if (data.success && data.books && data.books.length > 0) {
                            searchResults.innerHTML = data.books.map(book => {
                                const coverSrc = book.cover ? ('../img/covers/' + escapeHtml(book.cover)) : '../assets/images/default-avatar.svg';
                                return `
                                <div class="result-item">
                                    <img src="${coverSrc}" class="result-thumb" onerror="this.src='../assets/images/default-avatar.svg'" alt="cover">
                                    <div class="result-info">
                                        <div class="result-title">${escapeHtml(book.judul)}</div>
                                        <div class="result-meta">${escapeHtml(book.penulis || '-')} • ${escapeHtml(book.kode_buku || '-')}</div>
                                    </div>
                                    <div class="result-right">
                                        <div class="result-meta">Stok: <strong style="color:var(--text);">${book.stok}</strong></div>
                                        <button class="btn-generate" onclick="generateBarcode(${book.id})">
                                            <iconify-icon icon="mdi:qrcode" style="font-size:16px"></iconify-icon>
                                            Generate
                                        </button>
                                    </div>
                                </div>`;
                            }).join('');
                        } else {
                            searchResults.innerHTML = '<div class="empty-state" style="padding:20px;"><p>Tidak ada hasil</p></div>';
                        }
                    })
                    .catch(err => {
                        console.error('Search error:', err);
                        searchResults.innerHTML = `<div class="empty-state" style="padding:20px;"><p>Error: ${escapeHtml(err.message)}</p></div>`;
                    });
            }, 300);
        });

        function generateBarcode(bookId) {
            const button = event.target;
            button.disabled = true;
            button.textContent = 'Generating...';

            const formData = new FormData();
            formData.append('action', 'generate');
            formData.append('book_id', bookId);

            fetch('api/barcode-api.php', {
                method: 'POST',
                body: formData
            })
                .then(res => {
                    if (!res.ok) throw new Error(`HTTP ${res.status}`);
                    return res.text();
                })
                .then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Invalid JSON response:', text);
                        throw new Error('Server returned invalid JSON. Check server logs.');
                    }
                })
                .then(data => {
                    if (data.success) {
                        currentBookData = data;
                        displayModal(data);
                    } else {
                        alert('Error: ' + (data.error || 'Unknown error'));
                    }
                    button.disabled = false;
                    button.innerHTML = 'Generate';
                })
                .catch(err => {
                    console.error('Generate error:', err);
                    alert('Error: ' + err.message);
                    button.disabled = false;
                    button.innerHTML = 'Generate';
                });
        }

        function displayModal(data) {
            const book = data.book;
            
            document.getElementById('modalTitle').textContent = book.judul || '-';
            document.getElementById('modalCode').textContent = book.kode_buku || '-';
            document.getElementById('modalAuthor').textContent = book.penulis || '-';
            document.getElementById('modalStock').textContent = book.stok || '-';
            
            document.getElementById('barcodeImage').src = 'data:image/png;base64,' + data.barcode;
            
            document.getElementById('barcodeModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('barcodeModal').classList.remove('active');
            currentBookData = null;
        }

        function downloadBarcodes() {
            if (!currentBookData) return;

            const book = currentBookData.book;
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            // Set canvas size for barcode only
            canvas.width = 600;
            canvas.height = 400;
            
            // White background
            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            // Title
            ctx.fillStyle = '#000000';
            ctx.font = 'bold 16px Arial';
            ctx.textAlign = 'center';
            ctx.fillText('BARCODE BUKU', canvas.width / 2, 40);
            
            // Book info
            ctx.font = '12px Arial';
            ctx.fillText(`${book.judul}`, canvas.width / 2, 80);
            ctx.fillText(`Kode: ${book.kode_buku}`, canvas.width / 2, 110);
            
            // Draw Barcode
            const barcodeImg = new Image();
            barcodeImg.src = document.getElementById('barcodeImage').src;
            barcodeImg.onload = function () {
                ctx.drawImage(barcodeImg, 50, 150, 500, 200);
                
                // Download
                canvas.toBlob(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `barcode-${book.kode_buku}.png`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                });
            };
        }

        function printBarcodes() {
            window.print();
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Close modal on outside click
        document.getElementById('barcodeModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>

</html>
