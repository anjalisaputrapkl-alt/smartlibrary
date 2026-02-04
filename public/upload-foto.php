<?php
session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['school_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = require __DIR__ . '/../src/db.php';
$siswaId = (int) $_SESSION['user']['id'];  // Use user ID, not school_id

// Get current photo
$stmt = $pdo->prepare("SELECT foto FROM siswa WHERE id_siswa = ?");
$stmt->execute([$siswaId]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$currentFoto = $result['foto'] ?? '';

$message = '';
$errorMessage = '';

// Create upload directory if not exists
$uploadDir = __DIR__ . '/uploads/siswa';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto'])) {
    $file = $_FILES['foto'];

    // Validasi file
    $allowed = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 2 * 1024 * 1024; // 2MB

    $errorMsg = '';

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = "Gagal upload file.";
    } elseif ($file['size'] > $maxSize) {
        $errorMsg = "Ukuran file terlalu besar (maksimal 2MB).";
    } elseif (!in_array($file['type'], $allowed)) {
        $errorMsg = "Tipe file tidak didukung. Gunakan JPG, PNG, atau GIF.";
    } else {
        // Generate filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFilename = 'siswa_' . $siswaId . '_' . time() . '.' . $ext;
        $uploadPath = $uploadDir . '/' . $newFilename;

        // Upload file
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // Construct public URL (Relative path)
            $photoUrl = 'uploads/siswa/' . $newFilename;

            // Delete old photo if exists
            if (!empty($currentFoto)) {
                $oldPath = __DIR__ . str_replace('/perpustakaan-online/public', '', $currentFoto);
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            // Update database
            try {
                $updateStmt = $pdo->prepare("UPDATE siswa SET foto=?, updated_at=NOW() WHERE id_siswa=?");
                $updateStmt->execute([$photoUrl, $siswaId]);
                $message = "Foto berhasil diperbarui!";
                
                // Update session to keep it in sync with database
                if (isset($_SESSION['user'])) {
                    $_SESSION['user']['foto'] = $photoUrl;
                }

                // Refresh current foto
                $stmt->execute([$siswaId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $currentFoto = $result['foto'] ?? '';
            } catch (Exception $e) {
                $errorMsg = "Gagal menyimpan foto: " . $e->getMessage();
                @unlink($uploadPath);
            }
        } else {
            $errorMsg = "Gagal mengupload file ke server.";
        }
    }

    if ($errorMsg) {
        $errorMessage = $errorMsg;
    }
}

// Photo display
$photoUrl = !empty($currentFoto) 
    ? (strpos($currentFoto, 'uploads/') === 0 ? './'.$currentFoto : $currentFoto)
    : '../assets/images/default-avatar.svg';
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ganti Foto</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/school-profile.css">
    <style>
        :root {
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #0f1724;
            --muted: #6b7280;
            --accent: #0b3d61;
            --accent-light: #e0f2fe;
            --border: #e2e8f0;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
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
            background: linear-gradient(135deg, var(--accent) 0%, #062d4a 100%);
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
        }

        .photo-preview {
            width: 120px;
            height: 120px;
            border-radius: 8px;
            object-fit: cover;
            border: 2px solid var(--border);
            margin-bottom: 16px;
        }

        .upload-area {
            border: 2px dashed var(--border);
            border-radius: 8px;
            padding: 32px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: #f9fafb;
        }

        .upload-area:hover {
            border-color: var(--accent);
            background: rgba(11, 61, 97, 0.05);
        }

        .upload-area.active {
            border-color: var(--accent);
            background: rgba(11, 61, 97, 0.1);
        }

        .upload-input {
            display: none;
        }

        .upload-label {
            cursor: pointer;
            color: var(--accent);
            font-weight: 600;
        }

        .upload-label:hover {
            text-decoration: underline;
        }

        .info-text {
            font-size: 12px;
            color: var(--muted);
            margin-top: 12px;
        }

        .file-name {
            margin-top: 12px;
            font-size: 13px;
            color: var(--text);
            font-weight: 500;
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

        .btn.primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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
            <h1>Ganti Foto Profil</h1>
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

        <div class="card">
            <h2>Foto Saat Ini</h2>
            <img src="<?php echo htmlspecialchars($photoUrl); ?>" alt="Foto" class="photo-preview">

            <h2 style="margin-top: 24px;">Upload Foto Baru</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="upload-area" id="uploadArea">
                    <p>Seret file ke sini atau <label class="upload-label" for="fotoInput">pilih file</label></p>
                    <p class="info-text">Format: JPG, PNG, GIF. Ukuran maksimal: 2MB</p>
                    <p class="file-name" id="fileName"></p>
                </div>
                <input type="file" id="fotoInput" name="foto" class="upload-input" accept="image/*">

                <div class="actions">
                    <button type="submit" class="btn primary" id="submitBtn" disabled>Upload Foto</button>
                    <a href="profil.php" class="btn secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
    </div>

    <script>
        const uploadArea = document.getElementById('uploadArea');
        const fotoInput = document.getElementById('fotoInput');
        const fileNameSpan = document.getElementById('fileName');
        const submitBtn = document.getElementById('submitBtn');

        // Click to select file
        uploadArea.addEventListener('click', () => fotoInput.click());

        // File selection
        fotoInput.addEventListener('change', (e) => {
            const files = e.target.files;
            if (files.length > 0) {
                fileNameSpan.textContent = '✓ ' + files[0].name + ' (' + (files[0].size / 1024 / 1024).toFixed(2) + ' MB)';
                submitBtn.disabled = false;
            }
        });

        // Drag and drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('active');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('active');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('active');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fotoInput.files = files;
                fileNameSpan.textContent = '✓ ' + files[0].name + ' (' + (files[0].size / 1024 / 1024).toFixed(2) + ' MB)';
                submitBtn.disabled = false;
            }
        });
    </script>
</body>

</html>