<?php
/**
 * Student Dashboard Sidebar
 * File terpisah untuk navigasi sidebar siswa
 * Include: <?php include 'partials/student-sidebar.php'; ?>
 */

if (session_status() !== PHP_SESSION_ACTIVE)
    session_start();

// Get current page
$currentPage = basename($_SERVER['PHP_SELF']);

// Load school profile data
$school = null;
$school_photo = null;
$school_email = null;
$school_npsn = null;

// Get school ID from session
if (isset($_SESSION['user'])) {
    try {
        $pdo = require __DIR__ . '/../../src/db.php';
        $stmt = $pdo->prepare('SELECT name, photo_path, email, npsn FROM schools WHERE id = :id');
        $stmt->execute(['id' => $_SESSION['user']['school_id']]);
        $school = $stmt->fetch();

        if ($school) {
            $school_photo = $school['photo_path'] ?? null;
            $school_email = $school['email'] ?? null;
            $school_npsn = $school['npsn'] ?? null;
        }
    } catch (Exception $e) {
        // Fallback if database query fails
        error_log('Student sidebar error: ' . $e->getMessage());
    }
}
?>

<style>
    /* Remove entrance animation on responsive screens */
    @media (max-width: 768px) {
        .nav-sidebar {
            animation: none !important;
        }
    }
</style>

<!-- Navigation Sidebar -->
<aside class="nav-sidebar" id="navSidebar">
    <!-- School Profile Header -->
    <div class="school-profile-header">
        <!-- School Photo -->
        <div class="school-photo-wrapper">
            <?php if ($school_photo && file_exists(__DIR__ . '/../../' . $school_photo)): ?>
                <img src="/perpustakaan-online/<?php echo htmlspecialchars($school_photo); ?>"
                    alt="<?php echo htmlspecialchars($school['name'] ?? 'School Logo'); ?>" class="school-photo"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="school-photo-placeholder" style="display: none;">
                    <iconify-icon icon="mdi:school"></iconify-icon>
                </div>
            <?php else: ?>
                <div class="school-photo-placeholder">
                    <iconify-icon icon="mdi:school"></iconify-icon>
                </div>
            <?php endif; ?>
        </div>

        <!-- School Name -->
        <?php if ($school): ?>
            <h3 class="school-name"><?php echo htmlspecialchars($school['name']); ?></h3>

            <!-- School Info -->
            <div class="school-info">
                <?php if ($school_email): ?>
                    <div class="school-info-item">
                        <iconify-icon icon="mdi:email-outline"></iconify-icon>
                        <span><?php echo htmlspecialchars($school_email); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($school_npsn): ?>
                    <div class="school-info-item">
                        <iconify-icon icon="mdi:identifier"></iconify-icon>
                        <span><?php echo htmlspecialchars($school_npsn); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Navigation Menu -->
    <a href="student-dashboard.php" class="nav-sidebar-header" style="display: none;">
        <div class="nav-sidebar-header-icon">
            <iconify-icon icon="mdi:library" width="32" height="32"></iconify-icon>
        </div>
        <h2>AS Library</h2>
    </a>

    <ul class="nav-sidebar-menu">
        <li>
            <a href="student-dashboard.php" <?php echo $currentPage === 'student-dashboard.php' ? 'class="active"' : ''; ?>>
                <iconify-icon icon="mdi:home" width="18" height="18"></iconify-icon>
                Dashboard
            </a>
        </li>
        <li>
            <a href="student-borrowing-history.php" <?php echo $currentPage === 'student-borrowing-history.php' ? 'class="active"' : ''; ?>>
                <iconify-icon icon="mdi:book-open-variant" width="18" height="18"></iconify-icon>
                Riwayat Peminjaman
            </a>
        </li>
        <li>
            <a href="notifications.php" <?php echo $currentPage === 'notifications.php' ? 'class="active"' : ''; ?>>
                <iconify-icon icon="mdi:bell" width="18" height="18"></iconify-icon>
                Notifikasi
            </a>
        </li>
        <li>
            <a href="favorites.php" <?php echo $currentPage === 'favorites.php' ? 'class="active"' : ''; ?>>
                <iconify-icon icon="mdi:heart" width="18" height="18"></iconify-icon>
                Koleksi Favorit
            </a>
        </li>
        <li>
            <a href="profil.php" <?php echo $currentPage === 'profil.php' ? 'class="active"' : ''; ?>>
                <iconify-icon icon="mdi:account" width="18" height="18"></iconify-icon>
                Profil Saya
            </a>
        </li>
    </ul>

    <div class="nav-sidebar-divider"></div>

    <ul class="nav-sidebar-menu">
        <li>
            <a href="help.php" <?php echo $currentPage === 'help.php' ? 'class="active"' : ''; ?>>
                <iconify-icon icon="mdi:help-circle" width="18" height="18"></iconify-icon>
                Bantuan
            </a>
        </li>
        <li>
            <a href="logout.php" <?php echo $currentPage === 'logout.php' ? 'class="active"' : ''; ?>>
                <iconify-icon icon="mdi:logout" width="18" height="18"></iconify-icon>
                Logout
            </a>
        </li>
    </ul>
</aside>

<script>
    // Tampilkan animasi hanya di kunjungan pertama
    document.addEventListener('DOMContentLoaded', function () {
        const navSidebar = document.getElementById('navSidebar');
        const isFirstVisit = !sessionStorage.getItem('sidebarAnimated');

        if (!isFirstVisit) {
            // Jika bukan kunjungan pertama, hapus animasi
            const allElements = navSidebar.querySelectorAll('*');
            navSidebar.style.animation = 'none';
            allElements.forEach(el => {
                el.style.animation = 'none';
            });
        }

        // Tandai bahwa sidebar sudah ditampilkan
        sessionStorage.setItem('sidebarAnimated', 'true');
    });
</script>