<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];

// Get all books
$stmt = $pdo->prepare('
    SELECT id, title, isbn, author FROM books 
    WHERE school_id = :sid
    ORDER BY title ASC
');
$stmt->execute(['sid' => $sid]);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Barcode Buku</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .controls {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .control-group {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        label {
            color: #666;
            font-weight: 500;
        }

        select,
        input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        button {
            padding: 8px 16px;
            background: #3A7FF2;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
        }

        button:hover {
            background: #2a5fb8;
        }

        button.secondary {
            background: #666;
        }

        button.secondary:hover {
            background: #555;
        }

        .barcode-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .barcode-card {
            background: white;
            padding: 12px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .barcode-card h3 {
            font-size: 11px;
            color: #333;
            margin-bottom: 8px;
            word-break: break-word;
            line-height: 1.3;
        }

        .barcode-image {
            width: 130px;
            height: 130px;
            margin: 0 auto 8px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .barcode-image img {
            max-width: 100%;
            max-height: 100%;
        }

        .barcode-info {
            font-size: 10px;
            color: #666;
            margin-top: 6px;
            word-break: break-word;
        }

        .barcode-info strong {
            color: #333;
            display: block;
            margin-top: 3px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        button.print-btn {
            background: #10B981;
        }

        button.print-btn:hover {
            background: #059669;
        }

        .search-box {
            flex: 1;
            min-width: 200px;
        }

        .search-box input {
            width: 100%;
        }

        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-height: 300px;
            overflow-y: auto;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 0 0 calc(50% - 8px);
        }

        .checkbox-item input[type="checkbox"] {
            cursor: pointer;
            width: 18px;
            height: 18px;
        }

        .checkbox-item label {
            margin: 0;
            cursor: pointer;
            color: #333;
            font-size: 13px;
        }

        .select-all-btn {
            background: #667eea;
            font-size: 12px;
            padding: 6px 12px;
        }

        .select-all-btn:hover {
            background: #5568d3;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .header,
            .controls,
            .button-group,
            .checkbox-group,
            .search-box {
                display: none;
            }

            .barcode-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 15px;
            }

            .barcode-card {
                box-shadow: none;
                border: 1px solid #eee;
                break-inside: avoid;
                padding: 10px;
            }

            .barcode-image {
                width: 100px;
                height: 100px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>📚 Print Barcode Buku</h1>
            <p style="color: #666; font-size: 14px;">Pilih buku untuk mencetak barcode ISBN</p>
        </div>

        <div class="button-group">
            <button class="print-btn" onclick="window.print()">🖨️ Cetak</button>
            <button class="secondary" onclick="history.back()">← Kembali</button>
        </div>

        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
            <div class="search-box">
                <input type="text" id="searchBox" placeholder="Cari buku..." oninput="filterBooks()">
            </div>
            <button class="select-all-btn" onclick="selectAll()">Pilih Semua</button>
            <button class="select-all-btn" onclick="deselectAll()">Batal Pilih</button>
            <span style="display: flex; align-items: center; color: #666;">
                <strong id="selectedCount">0</strong> dipilih
            </span>
        </div>

        <div class="checkbox-group" id="bookList">
            <?php foreach ($books as $book): ?>
                <div class="checkbox-item">
                    <input type="checkbox" class="book-checkbox" value="<?php echo htmlspecialchars($book['id']); ?>"
                        data-isbn="<?php echo htmlspecialchars($book['isbn']); ?>"
                        data-title="<?php echo htmlspecialchars($book['title']); ?>"
                        data-author="<?php echo htmlspecialchars($book['author'] ?? ''); ?>" onchange="updateDisplay()">
                    <label><?php echo htmlspecialchars($book['title']); ?></label>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="barcode-grid" id="barcodeContainer">
            <p style="text-align: center; color: #999; grid-column: 1/-1; padding: 20px;">
                Pilih buku di atas untuk menampilkan barcode
            </p>
        </div>
    </div>

    <script>
        const bookData = <?php echo json_encode($books); ?>;
        const container = document.getElementById('barcodeContainer');
        const selectedCountEl = document.getElementById('selectedCount');
        const searchBox = document.getElementById('searchBox');

        function updateDisplay() {
            const selected = Array.from(document.querySelectorAll('.book-checkbox:checked')).map(cb => ({
                id: cb.value,
                isbn: cb.dataset.isbn,
                title: cb.dataset.title,
                author: cb.dataset.author
            }));

            selectedCountEl.textContent = selected.length;

            if (selected.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: #999; grid-column: 1/-1; padding: 20px;">Pilih buku di atas untuk menampilkan barcode</p>';
                return;
            }

            container.innerHTML = selected.map(book => `
                <div class="barcode-card">
                    <h3>${book.title}</h3>
                    <div class="barcode-image">
                        <img src="api/generate-qrcode.php?type=book&value=${encodeURIComponent(book.isbn)}&size=130" 
                             alt="QR Code ${book.isbn}" 
                             loading="lazy">
                    </div>
                    <div class="barcode-info">
                        <strong>ISBN</strong>
                        ${book.isbn || '-'}
                        ${book.author ? '<div style="margin-top: 3px; font-size: 9px;">' + book.author + '</div>' : ''}
                    </div>
                </div>
            `).join('');
        }

        function filterBooks() {
            const query = searchBox.value.toLowerCase();
            const items = document.querySelectorAll('.checkbox-item');

            items.forEach(item => {
                const label = item.querySelector('label').textContent.toLowerCase();
                item.style.display = label.includes(query) ? 'flex' : 'none';
            });
        }

        function selectAll() {
            document.querySelectorAll('.book-checkbox:visible').forEach(cb => cb.checked = true);
            updateDisplay();
        }

        function deselectAll() {
            document.querySelectorAll('.book-checkbox').forEach(cb => cb.checked = false);
            updateDisplay();
        }
    </script>
</body>

</html>