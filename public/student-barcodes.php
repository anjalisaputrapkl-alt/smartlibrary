<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];

// Get school info
$stmt = $pdo->prepare('SELECT * FROM schools WHERE id = :sid');
$stmt->execute(['sid' => $sid]);
$school = $stmt->fetch();
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Barcode Siswa - Perpustakaan Online</title>
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
        
        /* Student Avatar */
        .result-avatar {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--accent), var(--text));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 20px;
            text-transform: uppercase;
            flex-shrink: 0;
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

        .barcode-card {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            background: #fff;
        }
        
        /* --- Student ID Card Mockup - Professional Design --- */
        .id-card-mockup {
            width: 100%;
            aspect-ratio: 1.586 / 1; /* Standard ID card ratio */
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            border-radius: 20px;
            padding: 24px;
            position: relative;
            box-shadow: 0 20px 40px -10px rgba(30, 58, 138, 0.4);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.1);
        }

        /* Decorative patterns */
        .id-card-mockup::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .id-card-mockup::after {
            content: '';
            position: absolute;
            bottom: -30px;
            left: -30px;
            width: 180px;
            height: 180px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
            pointer-events: none;
        }
        
        .id-card-header {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255,255,255,0.15);
            position: relative;
            z-index: 2;
        }
        
        .school-logo {
            width: 48px;
            height: 48px;
            background: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #1e3a8a;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .school-name {
            font-size: 16px;
            font-weight: 700;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .id-card-body {
            display: flex;
            gap: 20px;
            align-items: center;
            position: relative;
            z-index: 2;
            flex: 1;
            padding: 10px 0;
        }
        
        .id-card-photo {
            width: 90px;
            height: 110px;
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: white;
            font-weight: 700;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            backdrop-filter: blur(4px);
        }
        
        .id-card-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .id-card-details h3 {
            font-size: 22px;
            font-weight: 800;
            color: white;
            margin-bottom: 6px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            letter-spacing: -0.01em;
        }
        
        .id-card-details p {
            font-size: 14px;
            color: rgba(255,255,255,0.9);
            margin: 0;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .id-card-barcode-area {
            background: white;
            padding: 12px 20px;
            border-radius: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            z-index: 2;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
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
            <strong>Barcode Siswa</strong>
            <a href="index.php" style="font-size: 13px; color: var(--accent); font-weight: 600; display: flex; align-items: center; gap: 4px;">
                <iconify-icon icon="mdi:arrow-left"></iconify-icon>
                Kembali ke Dashboard
            </a>
        </div>

        <div class="content">
            <div class="section-card">
                <div class="search-title">
                    <iconify-icon icon="mdi:qrcode-plus" style="color: var(--accent); font-size: 24px;"></iconify-icon>
                    Pusat Barcode Siswa
                </div>
                <p class="search-description">
                    Kelola barcode akses untuk para anggota. Siap membantu mencetak label barcode.<!-- updated --><br>
                    Ketik minimal 2 karakter untuk melihat hasil.
                </p>
                <div class="search-input-wrapper">
                    <iconify-icon icon="lucide:users" class="search-icon"></iconify-icon>
                    <input 
                        type="text" 
                        id="searchInput" 
                        class="search-input"
                        placeholder="Ketik nama atau NISN untuk mencari siswa..."
                        autocomplete="off"
                    >
                </div>
                <div style="margin-top: 16px;">
                    <button class="btn-generate" onclick="fetchAllStudents()" style="width: 100%; height: 48px; font-size: 14px; border-radius: 10px; box-shadow: none;">
                        <iconify-icon icon="mdi:account-group" style="font-size: 20px;"></iconify-icon>
                        Generate Barcode untuk Seluruh Anggota Perpustakaan
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
                        <input type="checkbox" id="selectAllStudents" class="checkbox-custom" onchange="toggleSelectAll(this)" style="width: 18px; height: 18px; cursor: pointer;">
                        <label for="selectAllStudents" style="font-size: 14px; font-weight: 600; cursor: pointer; color: var(--text);">Pilih Semua Anggota</label>
                    </div>
                </div>
                <div id="searchResults" class="search-results">
                    <!-- Results will be populated here -->
                </div>
            </div>

                <!-- Empty State -->
                <div id="emptyState" class="empty-state">
                    <div class="empty-state-icon">
                        <iconify-icon icon="mdi:account-details-outline"></iconify-icon>
                    </div>
                    <p style="font-weight: 600; font-size: 16px;">Siap membantu mencetak label barcode.</p>
                    <p style="font-size: 14px; margin-top: 4px;">Ketik minimal 2 karakter untuk melihat hasil.</p>
                </div>
            </div>

            <!-- Bulk Action Bar -->
            <div id="bulkActionBar" class="bulk-action-bar">
                <span class="bulk-info-text"><span id="selectedCount">0</span> siswa dipilih</span>
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
                <div class="barcode-preview">
                    <!-- Student ID Mockup -->
                    <div class="id-card-mockup">
                        <div class="id-card-header">
                            <div class="school-logo">
                                <iconify-icon icon="mdi:school"></iconify-icon>
                            </div>
                            <div class="school-name"><?php echo htmlspecialchars($school['name'] ?? 'PERPUSTAKAAN ONLINE'); ?></div>
                        </div>
                        
                        <div class="id-card-body">
                            <img id="modalPhoto" src="../assets/images/default-avatar.svg" alt="Foto" class="id-card-photo" style="display:block; object-fit: cover;">
                            <div class="id-card-details">
                                <p style="font-size: 10px; margin-bottom: 4px; opacity: 0.6; text-transform: uppercase;">Student Name</p>
                                <h3 id="modalName">-</h3>
                                <p id="modalNISN">NISN: -</p>
                            </div>
                        </div>

                        <div class="id-card-barcode-area">
                            <svg id="barcodeImage" style="width: 100%; height: 60px;"></svg>
                        </div>
                    </div>
                    
                    <div style="text-align: center; color: var(--text-muted); font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; margin-top: 10px;">ID Card Preview Mode</div>
                </div>
            </div>

            <div id="modalBulkBody" class="barcode-grid" style="display: none;">
                <!-- Bulk entries will go here -->
            </div>

            <div class="modal-actions">
                <button class="btn-modal btn-print" onclick="printBarcode()">
                    <iconify-icon icon="mdi:printer"></iconify-icon>
                    Cetak
                </button>
            </div>
        </div>
    </div>

    <!-- JsBarcode CDN -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

    <script>
        let currentStudentData = null; // Can be a single object or an array
        let searchResultsData = [];
        let selectedStudents = new Set();
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
                console.log('Searching for:', query);
                fetch(`api/search-students.php?q=${encodeURIComponent(query)}`)
                    .then(async res => {
                        console.log('Response status:', res.status);
                        const data = await res.json();
                        if (!res.ok) {
                            throw new Error(data.message || `HTTP error! status: ${res.status}`);
                        }
                        return data;
                    })
                    .then(data => {
                        console.log('Search results:', data);
                        if (data.success && data.students && data.students.length > 0) {
                            searchResultsData = data.students;
                            document.getElementById('selectAllStudents').checked = false;

                            searchResults.innerHTML = data.students.map(student => {
                                const isChecked = selectedStudents.has(student.id);
                                const initial = student.name.charAt(0).toUpperCase();
                                const avatarContent = student.foto 
                                    ? `<img src="${escapeHtml(student.foto)}" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">` 
                                    : initial;
                                const avatarStyle = student.foto ? '' : `background: ${getGradient(initial)}`;

                                return `
                                <div class="result-item ${isChecked ? 'selected' : ''}" id="student-item-${student.id}" onclick="triggerCheckbox(${student.id})">
                                    <div class="checkbox-container">
                                        ${isChecked ? '<iconify-icon icon="mdi:check-bold"></iconify-icon>' : ''}
                                    </div>
                                    <div class="result-avatar" style="${avatarStyle}">${avatarContent}</div>
                                    <div class="result-info">
                                        <div class="result-title">${escapeHtml(student.name)}</div>
                                        <div class="result-meta">${escapeHtml(student.status || 'Aktif')}</div>
                                        <div class="result-extra">NISN: ${escapeHtml(student.nisn || '-')}</div>
                                        <div class="result-actions">
                                            <button class="btn-generate" onclick="event.stopPropagation(); generateBarcode(${student.id}, '${escapeHtml(student.name)}', '${escapeHtml(student.nisn || '')}', '${escapeHtml(student.status)}', '${escapeHtml(student.foto || '')}')">
                                                <iconify-icon icon="mdi:card-account-details-outline" style="font-size:16px"></iconify-icon>
                                                View ID Barcode
                                            </button>
                                        </div>
                                    </div>
                                    <input type="checkbox" class="student-checkbox" hidden 
                                           onchange="toggleSelect(${student.id}, this)" ${isChecked ? 'checked' : ''}>
                                </div>`;
                            }).join('');
                        } else if (!data.success) {
                            // Show API error message
                            searchResults.innerHTML = `<div class="empty-state" style="padding:40px;"><p style="color:#e74c3c"><strong>API Error:</strong><br>${escapeHtml(data.message || 'Unknown error')}<br><small>Error type: ${escapeHtml(data.error_type || 'unknown')}</small></p></div>`;
                        } else {
                            searchResults.innerHTML = '<div class="empty-state" style="padding:40px;"><p>Tidak ada hasil ditemukan</p></div>';
                        }
                    })
                    .catch(err => {
                        console.error('Search error:', err);
                        searchResults.innerHTML = `<div class="empty-state" style="padding:40px;"><p style="color:#e74c3c"><strong>Error:</strong> ${err.message}<br><small>Cek console untuk detail lengkap</small></p></div>`;
                    });
            }, 300);
        });

        async function fetchAllStudents() {
            const btn = event.currentTarget;
            const originalHtml = btn.innerHTML;
            
            try {
                btn.disabled = true;
                btn.innerHTML = '<iconify-icon icon="mdi:loading" style="animation: spin 1s linear infinite;"></iconify-icon> Mengambil Data...';
                
                const response = await fetch('api/search-students.php?all=1');
                const json = await response.json();
                
                if (!json.success) throw new Error(json.message || 'Failed to fetch students');
                
                if (json.count === 0) {
                    alert('Tidak ada siswa untuk di-generate.');
                    return;
                }
                
                if (confirm(`Generate barcode untuk ${json.count} siswa? Halaman mungkin akan sedikit lambat.`)) {
                    btn.innerHTML = '<iconify-icon icon="mdi:loading" style="animation: spin 1s linear infinite;"></iconify-icon> Rendering...';
                    
                    // Display directly since student generation is client-side
                    currentStudentData = json.students;
                    displayModal(json.students);
                }
            } catch (err) {
                console.error('Fetch all students error:', err);
                alert('Error: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        }

        function getGradient(initial) {
            const gradients = [
                'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)',
                'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
                'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
                'linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%)',
                'linear-gradient(135deg, #ec4899 0%, #db2777 100%)'
            ];
            const charCode = initial.charCodeAt(0);
            return gradients[charCode % gradients.length];
        }

        function generateBarcode(id, name, nisn, status, foto) {
            currentStudentData = { id, name, nisn, status, foto };
            displayModal(currentStudentData);
        }

        function displayModal(data) {
            const modalBody = document.getElementById('modalPreviewBody');
            const bulkBody = document.getElementById('modalBulkBody');
            const modalTitle = document.querySelector('.modal-title');
            
            // Handle bulk results
            if (Array.isArray(data)) {
                modalTitle.textContent = `Preview Barcode (${data.length} Siswa)`;
                modalBody.style.display = 'none';
                bulkBody.style.display = 'grid';
                
                bulkBody.innerHTML = data.map(student => `
                    <div class="barcode-card">
                        <div style="font-size: 13px; font-weight: 700; color: var(--title-color); margin-bottom: 8px; border-bottom: 1px solid #eee; padding-bottom: 4px;">
                            ${escapeHtml(student.name)}
                        </div>
                        <div style="font-size: 11px; color: var(--muted-text); margin-bottom: 10px;">
                            NISN: <strong>${escapeHtml(student.nisn || '-')}</strong>
                        </div>
                        <div style="display: flex; justify-content: center; background: #f9fbff; padding: 10px; border-radius: 8px;">
                            <svg id="bulk-barcode-${student.id}" class="barcode-image"></svg>
                        </div>
                    </div>
                `).join('');

                // Render barcodes for each student
                data.forEach(student => {
                    try {
                        JsBarcode(`#bulk-barcode-${student.id}`, student.nisn || student.id, {
                            format: "CODE128",
                            displayValue: true,
                            fontSize: 12,
                            width: 1.5,
                            height: 40,
                            margin: 5
                        });
                    } catch (e) {
                        console.error("Bulk barcode failed", e);
                    }
                });
            } 
            // Handle single result
            else {
                modalTitle.textContent = 'Preview Barcode';
                modalBody.style.display = 'block';
                bulkBody.style.display = 'none';
                
                document.getElementById('modalName').textContent = data.name;
                document.getElementById('modalNISN').textContent = 'NISN: ' + (data.nisn || '-');
                const photoEl = document.getElementById('modalPhoto');
                if (data.foto) {
                    photoEl.src = data.foto;
                } else {
                    photoEl.src = '../assets/images/default-avatar.svg';
                }
                
                try {
                    JsBarcode("#barcodeImage", data.nisn || data.id, {
                        format: "CODE128",
                        displayValue: true,
                        fontSize: 14,
                        width: 2.5,
                        height: 50,
                        margin: 5
                    });
                } catch (e) {
                    console.error("Barcode generation failed", e);
                }
            }
            
            document.getElementById('barcodeModal').classList.add('active');
        }

        function toggleSelect(id, checkbox) {
            const item = document.getElementById(`student-item-${id}`);
            const checkContainer = item.querySelector('.checkbox-container');

            if (checkbox.checked) {
                selectedStudents.add(id);
                item.classList.add('selected');
                checkContainer.innerHTML = '<iconify-icon icon="mdi:check-bold"></iconify-icon>';
            } else {
                selectedStudents.delete(id);
                item.classList.remove('selected');
                checkContainer.innerHTML = '';
            }
            updateBulkBar();
        }

        function triggerCheckbox(id) {
            const checkbox = document.querySelector(`#student-item-${id} .student-checkbox`);
            checkbox.checked = !checkbox.checked;
            toggleSelect(id, checkbox);
        }

        function toggleSelectAll(checkbox) {
            searchResultsData.forEach(student => {
                const itemCheckbox = document.querySelector(`#student-item-${student.id} .student-checkbox`);
                if (itemCheckbox) {
                    itemCheckbox.checked = checkbox.checked;
                    toggleSelect(student.id, itemCheckbox);
                }
            });
        }

        function updateBulkBar() {
            const bar = document.getElementById('bulkActionBar');
            const count = selectedStudents.size;
            
            if (count > 0) {
                document.getElementById('selectedCount').textContent = count;
                document.getElementById('btnSelectedCount').textContent = count;
                bar.style.display = 'flex';
                setTimeout(() => bar.classList.add('active'), 10);
            } else {
                bar.classList.remove('active');
                setTimeout(() => {
                    if (!bar.classList.contains('active')) bar.style.display = 'none';
                }, 300);
            }
        }

        function clearSelection() {
            selectedStudents.clear();
            document.querySelectorAll('.result-item.selected').forEach(el => el.classList.remove('selected'));
            document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('selectAllStudents').checked = false;
            updateBulkBar();
        }

        function generateBulk() {
            const ids = Array.from(selectedStudents);
            if (ids.length === 0) return;
            
            const selectedData = searchResultsData.filter(student => selectedStudents.has(student.id));
            currentStudentData = selectedData;
            displayModal(currentStudentData);
        }

        function closeModal() {
            document.getElementById('barcodeModal').classList.remove('active');
            // reset scroll position of modal content
            document.querySelector('.modal-content').scrollTop = 0;
        }

        function printBarcode() {
            if (!currentStudentData) return;
            
            let students = Array.isArray(currentStudentData) ? currentStudentData : [currentStudentData];
            const printWindow = window.open('', '_blank', 'width=800,height=600');
            
            // Collect SVG data for each student
            const studentBarcodes = students.map(student => {
                // Temporary svg for extraction
                const tempSvg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
                JsBarcode(tempSvg, student.nisn || student.id, {
                    format: "CODE128",
                    displayValue: true,
                    fontSize: 12,
                    width: 2,
                    height: 50,
                    margin: 10
                });
                return {
                    name: student.name,
                    nisn: student.nisn || student.id,
                    svg: tempSvg.outerHTML
                };
            });

            const html = studentBarcodes.map(bc => `
                <div class="barcode-item">
                    <div class="info">${bc.name} (${bc.nisn})</div>
                    <div class="svg-container">${bc.svg}</div>
                </div>
            `).join('');

            printWindow.document.write(`
                <html>
                <head>
                    <title>Cetak Barcode Siswa</title>
                    <style>
                        body { margin: 20px; font-family: sans-serif; }
                        .barcode-item { 
                            margin-bottom: 30px; 
                            text-align: center; 
                            page-break-inside: avoid;
                            border: 1px dashed #eee;
                            padding: 15px;
                            display: inline-block;
                            width: 320px;
                        }
                        .info { 
                            font-size: 12px; 
                            font-weight: bold; 
                            margin-bottom: 8px;
                            white-space: nowrap;
                            overflow: hidden;
                            text-overflow: ellipsis;
                        }
                        .svg-container svg { max-width: 100%; height: auto; }
                        @page { margin: 10mm; }
                    </style>
                </head>
                <body>
                    <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 20px;">
                        ${html}
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