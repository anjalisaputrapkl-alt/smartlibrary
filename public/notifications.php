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

    // Get type filter from GET
    $typeFilter = $_GET['type'] ?? null;
    $validTypes = ['delay', 'warning', 'return', 'info', 'newbooks'];

    // Get notifications
    $notifications = $service->getAllNotifications($studentId);

    // Filter by type if specified
    if ($typeFilter && in_array($typeFilter, $validTypes)) {
        $typeMap = [
            'delay' => 'keterlambatan',
            'warning' => 'peringatan',
            'return' => 'pengembalian',
            'info' => 'informasi',
            'newbooks' => 'buku_baru'
        ];
        $filterType = $typeMap[$typeFilter];
        $notifications = array_filter($notifications, fn($n) => $n['jenis_notifikasi'] === $filterType);
        $notifications = array_values($notifications); // Re-index array
    }

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
    <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation" tabindex="0"
        onclick="toggleNavSidebar(event)">
        <iconify-icon icon="mdi:menu" width="24" height="24"></iconify-icon>
    </button>

    <!-- Global Student Header -->
    <?php include 'partials/student-header.php'; ?>

    <!-- Main Container -->
    <div class="container-main">
        <!-- Page Header -->
        <div class="page-header">
            <div class="topbar-title">
                <iconify-icon icon="mdi:bell-outline" width="28" height="28" style="color: var(--accent);"></iconify-icon>
                <h1>Notifikasi</h1>
            </div>
            <p>Pantau semua notifikasi penting dari sistem perpustakaan</p>
        </div>

        <!-- Error Alert -->
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger" style="background: var(--danger-soft); color: var(--danger); border-color: var(--danger-soft);">
                <iconify-icon icon="mdi:alert-circle" width="18" height="18"></iconify-icon>
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>

        <!-- Success Alert -->
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success" style="background: var(--success-soft); color: var(--success); border-color: var(--success-soft);">
                <iconify-icon icon="mdi:check-circle" width="18" height="18"></iconify-icon>
                <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>

        <!-- Stats Grid -->
        <?php if (!empty($stats)): ?>
            <div class="stats-grid">
                <div class="stat-card-link" onclick="showNotificationModal('Semua Notifikasi', 'all')">
                    <div class="stat-card">
                        <div class="stat-card-label">
                            <iconify-icon icon="mdi:bell" width="16" height="16"></iconify-icon>
                            Total Notifikasi
                        </div>
                        <div class="stat-card-value"><?php echo (int) ($stats['total'] ?? 0); ?></div>
                    </div>
                </div>
                <div class="stat-card-link" onclick="showNotificationModal('Keterlambatan', 'keterlambatan')">
                    <div class="stat-card overdue">
                        <div class="stat-card-label">
                            <iconify-icon icon="mdi:alert-circle" width="16" height="16"></iconify-icon>
                            Keterlambatan
                        </div>
                        <div class="stat-card-value"><?php echo (int) ($stats['overdue'] ?? 0); ?></div>
                    </div>
                </div>
                <div class="stat-card-link" onclick="showNotificationModal('Peringatan', 'peringatan')">
                    <div class="stat-card warning">
                        <div class="stat-card-label">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M13 14h-2V9h2m0 9h-2v-2h2M1 21h22L12 2z" />
                            </svg>
                            Peringatan
                        </div>
                        <div class="stat-card-value"><?php echo (int) ($stats['warning'] ?? 0); ?></div>
                    </div>
                </div>
                <div class="stat-card-link" onclick="showNotificationModal('Pengembalian', 'borrow')">
                    <div class="stat-card return">
                        <div class="stat-card-label">
                            <iconify-icon icon="mdi:package-variant-closed" width="16" height="16"></iconify-icon>
                            Pengembalian
                        </div>
                        <div class="stat-card-value"><?php echo (int) ($stats['return'] ?? 0); ?></div>
                    </div>
                </div>
                <div class="stat-card-link" onclick="showNotificationModal('Informasi', 'info')">
                    <div class="stat-card info">
                        <div class="stat-card-label">
                            <iconify-icon icon="mdi:information" width="16" height="16"></iconify-icon>
                            Informasi
                        </div>
                        <div class="stat-card-value"><?php echo (int) ($stats['info'] ?? 0); ?></div>
                    </div>
                </div>
                <div class="stat-card-link" onclick="showNotificationModal('Buku Baru', 'new_book')">
                    <div class="stat-card newbooks">
                        <div class="stat-card-label">
                            <iconify-icon icon="mdi:book-open-page-variant" width="16" height="16"></iconify-icon>
                            Buku Baru
                        </div>
                        <div class="stat-card-value"><?php echo (int) ($stats['newbooks'] ?? 0); ?></div>
                    </div>
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
                <a href="student-dashboard.php" class="btn-dashboard">
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

    <script>
        // Seed modal data with current PHP-provided notifications as fallback
        let allNotificationsForModal = <?php echo json_encode($notifications); ?> || [];

        // Try to load ALL notifications (unfiltered) from API to ensure modal can show every type
        document.addEventListener('DOMContentLoaded', async () => {
            try {
                const resp = await fetch('./api/notifications-all.php');
                const json = await resp.json();
                if (json && json.success && Array.isArray(json.data)) {
                    allNotificationsForModal = json.data;
                    console.log('✓ Loaded ' + allNotificationsForModal.length + ' notifications from API');
                    const types = [...new Set(allNotificationsForModal.map(n => n.jenis_notifikasi))];
                    console.log('Available types in database:', types);
                    console.log('Full notification data:', allNotificationsForModal);
                } else {
                    console.warn('API response invalid:', json);
                }
            } catch (err) {
                console.error('Failed to load full notifications via API:', err);
            }
        });

        /**
         * Show notifications modal with table view
         */
        function showNotificationModal(title, typeFilter) {
            let data = allNotificationsForModal || [];

            // Filter by type if specified (case-insensitive, trimmed)
            if (typeFilter && typeFilter !== 'all') {
                const f = typeFilter.toLowerCase().trim();
                data = data.filter(n => (n.jenis_notifikasi || '').toLowerCase().trim() === f);
            }

            // Create modal overlay
            const modal = document.createElement('div');
            modal.className = 'notification-modal-overlay';
            modal.style.background = 'var(--overlay)';
            modal.style.backdropFilter = 'blur(4px)';
            modal.onclick = (e) => {
                if (e.target === modal) closeNotificationModal(modal);
            };

            // Modal content
            const modalContent = document.createElement('div');
            modalContent.className = 'notification-modal-content';
            modalContent.style.background = 'var(--surface)';
            modalContent.style.color = 'var(--text)';
            modalContent.style.boxShadow = 'var(--shadow-lg)';
            
            modalContent.innerHTML = `
                <div class="notification-modal-header" style="border-bottom: 1px solid var(--border);">
                    <h2 style="color: var(--text);">${title}</h2>
                    <button onclick="closeNotificationModal()" class="notification-modal-close" style="background: var(--bg); color: var(--text); border: 1px solid var(--border);">
                        <iconify-icon icon="mdi:close" width="20" height="20"></iconify-icon>
                    </button>
                </div>
                <div class="notification-modal-body">
                    ${data && data.length > 0 ? renderNotificationTableHtml(data) : '<div class="empty-state" style="text-align: center; padding: 40px 20px;">\n<p style="color: var(--text-muted);">Data tidak ditemukan</p>\n</div>'}
                </div>
            `;

            modal.appendChild(modalContent);
            document.body.appendChild(modal);

            // Trigger animation
            setTimeout(() => modal.classList.add('active'), 10);
        }

        /**
         * Close notification modal
         */
        function closeNotificationModal(modalElement) {
            const modal = modalElement || document.querySelector('.notification-modal-overlay.active');
            if (modal) {
                modal.classList.remove('active');
                setTimeout(() => modal.remove(), 300);
            }
        }

        /**
         * Render notifications as HTML table
         */
        function renderNotificationTableHtml(data) {
            if (!data || data.length === 0) {
                return '<div class="empty-state"><p>Data tidak ditemukan</p></div>';
            }

            let html = '<table class="notification-modal-table"><thead><tr>';
            html += '<th>Judul</th>';
            html += '<th>Pesan</th>';
            html += '<th>Tipe</th>';
            html += '<th>Tanggal</th>';
            html += '</tr></thead><tbody>';

            data.forEach(item => {
                let typeBadge = '';
                const normalized = (item.jenis_notifikasi || '').toLowerCase().trim();
                
                switch(normalized) {
                    case 'keterlambatan':
                        typeBadge = '<span class="notification-badge badge-delay">Keterlambatan</span>';
                        break;
                    case 'peringatan':
                        typeBadge = '<span class="notification-badge badge-warning">Peringatan</span>';
                        break;
                    case 'borrow':
                        typeBadge = '<span class="notification-badge badge-return">Pengembalian</span>';
                        break;
                    case 'info':
                        typeBadge = '<span class="notification-badge badge-info">Informasi</span>';
                        break;
                    case 'new_book':
                        typeBadge = '<span class="notification-badge badge-newbooks">Buku Baru</span>';
                        break;
                    default:
                        typeBadge = '<span class="notification-badge">' + (item.jenis_notifikasi || '-') + '</span>';
                }

                const tanggal = new Date(item.tanggal);
                const formattedDate = tanggal.toLocaleDateString('id-ID', { 
                    year: 'numeric', 
                    month: '2-digit', 
                    day: '2-digit' 
                });

                html += `<tr>
                    <td class="title-cell">${item.judul || '-'}</td>
                    <td class="message-cell">${item.pesan || '-'}</td>
                    <td>${typeBadge}</td>
                    <td>${formattedDate}</td>
                </tr>`;
            });

            html += '</tbody></table>';
            return html;
        }

        // Close modal on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeNotificationModal();
            }
        });
    </script>
    <script src="../assets/js/notifications.js"></script>
    <script src="../assets/js/sidebar.js"></script>
</body>

</html>