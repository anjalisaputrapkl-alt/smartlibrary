<?php
require __DIR__ . '/../src/auth.php';
requireAuth();
ini_set('display_errors', 1);
error_reporting(E_ALL);
$pdo = require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/SchoolProfileModel.php';
require __DIR__ . '/../src/ThemeModel.php';

// Ensure user is not a student
if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'student') {
    header('Location: student-dashboard.php');
    exit;
}

$user = $_SESSION['user'];
$sid = $user['school_id'];
$schoolProfileModel = new SchoolProfileModel($pdo);
$themeModel = new ThemeModel($pdo);

// Fetch school data BEFORE POST processing so we have current values for fallbacks
$stmt = $pdo->prepare('SELECT * FROM schools WHERE id = :id');
$stmt->execute(['id' => $sid]);
$school = $stmt->fetch();

if (!$school) {
    die('Error: School data not found');
}

$error = null;
$success = null;
$profile_error = null;
$profile_success = null;
$theme_success = null;
$theme_error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_identity') {
        // Update basic identity info
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $npsn = trim($_POST['school_npsn'] ?? '') ?: null;
        $email = trim($_POST['school_email'] ?? '') ?: null;

        if ($name === '') {
            $error = 'Nama sekolah wajib diisi.';
        } else {
            // Auto-generate slug from name if not provided
            if ($slug === '') {
                $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9\s-]/', '', $name)));
                $slug = preg_replace('/\s+/', '-', $slug);
                $slug = preg_replace('/-+/', '-', $slug);
                $slug = trim($slug, '-');
            }

            // ensure slug unique
            $stmt = $pdo->prepare('SELECT id FROM schools WHERE slug = :slug AND id != :id');
            $stmt->execute(['slug' => $slug, 'id' => $sid]);
            $exists = $stmt->fetchColumn();
            if ($exists) {
                $error = 'Slug sudah digunakan oleh sekolah lain.';
            } else {
                try {
                    $schoolProfileModel->updateSchoolProfile($sid, [
                        'name' => $name,
                        'slug' => $slug,
                        'npsn' => $npsn,
                        'email' => $email
                    ]);
                    $success = 'Data identitas sekolah berhasil diperbarui.';
                } catch (Exception $e) {
                    $error = 'Gagal menyimpan data: ' . $e->getMessage();
                }
            }
        }
    } elseif ($action === 'update_theme') {
        // Update school theme and save to database
        try {
            $theme_name = trim($_POST['theme_name'] ?? 'light');
            if (!$theme_name) {
                $theme_name = 'light';
            }
            $themeModel->saveSchoolTheme($sid, $theme_name);
            $theme_success = 'Tema sekolah berhasil disimpan. Semua siswa akan menggunakan tema ini.';
        } catch (Exception $e) {
            $theme_error = 'Gagal menyimpan tema: ' . $e->getMessage();
        }
    } elseif ($action === 'update_borrows') {
        try {
            $schoolProfileModel->updateSchoolProfile($sid, [
                'borrow_duration' => (int)($_POST['borrow_duration'] ?? $school['borrow_duration']),
                'late_fine' => (float)($_POST['late_fine'] ?? $school['late_fine']),
                'max_books_student' => (int)($_POST['max_books_student'] ?? $school['max_books_student']),
                'max_books_teacher' => (int)($_POST['max_books_teacher'] ?? $school['max_books_teacher']),
                'max_books_employee' => (int)($_POST['max_books_employee'] ?? $school['max_books_employee'])
            ]);
            $profile_success = 'Peraturan peminjaman berhasil diperbarui.';
        } catch (Exception $e) {
            $profile_error = 'Gagal memperbarui peraturan: ' . $e->getMessage();
        }
    } elseif ($action === 'update_password') {
        // Update user (admin) password
        try {
            $password_old = $_POST['school_password_old'] ?? '';
            $password_new = $_POST['school_password_new'] ?? '';
            $password_confirm = $_POST['school_password_confirm'] ?? '';

            // Validate inputs
            if (empty($password_old) || empty($password_new) || empty($password_confirm)) {
                throw new Exception('Semua field password harus diisi.');
            }

            if ($password_new !== $password_confirm) {
                throw new Exception('Password baru dan konfirmasi password tidak cocok.');
            }

            if (strlen($password_new) < 6) {
                throw new Exception('Password baru minimal 6 karakter.');
            }

            // Verify old password from users table
            $stmt = $pdo->prepare('SELECT password FROM users WHERE id = :id');
            $stmt->execute(['id' => $user['id']]);
            $user_data = $stmt->fetch();

            if (!$user_data || !password_verify($password_old, $user_data['password'])) {
                throw new Exception('Password lama tidak sesuai.');
            }

            // Update password in users table
            $hashed_password = password_hash($password_new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
            $stmt->execute(['password' => $hashed_password, 'id' => $user['id']]);

            $profile_success = 'Password berhasil diubah.';
        } catch (Exception $e) {
            $profile_error = 'Gagal mengubah password: ' . $e->getMessage();
        }
    } elseif ($action === 'upload_photo') {
        // Handle photo upload
        try {
            if (!isset($_FILES['school_photo']) || $_FILES['school_photo']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('File tidak ditemukan');
            }

            // Validate file first
            $schoolProfileModel->validatePhotoFile($_FILES['school_photo']);

            // Delete old photo if exists
            $old_photo = $schoolProfileModel->getSchoolPhoto($sid);
            if ($old_photo) {
                $old_path = __DIR__ . '/' . $old_photo;
                if (file_exists($old_path)) {
                    @unlink($old_path);
                }
            }

            // Save new photo
            $filename = $schoolProfileModel->savePhotoFile($_FILES['school_photo']);
            $photo_path = 'public/uploads/school-photos/' . $filename;
            $schoolProfileModel->updateSchoolPhoto($sid, $photo_path);

            $profile_success = 'Foto profil sekolah berhasil diunggah.';
        } catch (Exception $e) {
            $profile_error = 'Gagal mengunggah foto: ' . $e->getMessage();
        }
    } elseif ($action === 'delete_photo') {
        // Handle photo deletion
        try {
            $schoolProfileModel->deleteSchoolPhoto($sid);
            $profile_success = 'Foto profil sekolah berhasil dihapus.';
        } catch (Exception $e) {
            $profile_error = 'Gagal menghapus foto: ' . $e->getMessage();
        }

    } elseif ($action === 'add_special_theme') {
        try {
            $themeModel->addSpecialTheme([
                'school_id' => $sid,
                'name' => $_POST['theme_name_label'] ?? '',
                'date' => $_POST['theme_date'] ?? '',
                'theme_key' => $_POST['theme_key'] ?? '',
                'description' => $_POST['theme_description'] ?? '',
                'is_active' => 1
            ]);
            $theme_success = 'Tema hari penting berhasil ditambahkan.';
        } catch (Exception $e) {
            $theme_error = 'Gagal menambah tema: ' . $e->getMessage();
        }
    } elseif ($action === 'toggle_special_theme') {
        try {
            $id = (int)$_POST['theme_id'];
            $status = (int)$_POST['status'];
            $themeModel->toggleSpecialTheme($id, $sid, $status);
            $theme_success = 'Status tema berhasil diubah.';
        } catch (Exception $e) {
            $theme_error = 'Gagal mengubah status: ' . $e->getMessage();
        }
    } elseif ($action === 'delete_special_theme') {
        try {
            $id = (int)$_POST['theme_id'];
            $themeModel->deleteSpecialTheme($id, $sid);
            $theme_success = 'Tema hari penting berhasil dihapus.';
        } catch (Exception $e) {
            $theme_error = 'Gagal menghapus tema: ' . $e->getMessage();
        }

    } elseif ($action === 'reset_scan_key') {
        try {
            $schoolProfileModel->resetScanAccessKey($sid);
            $profile_success = 'Scan Access Key berhasil direset. Silakan bagikan link baru ke staff.';
        } catch (Exception $e) {
            $profile_error = 'Gagal mereset key: ' . $e->getMessage();
        }
    } elseif ($action === 'update_scanner_settings') {
        try {
            $custom_url = trim($_POST['custom_base_url'] ?? '');
            $schoolProfileModel->updateSchoolProfile($sid, ['custom_base_url' => $custom_url ?: null]);
            $profile_success = 'Pengaturan scanner berhasil diperbarui.';
        } catch (Exception $e) {
            $profile_error = 'Gagal memperbarui pengaturan: ' . $e->getMessage();
        }
    }
}

// Re-fetch school data after POST to reflect updates in the UI
$stmt = $pdo->prepare('SELECT * FROM schools WHERE id = :id');
$stmt->execute(['id' => $sid]);
$school = $stmt->fetch();

// Get current theme
$currentTheme = $themeModel->getSchoolTheme($sid);
$specialThemes = $themeModel->getSpecialThemes($sid);
$activeSpecialTheme = $themeModel->checkSpecialTheme($sid);

// Debug: Display what we got from database
// var_dump($school); // Uncomment for debugging

// Safety check
if (!$school) {
    die('Error: School data not found');
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengaturan - Perpustakaan Online</title>
    <script src="../assets/js/theme-loader.js"></script>
    <script src="../assets/js/theme.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../assets/css/animations.css">
    <link rel="stylesheet" href="../assets/css/settings.css">
    <?php require_once __DIR__ . '/../theme-loader.php'; ?>
    <?php if ($activeSpecialTheme): ?>
        <script>window.isSpecialThemeActive = true;</script>
        <link rel="stylesheet" id="special-theme-css" href="themes/special/<?php echo htmlspecialchars($activeSpecialTheme); ?>.css">
    <?php endif; ?>
</head>

<body>
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <div class="app">
        <div class="topbar">
            <strong>Pengaturan Sekolah</strong>
        </div>

        <div class="content">
            <!-- Tabs Navigation -->
            <div class="settings-tabs">
                <div class="tab-link active" data-tab="identity">
                    <iconify-icon icon="mdi:bank-outline"></iconify-icon> Identitas
                </div>
                <div class="tab-link" data-tab="security">
                    <iconify-icon icon="mdi:shield-lock-outline"></iconify-icon> Keamanan
                </div>
                <div class="tab-link" data-tab="borrows">
                    <iconify-icon icon="mdi:book-clock-outline"></iconify-icon> Peminjaman
                </div>
                <div class="tab-link" data-tab="themes">
                    <iconify-icon icon="mdi:palette-outline"></iconify-icon> Tema
                </div>
                <div class="tab-link" data-tab="scanner">
                    <iconify-icon icon="mdi:barcode-scan"></iconify-icon> Scanner Mobile
                </div>
                <div class="tab-link" data-tab="faq">
                    <iconify-icon icon="mdi:help-circle-outline"></iconify-icon> Bantuan
                </div>
            </div>

            <!-- Tab: Identity (Previously General & Profile) -->
            <div class="tab-content active" id="identity">
                <div class="settings-grid">
                    <div class="card">
                        <h2 class="card-title">
                            <iconify-icon icon="mdi:card-account-details-outline"></iconify-icon> Informasi Sekolah
                        </h2>
                        <p class="card-subtitle">Kelola informasi dasar dan kontak resmi sekolah Anda.</p>
                        
                        <?php if (!empty($success) && $action === 'update_identity'): ?>
                            <div class="alert alert-success">
                                <iconify-icon icon="mdi:check-circle" style="font-size: 20px;"></iconify-icon>
                                <div>Tersimpan: <?php echo htmlspecialchars($success); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($error) && $action === 'update_identity'): ?>
                            <div class="alert alert-danger">
                                <iconify-icon icon="mdi:alert-circle" style="font-size: 20px;"></iconify-icon>
                                <div><?php echo htmlspecialchars($error); ?></div>
                            </div>
                        <?php endif; ?>

                        <form method="post">
                            <input type="hidden" name="action" value="update_identity">
                            <div class="form-group">
                                <label for="name">Nama Lengkap Sekolah</label>
                                <input id="name" name="name" type="text" required
                                    value="<?php echo htmlspecialchars($school['name'] ?? ''); ?>"
                                    placeholder="Contoh: SMA Negeri 1 Jakarta">
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="slug">URL Pendek (Slug)</label>
                                    <input id="slug" name="slug" type="text"
                                        value="<?php echo htmlspecialchars($school['slug'] ?? ''); ?>"
                                        placeholder="Contoh: sman1-jkt">
                                </div>
                                <div class="form-group">
                                    <label for="school_npsn">NPSN</label>
                                    <input id="school_npsn" name="school_npsn" type="text"
                                        value="<?php echo htmlspecialchars($school['npsn'] ?? ''); ?>"
                                        placeholder="Contoh: 20102012">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="school_email">Email Sekolah</label>
                                <input id="school_email" name="school_email" type="email"
                                    value="<?php echo htmlspecialchars($school['email'] ?? ''); ?>"
                                    placeholder="sekolah@example.com">
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <iconify-icon icon="mdi:content-save-outline"></iconify-icon>
                                Simpan Identitas
                            </button>
                        </form>
                    </div>

                    <div class="sidebar-widgets">
                        <div class="card">
                            <h2 class="card-title" style="font-size: 15px;">Logo Sekolah</h2>
                            <div class="profile-photo-container">
                                <div class="profile-photo-wrapper">
                                    <?php if ($school['photo_path'] && file_exists(__DIR__ . '/../' . $school['photo_path'])): ?>
                                        <img src="../<?php echo htmlspecialchars($school['photo_path']); ?>" 
                                             class="profile-photo" alt="Logo Sekolah" id="preview-img">
                                    <?php else: ?>
                                        <div class="photo-placeholder" id="preview-placeholder">
                                            <iconify-icon icon="mdi:school"></iconify-icon>
                                        </div>
                                        <img src="" class="profile-photo" alt="Logo Sekolah" id="preview-img" style="display: none;">
                                    <?php endif; ?>
                                    <label for="school_photo" class="photo-overlay">
                                        <iconify-icon icon="mdi:camera-outline"></iconify-icon>
                                    </label>
                                </div>
                                
                                <form method="post" enctype="multipart/form-data" id="photo-form">
                                    <input type="hidden" name="action" value="upload_photo">
                                    <input type="file" name="school_photo" id="school_photo" hidden accept="image/*">
                                    <div style="display: flex; gap: 8px; margin-top: 10px;">
                                        <button type="button" onclick="document.getElementById('school_photo').click()" class="btn" style="padding: 8px 12px; font-size: 12px;">
                                            Pilih
                                        </button>
                                        <button type="submit" class="btn btn-primary" style="padding: 8px 12px; font-size: 12px;">
                                            Unggah
                                        </button>
                                    </div>
                                </form>

                                <?php if ($school['photo_path']): ?>
                                    <form method="post">
                                        <input type="hidden" name="action" value="delete_photo">
                                        <button type="submit" class="btn btn-danger" style="margin-top: 8px; border: none; font-size: 12px; background: transparent;">
                                            <iconify-icon icon="mdi:trash-can-outline"></iconify-icon> Hapus Foto
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card">
                            <h3 class="card-title" style="font-size: 14px;">Status Kelengkapan</h3>
                            <div class="checklist-item done">
                                <iconify-icon icon="mdi:check-circle"></iconify-icon> Nama & Slug
                            </div>
                            <div class="checklist-item <?php echo !empty($school['npsn']) ? 'done' : 'todo'; ?>">
                                <iconify-icon icon="<?php echo !empty($school['npsn']) ? 'mdi:check-circle' : 'mdi:circle-outline'; ?>"></iconify-icon> NPSN Terdaftar
                            </div>
                            <div class="checklist-item <?php echo !empty($school['photo_path']) ? 'done' : 'todo'; ?>">
                                <iconify-icon icon="<?php echo !empty($school['photo_path']) ? 'mdi:check-circle' : 'mdi:circle-outline'; ?>"></iconify-icon> Logo Sekolah
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Security -->
            <div class="tab-content" id="security">
                <div class="settings-grid">
                    <div class="card">
                        <h2 class="card-title">
                            <iconify-icon icon="mdi:lock-reset"></iconify-icon> Ganti Password Admin
                        </h2>
                        
                        <?php if (!empty($profile_success) && $action === 'update_password'): ?>
                            <div class="alert alert-success">
                                <iconify-icon icon="mdi:check-circle" style="font-size: 20px;"></iconify-icon>
                                <div><?php echo htmlspecialchars($profile_success); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($profile_error) && $action === 'update_password'): ?>
                            <div class="alert alert-danger">
                                <iconify-icon icon="mdi:alert-circle" style="font-size: 20px;"></iconify-icon>
                                <div><?php echo htmlspecialchars($profile_error); ?></div>
                            </div>
                        <?php endif; ?>

                        <form method="post">
                            <input type="hidden" name="action" value="update_password">
                            <div class="form-group">
                                <label for="school_password_old">Password Saat Ini</label>
                                <input id="school_password_old" name="school_password_old" type="password" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="school_password_new">Password Baru</label>
                                    <input id="school_password_new" name="school_password_new" type="password" required>
                                </div>
                                <div class="form-group">
                                    <label for="school_password_confirm">Konfirmasi Password Baru</label>
                                    <input id="school_password_confirm" name="school_password_confirm" type="password" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <iconify-icon icon="mdi:shield-check-outline"></iconify-icon>
                                Update Password
                            </button>
                        </form>
                    </div>

                    <div class="sidebar-widgets">
                        <div class="card">
                            <h3 class="card-title" style="font-size: 14px;">Keamanan Akun</h3>
                            <div class="info-block">
                                <div class="info-icon"><iconify-icon icon="mdi:shield-key"></iconify-icon></div>
                                <div class="info-text">
                                    <h4>Encryption</h4>
                                    <p>Password Anda dienkripsi dengan algoritma standar industri (BCrypt).</p>
                                </div>
                            </div>
                            <div class="info-block">
                                <div class="info-icon"><iconify-icon icon="mdi:history"></iconify-icon></div>
                                <div class="info-text">
                                    <h4>Sesi Aktif</h4>
                                    <p>Pastikan Anda Logout jika mengakses dari komputer publik.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Peminjaman -->
            <div class="tab-content" id="borrows">
                <div class="settings-grid">
                    <div class="card">
                        <h2 class="card-title">
                            <iconify-icon icon="mdi:book-cog-outline"></iconify-icon> Peraturan Peminjaman
                        </h2>
                        <p class="card-subtitle">Atur kebijakan peminjaman buku untuk seluruh anggota perpustakaan di sekolah Anda.</p>

                        <?php if (!empty($profile_success) && $action === 'update_borrows'): ?>
                            <div class="alert alert-success">
                                <iconify-icon icon="mdi:check-circle" style="font-size: 20px;"></iconify-icon>
                                <div><?php echo htmlspecialchars($profile_success); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($profile_error) && $action === 'update_borrows'): ?>
                            <div class="alert alert-danger">
                                <iconify-icon icon="mdi:alert-circle" style="font-size: 20px;"></iconify-icon>
                                <div><?php echo htmlspecialchars($profile_error); ?></div>
                            </div>
                        <?php endif; ?>

                        <form method="post">
                            <input type="hidden" name="action" value="update_borrows">
                            
                            <div class="form-group">
                                <label for="borrow_duration">Durasi Peminjaman (Hari)</label>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <input type="number" name="borrow_duration" class="form-control" value="<?= htmlspecialchars($school['borrow_duration'] ?? 7) ?>" min="1" style="width: 100px;">
                                    <span>Hari kerja</span>
                                </div>
                                <small class="text-muted">Waktu maksimal seorang anggota boleh memegang buku sebelum harus dikembalikan.</small>
                            </div>

                            <div class="form-group">
                                <label for="late_fine">Denda Keterlambatan (Rp)</label>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span>Rp</span>
                                    <input type="number" name="late_fine" class="form-control" value="<?= htmlspecialchars($school['late_fine'] ?? 500) ?>" min="0" step="100" style="width: 150px;">
                                    <span>/ hari</span>
                                </div>
                                <small class="text-muted">Besar denda yang dikenakan per buku per satu hari keterlambatan.</small>
                            </div>

                            <div class="form-group">
                                <label>Maksimum Buku Disimpan (Berdasarkan Role)</label>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-top: 5px;">
                                    <div>
                                        <label style="font-size: 11px; color: var(--muted);">Siswa</label>
                                        <div style="display: flex; align-items: center; gap: 5px;">
                                            <input type="number" name="max_books_student" class="form-control" value="<?= htmlspecialchars($school['max_books_student'] ?? 3) ?>" min="1" max="50">
                                            <span style="font-size: 12px;">Buku</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; color: var(--muted);">Guru</label>
                                        <div style="display: flex; align-items: center; gap: 5px;">
                                            <input type="number" name="max_books_teacher" class="form-control" value="<?= htmlspecialchars($school['max_books_teacher'] ?? 10) ?>" min="1" max="50">
                                            <span style="font-size: 12px;">Buku</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; color: var(--muted);">Karyawan</label>
                                        <div style="display: flex; align-items: center; gap: 5px;">
                                            <input type="number" name="max_books_employee" class="form-control" value="<?= htmlspecialchars($school['max_books_employee'] ?? 5) ?>" min="1" max="50">
                                            <span style="font-size: 12px;">Buku</span>
                                        </div>
                                    </div>
                                </div>
                                <small class="text-muted">Tentukan jatah pinjam buku maksimal per role. Ini akan menjadi default saat menambah anggota baru.</small>
                            </div>

                            <button type="submit" class="btn btn-primary">Simpan Peraturan</button>
                        </form>
                    </div>

                    <div class="sidebar-widgets">
                        <div class="card">
                            <div class="info-block">
                                <div class="info-icon warning">
                                    <iconify-icon icon="mdi:information-outline"></iconify-icon>
                                </div>
                                <div class="info-text">
                                    <h4 style="font-size: 14px;">Tips Peraturan</h4>
                                    <p style="font-size: 13px;">Gunakan durasi 7-14 hari untuk sirkulasi buku yang optimal di lingkungan sekolah.</p>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <h3 style="font-size: 14px; margin-bottom: 12px;">Statistik Saat Ini</h3>
                            <div class="checklist-item done" style="margin-bottom: 8px;">
                                <iconify-icon icon="mdi:check-circle"></iconify-icon>
                                <span style="font-size: 13px;">Denda Otomatis Aktif</span>
                            </div>
                            <div class="checklist-item done">
                                <iconify-icon icon="mdi:check-circle"></iconify-icon>
                                <span style="font-size: 13px;">Validasi Limit Peminjaman</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Themes -->
            <div class="tab-content" id="themes">
                <div class="settings-grid">
                    <div class="card">
                        <h2 class="card-title">
                            <iconify-icon icon="mdi:palette-swatch-outline"></iconify-icon> Kustomisasi Tema
                        </h2>
                        <p class="card-subtitle">Pilih tema global yang akan diterapkan ke seluruh siswa di sekolah ini.</p>

                        <?php if (!empty($theme_success)): ?>
                            <div class="alert alert-success">
                                <iconify-icon icon="mdi:check-circle" style="font-size: 20px;"></iconify-icon>
                                <div><?php echo htmlspecialchars($theme_success); ?></div>
                            </div>
                        <?php endif; ?>

                        <form method="post">
                            <input type="hidden" name="action" value="update_theme">
                            <div class="theme-grid">
                                <?php 
                                $themes = [
                                    'light' => 'Terang',
                                    'dark' => 'Gelap',
                                    'blue' => 'Biru',
                                    'monochrome' => 'Hitam Putih',
                                    'sepia' => 'Klasik',
                                    'sunset' => 'Senja'
                                ];
                                $activeTheme = $currentTheme['theme_name'] ?? 'light';
                                foreach ($themes as $val => $label): 
                                ?>
                                <label class="theme-option">
                                    <input type="radio" name="theme_name" value="<?php echo $val; ?>" 
                                        <?php echo $activeTheme === $val ? 'checked' : ''; ?>
                                        onchange="this.form.submit()">
                                    <div class="theme-card">
                                        <div class="theme-preview <?php echo $val; ?>">
                                            <div class="preview-top"></div>
                                            <div class="preview-mid"></div>
                                            <div class="preview-bot"></div>
                                        </div>
                                        <span class="theme-name"><?php echo $label; ?></span>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </form>
                    </div>

                    <div class="sidebar-widgets">
                        <div class="card">
                            <h3 class="card-title" style="font-size: 14px;">Preview Tema</h3>
                            <p style="font-size: 13px; color: var(--muted); line-height: 1.6;">
                                Tema yang Anda pilih akan langsung diterapkan pada:<br>
                                • Sidebar Navigation<br>
                                • Dashboard Widgets<br>
                                • Data Tables<br>
                                • Kartu Anggota
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Section Tema Hari Penting -->
                <div class="card" style="margin-top: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <div>
                            <h2 class="card-title">
                                <iconify-icon icon="mdi:calendar-star-outline"></iconify-icon> Tema Hari Penting (Otomatis)
                            </h2>
                            <p class="card-subtitle">Tema akan berganti otomatis pada tanggal yang ditentukan.</p>
                        </div>
                        <button type="button" class="btn btn-primary" onclick="openAddSpecialThemeModal()">
                            <iconify-icon icon="mdi:plus"></iconify-icon> Tambah
                        </button>
                    </div>

                    <?php if (!empty($theme_error)): ?>
                        <div class="alert alert-danger" style="margin-bottom: 20px;">
                            <iconify-icon icon="mdi:alert-circle"></iconify-icon>
                            <div><?php echo htmlspecialchars($theme_error); ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="special-themes-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px;">
                        <?php if (empty($specialThemes)): ?>
                            <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: var(--muted); background: var(--bg); border: 1px dashed var(--border); border-radius: 12px;">
                                <iconify-icon icon="mdi:calendar-blank" style="font-size: 48px; margin-bottom: 10px;"></iconify-icon>
                                <p>Belum ada tema hari penting yang dikonfigurasi.</p>
                            </div>
                        <?php endif; ?>
                        
                        <?php foreach ($specialThemes as $st): ?>
                        <div class="card" style="padding: 15px; border: 1px solid var(--border); box-shadow: none;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <h3 style="font-size: 15px; margin-bottom: 10px; font-weight: 600;"><?php echo htmlspecialchars($st['name']); ?></h3>
                                    <div style="font-size: 12px; color: var(--text-muted); display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                                        <iconify-icon icon="mdi:calendar-outline" style="font-size: 16px; color: var(--primary);"></iconify-icon> 
                                        <?php echo date('d F Y', strtotime($st['date'])); ?>
                                    </div>
                                    <div style="display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; background: color-mix(in srgb, var(--primary) 10%, transparent); color: var(--primary);">
                                        CSS: <?php echo htmlspecialchars($st['theme_key']); ?>.css
                                    </div>
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 8px; align-items: flex-end;">
                                    <form method="post">
                                        <input type="hidden" name="action" value="toggle_special_theme">
                                        <input type="hidden" name="theme_id" value="<?php echo $st['id']; ?>">
                                        <input type="hidden" name="status" value="<?php echo $st['is_active'] ? 0 : 1; ?>">
                                        <button type="submit" class="btn" style="padding: 5px 12px; border-radius: 6px; font-size: 11px; font-weight: 700; border: none; background: <?php echo $st['is_active'] ? 'var(--success)' : 'var(--muted)'; ?>; color: white;">
                                            <?php echo $st['is_active'] ? 'AKTIF' : 'NONAKTIF'; ?>
                                        </button>
                                    </form>
                                    <form method="post" onsubmit="return confirm('Hapus tema ini?')">
                                        <input type="hidden" name="action" value="delete_special_theme">
                                        <input type="hidden" name="theme_id" value="<?php echo $st['id']; ?>">
                                        <button type="submit" class="btn-text" style="font-size: 11px; color: var(--danger); background: none; border: none; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                            <iconify-icon icon="mdi:trash-can-outline"></iconify-icon> Hapus
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Modal Tambah Tema Khusus -->
                <div id="addSpecialThemeModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 10000; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                    <div class="card" style="width: 450px; padding: 30px; border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.3);">
                        <h2 class="card-title" style="margin-bottom: 25px;">
                            <iconify-icon icon="mdi:plus-circle-outline"></iconify-icon> Tambah Hari Penting
                        </h2>
                        <form method="post">
                            <input type="hidden" name="action" value="add_special_theme">
                            <div class="form-group" style="margin-bottom: 18px;">
                                <label style="font-weight: 600; margin-bottom: 8px; display: block;">Nama Hari / Acara</label>
                                <input type="text" name="theme_name_label" placeholder="Contoh: HUT RI 81" required style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--border);">
                            </div>
                            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 18px;">
                                <div class="form-group">
                                    <label style="font-weight: 600; margin-bottom: 8px; display: block;">Tanggal</label>
                                    <input type="date" name="theme_date" required style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--border);">
                                </div>
                                <div class="form-group">
                                    <label style="font-weight: 600; margin-bottom: 8px; display: block;">File Tema (.css)</label>
                                    <select name="theme_key" required style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--border); background: white;">
                                        <?php 
                                        $configPath = __DIR__ . '/../theme-config.json';
                                        if (file_exists($configPath)) {
                                            $themeConfig = json_decode(file_get_contents($configPath), true);
                                            foreach ($themeConfig as $key => $theme) {
                                                $displayName = ucfirst($key);
                                                if ($key === 'hariguru') $displayName = 'Hari Guru';
                                                if ($key === 'tahunbaru') $displayName = 'Lunar New Year / Imlek';
                                                if ($key === 'kemerdekaan' || $key === '17agustus') $displayName = 'Kemerdekaan / HUT RI';
                                                if ($key === 'idulfitri') $displayName = 'Idul Fitri';
                                                if ($key === 'kartini') $displayName = 'Hari Kartini';
                                                if ($key === 'hardiknas') $displayName = 'Hardiknas';
                                                
                                                echo "<option value=\"$key\">" . htmlspecialchars($displayName) . " ($key.css)</option>";
                                            }
                                        } else {
                                            echo '<option value="kemerdekaan">kemerdekaan.css (Merah Putih)</option>';
                                            echo '<option value="idulfitri">idulfitri.css (Lebaran)</option>';
                                            echo '<option value="tahunbaru">tahunbaru.css (Festive)</option>';
                                            echo '<option value="hariguru">hariguru.css (Education)</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group" style="margin-bottom: 25px;">
                                <label style="font-weight: 600; margin-bottom: 8px; display: block;">Deskripsi Singkat</label>
                                <textarea name="theme_description" rows="2" placeholder="Catatan tambahan..." style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--border);"></textarea>
                            </div>
                            <div style="display: flex; gap: 12px;">
                                <button type="submit" class="btn btn-primary" style="flex: 2; padding: 12px;">
                                    <iconify-icon icon="mdi:content-save"></iconify-icon> Simpan Tema
                                </button>
                                <button type="button" class="btn" style="flex: 1; padding: 12px; background: var(--muted); border: none;" onclick="closeAddSpecialThemeModal()">Batal</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tab: Scanner Mobile -->
            <div class="tab-content" id="scanner">
                <div class="settings-grid">
                    <div class="card">
                        <h2 class="card-title">
                            <iconify-icon icon="mdi:cellphone-wireless"></iconify-icon> Scanner Tanpa Login
                        </h2>
                        <p class="card-subtitle">Gunakan fitur ini untuk memberikan akses scan buku ke staff lewat handphone tanpa harus memberitahukan password admin.</p>

                        <?php 
                        $scanKey = $school['scan_access_key']; 
                        
                        // Robust Base URL detection
                        $protocol = 'http';
                        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
                            $protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
                        } elseif (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                            $protocol = 'https';
                        }
                        
                        $host = $_SERVER['HTTP_HOST'];
                        $currentDir = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                        
                        // Use custom base URL if provided
                        $detectedBaseUrl = $protocol . "://" . $host . $currentDir;
                        
                        // Intelligent Custom URL processing:
                        $displayBaseUrl = $detectedBaseUrl;
                        if (!empty($school['custom_base_url'])) {
                            $custom = rtrim($school['custom_base_url'], '/');
                            $parsed = parse_url($custom);
                            
                            // If user only provided domain (e.g. https://xyz.ngrok-free.app) 
                            // and no path, append the current project directory.
                            if (empty($parsed['path']) || $parsed['path'] === '/') {
                                $displayBaseUrl = $custom . $currentDir;
                            } else {
                                $displayBaseUrl = $custom;
                            }
                        }
                        
                        $scanUrl = $displayBaseUrl . "/scan-mobile.php" . ($scanKey ? "?key=$scanKey" : "");
                        $returnUrl = $displayBaseUrl . "/scan-return-mobile.php" . ($scanKey ? "?key=$scanKey" : "");
                        ?>

                        <div class="card" style="background: var(--bg-secondary); border: 1px solid var(--border-color); margin: 16px 0;">
                            <h3 class="card-title" style="font-size: 14px;">Domain / Base URL</h3>
                            <p style="font-size: 12px; color: var(--muted); margin-bottom: 12px;">Jika Anda menggunakan <strong>ngrok</strong> atau akses IP Lokal, masukkan URL dasarnya di sini agar link yang dihasilkan benar.</p>
                            <form method="post" style="display: flex; gap: 8px;">
                                <input type="hidden" name="action" value="update_scanner_settings">
                                <input type="text" name="custom_base_url" placeholder="Contoh: https://abcd-123.ngrok-free.app" 
                                       value="<?= htmlspecialchars($school['custom_base_url'] ?? '') ?>" style="flex: 1;">
                                <button type="submit" class="btn btn-primary" style="white-space: nowrap;">Simpan</button>
                            </form>
                            <?php if (!empty($school['custom_base_url'])): ?>
                                <small style="color: var(--success); margin-top: 4px; display: block;">
                                    <iconify-icon icon="mdi:check-circle"></iconify-icon> URL Custom Aktif
                                </small>
                            <?php endif; ?>
                        </div>

                        <?php if (!$scanKey): ?>
                            <div class="alert alert-warning">
                                <iconify-icon icon="mdi:alert-circle-outline"></iconify-icon>
                                <div>Akses Scan Mobile belum diaktifkan. Klik tombol di bawah untuk membuat kunci akses pertama kali.</div>
                            </div>
                        <?php else: ?>
                            <div class="info-block" style="background: var(--bg-secondary); padding: 16px; border-radius: 12px; margin-bottom: 24px;">
                                <div style="margin-bottom: 16px;">
                                    <label style="font-weight: 700; display: block; margin-bottom: 8px;">1. Link Scanner Peminjaman</label>
                                    <div style="display: flex; gap: 8px;">
                                        <input type="text" readonly value="<?= $scanUrl ?>" style="flex: 1; font-family: monospace; font-size: 11px;">
                                        <button class="btn" onclick="copyToClipboard('<?= $scanUrl ?>')">Salin</button>
                                    </div>
                                </div>
                                <div style="margin-bottom: 16px;">
                                    <label style="font-weight: 700; display: block; margin-bottom: 8px;">2. Link Scanner Pengembalian</label>
                                    <div style="display: flex; gap: 8px;">
                                        <input type="text" readonly value="<?= $returnUrl ?>" style="flex: 1; font-family: monospace; font-size: 11px;">
                                        <button class="btn" onclick="copyToClipboard('<?= $returnUrl ?>')">Salin</button>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <form method="post">
                            <input type="hidden" name="action" value="reset_scan_key">
                            <button type="submit" class="btn btn-primary">
                                <iconify-icon icon="mdi:key-refresh"></iconify-icon>
                                <?= $scanKey ? 'Reset & Generate Kunci Baru' : 'Aktifkan Akses Scanner' ?>
                            </button>
                        </form>
                        
                        <?php if ($scanKey): ?>
                            <p class="text-muted" style="margin-top: 16px; font-size: 12px; color: var(--danger);">
                                <iconify-icon icon="mdi:alert-outline"></iconify-icon> 
                                <strong>Peringatan:</strong> Jika Anda mereset kunci, link yang lama tidak akan bisa digunakan lagi.
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="sidebar-widgets">
                        <div class="card">
                            <h3 class="card-title" style="font-size: 14px;">Cara Penggunaan</h3>
                            <ul style="font-size: 13px; color: var(--muted); padding-left: 20px; line-height: 1.6;">
                                <li>Klik aktifkan untuk mendapatkan kunci unik.</li>
                                <li>Salin link scanner (Peminjaman atau Pengembalian).</li>
                                <li>Kirim link tersebut ke WhatsApp atau buka di Browser handphone staff.</li>
                                <li>Staff bisa langsung scan barcode buku/anggota tanpa perlu login.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: FAQ -->
            <div class="tab-content" id="faq">
                <div class="settings-grid">
                    <div class="card">
                        <h2 class="card-title">
                            <iconify-icon icon="mdi:frequently-asked-questions"></iconify-icon> Pertanyaan Umum
                        </h2>
                        <div class="faq-list">
                            <?php
                            $faqs = [
                                [
                                    'q' => 'Bagaimana cara mengubah tema aplikasi?',
                                    'a' => 'Masuk ke tab "Tema", lalu klik pada salah satu pilihan tema yang tersedia. Tema akan langsung disimpan dan diterapkan untuk semua pengguna.'
                                ],
                                [
                                    'q' => 'Apa itu Slug?',
                                    'a' => 'Slug adalah teks unik yang akan muncul di URL pencarian atau identitas sekolah Anda. Misalnya jika slug diisi "sman1", maka link akses sekolah Anda akan lebih mudah diingat.'
                                ],
                                [
                                    'q' => 'Mengapa foto profil tidak berubah?',
                                    'a' => 'Pastikan ukuran foto tidak melebihi 5MB dan memiliki format JPG, PNG, atau WEBP. Jika sudah sesuai namun tidak berubah, coba segarkan (refresh) halaman browser Anda.'
                                ],
                                [
                                    'q' => 'Siapa yang bisa mengakses pengaturan ini?',
                                    'a' => 'Hanya pengguna dengan peran Admin yang dapat mengakses dan mengubah pengaturan sekolah.'
                                ]
                            ];
                            foreach ($faqs as $i => $item):
                            ?>
                            <div class="faq-item">
                                <div class="faq-question">
                                    <?php echo $item['q']; ?>
                                    <iconify-icon icon="mdi:chevron-down"></iconify-icon>
                                </div>
                                <div class="faq-answer">
                                    <?php echo $item['a']; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="sidebar-widgets">
                        <div class="card card-accent">
                            <h3 class="card-title" style="font-size: 14px;">Butuh Bantuan?</h3>
                            <p style="font-size: 12px; margin-bottom: 12px;">Hubungi tim IT sekolah Anda jika mengalami kendala teknis yang berat.</p>
                            <a href="mailto:support@sekolah.id" class="btn btn-full" style="font-size: 12px;">Email Support</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <?php include __DIR__ . '/partials/footer.php'; ?>

    <script>
        // Tab Switching Logic
        document.querySelectorAll('.tab-link').forEach(link => {
            link.addEventListener('click', () => {
                const target = link.dataset.tab;
                
                // Update active tab link
                document.querySelectorAll('.tab-link').forEach(l => l.classList.remove('active'));
                link.classList.add('active');
                
                // Show active tab content
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                document.getElementById(target).classList.add('active');
                
                // Save active tab to localStorage
                localStorage.setItem('active_settings_tab', target);
            });
        });

        // Restore active tab on load
        const savedTab = localStorage.getItem('active_settings_tab');
        if (savedTab) {
            const tabEl = document.querySelector(`.tab-link[data-tab="${savedTab}"]`);
            if (tabEl) tabEl.click();
        }

        // FAQ Toggle Logic
        document.querySelectorAll('.faq-question').forEach(q => {
            q.addEventListener('click', () => {
                const item = q.parentElement;
                item.classList.toggle('active');
            });
        });

        // Photo Upload Preview
        const photoInput = document.getElementById('school_photo');
        if (photoInput) {
            photoInput.addEventListener('change', function (e) {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    
                    // Simple preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const previewImg = document.getElementById('preview-img');
                        const previewPlaceholder = document.getElementById('preview-placeholder');
                        
                        if (previewImg) {
                            previewImg.src = e.target.result;
                            previewImg.style.display = 'block';
                        }
                        if (previewPlaceholder) {
                            previewPlaceholder.style.display = 'none';
                        }
                    }
                    reader.readAsDataURL(file);
                }
            });
        }


        function openAddSpecialThemeModal() {
            document.getElementById('addSpecialThemeModal').style.display = 'flex';
        }

        function closeAddSpecialThemeModal() {
            document.getElementById('addSpecialThemeModal').style.display = 'none';
        }

        // Copy to clipboard helper
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Link berhasil disalin ke clipboard!');
            }).catch(err => {
                const temp = document.createElement('input');
                document.body.appendChild(temp);
                temp.value = text;
                temp.select();
                document.execCommand('copy');
                document.body.removeChild(temp);
                alert('Link berhasil disalin ke clipboard!');
            });
        }
    </script>
</body>

</html>
