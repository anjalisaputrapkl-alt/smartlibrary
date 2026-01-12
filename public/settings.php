<?php
session_start();
if (empty($_SESSION['user'])) {
    header('Location: /perpustakaan-online/public/login');
    exit;
}
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
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengaturan Sekolah - Perpustakaan Online</title>
    <link rel="stylesheet" href="/perpustakaan-online/public/assets/css/styles.css">
</head>

<body>
    <?php include __DIR__ . '/partials/header.php'; ?>

    <div class="container">
        <div class="card">
            <div class="header-row">
                <div>
                    <h1>Pengaturan Sekolah</h1>
                </div>
                <div class="btn-group">
                    <a class="btn" href="/perpustakaan-online/public/index.php">← Dashboard</a>
                </div>
            </div>

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

            <form method="post" style="max-width: 500px;">
                <div class="form-group">
                    <label for="name">Nama Sekolah</label>
                    <input id="name" name="name" required value="<?php echo htmlspecialchars($school['name']); ?>">
                </div>
                <div class="form-group">
                    <label for="slug">Slug (untuk URL)</label>
                    <input id="slug" name="slug" required value="<?php echo htmlspecialchars($school['slug']); ?>">
                    <small>Gunakan huruf kecil, angka, dan tanda hubung (-)</small>
                </div>
                <button type="submit" class="btn" style="width: 100%; justify-content: center;">💾 Simpan
                    Pengaturan</button>
            </form>

            <?php include __DIR__ . '/partials/footer.php'; ?>
        </div>
    </div>

</body>

</html>