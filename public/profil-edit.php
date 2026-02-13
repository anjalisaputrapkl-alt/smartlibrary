<?php
session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['school_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = require __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/MemberHelper.php';
require_once __DIR__ . '/../src/maintenance/DamageController.php';

$siswaId = (int) $_SESSION['user']['school_id'];

// Get student profile
$stmt = $pdo->prepare("
    SELECT 
        id_siswa, nama_lengkap, nis, nisn, kelas, jurusan,
        tanggal_lahir, jenis_kelamin, alamat, email, no_hp, foto
    FROM siswa
    WHERE id_siswa = ?
");
$stmt->execute([$siswaId]);
$siswa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$siswa) {
    die("Profil tidak ditemukan.");
}

$message = '';
$errorMessage = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
    $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');

    // Validasi
    if (empty($nama_lengkap)) {
        $errorMessage = "Nama lengkap tidak boleh kosong.";
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Email tidak valid.";
    } elseif (!empty($no_hp) && !preg_match('/^[0-9\-\+\s]{7,20}$/', $no_hp)) {
        $errorMessage = "Nomor HP tidak valid (hanya angka, -, +).";
    } else {
        // Update ke database dengan prepared statement
        try {
            $updateStmt = $pdo->prepare("
                UPDATE siswa 
                SET nama_lengkap=?, email=?, no_hp=?, alamat=?, tanggal_lahir=?, jenis_kelamin=?, updated_at=NOW()
                WHERE id_siswa=?
            ");
            $updateStmt->execute([
                $nama_lengkap,
                !empty($email) ? $email : null,
                !empty($no_hp) ? $no_hp : null,
                !empty($alamat) ? $alamat : null,
                !empty($tanggal_lahir) ? $tanggal_lahir : null,
                !empty($jenis_kelamin) ? $jenis_kelamin : null,
                $siswaId
            ]);

            $message = "Profil berhasil diperbarui!";

            // Refresh data dari database
            $stmt->execute([$siswaId]);
            $siswa = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $errorMessage = "Gagal memperbarui profil: " . $e->getMessage();
        }
    }
}

// Get member_id dengan auto-create jika belum ada
$memberHelper = new MemberHelper($pdo);
$userData = $_SESSION['user'];
$member_id = $memberHelper->getMemberId($userData);

// Get damage fines for this member
$schoolId = $userData['school_id'];
$damageController = new DamageController($pdo, $schoolId);
$memberDamageFines = $damageController->getByMember($member_id);
$totalMemberDenda = 0;
$pendingMemberDenda = 0;
foreach ($memberDamageFines as $fine) {
    $totalMemberDenda += $fine['fine_amount'];
    if ($fine['status'] === 'pending') {
        $pendingMemberDenda += $fine['fine_amount'];
    }
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Profil</title>
    <script src="../assets/js/db-theme-loader.js"></script>
    <?php require_once __DIR__ . '/../theme-loader.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/school-profile.css">
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
            --accent-light: #e0f2fe;
            --success: #10B981;
            --warning: #f59e0b;
            --danger: #EF4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-40px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Navigation Sidebar */
        .nav-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 240px;
            background: linear-gradient(135deg, #0b3d61 0%, #062d4a 100%);
            color: white;
            padding: 24px 0;
            z-index: 1002;
            overflow-y: auto;
            animation: slideInLeft 0.6s ease-out;
        }

        .nav-sidebar-header {
            padding: 0 24px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: white;
        }

        .nav-sidebar-header-icon {
            font-size: 32px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
        }

        .nav-sidebar-header-icon iconify-icon {
            width: 32px;
            height: 32px;
            color: white;
        }

        .nav-sidebar-header h2 {
            font-size: 14px;
            font-weight: 700;
            margin: 0;
        }

        .nav-sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-sidebar-menu li {
            margin: 0;
        }

        .nav-sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 13px;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
            position: relative;
        }

        .nav-sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .nav-sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border-left-color: white;
            font-weight: 600;
        }

        .nav-sidebar-menu iconify-icon {
            font-size: 18px;
            width: 24px;
            height: 24px;
            color: rgba(255, 255, 255, 0.8);
        }

        .nav-sidebar-menu a:hover iconify-icon,
        .nav-sidebar-menu a.active iconify-icon {
            color: white;
        }

        .nav-sidebar-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
            margin: 16px 0;
        }

        /* Header */
        .header {
            background: var(--card);
            border-bottom: 1px solid var(--border);
            padding: 16px 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            animation: slideDown 0.6s ease-out;
            margin-left: 240px;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
        }

        /* Container */
        .container-main {
            margin-left: 240px;
            padding: 24px;
            max-width: 1400px;
        }

        /* Card */
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            animation: fadeInUp 0.6s ease-out;
        }

        .card h2 {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 20px 0;
            color: var(--section-header-text, var(--text));
            background: var(--section-header, transparent);
            padding: 16px 20px;
            margin: -24px -24px 20px -24px;
            border-radius: 12px 12px 0 0;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 16px;
        }

        label {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        input,
        select,
        textarea {
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 13px;
            width: 100%;
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--card);
            color: var(--text);
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(11, 61, 97, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn.primary {
            background: var(--accent);
            color: white;
        }

        .btn.primary:hover {
            background: #062d4a;
            transform: translateY(-2px);
        }

        .btn.secondary {
            background: var(--border);
            color: var(--text);
        }

        .btn.secondary:hover {
            background: #d1d5db;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            border-left: 4px solid;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: #065f46;
            border-left-color: var(--success);
        }

        .alert-error {
            background-color: rgba(220, 38, 38, 0.1);
            color: #7f1d1d;
            border-left-color: var(--danger);
        }
    </style>
</head>

<body>
    <?php require __DIR__ . '/partials/student-sidebar.php'; ?>

    <div class="header">
        <div class="header-container">
            <h1>Edit Profil</h1>
        </div>
    </div>

    <div class="container-main">
        <?php if (!empty($message)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <!-- Total Denda Section -->
        <div
            style="animation: fadeInSlideUp 0.4s ease-out; margin-bottom: 24px; padding: 16px; background-color: <?php echo $pendingMemberDenda > 0 ? 'rgba(239, 68, 68, 0.05)' : 'rgba(16, 185, 129, 0.05)'; ?>; border-radius: 8px; border-left: 4px solid <?php echo $pendingMemberDenda > 0 ? '#ef4444' : '#10b981'; ?>; display: flex; align-items: center; gap: 16px;">
            <div style="font-size: 24px; color: <?php echo $pendingMemberDenda > 0 ? '#dc2626' : '#059669'; ?>;">
                <iconify-icon icon="<?php echo $pendingMemberDenda > 0 ? 'mdi:alert-circle' : 'mdi:check-circle'; ?>"
                    width="24" height="24"></iconify-icon>
            </div>
            <div style="flex: 1;">
                <div
                    style="font-size: 13px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">
                    Denda Tertunda</div>
                <div
                    style="font-size: 24px; font-weight: 700; color: <?php echo $pendingMemberDenda > 0 ? '#dc2626' : '#059669'; ?>;">
                    Rp <?php echo number_format($pendingMemberDenda, 0, ',', '.'); ?></div>
                <?php if ($pendingMemberDenda > 0): ?>
                    <p style="font-size: 12px; color: var(--text-muted); margin: 4px 0 0 0; line-height: 1.5;">Denda dari
                        kerusakan buku saat peminjaman. Hubungi admin untuk detail lebih lanjut.</p>
                <?php else: ?>
                    <p style="font-size: 12px; color: #10b981; margin: 4px 0 0 0;">✓ Tidak ada denda tertunda</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <h2>Ubah Informasi Profil</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" required
                        value="<?php echo htmlspecialchars($siswa['nama_lengkap']); ?>">
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($siswa['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Nomor HP</label>
                    <input type="tel" name="no_hp" value="<?php echo htmlspecialchars($siswa['no_hp'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir"
                        value="<?php echo htmlspecialchars($siswa['tanggal_lahir'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Jenis Kelamin</label>
                    <select name="jenis_kelamin">
                        <option value="">-- Pilih --</option>
                        <option value="L" <?php echo $siswa['jenis_kelamin'] === 'L' ? 'selected' : ''; ?>>Laki-laki
                        </option>
                        <option value="P" <?php echo $siswa['jenis_kelamin'] === 'P' ? 'selected' : ''; ?>>Perempuan
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Alamat</label>
                    <textarea name="alamat" rows="4"><?php echo htmlspecialchars($siswa['alamat'] ?? ''); ?></textarea>
                </div>

                <div class="actions" style="margin-top: 24px;">
                    <button type="submit" class="btn primary">Simpan Perubahan</button>
                    <a href="upload-foto.php" class="btn primary">Ganti Foto</a>
                    <a href="profil.php" class="btn secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
    </div>
    <script src="../assets/js/sidebar.js"></script>
</body>

</html>