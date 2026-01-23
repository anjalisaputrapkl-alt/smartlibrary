<?php
session_start();
$pdo = require __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/NotificationsService.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: /?login_required=1');
    exit;
}

$user = $_SESSION['user'];
$school_id = $user['school_id'];
$studentId = $user['id'];

// Initialize variables
$notifications = [];
$stats = [];
$errorMessage = '';
$successMessage = '';

try {
    $service = new NotificationsService($pdo);

    // Get sort filter from GET
    $sort = $_GET['sort'] ?? 'latest';
    $validSorts = ['latest', 'oldest'];
    $sort = in_array($sort, $validSorts) ? $sort : 'latest';

    // Get notifications
    $notifications = $service->getAllNotifications($studentId);

    // Sort
    if ($sort === 'oldest') {
        $notifications = array_reverse($notifications);
    }

    $stats = $service->getStatistics($studentId);

} catch (Exception $e) {
    $errorMessage = 'Error: ' . htmlspecialchars($e->getMessage());
}

$pageTitle = 'Notifikasi';

// Helper function untuk format tanggal
function formatDate($date)
{
    return NotificationsService::formatDate($date);
}

// Helper function untuk get icon
function getIcon($type)
{
    return NotificationsService::getIcon($type);
}

// Helper function untuk get badge class
function getBadgeClass($type)
{
    return NotificationsService::getBadgeClass($type);
}

// Helper function untuk get label
function getLabel($type)
{
    return NotificationsService::getLabel($type);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi - Perpustakaan Digital</title>
    <script src="../assets/js/db-theme-loader.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
</head>

<body>
    <!-- Navigation Sidebar -->
    <?php include 'partials/student-sidebar.php'; ?>

    <!-- Hamburger Menu Button -->
    <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
        <iconify-icon icon="mdi:menu" width="24" height="24"></iconify-icon>
    </button>

    <!-- Global Student Header -->
    <?php include 'partials/student-header.php'; ?>

    <!-- Main Container -->
    <div class="container-main">
        <!-- Error Alert -->
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger">
                <iconify-icon icon="mdi:alert-circle" width="18" height="18"></iconify-icon>
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>

        <!-- Success Alert -->
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success">
                <iconify-icon icon="mdi:check-circle" width="18" height="18"></iconify-icon>
                <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="page-header">
            <h1>
                <iconify-icon icon="mdi:bell" width="28" height="28"></iconify-icon>
                Notifikasi
            </h1>
            <p>Pantau semua notifikasi penting dari sistem perpustakaan</p>
        </div>

        <!-- Stats Grid -->
        <?php if (!empty($stats)): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-label">
                        <iconify-icon icon="mdi:bell" width="16" height="16"></iconify-icon>
                        Total Notifikasi
                    </div>
                    <div class="stat-card-value"><?php echo (int) ($stats['total'] ?? 0); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-label">
                        <iconify-icon icon="mdi:email-multiple-outline" width="16" height="16"></iconify-icon>
                        Belum Dibaca
                    </div>
                    <div class="stat-card-value"><?php echo (int) ($stats['unread'] ?? 0); ?></div>
                </div>
                <div class="stat-card overdue">
                    <div class="stat-card-label">
                        <iconify-icon icon="mdi:alert-circle" width="16" height="16"></iconify-icon>
                        Keterlambatan
                    </div>
                    <div class="stat-card-value"><?php echo (int) ($stats['overdue'] ?? 0); ?></div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-card-label">
                        <iconify-icon icon="mdi:alert-triangle" width="16" height="16"></iconify-icon>
                        Peringatan
                    </div>
                    <div class="stat-card-value"><?php echo (int) ($stats['warning'] ?? 0); ?></div>
                </div>
                <div class="stat-card return">
                    <div class="stat-card-label">
                        <iconify-icon icon="mdi:package-variant-closed" width="16" height="16"></iconify-icon>
                        Pengembalian
                    </div>
                    <div class="stat-card-value"><?php echo (int) ($stats['return'] ?? 0); ?></div>
                </div>
                <div class="stat-card info">
                    <div class="stat-card-label">
                        <iconify-icon icon="mdi:information" width="16" height="16"></iconify-icon>
                        Informasi
                    </div>
                    <div class="stat-card-value"><?php echo (int) ($stats['info'] ?? 0); ?></div>
                </div>
                <div class="stat-card newbooks">
                    <div class="stat-card-label">
                        <iconify-icon icon="mdi:book-open-page-variant" width="16" height="16"></iconify-icon>
                        Buku Baru
                    </div>
                    <div class="stat-card-value"><?php echo (int) ($stats['newbooks'] ?? 0); ?></div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <a href="?sort=latest" class="filter-btn <?php echo $sort === 'latest' ? 'active' : ''; ?>">
                <iconify-icon icon="mdi:clock" width="16" height="16"></iconify-icon>
                Terbaru
            </a>
            <a href="?sort=oldest" class="filter-btn <?php echo $sort === 'oldest' ? 'active' : ''; ?>">
                <iconify-icon icon="mdi:archive" width="16" height="16"></iconify-icon>
                Terlama
            </a>
        </div>

        <!-- Notifications List -->
        <?php if (empty($notifications)): ?>
            <!-- Empty State -->
            <div class="empty-state">
                <div class="empty-state-icon">
                    <iconify-icon icon="mdi:inbox-multiple" width="64" height="64"></iconify-icon>
                </div>
                <h3>Belum Ada Notifikasi</h3>
                <p>Semua peminjaman Anda dalam kondisi baik. Tidak ada notifikasi penting saat ini.</p>
                <a href="student-dashboard.php"
                    style="display: inline-block; padding: 10px 20px; background: var(--accent); color: white; text-decoration: none; border-radius: 6px; font-weight: 600; transition: all 0.2s ease;">
                    <iconify-icon icon="mdi:arrow-left" width="16" height="16"></iconify-icon>
                    Kembali ke Dashboard
                </a>
            </div>
        <?php else: ?>
            <!-- Notifications Cards -->
            <?php foreach ($notifications as $notif): ?>
                <div
                    class="notification-card <?php echo !$notif['status_baca'] ? 'unread' : ''; ?> <?php echo htmlspecialchars($notif['jenis_notifikasi']); ?>">
                    <div class="notification-card-content">
                        <div class="notification-card-icon">
                            <iconify-icon icon="<?php echo getIcon($notif['jenis_notifikasi']); ?>" width="24"
                                height="24"></iconify-icon>
                        </div>
                        <div class="notification-card-body">
                            <div class="notification-card-header">
                                <h3 class="notification-card-title">
                                    <?php echo htmlspecialchars($notif['judul']); ?>
                                </h3>
                                <span class="notification-card-badge <?php echo getBadgeClass($notif['jenis_notifikasi']); ?>">
                                    <?php echo getLabel($notif['jenis_notifikasi']); ?>
                                </span>
                            </div>
                            <p class="notification-card-message">
                                <?php echo htmlspecialchars($notif['pesan']); ?>
                            </p>
                            <div class="notification-card-footer">
                                <div class="notification-card-date">
                                    <iconify-icon icon="mdi:clock-outline" width="16" height="16"></iconify-icon>
                                    <?php echo formatDate($notif['tanggal']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="../assets/js/notifications.js"></script>
</body>

</html>