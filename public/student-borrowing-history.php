<?php
session_start();
$pdo = require __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/MemberHelper.php';
require_once __DIR__ . '/../src/maintenance/DamageController.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: /?login_required=1');
    exit;
}

$user = $_SESSION['user'];
$school_id = $user['school_id'];

// Get member_id dengan auto-create jika belum ada
$memberHelper = new MemberHelper($pdo);
$member_id = $memberHelper->getMemberId($user);

// Get damage fines for this member
$damageController = new DamageController($pdo, $school_id);
$memberDamageFines = $damageController->getByMember($member_id);
$totalMemberDenda = 0;
$pendingMemberDenda = 0;
foreach ($memberDamageFines as $fine) {
    $totalMemberDenda += $fine['fine_amount'];
    if ($fine['status'] === 'pending') {
        $pendingMemberDenda += $fine['fine_amount'];
    }
}

// Inisialisasi variabel
$borrowingHistory = [];
$totalBooks = 0;
$borrowedBooks = 0;
$returnedBooks = 0;
$overdueBooks = 0;
$errorMessage = '';

try {
    /**
     * Query untuk mengambil riwayat peminjaman dengan informasi buku
     * 
     * JOIN antara tabel:
     * - borrows: data peminjaman
     * - books: informasi buku (judul, penulis, cover)
     * 
     * Filter berdasarkan member_id dan sortir dari tanggal pinjam terbaru
     */
    $query = "
        SELECT 
            b.id AS borrow_id,
            b.member_id,
            b.book_id,
            b.borrowed_at,
            b.due_at,
            b.returned_at,
            b.status,
            bk.title AS book_title,
            bk.author,
            bk.cover_image,
            CASE 
                WHEN b.status = 'returned' THEN 'Dikembalikan'
                WHEN b.status = 'overdue' THEN 'Telat'
                WHEN b.status = 'borrowed' THEN 'Dipinjam'
                ELSE b.status
            END AS status_text,
            DATEDIFF(b.due_at, NOW()) AS hari_sisa
        FROM borrows b
        LEFT JOIN books bk ON b.book_id = bk.id
        WHERE b.member_id = ?
        ORDER BY b.borrowed_at DESC
    ";

    $stmt = $pdo->prepare($query);
    
    // Sanitasi input
    if (!$stmt->execute([$member_id])) {
        throw new Exception('Gagal mengambil data peminjaman');
    }

    $borrowingHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalBooks = count($borrowingHistory);

    // Hitung statistik
    foreach ($borrowingHistory as $item) {
        switch ($item['status']) {
            case 'borrowed':
                $borrowedBooks++;
                break;
            case 'returned':
                $returnedBooks++;
                break;
            case 'overdue':
                $overdueBooks++;
                break;
        }
    }

} catch (PDOException $e) {
    $errorMessage = 'Error Database: ' . htmlspecialchars($e->getMessage());
} catch (Exception $e) {
    $errorMessage = 'Error: ' . htmlspecialchars($e->getMessage());
}

// Helper function untuk format tanggal
function formatDate($date) {
    if (empty($date) || $date === '0000-00-00 00:00:00') {
        return '-';
    }
    return date('d M Y H:i', strtotime($date));
}

// Helper function untuk format status
function getStatusBadge($status) {
    switch ($status) {
        case 'borrowed':
            return '<span class="badge badge-primary">Dipinjam</span>';
        case 'returned':
            return '<span class="badge badge-success">Dikembalikan</span>';
        case 'overdue':
            return '<span class="badge badge-danger">Telat</span>';
        default:
            return '<span class="badge badge-secondary">' . htmlspecialchars($status) . '</span>';
    }
}

// Helper function untuk status warna
function getStatusColor($status) {
    switch ($status) {
        case 'borrowed':
            return 'warning';
        case 'returned':
            return 'success';
        case 'overdue':
            return 'danger';
        default:
            return 'secondary';
    }
}

$pageTitle = 'Riwayat Peminjaman';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman Buku - Perpustakaan Digital</title>
    <script src="../assets/js/db-theme-loader.js"></script>
    <?php require_once __DIR__ . '/../theme-loader.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/student-borrowing-history.css">
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

        <!-- Page Header -->
        <div class="page-header">
            <h1>
                <iconify-icon icon="mdi:history" width="28" height="28"></iconify-icon>
                Riwayat Peminjaman Buku
            </h1>
            <p>Lihat semua buku yang pernah Anda pinjam dan status pengembaliannya</p>
        </div>

        <!-- Total Denda Section -->
        <div style="animation: fadeInSlideUp 0.4s ease-out; margin-bottom: 24px; padding: 16px; background-color: <?php echo $pendingMemberDenda > 0 ? 'rgba(239, 68, 68, 0.05)' : 'rgba(16, 185, 129, 0.05)'; ?>; border-radius: 8px; border-left: 4px solid <?php echo $pendingMemberDenda > 0 ? '#ef4444' : '#10b981'; ?>; display: flex; align-items: center; gap: 16px;">
            <div style="font-size: 24px; color: <?php echo $pendingMemberDenda > 0 ? '#dc2626' : '#059669'; ?>;">
                <iconify-icon icon="<?php echo $pendingMemberDenda > 0 ? 'mdi:alert-circle' : 'mdi:check-circle'; ?>" width="24" height="24"></iconify-icon>
            </div>
            <div style="flex: 1;">
                <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Denda Tertunda</div>
                <div style="font-size: 24px; font-weight: 700; color: <?php echo $pendingMemberDenda > 0 ? '#dc2626' : '#059669'; ?>;">Rp <?php echo number_format($pendingMemberDenda, 0, ',', '.'); ?></div>
                <?php if ($pendingMemberDenda > 0): ?>
                    <p style="font-size: 12px; color: var(--text-muted); margin: 4px 0 0 0; line-height: 1.5;">Denda dari kerusakan buku saat peminjaman. Hubungi admin untuk detail lebih lanjut.</p>
                <?php else: ?>
                    <p style="font-size: 12px; color: #10b981; margin: 4px 0 0 0;">✓ Tidak ada denda tertunda</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Statistik Cards - Modal Popup Style -->
        <div class="stats-grid-interactive">
            <!-- Total Peminjaman -->
            <div class="stat-card-interactive" onclick="showBorrowingModal('Semua Peminjaman', 'semua')">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Total Peminjaman</div>
                        <div class="stat-card-value"><?php echo $totalBooks; ?></div>
                    </div>
                    <div class="stat-card-chevron">
                        <iconify-icon icon="mdi:folder-open" width="20" height="20"></iconify-icon>
                    </div>
                </div>
            </div>

            <!-- Sedang Dipinjam -->
            <div class="stat-card-interactive" onclick="showBorrowingModal('Sedang Dipinjam', 'dipinjam')">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Sedang Dipinjam</div>
                        <div class="stat-card-value"><?php echo $borrowedBooks; ?></div>
                    </div>
                    <div class="stat-card-chevron">
                        <iconify-icon icon="mdi:folder-open" width="20" height="20"></iconify-icon>
                    </div>
                </div>
            </div>

            <!-- Sudah Dikembalikan -->
            <div class="stat-card-interactive" onclick="showBorrowingModal('Sudah Dikembalikan', 'dikembalikan')">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Sudah Dikembalikan</div>
                        <div class="stat-card-value"><?php echo $returnedBooks; ?></div>
                    </div>
                    <div class="stat-card-chevron">
                        <iconify-icon icon="mdi:folder-open" width="20" height="20"></iconify-icon>
                    </div>
                </div>
            </div>

            <!-- Telat Dikembalikan -->
            <div class="stat-card-interactive" onclick="showBorrowingModal('Telat Dikembalikan', 'telat')">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-label">Telat Dikembalikan</div>
                        <div class="stat-card-value"><?php echo $overdueBooks; ?></div>
                    </div>
                    <div class="stat-card-chevron">
                        <iconify-icon icon="mdi:folder-open" width="20" height="20"></iconify-icon>
                    </div>
                </div>
            </div>
        </div>

        <!-- History Card -->
        <div class="history-card">
            <div class="history-card-header">
                <iconify-icon icon="mdi:list-box" width="24" height="24"></iconify-icon>
                <h2>Detail Riwayat Peminjaman</h2>
            </div>

            <?php if (empty($borrowingHistory)): ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <iconify-icon icon="mdi:inbox-multiple" width="64" height="64"></iconify-icon>
                    </div>
                    <h3>Belum Ada Riwayat Peminjaman</h3>
                    <p>Anda belum pernah meminjam buku. Silakan kunjungi katalog buku untuk memulai.</p>
                    <a href="student-dashboard.php" class="empty-state-btn">
                        <iconify-icon icon="mdi:arrow-left" width="16" height="16"></iconify-icon>
                        Kembali ke Dashboard
                    </a>
                </div>
            <?php else: ?>
                <!-- Tabel Responsif -->
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Cover</th>
                                <th>Judul & Penulis</th>
                                <th>Tanggal Pinjam</th>
                                <th>Tenggat Kembali</th>
                                <th>Tanggal Kembali</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($borrowingHistory as $item): ?>
                                <tr>
                                    <!-- Cover -->
                                    <td data-label="Cover">
                                        <?php if (!empty($item['cover_image'])): ?>
                                            <img 
                                                src="<?php echo htmlspecialchars('../img/covers/' . $item['cover_image']); ?>" 
                                                alt="<?php echo htmlspecialchars($item['book_title']); ?>"
                                                class="book-cover"
                                            >
                                        <?php else: ?>
                                            <div style="width: 50px; height: 70px; background: linear-gradient(135deg, var(--accent) 0%, #062d4a 100%); border-radius: 6px; display: flex; align-items: center; justify-content: center; color: white;">
                                                <iconify-icon icon="mdi:book" width="24" height="24"></iconify-icon>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Info Buku -->
                                    <td data-label="Judul & Penulis">
                                        <div class="book-details">
                                            <h6><?php echo htmlspecialchars($item['book_title'] ?? 'Buku Tidak Ditemukan'); ?></h6>
                                            <small>
                                                <iconify-icon icon="mdi:pen" width="14" height="14"></iconify-icon>
                                                <?php echo htmlspecialchars($item['author'] ?? '-'); ?>
                                            </small>
                                        </div>
                                    </td>

                                    <!-- Tanggal Pinjam -->
                                    <td data-label="Tanggal Pinjam" class="date-cell">
                                        <div class="date-label">Pinjam</div>
                                        <div class="date-value"><?php echo formatDate($item['borrowed_at']); ?></div>
                                    </td>

                                    <!-- Tenggat Kembali -->
                                    <td data-label="Tenggat Kembali" class="date-cell">
                                        <div class="date-label">Tenggat</div>
                                        <div class="date-value"><?php echo formatDate($item['due_at']); ?></div>
                                        <?php if ($item['status'] === 'borrowed' && $item['hari_sisa'] >= 0): ?>
                                            <div class="date-hint success">
                                                <iconify-icon icon="mdi:check-circle" width="12" height="12"></iconify-icon>
                                                <?php echo $item['hari_sisa']; ?> hari tersisa
                                            </div>
                                        <?php elseif ($item['status'] === 'borrowed'): ?>
                                            <div class="date-hint danger">
                                                <iconify-icon icon="mdi:alert-circle" width="12" height="12"></iconify-icon>
                                                <?php echo abs($item['hari_sisa']); ?> hari telat
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Tanggal Kembali -->
                                    <td data-label="Tanggal Kembali" class="date-cell">
                                        <div class="date-label">Dikembalikan</div>
                                        <div class="date-value"><?php echo formatDate($item['returned_at']); ?></div>
                                    </td>

                                    <!-- Status -->
                                    <td data-label="Status">
                                        <?php
                                        $statusClass = '';
                                        $statusText = '';
                                        switch ($item['status']) {
                                            case 'borrowed':
                                                $statusClass = 'badge-borrowed';
                                                $statusText = 'Dipinjam';
                                                break;
                                            case 'returned':
                                                $statusClass = 'badge-returned';
                                                $statusText = 'Dikembalikan';
                                                break;
                                            case 'overdue':
                                                $statusClass = 'badge-overdue';
                                                $statusText = 'Telat';
                                                break;
                                            case 'pending_return':
                                                $statusClass = 'badge-pending';
                                                $statusText = 'Menunggu Konfirmasi';
                                                break;
                                            default:
                                                $statusClass = '';
                                                $statusText = htmlspecialchars($item['status']);
                                        }
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                        <?php if ($item['status'] === 'borrowed' || $item['status'] === 'overdue'): ?>
                                            <div style="margin-top: 8px;">
                                                <button onclick="requestReturn(<?php echo $item['borrow_id']; ?>)" style="padding: 6px 12px; background: #f59e0b; color: white; border: none; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; white-space: nowrap;">
                                                    Ajukan Pengembalian
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer Info -->
        <div style="margin-top: 24px; padding: 16px; background: var(--card); border-radius: 8px; text-align: center; color: var(--text-muted); font-size: 13px; border-left: 4px solid var(--accent);">
            <p style="margin: 0; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <iconify-icon icon="mdi:information" width="16" height="16"></iconify-icon>
                Harap kembalikan buku sebelum tenggat waktu untuk menghindari denda. Hubungi pustakawan jika ada pertanyaan.
            </p>
        </div>
    </div>

    <script src="../assets/js/student-borrowing-history.js"></script>
    <script>
        // Store modal data
        const modalDataStore = {
            'semua': <?php echo json_encode(array_map(function($b){ return ['title' => $b['book_title'] ?? '-', 'author' => $b['author'] ?? '-', 'category' => '-', 'isbn' => '-', 'copies' => 0, 'borrowed_at' => $b['borrowed_at'], 'returned_at' => $b['returned_at'], 'due_at' => $b['due_at'], 'status' => $b['status']]; }, $borrowingHistory)); ?>,
            'dipinjam': <?php echo json_encode(array_values(array_map(function($b){ return ['title' => $b['book_title'] ?? '-', 'author' => $b['author'] ?? '-', 'category' => '-', 'isbn' => '-', 'copies' => 0, 'borrowed_at' => $b['borrowed_at'], 'returned_at' => $b['returned_at'], 'due_at' => $b['due_at'], 'status' => $b['status']]; }, array_filter($borrowingHistory, fn($b) => $b['status'] === 'borrowed')))); ?>,
            'dikembalikan': <?php echo json_encode(array_values(array_map(function($b){ return ['title' => $b['book_title'] ?? '-', 'author' => $b['author'] ?? '-', 'category' => '-', 'isbn' => '-', 'copies' => 0, 'borrowed_at' => $b['borrowed_at'], 'returned_at' => $b['returned_at'], 'due_at' => $b['due_at'], 'status' => $b['status']]; }, array_filter($borrowingHistory, fn($b) => $b['status'] === 'returned')))); ?>,
            'telat': <?php echo json_encode(array_values(array_map(function($b){ return ['title' => $b['book_title'] ?? '-', 'author' => $b['author'] ?? '-', 'category' => '-', 'isbn' => '-', 'copies' => 0, 'borrowed_at' => $b['borrowed_at'], 'returned_at' => $b['returned_at'], 'due_at' => $b['due_at'], 'status' => $b['status']]; }, array_filter($borrowingHistory, fn($b) => $b['status'] === 'overdue')))); ?>
        };

        /**
         * Show borrowing modal with table view
         * Opens overlay modal with filtered borrowing data
         */
        function showBorrowingModal(title, dataKey) {
            const data = modalDataStore[dataKey] || [];
            
            // Create modal overlay
            const modal = document.createElement('div');
            modal.className = 'borrowing-modal-overlay';
            modal.onclick = (e) => {
                if (e.target === modal) closeBorrowingModal(modal);
            };
            
            // Modal content
            const modalContent = document.createElement('div');
            modalContent.className = 'borrowing-modal-content';
            modalContent.innerHTML = `
                <div class="borrowing-modal-header">
                    <h2>${title}</h2>
                    <button onclick="closeBorrowingModal()" class="borrowing-modal-close">
                        <iconify-icon icon="mdi:close" width="20" height="20"></iconify-icon>
                    </button>
                </div>
                <div class="borrowing-modal-body">
                    ${data && data.length > 0 ? renderBorrowingTableHtml(data) : '<div class="empty-state"><p>Data tidak ditemukan</p></div>'}
                </div>
            `;
            
            modal.appendChild(modalContent);
            document.body.appendChild(modal);
            
            // Trigger animation
            setTimeout(() => modal.classList.add('active'), 10);
        }

        /**
         * Close borrowing modal
         */
        function closeBorrowingModal(modalElement) {
            const modal = modalElement || document.querySelector('.borrowing-modal-overlay.active');
            if (modal) {
                modal.classList.remove('active');
                setTimeout(() => modal.remove(), 300);
            }
        }

        /**
         * Render borrowing data as HTML table
         */
        function renderBorrowingTableHtml(data) {
            if (!data || data.length === 0) {
                return '<div class="empty-state"><p>Data tidak ditemukan</p></div>';
            }
            
            let html = '<table class="borrowing-modal-table"><thead><tr>';
            html += '<th>Judul Buku</th>';
            html += '<th>Tanggal Pinjam</th>';
            html += '<th>Tanggal Kembali</th>';
            html += '<th>Status</th>';
            html += '</tr></thead><tbody>';
            
            data.forEach(item => {
                let statusBadge = '';
                switch(item.status) {
                    case 'borrowed':
                        statusBadge = '<span class="badge badge-borrowed">Dipinjam</span>';
                        break;
                    case 'returned':
                        statusBadge = '<span class="badge badge-returned">Dikembalikan</span>';
                        break;
                    case 'overdue':
                        statusBadge = '<span class="badge badge-overdue">Telat</span>';
                        break;
                    default:
                        statusBadge = '<span class="badge">' + item.status + '</span>';
                }
                
                html += `<tr>
                    <td class="title-cell">${item.title || '-'}</td>
                    <td>${formatDateModal(item.borrowed_at)}</td>
                    <td>${item.returned_at ? formatDateModal(item.returned_at) : '-'}</td>
                    <td>${statusBadge}</td>
                </tr>`;
            });
            
            html += '</tbody></table>';
            return html;
        }

        /**
         * Format date untuk modal display
         */
        function formatDateModal(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            const d = String(date.getDate()).padStart(2, '0');
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const y = date.getFullYear();
            return `${d}/${m}/${y}`;
        }

        // Close modal on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeBorrowingModal();
            }
        });
    </script>
</body>
</html>
