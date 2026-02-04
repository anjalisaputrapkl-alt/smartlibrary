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
            padding: 16px;
            display: flex;
            gap: 16px;
            align-items: center;
            transition: transform 0.12s ease, box-shadow 0.12s ease;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            box-shadow: var(--shadow-1);
            margin-bottom: 12px;
            cursor: pointer;
        }

        .result-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 22px rgba(32,45,88,0.06);
        }

        .result-avatar {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--admin-blue), #667eea);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 20px;
            flex-shrink: 0;
        }

        .result-info {
            flex: 1;
        }

        .result-title {
            font-weight: 700;
            color: var(--title-color);
            margin-bottom: 4px;
            font-size: 15px;
        }

        .result-meta {
            font-size: 13px;
            color: var(--muted-text);
            font-family: 'Courier New', monospace;
        }

        .btn-generate {
            padding: 10px 16px;
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

        .empty-state { 
            text-align: center; 
            padding: 60px 20px; 
            color: #94a0b5; 
        }
        
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }

        .loading { 
            text-align: center; 
            padding: 20px; 
            color: #94a0b5; 
        }

        /* Modal Styles */
        .modal { 
            display: none; 
            position: fixed; 
            inset:0; 
            background: rgba(0,0,0,0.45); 
            z-index:1000; 
            align-items: center; 
            justify-content:center; 
        }
        
        .modal.active { 
            display:flex; 
        }

        .modal-content {
            background: #ffffff;
            border-radius: 16px;
            padding: 36px;
            max-width: 600px;
            width: 94%;
            max-height: 86vh;
            overflow-y: auto;
            position: relative;
            box-shadow: var(--modal-shadow);
        }

        .modal-header { 
            display:flex; 
            justify-content:space-between; 
            align-items:center; 
            margin-bottom:24px; 
        }
        
        .modal-title { 
            font-size:20px; 
            font-weight:800; 
            color:var(--title-color); 
        }
        
        .modal-close { 
            background:none; 
            border:none; 
            font-size:28px; 
            cursor:pointer; 
            color:#98a0b3; 
            line-height: 1;
        }

        .barcode-preview { 
            display:flex; 
            flex-direction:column; 
            gap:20px; 
        }

        .student-info { 
            padding: 16px; 
            background: var(--card-bg);
            border-radius: 12px;
        }
        
        .student-info-row { 
            display:flex; 
            justify-content:space-between; 
            padding:8px 0; 
            border-bottom: 1px solid var(--card-border);
        }
        
        .student-info-row:last-child {
            border-bottom: none;
        }
        
        .student-info-label { 
            color: var(--muted-text); 
            font-weight:600; 
            font-size:13px; 
        }
        
        .student-info-value { 
            color: var(--value-color); 
            font-weight:700; 
            font-size:13px; 
        }

        .barcode-section { 
            display:flex; 
            flex-direction:column; 
            align-items:center; 
            padding:24px; 
            border-radius:12px; 
            background: var(--card-bg); 
        }
        
        .barcode-label { 
            color: var(--admin-blue); 
            font-weight:800; 
            margin-bottom:16px; 
            font-size:13px; 
            letter-spacing:0.5px; 
            text-transform: uppercase;
        }
        
        .barcode-image { 
            max-width:100%; 
            height:auto; 
        }

        .modal-actions { 
            display:flex; 
            gap:12px; 
            margin-top:24px; 
        }
        
        .btn-modal { 
            flex:1; 
            padding:12px; 
            border-radius:10px; 
            font-weight:700; 
            font-size:14px; 
            cursor:pointer; 
            display:inline-flex; 
            align-items:center; 
            justify-content:center; 
            gap:8px; 
            border: none;
            transition: all 0.2s ease;
        }
        
        .btn-download { 
            background:var(--admin-blue); 
            color:#fff; 
            box-shadow: 0 6px 18px rgba(58,122,254,0.15); 
        }
        
        .btn-download:hover { 
            background:var(--admin-blue-dark); 
            transform: translateY(-2px);
        }
        
        .btn-print { 
            background:#fff; 
            color:var(--title-color); 
            border:1px solid var(--card-border); 
        }
        
        .btn-print:hover {
            border-color: var(--admin-blue);
            color: var(--admin-blue);
        }

        @media print {
            .modal-header, .modal-actions { display: none; }
            .modal-content { max-width:100%; padding:20px; box-shadow:none; }
            .barcode-section { page-break-inside: avoid; }
        }

        .topbar strong {
            margin-left: 20px;
            font-size: 16px;
        }
    </style>
</head>

<body>
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <div class="app">
        <div class="topbar" style="margin-left: -20px;">
            <strong>Barcode Siswa</strong>
        </div>

        <div class="content">
            <!-- Search Section -->
            <div class="card" style="margin-bottom: 24px;">
                <div class="search-container">
                    <h3 style="margin-bottom: 16px; color: var(--text);">Cari Siswa</h3>
                    <div class="search-input-wrapper">
                        <iconify-icon icon="mdi:magnify" class="search-icon"></iconify-icon>
                        <input 
                            type="text" 
                            id="searchInput" 
                            class="search-input"
                            placeholder="Cari berdasarkan nama atau NISN siswa..."
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
                    <div class="empty-state-icon">👥</div>
                    <p>Mulai mengetik untuk mencari siswa</p>
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
                <!-- Student Info -->
                <div class="student-info">
                    <div class="student-info-row">
                        <span class="student-info-label">Nama</span>
                        <span class="student-info-value" id="modalName">-</span>
                    </div>
                    <div class="student-info-row">
                        <span class="student-info-label">NISN</span>
                        <span class="student-info-value" id="modalNISN">-</span>
                    </div>
                    <div class="student-info-row">
                        <span class="student-info-label">Status</span>
                        <span class="student-info-value" id="modalStatus">-</span>
                    </div>
                </div>

                <!-- Barcode -->
                <div class="barcode-section">
                    <div class="barcode-label">Barcode Code128</div>
                    <svg id="barcodeImage" class="barcode-image"></svg>
                </div>
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
        let currentStudentData = null;
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
                            searchResults.innerHTML = data.students.map(student => {
                                const initial = student.name.charAt(0).toUpperCase();
                                return `
                                <div class="result-item" onclick="generateBarcode(${student.id}, '${escapeHtml(student.name)}', '${escapeHtml(student.nisn || '')}', '${escapeHtml(student.status)}')">
                                    <div class="result-avatar">${initial}</div>
                                    <div class="result-info">
                                        <div class="result-title">${escapeHtml(student.name)}</div>
                                        <div class="result-meta">NISN: ${escapeHtml(student.nisn || '-')}</div>
                                    </div>
                                    <button class="btn-generate">
                                        <iconify-icon icon="mdi:barcode" style="font-size:18px"></iconify-icon>
                                        Generate
                                    </button>
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

        function generateBarcode(id, name, nisn, status) {
            event.stopPropagation();
            
            currentStudentData = { id, name, nisn, status };
            
            document.getElementById('modalName').textContent = name;
            document.getElementById('modalNISN').textContent = nisn || '-';
            document.getElementById('modalStatus').textContent = status === 'active' ? 'Aktif' : 'Nonaktif';
            
            // Generate barcode
            try {
                JsBarcode("#barcodeImage", nisn || id, {
                    format: "CODE128",
                    displayValue: true,
                    fontSize: 14,
                    width: 2,
                    height: 60,
                    margin: 10
                });
            } catch (e) {
                console.error("Barcode generation failed", e);
            }
            
            document.getElementById('barcodeModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('barcodeModal').classList.remove('active');
            currentStudentData = null;
        }

        function printBarcode() {
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