<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];
$member_id = $user['id'];

// Get member data
$stmt = $pdo->prepare(
    'SELECT m.*, s.name AS school_name, s.location
     FROM members m
     LEFT JOIN schools s ON m.school_id = s.id
     WHERE m.id = :id AND m.school_id = :sid'
);
$stmt->execute(['id' => $member_id, 'sid' => $sid]);
$member = $stmt->fetch();

if (!$member) {
    header('Location: index.php');
    exit;
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kartu Pelajar Digital - Perpustakaan Online</title>
    <script src="../assets/js/theme-loader.js"></script>
    <script src="../assets/js/theme.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../assets/css/animations.css">
    <style>
        :root {
            --primary: #3A7FF2;
            --primary-2: #7AB8F5;
            --primary-dark: #0A1A4F;
            --bg: #F6F9FF;
            --muted: #F3F7FB;
            --card: #FFFFFF;
            --surface: #FFFFFF;
            --muted-surface: #F7FAFF;
            --border: #E6EEF8;
            --text: #0F172A;
            --text-muted: #50607A;
            --accent: #3A7FF2;
            --success: #10B981;
            --warning: #f59e0b;
            --danger: #EF4444;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --primary: #3A7FF2;
                --primary-2: #7AB8F5;
                --primary-dark: #0A1A4F;
                --bg: #0f172a;
                --muted: #1e293b;
                --card: #1e293b;
                --surface: #1e293b;
                --muted-surface: #334155;
                --border: #334155;
                --text: #f1f5f9;
                --text-muted: #94a3b8;
                --accent: #3A7FF2;
                --success: #10B981;
                --warning: #f59e0b;
                --danger: #EF4444;
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .header p {
            color: var(--text-muted);
            font-size: 14px;
        }

        .card-container {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            perspective: 1000px;
        }

        .student-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            width: 100%;
            max-width: 500px;
            border-radius: 20px;
            padding: 40px;
            color: white;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
        }

        .student-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
            background-size: 20px 20px;
        }

        .card-content {
            position: relative;
            z-index: 1;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.3);
            padding-bottom: 20px;
        }

        .card-logo {
            font-size: 24px;
            font-weight: 700;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .card-type {
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-school {
            text-align: center;
            margin-bottom: 25px;
        }

        .card-school-name {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .card-school-location {
            font-size: 12px;
            opacity: 0.9;
        }

        .card-student-info {
            margin-bottom: 25px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            align-items: center;
        }

        .info-label {
            font-size: 11px;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .info-value {
            font-size: 16px;
            font-weight: 700;
            text-align: right;
        }

        .card-barcode {
            background: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
        }

        .card-barcode img,
        .card-barcode svg {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        .card-barcode-label {
            color: var(--text);
            font-size: 11px;
            font-weight: 600;
            margin-top: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            opacity: 0.8;
            border-top: 2px solid rgba(255, 255, 255, 0.3);
            padding-top: 15px;
            margin-top: 15px;
        }

        .card-valid {
            text-align: left;
        }

        .card-signature {
            text-align: right;
        }

        .actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(58, 127, 242, 0.3);
        }

        .btn-secondary {
            background: var(--border);
            color: var(--text);
        }

        .btn-secondary:hover {
            background: var(--muted);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
        }

        .info-box {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            margin-top: 30px;
        }

        .info-box h3 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-box p {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.6;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
            transition: all 0.2s ease;
        }

        .back-link:hover {
            gap: 10px;
        }

        @media (max-width: 768px) {
            .student-card {
                max-width: 100%;
                padding: 30px 20px;
            }

            .card-header {
                flex-direction: column;
                gap: 15px;
                align-items: center;
            }

            .info-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }

            .info-value {
                text-align: left;
            }

            .card-footer {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .card-valid,
            .card-signature {
                text-align: center;
            }

            .actions {
                grid-template-columns: 1fr;
            }
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .header,
            .info-box,
            .actions,
            .back-link {
                display: none;
            }

            .card-container {
                margin: 0;
            }

            .student-card {
                max-width: 100%;
                box-shadow: none;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="profil.php" class="back-link">
            <iconify-icon icon="mdi:arrow-left"></iconify-icon>
            Kembali ke Profil
        </a>

        <div class="header">
            <h1>Kartu Pelajar Digital</h1>
            <p>Tunjukkan barcode untuk peminjaman buku di perpustakaan</p>
        </div>

        <div class="card-container">
            <div class="student-card">
                <div class="card-content">
                    <div class="card-header">
                        <div class="card-logo">📚 PERPUS</div>
                        <div class="card-type">Digital Student Card</div>
                    </div>

                    <div class="card-school">
                        <div class="card-school-name"><?= htmlspecialchars($member['school_name'] ?? 'Sekolah Anda') ?></div>
                        <div class="card-school-location">
                            <iconify-icon icon="mdi:map-marker" style="vertical-align: middle; margin-right: 4px;"></iconify-icon>
                            <?= htmlspecialchars($member['location'] ?? 'Indonesia') ?>
                        </div>
                    </div>

                    <div class="card-student-info">
                        <div class="info-row">
                            <span class="info-label">Nama Lengkap</span>
                            <span class="info-value"><?= htmlspecialchars($member['name']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">NISN / No. Induk</span>
                            <span class="info-value"><?= htmlspecialchars($member['nisn'] ?? '-') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Status</span>
                            <span class="info-value"><?= $member['status'] === 'active' ? '✓ Aktif' : '• Nonaktif' ?></span>
                        </div>
                    </div>

                    <div class="card-barcode">
                        <object data="api/generate-student-barcode.php?member_id=<?= $member['id'] ?>" type="image/svg+xml" style="width: 100%; max-height: 80px;"></object>
                        <div class="card-barcode-label">Scan barcode ini untuk meminjam buku</div>
                    </div>

                    <div class="card-footer">
                        <div class="card-valid">
                            <div style="font-size: 10px; opacity: 0.7;">Member ID</div>
                            <div><?= str_pad($member['id'], 6, '0', STR_PAD_LEFT) ?></div>
                        </div>
                        <div class="card-signature">
                            <div style="font-size: 10px; opacity: 0.7;">Berlaku seumur hidup</div>
                            <div><?= date('Y') ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="actions">
            <button onclick="window.print()" class="btn btn-primary">
                <iconify-icon icon="mdi:printer"></iconify-icon>
                Cetak Kartu
            </button>
            <a href="student-dashboard.php" class="btn btn-secondary">
                <iconify-icon icon="mdi:home"></iconify-icon>
                Ke Dashboard
            </a>
            <button onclick="downloadQR()" class="btn btn-success">
                <iconify-icon icon="mdi:download"></iconify-icon>
                Download Barcode
            </button>
        </div>

        <div class="info-box">
            <h3>
                <iconify-icon icon="mdi:information-outline"></iconify-icon>
                Cara Menggunakan Kartu
            </h3>
            <p>
                Tunjukkan barcode ini kepada admin perpustakaan saat ingin meminjam atau mengembalikan buku. Barcode Anda dapat discan untuk memverifikasi identitas dan mencatat transaksi peminjaman. Pastikan selalu membawa kartu digital ini atau screenshot barcode saat berkunjung ke perpustakaan.
            </p>
        </div>
    </div>

    <script>
        function downloadQR() {
            const svg = document.querySelector('.card-barcode object');
            const link = document.createElement('a');
            link.href = 'api/generate-student-barcode.php?member_id=<?= $member['id'] ?>';
            link.download = 'barcode-siswa-<?= str_pad($member['id'], 6, '0', STR_PAD_LEFT) ?>.svg';
            link.click();
        }
    </script>
</body>

</html>
