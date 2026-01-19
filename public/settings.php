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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">  <link rel="stylesheet" href="../assets/css/animations.css">    <link rel="stylesheet" href="../assets/css/settings.css">
</head>

<body>
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <div class="app">

        <div class="topbar">
            <strong>⚙️ Pengaturan Sekolah</strong>
        </div>

        <div class="content">
            <div class="main">

                <div class="settings-section">
                    <div class="settings-controls">

                        <!-- Theme Settings -->
                        <div class="card">
                            <h2>🎨 Pengaturan Tema</h2>

                            <h3>Pilih Tema</h3>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                                <button class="btn theme-btn" data-theme="light"
                                    style="padding: 12px; background: #f0f9ff; border: 2px solid var(--accent); font-weight: 600;">☀️
                                    Light</button>
                                <button class="btn theme-btn" data-theme="dark"
                                    style="padding: 12px; background: #1f2937; color: white; font-weight: 600;">🌙
                                    Dark</button>
                                <button class="btn theme-btn" data-theme="blue"
                                    style="padding: 12px; background: #0f172a; color: #60a5fa; border: 2px solid #60a5fa; font-weight: 600;">🔵
                                    Blue</button>
                            </div>
                            <small style="display: block; margin-top: 12px; color: var(--muted);">Tema yang dipilih akan
                                disimpan secara otomatis</small>

                            <h3>Tema Tambahan</h3>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                                <button class="btn theme-btn" data-theme="green"
                                    style="padding: 12px; background: #065f46; color: #d1fae5; border: 2px solid #10b981; font-weight: 600;">🟢
                                    Green</button>
                                <button class="btn theme-btn" data-theme="purple"
                                    style="padding: 12px; background: #581c87; color: #e9d5ff; border: 2px solid #d946ef; font-weight: 600;">🟣
                                    Purple</button>
                                <button class="btn theme-btn" data-theme="orange"
                                    style="padding: 12px; background: #7c2d12; color: #fed7aa; border: 2px solid #f97316; font-weight: 600;">🟠
                                    Orange</button>
                                <button class="btn theme-btn" data-theme="rose"
                                    style="padding: 12px; background: #831843; color: #ffe4e6; border: 2px solid #f43f5e; font-weight: 600;">🌹
                                    Rose</button>
                                <button class="btn theme-btn" data-theme="indigo"
                                    style="padding: 12px; background: #312e81; color: #e0e7ff; border: 2px solid #6366f1; font-weight: 600;">💜
                                    Indigo</button>
                                <button class="btn theme-btn" data-theme="cyan"
                                    style="padding: 12px; background: #164e63; color: #cffafe; border: 2px solid #06b6d4; font-weight: 600;">🔷
                                    Cyan</button>
                                <button class="btn theme-btn" data-theme="pink"
                                    style="padding: 12px; background: #831854; color: #fbcfe8; border: 2px solid #ec4899; font-weight: 600;">💖
                                    Pink</button>
                                <button class="btn theme-btn" data-theme="amber"
                                    style="padding: 12px; background: #78350f; color: #fef3c7; border: 2px solid #f59e0b; font-weight: 600;">🟡
                                    Amber</button>
                                <button class="btn theme-btn" data-theme="red"
                                    style="padding: 12px; background: #7f1d1d; color: #fee2e2; border: 2px solid #ef4444; font-weight: 600;">🔴
                                    Red</button>
                                <button class="btn theme-btn" data-theme="slate"
                                    style="padding: 12px; background: #1e293b; color: #e2e8f0; border: 2px solid #64748b; font-weight: 600;">⚫
                                    Slate</button>
                                <button class="btn theme-btn" data-theme="teal"
                                    style="padding: 12px; background: #134e4a; color: #ccfbf1; border: 2px solid #14b8a6; font-weight: 600;">🧊
                                    Teal</button>
                                <button class="btn theme-btn" data-theme="lime"
                                    style="padding: 12px; background: #365314; color: #cdfe56; border: 2px solid #84cc16; font-weight: 600;">🟢
                                    Lime</button>
                            </div>
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
                        <h2>🏫 Informasi Sekolah</h2>

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

                            <button type="submit" class="btn primary" style="width: 100%;">💾 Simpan
                                Perubahan</button>
                        </form>
                    </div>

                </div>

                <!-- FAQ Section -->
                <div class="card">
                    <h2>Pertanyaan Umum</h2>
                    <div class="faq-item">
                        <div class="faq-question">Bagaimana cara mengubah tema aplikasi? <span>+</span></div>
                        <div class="faq-answer">Klik salah satu tombol tema yang tersedia di bagian "Pengaturan Tema". Pilihan Anda akan disimpan secara otomatis dan diterapkan ke seluruh aplikasi.</div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question">Berapa banyak tema yang tersedia? <span>+</span></div>
                        <div class="faq-answer">Ada 15 tema yang dapat dipilih: Light, Dark, Blue, Green, Purple, Orange, Rose, Indigo, Cyan, Pink, Amber, Red, Slate, Teal, dan Lime.</div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question">Apakah tema yang saya pilih disimpan? <span>+</span></div>
                        <div class="faq-answer">Ya, tema yang Anda pilih akan disimpan di database sekolah Anda. Tema akan tetap diterapkan ketika Anda login kembali.</div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question">Bagaimana cara mengubah nama dan slug sekolah? <span>+</span></div>
                        <div class="faq-answer">Masukkan nama sekolah dan slug baru di form "Informasi Sekolah", kemudian klik tombol "Simpan Perubahan". Slug harus unik dan hanya boleh menggunakan huruf kecil, angka, dan tanda hubung (-).</div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question">Apa itu slug? <span>+</span></div>
                        <div class="faq-answer">Slug adalah identitas unik sekolah Anda yang digunakan dalam URL. Misalnya, slug "sma-negeri-1" akan digunakan dalam alamat website sekolah Anda.</div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question">Bisakah saya mengubah slug yang sudah ada? <span>+</span></div>
                        <div class="faq-answer">Ya, Anda bisa mengubah slug kapan saja, tetapi pastikan slug baru belum digunakan oleh sekolah lain dalam sistem.</div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php include __DIR__ . '/partials/footer.php'; ?>

    <script src="../assets/js/settings.js"></script>


</body>

</html>