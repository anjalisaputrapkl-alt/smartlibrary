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
$error = null;
$success = null;
$profile_error = null;
$profile_success = null;
$theme_success = null;
$theme_error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_basic') {
        // Update basic school info
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');

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
                $stmt = $pdo->prepare('UPDATE schools SET name = :name, slug = :slug WHERE id = :id');
                $stmt->execute(['name' => $name, 'slug' => $slug, 'id' => $sid]);
                $success = 'Pengaturan dasar tersimpan.';
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
    } elseif ($action === 'update_profile') {
        // Update school profile data
        try {
            $data = [
                'email' => trim($_POST['school_email'] ?? '') ?: null,
                'npsn' => trim($_POST['school_npsn'] ?? '') ?: null,
            ];

            $schoolProfileModel->updateSchoolProfile($sid, $data);
            $profile_success = 'Data profil sekolah berhasil diperbarui.';
        } catch (Exception $e) {
            $profile_error = 'Gagal memperbarui data profil: ' . $e->getMessage();
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
    <title>Pengaturan Sekolah - Perpustakaan Online</title>
    <script src="../assets/js/theme-loader.js"></script>
    <script src="../assets/js/theme.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../assets/css/animations.css">
    <link rel="stylesheet" href="../assets/css/school-profile.css">
    <link rel="stylesheet" href="../assets/css/settings.css">
</head>

<body>
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <div class="app">

        <div class="topbar">
            <strong class="topbar-title"><iconify-icon icon="mdi:cog" class="topbar-icon"></iconify-icon>Pengaturan
                Sekolah</strong>
        </div>

        <div class="content">
            <div class="main">

                <div class="settings-section">
                    <div class="settings-controls">

                        <!-- Theme Settings -->
                        <div class="card">
                            <h2 class="theme-header"><iconify-icon icon="mdi:palette"
                                    class="theme-header-icon"></iconify-icon>Pengaturan Tema</h2>

                            <?php if (!empty($theme_error)): ?>
                                <div class="alert alert-danger">
                                    <span>⚠️</span>
                                    <div><?php echo htmlspecialchars($theme_error); ?></div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($theme_success)): ?>
                                <div class="alert alert-success">
                                    <span>✓</span>
                                    <div><?php echo htmlspecialchars($theme_success); ?></div>
                                </div>
                            <?php endif; ?>

                            <h3>Pilih Tema untuk Sekolah</h3>
                            <div style="background: #f5f5f5; padding: 12px; border-radius: 6px; margin-bottom: 16px;">
                                <small><strong>Tema Saat Ini:</strong> <span
                                        style="color: #2563eb; font-weight: 600; text-transform: capitalize;"><?php echo htmlspecialchars($currentTheme['theme_name'] ?? 'light'); ?></span></small>
                            </div>

                            <form method="post" style="margin-bottom: 16px;">
                                <input type="hidden" name="action" value="update_theme">

                                <div class="theme-grid">
                                    <button type="submit" formaction="" name="theme_name" value="light"
                                        class="btn btn-secondary theme-btn theme-btn-light" data-theme="light">
                                        Light
                                    </button>
                                    <button type="submit" formaction="" name="theme_name" value="dark"
                                        class="btn btn-secondary theme-btn theme-btn-dark" data-theme="dark">
                                        Dark
                                    </button>
                                    <button type="submit" formaction="" name="theme_name" value="blue"
                                        class="btn btn-secondary theme-btn theme-btn-blue" data-theme="blue">
                                        Blue
                                    </button>
                                    <button type="submit" formaction="" name="theme_name" value="monochrome"
                                        class="btn btn-secondary theme-btn theme-btn-monochrome"
                                        data-theme="monochrome">
                                        Monochrome
                                    </button>
                                    <button type="submit" formaction="" name="theme_name" value="sepia"
                                        class="btn btn-secondary theme-btn theme-btn-sepia" data-theme="sepia">
                                        Sepia
                                    </button>
                                    <button type="submit" formaction="" name="theme_name" value="sunset"
                                        class="btn btn-secondary theme-btn theme-btn-sunset" data-theme="sunset">
                                        Sunset
                                    </button>
                                </div>
                            </form>
                            <small class="theme-hint" style="display: block; margin-top: 12px;">💡 Pilih tema dengan
                                mengklik salah satu tombol di atas. Tema akan langsung diterapkan ke semua siswa di
                                sekolah ini.</small>
                        </div>

                        <!-- Basic School Info Panel -->
                        <div class="card preview-card">
                            <h2 class="school-header"><iconify-icon icon="mdi:information"
                                    class="school-header-icon"></iconify-icon>Informasi Dasar</h2>

                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger">
                                    <span>⚠️</span>
                                    <div><?php echo htmlspecialchars($error); ?></div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($success)): ?>
                                <div class="alert alert-success">
                                    <span>✓</span>
                                    <div><?php echo htmlspecialchars($success); ?></div>
                                </div>
                            <?php endif; ?>

                            <form method="post" class="school-form">
                                <input type="hidden" name="action" value="update_basic">

                                <div class="form-group">
                                    <label for="name">Nama Sekolah</label>
                                    <input id="name" name="name" required
                                        value="<?php echo htmlspecialchars($school['name'] ?? ''); ?>">
                                </div>

                                <button type="submit" class="btn btn-submit">
                                    <iconify-icon icon="mdi:content-save"
                                        style="font-size: 16px; vertical-align: middle; margin-right: 6px;"></iconify-icon>Simpan
                                    Dasar
                                </button>
                            </form>
                        </div>

                        <!-- Color Customization -->
                        <!-- REMOVED -->

                        <!-- Typography -->
                        <!-- REMOVED -->

                        <!-- Layout Settings -->
                        <!-- REMOVED -->

                    </div>

                    <!-- School Profile Panel -->
                    <div class="card preview-card" id="school-profile">
                        <h2 class="school-header"><iconify-icon icon="mdi:school"
                                class="school-header-icon"></iconify-icon>Profil Sekolah</h2>

                        <?php if (!empty($profile_error)): ?>
                            <div class="alert alert-danger">
                                <span>⚠️</span>
                                <div><?php echo htmlspecialchars($profile_error); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($profile_success)): ?>
                            <div class="alert alert-success">
                                <span>✓</span>
                                <div><?php echo htmlspecialchars($profile_success); ?></div>
                            </div>
                        <?php endif; ?>

                        <!-- Photo Upload Section -->
                        <div class="school-photo-section"
                            style="margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #e2e8f0;">
                            <h3
                                style="font-size: 13px; font-weight: 600; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                                <iconify-icon icon="mdi:image" style="font-size: 16px;"></iconify-icon>
                                Foto Profil
                            </h3>

                            <div style="margin-bottom: 10px;">
                                <div
                                    style="width: 80px; height: 80px; border-radius: 50%; overflow: hidden; background: #f0f0f0; display: flex; align-items: center; justify-content: center; margin-bottom: 8px; border: 2px solid #e2e8f0;">
                                    <?php if ($school['photo_path'] && file_exists(__DIR__ . '/../' . $school['photo_path'])): ?>
                                        <img src="../<?php echo htmlspecialchars($school['photo_path']); ?>"
                                            alt="Foto Profil Sekolah"
                                            style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;"
                                            onerror="this.src='../assets/img/default-school.png';">
                                    <?php else: ?>
                                        <iconify-icon icon="mdi:school"
                                            style="font-size: 40px; color: #a0aec0;"></iconify-icon>
                                    <?php endif; ?>
                                </div>
                                <small style="color: #6b7280; display: block; font-size: 11px;">500x500px max
                                    5MB</small>
                            </div>

                            <!-- Photo Upload Form -->
                            <form method="post" enctype="multipart/form-data" style="margin-bottom: 8px;">
                                <input type="hidden" name="action" value="upload_photo">
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <input type="file" name="school_photo" id="school_photo"
                                        accept="image/jpeg,image/png,image/webp" style="display: none;">
                                    <label for="school_photo"
                                        style="display: inline-block; padding: 8px 12px; background: #0b3d61; color: white; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 500; transition: all 0.2s ease;">
                                        <iconify-icon icon="mdi:upload"
                                            style="font-size: 14px; vertical-align: middle; margin-right: 4px;"></iconify-icon>
                                        Pilih
                                    </label>
                                    <button type="submit" class="btn btn-submit"
                                        style="padding: 8px 12px; font-size: 13px;">
                                        <iconify-icon icon="mdi:check"
                                            style="font-size: 14px; vertical-align: middle; margin-right: 4px;"></iconify-icon>Unggah
                                    </button>
                                </div>
                                <small id="file-name"
                                    style="color: #6b7280; display: block; margin-top: 6px; font-size: 11px;"></small>
                            </form>

                            <!-- Delete Photo Button -->
                            <?php if ($school['photo_path']): ?>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_photo">
                                    <button type="submit" class="btn btn-danger"
                                        style="padding: 6px 10px; font-size: 12px;">
                                        <iconify-icon icon="mdi:trash-can-outline"
                                            style="font-size: 14px; vertical-align: middle; margin-right: 4px;"></iconify-icon>Hapus
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <!-- School Data Form -->
                        <form method="post" class="school-form">
                            <input type="hidden" name="action" value="update_profile">

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                                <div class="form-group">
                                    <label for="school_npsn">NPSN</label>
                                    <input id="school_npsn" name="school_npsn" type="text" placeholder="20102012"
                                        value="<?php echo htmlspecialchars($school['npsn'] ?? ''); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="school_email">Email Sekolah</label>
                                    <input id="school_email" name="school_email" type="email"
                                        placeholder="sekolah@example.com"
                                        value="<?php echo htmlspecialchars($school['email'] ?? ''); ?>">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-submit">
                                <iconify-icon icon="mdi:content-save"
                                    style="font-size: 16px; vertical-align: middle; margin-right: 6px;"></iconify-icon>Simpan
                                Profil
                            </button>
                        </form>

                        <!-- Change Password Form -->
                        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                            <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">
                                <iconify-icon icon="mdi:lock"
                                    style="font-size: 18px; vertical-align: middle; margin-right: 8px;"></iconify-icon>Ganti
                                Password
                            </h3>

                            <form method="post" class="school-form">
                                <input type="hidden" name="action" value="update_password">

                                <div class="form-group" style="margin-bottom: 12px;">
                                    <label for="school_password_old">Password Lama</label>
                                    <input id="school_password_old" name="school_password_old" type="password"
                                        placeholder="Masukkan password lama" required>
                                </div>

                                <div class="form-group" style="margin-bottom: 12px;">
                                    <label for="school_password_new">Password Baru</label>
                                    <input id="school_password_new" name="school_password_new" type="password"
                                        placeholder="Masukkan password baru (minimal 6 karakter)" required>
                                </div>

                                <div class="form-group" style="margin-bottom: 12px;">
                                    <label for="school_password_confirm">Konfirmasi Password Baru</label>
                                    <input id="school_password_confirm" name="school_password_confirm" type="password"
                                        placeholder="Ulangi password baru" required>
                                </div>

                                <button type="submit" class="btn btn-submit">
                                    <iconify-icon icon="mdi:lock-reset"
                                        style="font-size: 16px; vertical-align: middle; margin-right: 6px;"></iconify-icon>Ganti
                                    Password
                                </button>
                            </form>
                        </div>
                    </div>

                </div>

                <!-- FAQ Section -->
                <div class="card">
                    <h2>Pertanyaan Umum</h2>
                    <div class="faq-item">
                        <div class="faq-question">Bagaimana cara mengubah tema aplikasi? <span>+</span></div>
                        <div class="faq-answer">Klik salah satu tombol tema yang tersedia di bagian "Pengaturan Tema".
                            Pilihan Anda akan disimpan secara otomatis dan diterapkan ke seluruh aplikasi.</div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question">Berapa banyak tema yang tersedia? <span>+</span></div>
                        <div class="faq-answer">Ada 9 tema yang dapat dipilih: Light, Dark, Blue, Monochrome, Sepia,
                            Slate, Ocean, Sunset, dan Teal. Setiap tema dirancang untuk kenyamanan visual pengguna.
                        </div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question">Apakah tema yang saya pilih disimpan? <span>+</span></div>
                        <div class="faq-answer">Ya, tema yang Anda pilih akan disimpan di database sekolah Anda. Tema
                            akan tetap diterapkan ketika Anda login kembali.</div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question">Bagaimana cara mengubah nama dan slug sekolah? <span>+</span></div>
                        <div class="faq-answer">Masukkan nama sekolah dan slug baru di form "Informasi Sekolah",
                            kemudian klik tombol "Simpan Perubahan". Slug harus unik dan hanya boleh menggunakan huruf
                            kecil, angka, dan tanda hubung (-).</div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question">Apa itu slug? <span>+</span></div>
                        <div class="faq-answer">Slug adalah identitas unik sekolah Anda yang digunakan dalam URL.
                            Misalnya, slug "sma-negeri-1" akan digunakan dalam alamat website sekolah Anda.</div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question">Bisakah saya mengubah slug yang sudah ada? <span>+</span></div>
                        <div class="faq-answer">Ya, Anda bisa mengubah slug kapan saja, tetapi pastikan slug baru belum
                            digunakan oleh sekolah lain dalam sistem.</div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php include __DIR__ . '/partials/footer.php'; ?>

    <script src="../assets/js/settings.js"></script>
    <script>
        // Handle school photo file input
        const photoInput = document.getElementById('school_photo');
        if (photoInput) {
            photoInput.addEventListener('change', function (e) {
                const fileName = document.getElementById('file-name');
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    const maxSize = 5 * 1024 * 1024; // 5MB

                    if (file.size > maxSize) {
                        alert('File terlalu besar. Maksimal ukuran: 5MB');
                        this.value = '';
                        fileName.textContent = '';
                    } else if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                        alert('Format file tidak didukung. Gunakan JPG, PNG, atau WEBP');
                        this.value = '';
                        fileName.textContent = '';
                    } else {
                        fileName.textContent = 'File dipilih: ' + file.name;
                    }
                }
            });
        }
    </script>


</body>

</html>