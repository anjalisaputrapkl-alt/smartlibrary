<?php

/**
 * EXAMPLE: School Admin Dashboard - Multi-Tenant Aware
 * 
 * File: public/admin-dashboard.php
 * 
 * Fitur:
 * - Tampilkan status sekolah (trial/active/suspended)
 * - Tampilkan trust score dan factors
 * - Tampilkan kode aktivasi (masked)
 * - Tampilkan trial status dan days remaining
 * - Tampilkan kapasitas limits (books, students, borrows)
 * - Tombol "Ajukan Aktivasi" (hanya untuk trial)
 * - Warning jika approaching limits atau trial akan expired
 */

session_start();
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/MultiTenantManager.php';
require_once __DIR__ . '/../src/TrialLimitsManager.php';

$pdo = require __DIR__ . '/../src/db.php';

// Require authentication
if (empty($_SESSION['user'])) {
    header('Location: /perpustakaan-online/?login_required=1');
    exit;
}

$school_id = $_SESSION['user']['school_id'];
$mtManager = new MultiTenantManager($pdo);
$limitsManager = new TrialLimitsManager($pdo, $mtManager);

$school = $mtManager->getSchool($school_id);
$trustScore = $mtManager->getTrustScore($school_id);
$activationCodeMasked = $mtManager->getActivationCodeMasked($school_id);
$allLimits = $limitsManager->getAllLimits($school_id);
$warnings = $limitsManager->getWarnings($school_id);

// Handle activation request
$activation_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_activation'])) {
    try {
        $mtManager->requestActivation($school_id, $_SESSION['user']['id']);
        $activation_message = 'Permintaan aktivasi telah dikirim. Sistem akan mengevaluasi status sekolah Anda.';

        // Refresh trust score
        $trustScore = $mtManager->recalculateTrustScore($school_id);
        $school = $mtManager->getSchool($school_id);
    } catch (Exception $e) {
        $activation_message = 'Error: ' . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - <?php echo htmlspecialchars($school['name']); ?></title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: system-ui, -apple-system, sans-serif;
            background: #f5f5f5;
            color: #333;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            margin: 0 0 8px;
            font-size: 28px;
        }

        header p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 12px;
            margin-top: 12px;
        }

        .status-trial {
            background: #fef08a;
            color: #92400e;
            border: 1px solid #fcd34d;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .status-suspended {
            background: #fee2e2;
            color: #7f1d1d;
            border: 1px solid #fecaca;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .card h3 {
            margin: 0 0 16px;
            font-size: 16px;
            color: #333;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #666;
            font-weight: 500;
        }

        .info-value {
            color: #333;
            font-weight: 600;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            margin-top: 8px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: #3b82f6;
            transition: width 0.3s ease;
        }

        .progress-fill.warning {
            background: #f59e0b;
        }

        .progress-fill.critical {
            background: #ef4444;
        }

        .warnings {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .warning-item {
            margin: 8px 0;
            font-size: 14px;
            color: #7f1d1d;
        }

        .activation-section {
            background: #f0f9ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .activation-section h3 {
            margin: 0 0 12px;
            color: #1e40af;
        }

        .activation-section p {
            margin: 8px 0;
            color: #1e40af;
            font-size: 14px;
            line-height: 1.5;
        }

        .code-display {
            background: white;
            border: 1px dashed #bfdbfe;
            padding: 12px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 16px;
            color: #1e40af;
            margin: 12px 0;
            text-align: center;
        }

        .button {
            display: inline-block;
            padding: 12px 20px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s;
        }

        .button:hover {
            background: #1d4ed8;
        }

        .button-secondary {
            background: #6b7280;
        }

        .button-secondary:hover {
            background: #4b5563;
        }

        .message {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .message.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .message.error {
            background: #fee2e2;
            color: #7f1d1d;
            border: 1px solid #fecaca;
        }

        .trust-score-details {
            background: #f9fafb;
            border-radius: 4px;
            padding: 12px;
            margin-top: 12px;
            font-size: 13px;
        }

        .trust-score-detail-item {
            padding: 4px 0;
            color: #666;
        }

        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #2563eb;
            margin: 12px 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <h1><?php echo htmlspecialchars($school['name']); ?></h1>
            <p>ID Sekolah: #<?php echo $school_id; ?> | Admin:
                <?php echo htmlspecialchars($_SESSION['user']['name']); ?>
            </p>
            <span class="status-badge status-<?php echo $school['status']; ?>">
                <?php echo ucfirst($school['status']); ?>
            </span>
        </header>

        <?php if ($activation_message): ?>
            <div class="message success">
                <?php echo htmlspecialchars($activation_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($warnings)): ?>
            <div class="warnings">
                <?php foreach ($warnings as $warning): ?>
                    <div class="warning-item">
                        <strong><?php echo ucfirst($warning['type']); ?>:</strong>
                        <?php echo htmlspecialchars($warning['message']); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Status & Activation Section -->
        <?php if ($school['status'] === 'trial'): ?>
            <div class="activation-section">
                <h3>Aktivasi Sekolah</h3>
                <p>
                    Sekolah Anda masih dalam status TRIAL dengan akses terbatas.
                    Ajukan aktivasi untuk mendapatkan akses penuh ke semua fitur.
                </p>

                <div style="margin: 16px 0; padding: 12px; background: white; border-radius: 4px;">
                    <div class="info-row">
                        <span class="info-label">Status Sekolah:</span>
                        <span class="info-value">Trial</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Hari Trial Tersisa:</span>
                        <span class="info-value">
                            <?php echo $allLimits['trial_days_remaining'] ?? 0; ?> hari
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Kode Aktivasi:</span>
                        <span class="info-value"><?php echo $activationCodeMasked; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Trust Score:</span>
                        <span class="info-value"><?php echo $trustScore; ?>/95</span>
                    </div>
                </div>

                <div class="trust-score-details">
                    <strong>Faktor Trust Score:</strong><br>
                    Ajukan aktivasi untuk mendapat +10 poin<br>
                    Email admin .sch.id: +15 poin<br>
                    Kode aktivasi dimasukkan: +20 poin<br>
                    Aktivitas sistem wajar: +25 poin<br>
                    Trial > 7 hari: +10 poin<br>
                    Minimal 5 transaksi: +10 poin<br>
                    <strong>Ambang batas: 70 poin</strong>
                </div>

                <form method="POST" style="margin-top: 20px;">
                    <button type="submit" name="request_activation" class="button">
                        Ajukan Aktivasi Sekolah
                    </button>
                </form>

                <?php if ($school['activation_requested_at']): ?>
                    <p style="color: #059669; margin-top: 12px; font-size: 13px;">
                        Anda sudah mengajukan aktivasi pada
                        <?php echo date('d M Y H:i', strtotime($school['activation_requested_at'])); ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Limits & Capacity -->
        <div class="grid">
            <div class="card">
                <h3>Buku</h3>
                <div class="stat-number"><?php echo $allLimits['books']['current']; ?></div>
                <div class="info-row">
                    <span class="info-label">Kapasitas:</span>
                    <span class="info-value">
                        <?php echo $allLimits['books']['current']; ?> / <?php echo $allLimits['books']['max']; ?>
                    </span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill <?php echo $allLimits['books']['percentage'] > 80 ? 'warning' : ''; ?>"
                        style="width: <?php echo min($allLimits['books']['percentage'], 100); ?>%"></div>
                </div>
                <div style="font-size: 13px; color: #666; margin-top: 8px;">
                    <?php echo $allLimits['books']['percentage']; ?>% terisi
                </div>
            </div>

            <div class="card">
                <h3>Siswa Aktif</h3>
                <div class="stat-number"><?php echo $allLimits['students']['current']; ?></div>
                <div class="info-row">
                    <span class="info-label">Kapasitas:</span>
                    <span class="info-value">
                        <?php echo $allLimits['students']['current']; ?> / <?php echo $allLimits['students']['max']; ?>
                    </span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill <?php echo $allLimits['students']['percentage'] > 80 ? 'warning' : ''; ?>"
                        style="width: <?php echo min($allLimits['students']['percentage'], 100); ?>%"></div>
                </div>
                <div style="font-size: 13px; color: #666; margin-top: 8px;">
                    <?php echo $allLimits['students']['percentage']; ?>% terisi
                </div>
            </div>

            <div class="card">
                <h3>Peminjaman Bulan Ini</h3>
                <div class="stat-number"><?php echo $allLimits['borrows']['current']; ?></div>
                <div class="info-row">
                    <span class="info-label">Kapasitas:</span>
                    <span class="info-value">
                        <?php echo $allLimits['borrows']['current']; ?> / <?php echo $allLimits['borrows']['max']; ?>
                    </span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill <?php echo $allLimits['borrows']['percentage'] > 80 ? 'warning' : ''; ?>"
                        style="width: <?php echo min($allLimits['borrows']['percentage'], 100); ?>%"></div>
                </div>
                <div style="font-size: 13px; color: #666; margin-top: 8px;">
                    <?php echo $allLimits['borrows']['percentage']; ?>% terisi
                    (<?php echo $allLimits['borrows']['period']; ?>)
                </div>
            </div>
        </div>

        <!-- School Information -->
        <div class="card">
            <h3>Informasi Sekolah</h3>
            <div class="info-row">
                <span class="info-label">Nama:</span>
                <span class="info-value"><?php echo htmlspecialchars($school['name']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span
                    class="info-value"><?php echo $school['email'] ? htmlspecialchars($school['email']) : '-'; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="info-value"><?php echo ucfirst($school['status']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Terdaftar:</span>
                <span class="info-value"><?php echo date('d M Y', strtotime($school['created_at'])); ?></span>
            </div>
        </div>
    </div>
</body>

</html>