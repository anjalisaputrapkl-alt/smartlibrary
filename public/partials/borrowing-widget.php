<?php
/**
 * Widget - Recent Borrowing Activity
 * Untuk ditampilkan di student-dashboard.php
 * 
 * Copy & paste kode di bawah ke student-dashboard.php
 * Letakkan di bagian yang tepat dalam layout
 */

// ============================================
// MINIMAL WIDGET (Copy paste langsung)
// ============================================
?>

<!-- Widget: Riwayat Peminjaman Terbaru -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-gradient">
        <h5 class="mb-0">
            <i class="fas fa-book-open me-2"></i> 
            Riwayat Peminjaman Terbaru
        </h5>
    </div>
    <div class="card-body p-0">
        <?php
        // Load dependencies
        require_once __DIR__ . '/../src/db.php';
        require_once __DIR__ . '/../src/BorrowingHistoryModel.php';

        if (isset($_SESSION['user'])) {
            try {
                $model = new BorrowingHistoryModel($pdo);
                $memberId = $_SESSION['user']['id'];

                // Ambil data terbaru (limit 5)
                $history = $model->getBorrowingHistory($memberId, ['limit' => 5]);

                if (empty($history)) {
                    echo '<div class="p-4 text-center text-muted">';
                    echo '<i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem;"></i>';
                    echo '<p>Belum ada riwayat peminjaman</p>';
                    echo '<a href="books.php" class="btn btn-sm btn-primary">Pinjam Buku</a>';
                    echo '</div>';
                } else {
                    echo '<div class="table-responsive">';
                    echo '<table class="table table-hover mb-0">';
                    echo '<thead class="bg-light">';
                    echo '<tr>';
                    echo '<th style="width: 40%;">Buku</th>';
                    echo '<th style="width: 25%;">Tanggal Pinjam</th>';
                    echo '<th style="width: 20%;">Status</th>';
                    echo '<th style="width: 15%;" class="text-center">Aksi</th>';
                    echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';

                    foreach ($history as $borrow) {
                        $statusClass = '';
                        $statusText = '';

                        switch ($borrow['status']) {
                            case 'borrowed':
                                $statusClass = 'badge bg-warning';
                                $statusText = 'Dipinjam';
                                break;
                            case 'returned':
                                $statusClass = 'badge bg-success';
                                $statusText = 'Dikembalikan';
                                break;
                            case 'overdue':
                                $statusClass = 'badge bg-danger';
                                $statusText = 'Telat';
                                break;
                        }

                        echo '<tr>';
                        echo '<td>';
                        echo '<strong>' . htmlspecialchars($borrow['book_title'] ?? 'N/A') . '</strong><br>';
                        echo '<small class="text-muted">' . htmlspecialchars($borrow['author'] ?? '-') . '</small>';
                        echo '</td>';
                        echo '<td>' . BorrowingHistoryModel::formatDate($borrow['borrowed_at'], 'd M Y') . '</td>';
                        echo '<td><span class="' . $statusClass . '">' . $statusText . '</span></td>';
                        echo '<td class="text-center">';
                        echo '<a href="student-borrowing-history.php" class="btn btn-sm btn-link" title="Lihat Detail">';
                        echo '<i class="fas fa-eye"></i>';
                        echo '</a>';
                        echo '</td>';
                        echo '</tr>';
                    }

                    echo '</tbody>';
                    echo '</table>';
                    echo '</div>';
                    echo '<div class="p-3 border-top text-center">';
                    echo '<a href="student-borrowing-history.php" class="text-decoration-none">';
                    echo 'Lihat Riwayat Lengkap <i class="fas fa-arrow-right ms-2"></i>';
                    echo '</a>';
                    echo '</div>';
                }
            } catch (Exception $e) {
                echo '<div class="p-4 text-center text-danger">';
                echo '<i class="fas fa-exclamation-circle" style="font-size: 2rem; margin-bottom: 1rem;"></i>';
                echo '<p>Gagal memuat riwayat peminjaman</p>';
                echo '</div>';
            }
        }
        ?>
    </div>
</div>

<?php
// ============================================
// CSS STYLING (tambah ke <style> tag di head)
// ============================================
?>

<style>
    /* Styling untuk widget */
    .card {
        border: none;
        border-radius: 8px;
    }

    .card-header {
        border-bottom: 2px solid #e9ecef;
        padding: 1.25rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .card-header h5 {
        color: white;
        font-weight: 600;
    }

    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }

    .table th {
        font-size: 0.85rem;
        font-weight: 600;
        color: #495057;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table td {
        vertical-align: middle;
        padding: 1rem;
    }

    .badge {
        padding: 0.5rem 0.75rem;
        font-weight: 500;
    }

    .btn-link {
        color: #667eea;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .btn-link:hover {
        color: #764ba2;
        transform: scale(1.2);
    }
</style>

<?php
// ============================================
// ADVANCED WIDGET (dengan statistik)
// ============================================
?>

<!-- ALTERNATIVE: Widget dengan Statistik -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card text-center shadow-sm" style="border-left: 5px solid #667eea;">
            <div class="card-body">
                <h3 class="text-primary mb-2">
                    <?php 
                    echo isset($stats['total']) ? $stats['total'] : 0;
                    ?>
                </h3>
                <p class="text-muted mb-0">Total Peminjaman</p>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card text-center shadow-sm" style="border-left: 5px solid #ffc107;">
            <div class="card-body">
                <h3 class="text-warning mb-2">
                    <?php 
                    echo isset($stats['borrowed']) ? $stats['borrowed'] : 0;
                    ?>
                </h3>
                <p class="text-muted mb-0">Sedang Dipinjam</p>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card text-center shadow-sm" style="border-left: 5px solid #28a745;">
            <div class="card-body">
                <h3 class="text-success mb-2">
                    <?php 
                    echo isset($stats['returned']) ? $stats['returned'] : 0;
                    ?>
                </h3>
                <p class="text-muted mb-0">Dikembalikan</p>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card text-center shadow-sm" style="border-left: 5px solid #dc3545;">
            <div class="card-body">
                <h3 class="text-danger mb-2">
                    <?php 
                    echo isset($stats['actually_overdue']) ? $stats['actually_overdue'] : 0;
                    ?>
                </h3>
                <p class="text-muted mb-0">Telat Dikembalikan</p>
            </div>
        </div>
    </div>
</div>

<?php
// ============================================
// NOTIFICATION ALERT (untuk warning)
// ============================================
?>

<!-- Alert untuk status penting -->
<?php
if (isset($_SESSION['user'])) {
    try {
        $model = new BorrowingHistoryModel($pdo);
        $memberId = $_SESSION['user']['id'];
        $stats = $model->getBorrowingStats($memberId);

        // Jika ada buku telat
        if ($stats['actually_overdue'] > 0) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
            echo '<i class="fas fa-exclamation-circle"></i> <strong>Perhatian!</strong> ';
            echo 'Anda memiliki ' . $stats['actually_overdue'] . ' buku yang telat dikembalikan.';
            echo '<a href="student-borrowing-history.php" class="alert-link ms-2">Lihat Detail</a>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            echo '</div>';
        }
        // Jika ada buku yang akan jatuh tempo
        else if ($stats['borrowed'] > 0) {
            $upcoming = $pdo->prepare("
                SELECT 
                    COUNT(*) as count,
                    MIN(DATEDIFF(due_at, NOW())) as days_until_due
                FROM borrows 
                WHERE member_id = ? AND status = 'borrowed' AND returned_at IS NULL
            ");
            $upcoming->execute([$memberId]);
            $result = $upcoming->fetch(PDO::FETCH_ASSOC);

            if ($result['days_until_due'] !== null && $result['days_until_due'] <= 3 && $result['days_until_due'] > 0) {
                echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">';
                echo '<i class="fas fa-bell"></i> ';
                echo 'Buku Anda akan jatuh tempo dalam ' . $result['days_until_due'] . ' hari.';
                echo '<a href="student-borrowing-history.php" class="alert-link ms-2">Cek Sekarang</a>';
                echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                echo '</div>';
            }
        }
    } catch (Exception $e) {
        // Silent fail untuk alert
    }
}
?>

<?php
// ============================================
// USAGE INSTRUCTIONS
// ============================================

/*
CARA MENGGUNAKAN:

1. Buka file: public/student-dashboard.php

2. Copy salah satu widget di atas:
   - Minimal widget (simple)
   - Advanced widget (dengan statistik)
   - Notification alert

3. Paste di lokasi yang sesuai dalam layout

4. Pastikan sudah require BorrowingHistoryModel.php dan db.php

5. Test dengan:
   - Login sebagai siswa
   - Buka dashboard
   - Lihat widget yang sudah ditambah

CONTOH INTEGRASI LENGKAP:

```php
<?php
// Di atas kode HTML dashboard
require_once '../src/db.php';
require_once '../src/BorrowingHistoryModel.php';

// ... rest of dashboard code

// Include widget di tengah halaman
include 'widgets/borrowing-widget.php';
?>
```

CUSTOMIZATION:

- Ubah warna border: border-left: 5px solid #667eea;
- Ubah jumlah data ditampilkan: ['limit' => 5]
- Ubah style card: .card-header background
- Ubah badge size: .badge padding

*/
?>
