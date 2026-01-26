<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['school_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = require __DIR__ . '/../src/db.php';
$userId = (int) $_SESSION['user']['id'];
$schoolId = (int) $_SESSION['user']['school_id'];

$success_message = '';
$error_message = '';
$isEditing = false;

// Handle photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto']) && isset($_POST['upload_photo'])) {
    try {
        $file = $_FILES['foto'];

        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload failed: ' . $file['error']);
        }

        // Check file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('Ukuran file terlalu besar (max 5MB)');
        }

        // Check MIME type
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, $allowed_types)) {
            throw new Exception('Format file harus JPG, PNG, atau WEBP');
        }

        // Create upload directory if not exists
        $upload_dir = __DIR__ . '/uploads/siswa';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Generate unique filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'siswa_' . $userId . '_' . time() . '_' . uniqid() . '.' . strtolower($ext);
        $filepath = $upload_dir . '/' . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Gagal menyimpan file');
        }

        // Update siswa table with photo path
        $photo_path = 'uploads/siswa/' . $filename;
        $update = $pdo->prepare("UPDATE siswa SET foto = ?, updated_at = NOW() WHERE id_siswa = ?");
        $update->execute([$photo_path, $userId]);

        $success_message = '✅ Foto berhasil diubah!';

        // Refresh siswa data to show new photo
        $stmt = $pdo->prepare("SELECT foto FROM siswa WHERE id_siswa = ?");
        $stmt->execute([$userId]);
        $siswa['foto'] = $stmt->fetch(PDO::FETCH_ASSOC)['foto'];
    } catch (Exception $e) {
        $error_message = '❌ Error upload: ' . htmlspecialchars($e->getMessage());
        error_log('Photo upload error: ' . $e->getMessage());
    }
}

// Handle form submission (save custom fields)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    try {
        // Validate and sanitize input
        $kelas = trim($_POST['kelas'] ?? '');
        $jurusan = trim($_POST['jurusan'] ?? '');
        $tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
        $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $no_hp = trim($_POST['no_hp'] ?? '');

        // Update custom fields in siswa table
        $update = $pdo->prepare("
            UPDATE siswa 
            SET 
                kelas = ?,
                jurusan = ?,
                tanggal_lahir = ?,
                jenis_kelamin = ?,
                alamat = ?,
                no_hp = ?,
                updated_at = NOW()
            WHERE id_siswa = ?
        ");
        $update->execute([
            $kelas ?: null,
            $jurusan ?: null,
            $tanggal_lahir ?: null,
            $jenis_kelamin ?: null,
            $alamat ?: null,
            $no_hp ?: null,
            $userId
        ]);

        $success_message = 'Profil berhasil diperbarui!';
    } catch (Exception $e) {
        $error_message = '❌ Error: ' . htmlspecialchars($e->getMessage());
        error_log('Profile update error: ' . $e->getMessage());
    }
}

// First, get user data from users table (source of truth for login)
try {
    $stmt = $pdo->prepare("
        SELECT id, school_id, name, nisn, email, role, is_verified, created_at
        FROM users 
        WHERE id = ? AND school_id = ?
    ");
    $stmt->execute([$userId, $schoolId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        die("User tidak ditemukan. Hubungi administrator.");
    }
} catch (Exception $e) {
    error_log('Error fetching user: ' . $e->getMessage());
    die("Terjadi kesalahan saat memuat data pengguna.");
}

// Now try to get extended profile from siswa table
// If not exists, create from user data
try {
    $stmt = $pdo->prepare("
        SELECT 
            id_siswa, nama_lengkap, nisn, kelas, jurusan,
            tanggal_lahir, jenis_kelamin, alamat, email, no_hp, foto,
            created_at, updated_at
        FROM siswa
        WHERE id_siswa = ?
    ");
    $stmt->execute([$userId]);
    $siswa = $stmt->fetch(PDO::FETCH_ASSOC);

    // If siswa record doesn't exist, create one from user data
    if (!$siswa) {
        try {
            $insert = $pdo->prepare("
                INSERT INTO siswa 
                (id_siswa, nama_lengkap, nisn, email, created_at, updated_at)
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ");
            $insert->execute([
                $userId,
                $userData['name'],
                $userData['nisn'],
                $userData['email']
            ]);

            // Fetch the newly created record
            $stmt = $pdo->prepare("
                SELECT 
                    id_siswa, nama_lengkap, nisn, kelas, jurusan,
                    tanggal_lahir, jenis_kelamin, alamat, email, no_hp, foto,
                    created_at, updated_at
                FROM siswa
                WHERE id_siswa = ?
            ");
            $stmt->execute([$userId]);
            $siswa = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error creating siswa record: ' . $e->getMessage());
            // Fallback: use user data
            $siswa = [
                'id_siswa' => $userData['id'],
                'nama_lengkap' => $userData['name'],
                'nisn' => $userData['nisn'],
                'email' => $userData['email'],
                'kelas' => null,
                'jurusan' => null,
                'tanggal_lahir' => null,
                'jenis_kelamin' => null,
                'alamat' => null,
                'no_hp' => null,
                'foto' => null,
                'created_at' => $userData['created_at'],
                'updated_at' => null
            ];
        }
    }
} catch (Exception $e) {
    error_log('Error fetching siswa: ' . $e->getMessage());
    die("Terjadi kesalahan saat memuat profil.");
}

// Format dates
$tanggalLahir = !empty($siswa['tanggal_lahir']) ? date('d M Y', strtotime($siswa['tanggal_lahir'])) : '-';
$createdAt = !empty($siswa['created_at']) ? date('d M Y, H:i', strtotime($siswa['created_at'])) : '-';
$updatedAt = !empty($siswa['updated_at']) ? date('d M Y, H:i', strtotime($siswa['updated_at'])) : '-';

// Gender display
$genderDisplay = match ($siswa['jenis_kelamin'] ?? null) {
    'L', 'M' => 'Laki-laki',
    'P', 'F' => 'Perempuan',
    default => '-'
};

// Photo - get from siswa table if exists, otherwise default
$photoUrl = $siswa['foto'] ? '/perpustakaan-online/public/' . htmlspecialchars($siswa['foto']) : '/perpustakaan-online/assets/img/default-avatar.png';

$pageTitle = 'Profil Saya';
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil Saya</title>
    <script src="../assets/js/db-theme-loader.js"></script>
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

        /* Hamburger Menu Button */
        .nav-toggle {
            display: none;
            position: fixed;
            top: 4px;
            left: 12px;
            z-index: 999;
            background: var(--card);
            color: var(--text);
            cursor: pointer;
            width: 44px;
            height: 44px;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            padding: 0;
            transition: all 0.2s ease;
            border: none;
        }

        .nav-toggle:hover {
            background: var(--bg);
        }

        .nav-toggle:active {
            transform: scale(0.95);
        }

        .nav-toggle iconify-icon {
            width: 24px;
            height: 24px;
            color: var(--accent);
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

        .header-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text);
            margin-left: 0;
        }

        .header-brand-icon {
            font-size: 32px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--accent-light);
            border-radius: 8px;
        }

        .header-brand-icon iconify-icon {
            width: 32px;
            height: 32px;
            color: var(--accent);
        }

        .header-brand-text h2 {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
        }

        .header-brand-text p {
            font-size: 12px;
            color: var(--text-muted);
            margin: 2px 0 0 0;
        }

        .header-user {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header-user-info {
            text-align: right;
        }

        .header-user-info p {
            font-size: 13px;
            margin: 0;
        }

        .header-user-info .name {
            font-weight: 600;
            color: var(--text);
        }

        .header-user-info .role {
            color: var(--text-muted);
            font-size: 12px;
        }

        .header-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 16px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .header-logout {
            padding: 8px 16px;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: var(--bg);
            color: var(--text);
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }

        .header-logout:hover {
            background: #f0f0f0;
            border-color: var(--text);
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

        .profile-header {
            display: flex;
            gap: 24px;
            align-items: flex-start;
            margin-bottom: 32px;
        }

        .profile-photo {
            width: 120px;
            height: 120px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid var(--border);
            flex-shrink: 0;
        }

        .profile-info h2 {
            margin: 0 0 8px 0;
            font-size: 18px;
            font-weight: 600;
            color: var(--section-header-text, var(--text));
            background: transparent;
            padding: 12px 16px;
            margin: 8px 0 8px -24px;
            padding-left: 24px;
        }

        .profile-info p {
            margin: 0;
            color: var(--text-muted);
            font-size: 13px;
        }

        .divider {
            border-top: 1px solid var(--border);
            margin: 24px 0;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .info-item {
            padding: 12px;
            background: var(--surface);
            border-radius: 8px;
        }

        .info-label {
            display: block;
            color: var(--text-muted);
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 4px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .info-value {
            color: var(--text);
            font-size: 13px;
            font-weight: 500;
        }

        .meta-section {
            background: var(--surface);
            border-radius: 8px;
            padding: 12px;
            margin-top: 16px;
        }

        .meta-item {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 12px;
            color: var(--text-muted);
        }

        .meta-item strong {
            color: var(--text);
            font-weight: 600;
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

        /* Form Styles */
        .profile-form {
            width: 100%;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            display: block;
            color: var(--text);
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
            text-transform: capitalize;
        }

        .form-input {
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-family: 'Inter', system-ui, sans-serif;
            font-size: 13px;
            color: var(--text);
            background: var(--surface);
            transition: all 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(11, 61, 97, 0.1);
            background: var(--surface);
        }

        .form-input::placeholder {
            color: var(--text-muted);
        }

        textarea.form-input {
            resize: vertical;
            min-height: 80px;
        }

        @media (max-width: 768px) {
            .nav-toggle {
                display: flex;
            }

            .nav-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                width: 240px;
                box-shadow: 2px 0 8px rgba(0, 0, 0, 0.15);
            }

            .nav-sidebar.active {
                transform: translateX(0);
            }

            .header {
                margin-left: 0;
                padding: 12px 0;
                padding-left: 12px;
            }

            .header-container {
                flex-wrap: wrap;
                padding: 0 16px 0 60px;
                gap: 12px;
            }

            .header-brand {
                flex: 0 1 auto;
                min-width: auto;
            }

            .header-brand-icon {
                font-size: 24px;
                width: 32px;
                height: 32px;
            }

            .header-brand-text h2 {
                font-size: 14px;
            }

            .header-brand-text p {
                font-size: 11px;
            }

            .header-user {
                flex: 1;
                justify-content: flex-end;
                gap: 12px;
                order: 3;
                width: 100%;
            }

            .header-user-info {
                display: none;
            }

            .header-user-avatar {
                width: 36px;
                height: 36px;
                font-size: 14px;
            }

            .header-logout {
                padding: 6px 12px;
                font-size: 12px;
            }

            .container-main {
                margin-left: 0;
                padding: 16px;
            }

            .profile-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .button-group {
                flex-direction: column;
            }

            .btn {
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .nav-toggle {
                width: 40px;
                height: 40px;
                left: 10px;
                top: 4px;
            }

            .nav-toggle iconify-icon {
                width: 20px;
                height: 20px;
            }

            .nav-sidebar {
                width: 200px;
            }

            .header {
                padding: 10px 0;
                padding-left: 10px;
            }

            .header-container {
                padding: 0 12px 0 50px;
                gap: 8px;
            }

            .header-brand {
                flex: 0;
                min-width: auto;
            }

            .header-brand-icon {
                font-size: 20px;
                width: 28px;
                height: 28px;
            }

            .header-brand-text {
                display: none;
            }

            .header-user-avatar {
                width: 32px;
                height: 32px;
                font-size: 12px;
            }

            .header-logout {
                padding: 5px 10px;
                font-size: 11px;
            }

            .container-main {
                padding: 12px;
            }

            .profile-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .button-group {
                flex-direction: column;
            }

            .btn {
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <?php require __DIR__ . '/partials/student-sidebar.php'; ?>

    <!-- Hamburger Menu Button -->
    <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
        <iconify-icon icon="mdi:menu" width="24" height="24"></iconify-icon>
    </button>

    <!-- Global Student Header -->
    <?php include 'partials/student-header.php'; ?>

    <div class="container-main">
        <div class="card">
            <div class="profile-header">
                <img src="<?php echo htmlspecialchars($photoUrl); ?>" alt="Foto" class="profile-photo">
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></h2>
                    <p><?php echo htmlspecialchars($siswa['nisn'] ?? 'NISN: -'); ?> -
                        <?php echo htmlspecialchars($siswa['status'] ?? 'active'); ?>
                    </p>
                </div>
            </div>

            <!-- Photo Upload Section -->
            <div style="background: var(--surface); border-radius: 8px; padding: 16px; margin-bottom: 24px;">
                <h4 style="color: var(--text); font-size: 14px; font-weight: 600; margin-bottom: 12px;">Ubah Foto Profil
                </h4>
                <form method="POST" enctype="multipart/form-data"
                    style="display: flex; gap: 12px; align-items: flex-start;">
                    <div style="flex: 1;">
                        <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" class="form-input"
                            style="width: 100%;" required>
                        <small style="color: var(--text-muted); display: block; margin-top: 4px;">Format: JPG, PNG, WEBP
                            (Max
                            5MB)</small>
                    </div>
                    <button type="submit" name="upload_photo" value="1" class="btn primary"
                        style="margin-top: 0;">Upload</button>
                </form>
            </div>

            <div class="divider"></div>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form method="POST" class="profile-form" id="form-profile">
                <!-- Read-only Fields (Auto-synced from members) -->
                <div style="margin-bottom: 24px;">
                    <h3
                        style="color: var(--text-muted); font-size: 12px; text-transform: uppercase; margin-bottom: 12px; font-weight: 600;">
                        Informasi dari Registrasi (Tidak dapat diubah)</h3>

                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Nama Lengkap</span>
                            <div class="info-value"><?php echo htmlspecialchars($siswa['nama_lengkap'] ?? '-'); ?></div>
                        </div>

                        <div class="info-item">
                            <span class="info-label">NISN</span>
                            <div class="info-value"><?php echo htmlspecialchars($siswa['nisn'] ?? '-'); ?></div>
                        </div>

                        <div class="info-item">
                            <span class="info-label">Email</span>
                            <div class="info-value"><?php echo htmlspecialchars($siswa['email'] ?? '-'); ?></div>
                        </div>

                        <div class="info-item">
                            <span class="info-label">Nomor Telepon</span>
                            <div class="info-value"><?php echo htmlspecialchars($siswa['no_hp'] ?? '-'); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Editable Fields -->
                <div style="margin-bottom: 24px;">
                    <h3
                        style="color: var(--text-muted); font-size: 12px; text-transform: uppercase; margin-bottom: 12px; font-weight: 600;">
                        Data Pribadi (Dapat diubah)</h3>

                    <div class="info-grid">
                        <div class="form-group">
                            <label class="form-label">Kelas</label>
                            <input type="text" name="kelas" class="form-input"
                                value="<?php echo htmlspecialchars($siswa['kelas'] ?? ''); ?>"
                                placeholder="Contoh: XII RPL">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Jurusan</label>
                            <input type="text" name="jurusan" class="form-input"
                                value="<?php echo htmlspecialchars($siswa['jurusan'] ?? ''); ?>"
                                placeholder="Contoh: Rekayasa Perangkat Lunak">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-input">
                                <option value="">-- Pilih --</option>
                                <option value="L" <?php echo ($siswa['jenis_kelamin'] === 'L') ? 'selected' : ''; ?>>
                                    Laki-laki</option>
                                <option value="P" <?php echo ($siswa['jenis_kelamin'] === 'P') ? 'selected' : ''; ?>>
                                    Perempuan</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" class="form-input"
                                value="<?php echo htmlspecialchars($siswa['tanggal_lahir'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" class="form-input" rows="3"
                                placeholder="Jalan, No., Kelurahan, Kecamatan, Kota"><?php echo htmlspecialchars($siswa['alamat'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nomor HP</label>
                            <input type="tel" name="no_hp" class="form-input"
                                value="<?php echo htmlspecialchars($siswa['no_hp'] ?? ''); ?>"
                                placeholder="Contoh: 081234567890">
                        </div>
                    </div>
                </div>

                <input type="hidden" name="save_profile" value="1">
            </form>

            <div class="meta-section">
                <div class="meta-item">
                    <strong>Terdaftar:</strong>
                    <span><?php echo htmlspecialchars($createdAt); ?></span>
                </div>
                <div class="meta-item">
                    <strong>Diperbarui:</strong>
                    <span><?php echo htmlspecialchars($updatedAt); ?></span>
                </div>
            </div>

            <div class="button-group">
                <button type="submit" form="form-profile" class="btn primary">Simpan Perubahan</button>
                <a href="student-dashboard.php" class="btn secondary">Kembali</a>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar on hamburger menu click
        const navToggle = document.getElementById('navToggle');
        const navSidebar = document.querySelector('.nav-sidebar');

        if (navToggle && navSidebar) {
            navToggle.addEventListener('click', function () {
                navSidebar.classList.toggle('active');
            });

            // Close sidebar when clicking outside of it
            document.addEventListener('click', function (event) {
                if (!navSidebar.contains(event.target) && event.target !== navToggle && !navToggle.contains(event.target)) {
                    navSidebar.classList.remove('active');
                }
            });
        }
    </script>
</body>

</html>