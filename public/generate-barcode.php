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
    <link rel="stylesheet" href="../assets/css/index.css">
    <style>
        .topbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 23px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: fixed;
            top: 0;
            left: 240px;
            right: 0;
            z-index: 999;
        }

        .topbar strong {
            font-size: 15px;
            color: var(--text);
            margin-left: 0;
        }

        .content {
            padding: 32px;
            margin-top: 64px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .section-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
        }

        .search-container {
            margin-bottom: 24px;
        }

        .search-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 6px;
        }

        .search-description {
            font-size: 14px;
            color: var(--muted);
            margin-bottom: 24px;
            line-height: 1.5;
        }

        .search-input-wrapper {
            position: relative;
            max-width: 800px;
            margin: 0 auto;
        }

        .search-input {
            width: 100%;
            padding: 16px 20px 16px 56px;
            border: 2px solid var(--border);
            border-radius: 20px;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: var(--bg);
            color: var(--text);
            box-shadow: var(--shadow-sm);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px var(--accent-soft), var(--shadow);
            transform: translateY(-2px);
        }

        .search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 20px;
            transition: all 0.3s ease;
        }

        .search-input:focus + .search-icon {
            color: var(--accent);
            transform: translateY(-50%) scale(1.1);
        }

        .search-results {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-top: 24px;
            padding: 4px;
        }

        .result-item {
            position: relative;
            padding: 16px;
            display: flex;
            gap: 16px;
            align-items: center;
            transition: all 0.2s ease;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            cursor: pointer;
        }

        .result-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--accent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .result-item:hover {
            border-color: var(--accent);
            background: var(--bg);
        }

        .result-item.selected {
            background: var(--accent-soft);
            border-color: var(--accent);
            box-shadow: 0 0 0 1px var(--accent);
        }

        .result-item.selected::before {
            opacity: 1;
        }

        .result-thumb {
            width: 50px;
            height: 70px;
            object-fit: cover;
            border-radius: 6px;
            flex-shrink: 0;
            border: 1px solid var(--border);
        }

        .result-item:hover .result-thumb {
            transform: scale(1.05);
        }

        .result-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .result-title {
            font-weight: 700;
            color: var(--text);
            font-size: 15px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .result-meta {
            font-size: 13px;
            color: var(--muted);
            font-weight: 500;
        }

        .result-extra {
            font-family: monospace;
            font-size: 12px;
            color: var(--accent);
            font-weight: 700;
            margin-top: 2px;
        }

        .result-actions {
            margin-top: 8px;
            display: flex;
            gap: 8px;
        }

        .btn-generate {
            padding: 10px 16px;
            background: var(--accent);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(58, 127, 242, 0.2);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-generate:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .btn-secondary {
            padding: 8px 14px;
            background: var(--bg);
            color: var(--text);
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-secondary:hover {
            background: var(--border);
        }

        /* --- Custom Checkbox --- */
        .checkbox-container {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 24px;
            height: 24px;
            background: var(--bg);
            border: 2px solid var(--border);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            z-index: 2;
        }

        .result-item.selected .checkbox-container {
            background: var(--accent);
            border-color: var(--accent);
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 80px 24px;
            color: var(--muted);
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        /* Modal Styles Refined */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(8px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal.active {
            display: flex;
            animation: fadeIn 0.3s ease-out;
        }

        .modal-content {
            background: var(--surface);
            border-radius: 16px;
            padding: 32px;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            box-shadow: var(--shadow-md);
        }

        .modal.active .modal-content {
            transform: translateY(0);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }

        .modal-title {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.025em;
            color: var(--text);
        }

        .modal-close {
            background: var(--bg);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--muted);
            transition: all 0.2s ease;
        }

        .modal-close:hover {
            background: var(--border);
            color: var(--text);
            transform: rotate(90deg);
        }

        .barcode-preview-card {
            background: var(--bg);
            border-radius: 24px;
            padding: 32px;
            border: 1px dashed var(--border);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 24px;
        }

        .barcode-book-info {
            text-align: center;
            width: 100%;
        }

        .barcode-book-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .barcode-book-code {
            font-family: 'Monaco', 'Courier New', monospace;
            font-size: 14px;
            color: var(--accent);
            background: var(--accent-soft);
            padding: 4px 12px;
            border-radius: 8px;
            font-weight: 600;
        }

        .barcode-container {
            background: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: var(--shadow);
            width: 100%;
            display: flex;
            justify-content: center;
        }

        .bulk-action-bar {
            position: fixed;
            bottom: 32px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: var(--surface);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 10px 10px 10px 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 24px;
            box-shadow: var(--shadow-md);
            z-index: 1500;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .bulk-action-bar.active {
            transform: translateX(-50%) translateY(0);
        }

        .btn-bulk-generate {
            background: var(--accent);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 16px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s ease;
        }

        .btn-bulk-generate:hover {
            background: var(--accent);
            opacity: 0.9;
            transform: scale(1.05);
        }

        .barcode-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @media (max-width: 768px) {
            .content { padding: 20px; }
            .search-results { grid-template-columns: 1fr; }
            .barcode-grid { grid-template-columns: 1fr; }
            .modal-content { padding: 24px; }
        }

        @media (max-width: 768px) {
            .modal-content { padding: 20px; width: 96%; }
            .result-item { flex-direction: row; }
            .barcode-grid { grid-template-columns: 1fr; }
        }

        @media print {
            body { display: none; }
        }
    </style>
</head>

<body>
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <div class="app">
        <div class="topbar">
            <strong>Barcode Buku</strong>
            <a href="index.php" style="font-size: 13px; color: var(--accent); font-weight: 600; display: flex; align-items: center; gap: 4px;">
                <iconify-icon icon="mdi:arrow-left"></iconify-icon>
                Kembali ke Dashboard
            </a>
        </div>

        <div class="content">
            <div class="section-card">
                <div class="search-title">
                    <iconify-icon icon="mdi:qrcode-plus" style="color: var(--accent); font-size: 24px;"></iconify-icon>
                    Pusat Barcode Buku
                </div>
                <p class="search-description">
                    Kelola label dan barcode untuk seluruh koleksi buku. Siap membantu mencetak label barcode.<br>
                    Ketik minimal 2 karakter untuk melihat hasil.
                </p>
                <div class="search-input-wrapper">
                    <iconify-icon icon="lucide:search" class="search-icon"></iconify-icon>
                    <input 
                        type="text" 
                        id="searchInput" 
                        class="search-input"
                        placeholder="Cari berdasarkan judul, kode buku, atau penulis..."
                        autocomplete="off"
                    >
                </div>
                <div style="margin-top: 16px;">
                    <button class="btn-generate" onclick="generateAll()" style="width: 100%; height: 48px; font-size: 14px; border-radius: 10px; box-shadow: none;">
                        <iconify-icon icon="mdi:qrcode-scan" style="font-size: 20px;"></iconify-icon>
                        Generate Barcode untuk Seluruh Koleksi Buku
                    </button>
                </div>
            </div>

            <div id="searchResultsWrapper" style="display: none; margin-top: 32px;">
                <div class="search-title" style="margin-bottom: 16px;">
                    <iconify-icon icon="mdi:format-list-bulleted" style="color: var(--accent); font-size: 22px;"></iconify-icon>
                    Hasil Pencarian
                </div>
                <div class="select-all-wrapper" style="margin-bottom: 16px;">
                    <div style="display: flex; align-items: center; gap: 12px; background: var(--surface); padding: 12px 16px; border-radius: 10px; border: 1px solid var(--border);">
                        <input type="checkbox" id="selectAllBooks" class="checkbox-custom" onchange="toggleSelectAll(this)" style="width: 18px; height: 18px; cursor: pointer;">
                        <label for="selectAllBooks" style="font-size: 14px; font-weight: 600; cursor: pointer; color: var(--text);">Pilih Semua Buku</label>
                    </div>
                </div>
                <div id="searchResults" class="search-results">
                    <!-- Results will be populated here -->
                </div>
            </div>

                <!-- Empty State -->
                <div id="emptyState" class="empty-state">
                    <div class="empty-state-icon">
                        <iconify-icon icon="mdi:book-search-outline"></iconify-icon>
                    </div>
                    <p style="font-weight: 600; font-size: 16px;">Siap membantu mencetak label barcode.</p>
                    <p style="font-size: 14px; margin-top: 4px;">Ketik minimal 2 karakter untuk melihat hasil.</p>
                </div>
            </div>

            <!-- Bulk Action Bar -->
            <div id="bulkActionBar" class="bulk-action-bar">
                <span class="bulk-info-text"><span id="selectedCount">0</span> buku dipilih</span>
                <button class="btn-bulk-generate" onclick="generateBulk()">
                    <iconify-icon icon="mdi:qrcode-plus"></iconify-icon>
                    Generate Barcode (<span id="btnSelectedCount">0</span>)
                </button>
                <button onclick="clearSelection()" style="background:none; border:none; color:#ffb0b0; cursor:pointer; font-size:13px; font-weight:600;">Batal</button>
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

            <div id="modalPreviewBody">
                <div class="barcode-preview-card">
                    <div class="barcode-book-info">
                        <div class="barcode-book-title" id="modalTitle">-</div>
                        <span class="barcode-book-code" id="modalCode">-</span>
                        <div style="margin-top: 12px; font-size: 13px; color: var(--muted);" id="modalAuthor">-</div>
                    </div>

                    <div class="barcode-container">
                        <img id="barcodeImage" style="max-width: 100%; height: auto;" src="" alt="Barcode">
                    </div>
                    
                    <div style="font-size: 11px; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em;">Barcode Label Preview</div>
                </div>
            </div>
            
            <div id="modalBulkBody" class="barcode-grid" style="display: none; padding: 10px; background: var(--bg); border-radius: 20px;">
                <!-- Bulk entries will go here -->
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
        let currentBookData = null; // Can be a single object or an array of results
        let searchResultsData = [];
        let selectedBooks = new Set();
        let searchTimeout;

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function (e) {
            const query = e.target.value.trim();
            const emptyState = document.getElementById('emptyState');
            const searchResults = document.getElementById('searchResults');
            const searchResultsWrapper = document.getElementById('searchResultsWrapper');

            clearTimeout(searchTimeout);

            if (query.length < 2) {
                searchResultsWrapper.style.display = 'none';
                searchResults.classList.remove('active');
                emptyState.style.display = 'block';
                searchResultsData = [];
                return;
            }

            emptyState.style.display = 'none';
            searchResults.innerHTML = '<div class="loading"><iconify-icon icon="mdi:loading" style="animation: spin 1s linear infinite;"></iconify-icon> Mencari...</div>';
            searchResultsWrapper.style.display = 'block';
            searchResults.classList.add('active');

            searchTimeout = setTimeout(() => {
                fetch(`api/barcode-api.php?action=search&q=${encodeURIComponent(query)}`)
                    .then(res => {
                        if (!res.ok) throw new Error(`HTTP ${res.status}`);
                        return res.json();
                    })
                    .then(json => {
                        if (json.success) {
                            const books = json.books;
                            searchResultsData = books;
                            document.getElementById('selectAllBooks').checked = false;
                            
                            if (books && books.length > 0) {
                                searchResults.innerHTML = books.map(book => {
                                    const isChecked = selectedBooks.has(book.id);
                                    const coverSrc = book.cover ? ('../img/covers/' + escapeHtml(book.cover)) : '../assets/images/default-avatar.svg';
                                    return `
                                    <div class="result-item ${isChecked ? 'selected' : ''}" id="book-item-${book.id}" onclick="triggerCheckbox(${book.id})">
                                        <div class="checkbox-container">
                                            ${isChecked ? '<iconify-icon icon="mdi:check-bold"></iconify-icon>' : ''}
                                        </div>
                                        <img src="${coverSrc}" class="result-thumb" onerror="this.src='../assets/images/default-avatar.svg'" alt="cover">
                                        <div class="result-info">
                                            <div class="result-title">${escapeHtml(book.judul)}</div>
                                            <div class="result-meta">${escapeHtml(book.penulis || '-')}</div>
                                            <div class="result-extra">${escapeHtml(book.kode_buku || '-')}</div>
                                            <div class="result-actions">
                                                <button class="btn-generate" onclick="event.stopPropagation(); generateBarcode(${book.id})">
                                                    <iconify-icon icon="mdi:qrcode-view" style="font-size:16px"></iconify-icon>
                                                    View Barcode
                                                </button>
                                            </div>
                                        </div>
                                        <input type="checkbox" class="book-checkbox" hidden 
                                               onchange="toggleSelect(${book.id}, this)" ${isChecked ? 'checked' : ''}>
                                    </div>`;
                                }).join('');
                            } else {
                                searchResults.innerHTML = '<div class="empty-state" style="padding:20px;"><p>Tidak ada hasil</p></div>';
                            }
                        } else {
                            throw new Error(json.error || 'Invalid data');
                        }
                    })
                    .catch(err => {
                        console.error('Search error:', err);
                        searchResults.innerHTML = `<div class="empty-state" style="padding:20px;"><p>Error: ${escapeHtml(err.message)}</p></div>`;
                    });
            }, 300);
        });

        async function generateAll() {
            const btn = event.currentTarget;
            const originalHtml = btn.innerHTML;
            
            try {
                btn.disabled = true;
                btn.innerHTML = '<iconify-icon icon="mdi:loading" style="animation: spin 1s linear infinite;"></iconify-icon> Mengambil Data...';
                
                const response = await fetch('api/barcode-api.php?action=get_all_ids');
                const json = await response.json();
                
                if (!json.success) throw new Error(json.error || 'Failed to fetch IDs');
                
                if (json.count === 0) {
                    alert('Tidak ada buku untuk di-generate.');
                    return;
                }
                
                if (confirm(`Generate barcode untuk ${json.count} buku? Halaman mungkin akan sedikit lambat.`)) {
                    btn.innerHTML = '<iconify-icon icon="mdi:loading" style="animation: spin 1s linear infinite;"></iconify-icon> Generating...';
                    
                    const formData = new FormData();
                    formData.append('action', 'generate_bulk');
                    json.ids.forEach(id => formData.append('book_ids[]', id));
                    
                    const bulkResponse = await fetch('api/barcode-api.php', {
                        method: 'POST',
                        body: formData
                    });
                    const bulkJson = await bulkResponse.json();
                    
                    if (bulkJson.success) {
                        displayModal(bulkJson);
                    } else {
                        throw new Error(bulkJson.error || 'Bulk generation failed');
                    }
                }
            } catch (err) {
                console.error('Generate all error:', err);
                alert('Error: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        }

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
            const modalBody = document.getElementById('modalPreviewBody');
            const bulkBody = document.getElementById('modalBulkBody');
            const modalTitle = document.querySelector('.modal-title');
            const downloadBtn = document.querySelector('.btn-download');
            
            // Handle bulk results
            if (data.results) {
                currentBookData = data.results;
                modalTitle.textContent = `Preview Barcode (${data.count} Buku)`;
                modalBody.style.display = 'none';
                bulkBody.style.display = 'grid';
                downloadBtn.style.display = 'none'; // Hide download for bulk
                
                bulkBody.innerHTML = data.results.map(res => `
                    <div class="barcode-card">
                        <div style="font-size: 13px; font-weight: 700; color: var(--title-color); margin-bottom: 8px; border-bottom: 1px solid #eee; padding-bottom: 4px;">
                            ${escapeHtml(res.book.judul)}
                        </div>
                        <div style="font-size: 11px; color: var(--muted); margin-bottom: 10px;">
                            Kode: <strong>${escapeHtml(res.book.kode_buku)}</strong>
                        </div>
                        <div style="display: flex; justify-content: center; background: #f9fbff; padding: 10px; border-radius: 8px;">
                            <img class="print-target-barcode" src="data:image/png;base64,${res.barcode}" style="max-width: 100%; height: auto;">
                        </div>
                    </div>
                `).join('');
            } 
            // Handle single result
            else {
                currentBookData = data;
                modalTitle.textContent = 'Preview Barcode';
                modalBody.style.display = 'block';
                bulkBody.style.display = 'none';
                downloadBtn.style.display = 'flex'; // Show download for single
                
                const book = data.book;
                document.getElementById('modalTitle').textContent = book.judul || '-';
                document.getElementById('modalCode').textContent = book.kode_buku || '-';
                document.getElementById('modalAuthor').textContent = book.penulis || '-';
                document.getElementById('barcodeImage').src = 'data:image/png;base64,' + data.barcode;
            }
            
            document.getElementById('barcodeModal').classList.add('active');
        }

        function toggleSelect(id, checkbox) {
            const item = document.getElementById(`book-item-${id}`);
            const checkContainer = item.querySelector('.checkbox-container');
            
            if (checkbox.checked) {
                selectedBooks.add(id);
                item.classList.add('selected');
                checkContainer.innerHTML = '<iconify-icon icon="mdi:check-bold"></iconify-icon>';
            } else {
                selectedBooks.delete(id);
                item.classList.remove('selected');
                checkContainer.innerHTML = '';
            }
            updateBulkBar();
        }

        function triggerCheckbox(id) {
            const checkbox = document.querySelector(`#book-item-${id} .book-checkbox`);
            checkbox.checked = !checkbox.checked;
            toggleSelect(id, checkbox);
        }

        function toggleSelectAll(checkbox) {
            searchResultsData.forEach(book => {
                const itemCheckbox = document.querySelector(`#book-item-${book.id} .book-checkbox`);
                if (itemCheckbox) {
                    itemCheckbox.checked = checkbox.checked;
                    toggleSelect(book.id, itemCheckbox);
                }
            });
        }

        function updateBulkBar() {
            const bar = document.getElementById('bulkActionBar');
            const count = selectedBooks.size;
            
            if (count > 0) {
                document.getElementById('selectedCount').textContent = count;
                document.getElementById('btnSelectedCount').textContent = count;
                bar.style.display = 'flex';
                // Trigger reflow for animation
                setTimeout(() => bar.classList.add('active'), 10);
            } else {
                bar.classList.remove('active');
                setTimeout(() => {
                    if (!bar.classList.contains('active')) bar.style.display = 'none';
                }, 300);
            }
        }

        function clearSelection() {
            selectedBooks.clear();
            document.querySelectorAll('.result-item.selected').forEach(el => el.classList.remove('selected'));
            document.querySelectorAll('.book-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('selectAllBooks').checked = false;
            updateBulkBar();
        }

        function generateBulk() {
            const ids = Array.from(selectedBooks);
            if (ids.length === 0) return;
            
            const btn = document.querySelector('.btn-bulk-generate');
            const originalContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<iconify-icon icon="mdi:loading" style="animation: spin 1s linear infinite;"></iconify-icon> Processing...';
            
            const formData = new FormData();
            formData.append('action', 'generate_bulk');
            formData.append('book_ids', JSON.stringify(ids));
            
            fetch('api/barcode-api.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    displayModal(data);
                } else {
                    alert('Error: ' + data.error);
                }
                btn.disabled = false;
                btn.innerHTML = originalContent;
            })
            .catch(err => {
                console.error(err);
                alert('Connection error');
                btn.disabled = false;
                btn.innerHTML = originalContent;
            });
        }

        function closeModal() {
            document.getElementById('barcodeModal').classList.remove('active');
            // reset scroll position of modal content
            document.querySelector('.modal-content').scrollTop = 0;
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
            if (!currentBookData) return;
            
            let barcodes = [];
            if (Array.isArray(currentBookData)) {
                barcodes = currentBookData.map(res => ({
                    base64: res.barcode,
                    title: res.book.judul,
                    code: res.book.kode_buku
                }));
            } else {
                barcodes = [{
                    base64: currentBookData.barcode,
                    title: currentBookData.book.judul,
                    code: currentBookData.book.kode_buku
                }];
            }
            
            const printWindow = window.open('', '_blank', 'width=800,height=600');
            
            const barcodeHtml = barcodes.map(bc => `
                <div class="barcode-item">
                    <div class="info">${bc.title} (${bc.code})</div>
                    <img src="data:image/png;base64,${bc.base64}">
                </div>
            `).join('');

            printWindow.document.write(`
                <html>
                <head>
                    <title>Cetak Barcode</title>
                    <style>
                        body { margin: 20px; font-family: sans-serif; }
                        .barcode-item { 
                            margin-bottom: 30px; 
                            text-align: center; 
                            page-break-inside: avoid;
                            border: 1px dashed #eee;
                            padding: 10px;
                            display: inline-block;
                            width: 300px;
                        }
                        .info { 
                            font-size: 11px; 
                            font-weight: bold; 
                            margin-bottom: 5px;
                            white-space: nowrap;
                            overflow: hidden;
                            text-overflow: ellipsis;
                        }
                        img { max-width: 100%; height: auto; }
                        @page { margin: 10mm; }
                    </style>
                </head>
                <body>
                    <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 20px;">
                        ${barcodeHtml}
                    </div>
                    <script>
                        window.onload = function() {
                            setTimeout(() => {
                                window.print();
                                window.close();
                            }, 500);
                        };
                    <\/script>
                </body>
                </html>
            `);
            printWindow.document.close();
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
