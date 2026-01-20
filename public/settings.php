<?php
require __DIR__ . '/../src/auth.php';
requireAuth();
$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if ($name === '' || $slug === '') {
        $error = 'Nama dan slug wajib diisi.';
    } else {
        // ensure slug unique
        $stmt = $pdo->prepare('SELECT id FROM schools WHERE slug = :slug AND id != :id');
        $stmt->execute(['slug' => $slug, 'id' => $sid]);
        $exists = $stmt->fetchColumn();
        if ($exists) {
            $error = 'Slug sudah digunakan oleh sekolah lain.';
        } else {
            $stmt = $pdo->prepare('UPDATE schools SET name = :name, slug = :slug WHERE id = :id');
            $stmt->execute(['name' => $name, 'slug' => $slug, 'id' => $sid]);
            $success = 'Pengaturan tersimpan.';
        }
    }
}

$stmt = $pdo->prepare('SELECT * FROM schools WHERE id = :id');
$stmt->execute(['id' => $sid]);
$school = $stmt->fetch();
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
    <link rel="stylesheet" href="../assets/css/settings.css">
</head>

<body>
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <div class="app">

        <div class="topbar">
            <strong><iconify-icon icon="mdi:cog"
                    style="vertical-align: middle; margin-right: 8px;"></iconify-icon>Pengaturan Sekolah</strong>
        </div>

        <div class="content">
            <div class="main">

                <div class="settings-section">
                    <div class="settings-controls">

                        <!-- Theme Settings -->
                        <div class="card">
                            <h2><iconify-icon icon="mdi:palette"
                                    style="vertical-align: middle; margin-right: 8px;"></iconify-icon>Pengaturan Tema
                            </h2>

                            <h3>Pilih Tema</h3>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                                <button class="btn btn-secondary theme-btn" data-theme="light"
                                    style="padding: 12px; background: #f0f9ff; border: 2px solid var(--accent); font-weight: 600;"><iconify-icon
                                        icon="mdi:white-balance-sunny"
                                        style="margin-right: 6px; vertical-align: middle;"></iconify-icon>Light</button>
                                <button class="btn btn-secondary theme-btn" data-theme="dark"
                                    style="padding: 12px; background: #1f2937; color: white; font-weight: 600;"><iconify-icon
                                        icon="mdi:moon-waning-crescent"
                                        style="margin-right: 6px; vertical-align: middle;"></iconify-icon>Dark</button>
                                <button class="btn btn-secondary theme-btn" data-theme="blue"
                                    style="padding: 12px; background: #0f172a; color: #60a5fa; border: 2px solid #60a5fa; font-weight: 600;"><iconify-icon
                                        icon="mdi:circle-multiple"
                                        style="margin-right: 6px; vertical-align: middle;"></iconify-icon>Blue</button>
                                <button class="btn btn-secondary theme-btn" data-theme="monochrome"
                                    style="padding: 12px; background: #262626; color: #f5f5f5; border: 2px solid #808080; font-weight: 600;"><iconify-icon
                                        icon="mdi:checkbox-multiple-blank-circle-outline"
                                        style="margin-right: 6px; vertical-align: middle;"></iconify-icon>Monochrome</button>
                                <button class="btn btn-secondary theme-btn" data-theme="sepia"
                                    style="padding: 12px; background: #5d4e37; color: #e8d7c3; border: 2px solid #8b7355; font-weight: 600;"><iconify-icon
                                        icon="mdi:image-filter-vintage"
                                        style="margin-right: 6px; vertical-align: middle;"></iconify-icon>Sepia</button>
                                <button class="btn btn-secondary theme-btn" data-theme="slate"
                                    style="padding: 12px; background: #1c1f26; color: #7c88a3; border: 2px solid #3a4251; font-weight: 600;"><iconify-icon
                                        icon="mdi:palette-gray"
                                        style="margin-right: 6px; vertical-align: middle;"></iconify-icon>Slate</button>
                                <button class="btn btn-secondary theme-btn" data-theme="ocean"
                                    style="padding: 12px; background: #0a1628; color: #1fa3f4; border: 2px solid #2d4a6f; font-weight: 600;"><iconify-icon
                                        icon="mdi:water"
                                        style="margin-right: 6px; vertical-align: middle;"></iconify-icon>Ocean</button>
                                <button class="btn btn-secondary theme-btn" data-theme="sunset"
                                    style="padding: 12px; background: #2d1810; color: #ff9f4a; border: 2px solid #5c4033; font-weight: 600;"><iconify-icon
                                        icon="mdi:weather-sunset"
                                        style="margin-right: 6px; vertical-align: middle;"></iconify-icon>Sunset</button>
                                <button class="btn btn-secondary theme-btn" data-theme="teal"
                                    style="padding: 12px; background: #0d4f4f; color: #26a69a; border: 2px solid #2d7a7a; font-weight: 600;"><iconify-icon
                                        icon="mdi:water-opacity"
                                        style="margin-right: 6px; vertical-align: middle;"></iconify-icon>Teal</button>
                            </div>
                            <small style="display: block; margin-top: 12px; color: var(--muted);">Tema yang dipilih akan
                                disimpan secara otomatis</small>


                        </div>

                        <!-- Color Customization -->
                        <!-- REMOVED -->

                        <!-- Typography -->
                        <!-- REMOVED -->

                        <!-- Layout Settings -->
                        <!-- REMOVED -->

                    </div>

                    <!-- School Info Panel -->
                    <div class="card preview-card">
                        <h2><iconify-icon icon="mdi:school"
                                style="vertical-align: middle; margin-right: 8px;"></iconify-icon>Informasi Sekolah</h2>

                        <?php if (!empty($error)): ?>
                            <div class="alert danger">
                                <span>⚠️</span>
                                <div><?php echo $error; ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success)): ?>
                            <div class="alert success">
                                <span>✓</span>
                                <div><?php echo $success; ?></div>
                            </div>
                        <?php endif; ?>

                        <form method="post">
                            <div class="form-group">
                                <label for="name">Nama Sekolah</label>
                                <input id="name" name="name" required
                                    value="<?php echo htmlspecialchars($school['name']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="slug">Slug (untuk URL)</label>
                                <input id="slug" name="slug" required
                                    value="<?php echo htmlspecialchars($school['slug']); ?>">
                                <small>Gunakan huruf kecil, angka, dan tanda hubung (-)</small>
                            </div>

                            <button type="submit" class="btn" style="width: 100%;"><iconify-icon icon="mdi:content-save"
                                    style="vertical-align: middle; margin-right: 6px;"></iconify-icon>Simpan
                                Perubahan</button>
                        </form>
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


</body>

</html>