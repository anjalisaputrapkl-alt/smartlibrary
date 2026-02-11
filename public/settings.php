<?php
require __DIR__ . '/../src/auth.php';
requireAuth();
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
    }
}

// Re-fetch school data after POST to reflect updates in the UI
$stmt = $pdo->prepare('SELECT * FROM schools WHERE id = :id');
$stmt->execute(['id' => $sid]);
$school = $stmt->fetch();

// Get current theme
$currentTheme = $themeModel->getSchoolTheme($sid);

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
    </script>
</body>

</html>
