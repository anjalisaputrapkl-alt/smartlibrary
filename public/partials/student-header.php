<?php
/**
 * Global Student Header Component
 * 
 * Menampilkan header dengan brand, user info, dan logout button
 * Digunakan di semua halaman siswa
 * 
 * Requirements:
 * - $_SESSION['user'] harus sudah di-set sebelumnya
 * - $pageTitle variable bisa di-set sebelum include untuk mengubah subtitle
 * 
 * Usage:
 * $pageTitle = 'Riwayat Peminjaman';
 * include 'partials/student-header.php';
 */

if (!isset($_SESSION['user'])) {
    header('Location: /?login_required=1');
    exit;
}

$user = $_SESSION['user'];
$pageTitle = $pageTitle ?? 'Dashboard Siswa';

// Get student photo from database
$studentPhoto = null;
try {
    $pdo = require __DIR__ . '/../../src/db.php';
    $userId = (int) $_SESSION['user']['id'];
    $stmt = $pdo->prepare("SELECT foto FROM siswa WHERE id_siswa = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $studentPhoto = $result['foto'] ?? null;
} catch (Exception $e) {
    error_log('Header photo fetch error: ' . $e->getMessage());
}
?>
<!-- Header -->
<header class="header">
    <div class="header-container">
        <a href="student-dashboard.php" class="header-brand">
            <div class="header-brand-icon">
                <iconify-icon icon="mdi:library" width="32" height="32"></iconify-icon>
            </div>
            <div class="header-brand-text">
                <h2>AS Library</h2>
                <p><?php echo htmlspecialchars($pageTitle); ?></p>
            </div>
        </a>

        <div class="header-user">
            <div class="header-user-info">
                <p class="name"><?php echo htmlspecialchars($user['name'] ?? 'Siswa'); ?></p>
                <p class="role">Siswa</p>
            </div>
            <div class="header-user-avatar">
                <?php if ($studentPhoto && file_exists(__DIR__ . '/../' . $studentPhoto)): ?>
                    <img src="/perpustakaan-online/public/<?php echo htmlspecialchars($studentPhoto); ?>" alt="Foto Profil"
                        style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                <?php else: ?>
                    <?php echo strtoupper(substr($user['name'] ?? 'S', 0, 1)); ?>
                <?php endif; ?>
            </div>
            <a href="logout.php" class="header-logout">Logout</a>
        </div>
    </div>
</header>