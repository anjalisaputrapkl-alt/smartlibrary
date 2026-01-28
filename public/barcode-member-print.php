<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];

// Get all members
$stmt = $pdo->prepare('
    SELECT id, name, nisn FROM members 
    WHERE school_id = :sid AND status = "active"
    ORDER BY name ASC
');
$stmt->execute(['sid' => $sid]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Barcode Anggota</title>
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
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .barcode-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .barcode-card h3 {
            font-size: 12px;
            color: #333;
            margin-bottom: 10px;
            word-break: break-word;
        }

        .barcode-image {
            width: 150px;
            height: 150px;
            margin: 0 auto 10px;
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
            font-size: 11px;
            color: #666;
            margin-top: 8px;
            word-break: break-word;
        }

        .barcode-info strong {
            color: #333;
            display: block;
            margin-top: 4px;
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

        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
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
            .checkbox-group {
                display: none;
            }

            .barcode-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 20px;
            }

            .barcode-card {
                box-shadow: none;
                border: 1px solid #eee;
                break-inside: avoid;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🏷️ Print Barcode Anggota</h1>
            <p style="color: #666; font-size: 14px;">Pilih anggota untuk mencetak barcode NISN</p>
        </div>

        <div class="button-group">
            <button class="print-btn" onclick="window.print()">🖨️ Cetak</button>
            <button class="secondary" onclick="history.back()">← Kembali</button>
        </div>

        <div class="checkbox-group">
            <button class="select-all-btn" onclick="selectAll()">Pilih Semua</button>
            <button class="select-all-btn" onclick="deselectAll()">Batal Pilih</button>
            <span style="margin-left: auto; color: #666;">
                <strong id="selectedCount">0</strong> dipilih
            </span>
        </div>

        <div class="checkbox-group">
            <?php foreach ($members as $member): ?>
                <div class="checkbox-item">
                    <input type="checkbox" class="member-checkbox" value="<?php echo htmlspecialchars($member['id']); ?>"
                        data-nisn="<?php echo htmlspecialchars($member['nisn']); ?>"
                        data-name="<?php echo htmlspecialchars($member['name']); ?>" onchange="updateDisplay()">
                    <label><?php echo htmlspecialchars($member['name']); ?></label>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="barcode-grid" id="barcodeContainer">
            <p style="text-align: center; color: #999; grid-column: 1/-1; padding: 20px;">
                Pilih anggota di atas untuk menampilkan barcode
            </p>
        </div>
    </div>

    <script>
        const memberData = <?php echo json_encode($members); ?>;
        const container = document.getElementById('barcodeContainer');
        const selectedCountEl = document.getElementById('selectedCount');

        function updateDisplay() {
            const selected = Array.from(document.querySelectorAll('.member-checkbox:checked')).map(cb => ({
                id: cb.value,
                nisn: cb.dataset.nisn,
                name: cb.dataset.name
            }));

            selectedCountEl.textContent = selected.length;

            if (selected.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: #999; grid-column: 1/-1; padding: 20px;">Pilih anggota di atas untuk menampilkan barcode</p>';
                return;
            }

            container.innerHTML = selected.map(member => `
                <div class="barcode-card">
                    <h3>${member.name}</h3>
                    <div class="barcode-image">
                        <img src="api/generate-qrcode.php?type=member&value=${encodeURIComponent(member.nisn)}&size=150" 
                             alt="QR Code ${member.nisn}" 
                             loading="lazy">
                    </div>
                    <div class="barcode-info">
                        <strong>NISN</strong>
                        ${member.nisn}
                    </div>
                </div>
            `).join('');
        }

        function selectAll() {
            document.querySelectorAll('.member-checkbox').forEach(cb => cb.checked = true);
            updateDisplay();
        }

        function deselectAll() {
            document.querySelectorAll('.member-checkbox').forEach(cb => cb.checked = false);
            updateDisplay();
        }
    </script>
</body>

</html>