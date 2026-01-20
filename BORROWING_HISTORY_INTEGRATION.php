<?php
/**
 * Snippet Integrasi - Riwayat Peminjaman Buku
 * 
 * Gunakan kode-kode di bawah ini untuk integrasi dengan halaman lain
 */

// ============================================
// 1. WIDGET UNTUK DASHBOARD SISWA
// ============================================

/**
 * Tampilkan widget riwayat peminjaman di dashboard
 * Letakkan di file: public/student-dashboard.php
 */
?>

<!-- Widget: Buku Sedang Dipinjam -->
<div class="col-md-6 mb-4">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-book-reader"></i> Buku Sedang Dipinjam</h5>
        </div>
        <div class="card-body">
            <?php
            // Load model
            require_once __DIR__ . '/../src/BorrowingHistoryModel.php';
            require_once __DIR__ . '/../src/db.php';
            
            $model = new BorrowingHistoryModel($pdo);
            $memberId = $_SESSION['user']['id'];
            
            try {
                $currentBorrows = $model->getCurrentBorrows($memberId);
                
                if (empty($currentBorrows)) {
                    echo '<p class="text-muted">Belum ada buku yang sedang dipinjam</p>';
                } else {
                    echo '<ul class="list-unstyled">';
                    foreach ($currentBorrows as $borrow) {
                        $urgencyClass = $borrow['urgency'] === 'overdue' ? 'text-danger' : 
                                      ($borrow['urgency'] === 'warning' ? 'text-warning' : 'text-success');
                        
                        echo '<li class="mb-3 pb-3 border-bottom">';
                        echo '<strong>' . htmlspecialchars($borrow['book_title']) . '</strong><br>';
                        echo '<small class="text-muted">' . htmlspecialchars($borrow['author']) . '</small><br>';
                        echo '<small class="' . $urgencyClass . '">';
                        
                        if ($borrow['urgency'] === 'overdue') {
                            echo '<i class="fas fa-exclamation-circle"></i> ' . abs($borrow['days_remaining']) . ' hari telat';
                        } elseif ($borrow['urgency'] === 'warning') {
                            echo '<i class="fas fa-exclamation-triangle"></i> ' . $borrow['days_remaining'] . ' hari tersisa';
                        } else {
                            echo '<i class="fas fa-check-circle"></i> ' . $borrow['days_remaining'] . ' hari tersisa';
                        }
                        
                        echo '</small>';
                        echo '</li>';
                    }
                    echo '</ul>';
                    
                    // Tombol lihat semua
                    echo '<a href="student-borrowing-history.php" class="btn btn-sm btn-primary">Lihat Riwayat Lengkap</a>';
                }
            } catch (Exception $e) {
                echo '<p class="text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
            ?>
        </div>
    </div>
</div>

<!-- Widget: Statistik Peminjaman -->
<div class="col-md-6 mb-4">
    <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Statistik Peminjaman</h5>
        </div>
        <div class="card-body">
            <?php
            try {
                $stats = $model->getBorrowingStats($memberId);
                
                echo '<div class="row text-center">';
                echo '<div class="col-6 col-md-3 mb-3">';
                echo '<div class="stat-box">';
                echo '<h3 class="text-primary">' . $stats['total'] . '</h3>';
                echo '<small>Total Peminjaman</small>';
                echo '</div>';
                echo '</div>';
                
                echo '<div class="col-6 col-md-3 mb-3">';
                echo '<div class="stat-box">';
                echo '<h3 class="text-warning">' . $stats['borrowed'] . '</h3>';
                echo '<small>Sedang Dipinjam</small>';
                echo '</div>';
                echo '</div>';
                
                echo '<div class="col-6 col-md-3 mb-3">';
                echo '<div class="stat-box">';
                echo '<h3 class="text-success">' . $stats['returned'] . '</h3>';
                echo '<small>Dikembalikan</small>';
                echo '</div>';
                echo '</div>';
                
                echo '<div class="col-6 col-md-3 mb-3">';
                echo '<div class="stat-box">';
                echo '<h3 class="text-danger">' . $stats['actually_overdue'] . '</h3>';
                echo '<small>Telat</small>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                
                if ($stats['actually_overdue'] > 0) {
                    echo '<div class="alert alert-danger mt-3 mb-0">';
                    echo '<i class="fas fa-exclamation-circle"></i> ';
                    echo 'Anda memiliki ' . $stats['actually_overdue'] . ' buku yang telat dikembalikan.';
                    echo '</div>';
                }
            } catch (Exception $e) {
                echo '<p class="text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
            ?>
        </div>
    </div>
</div>

<?php
// ============================================
// 2. SIDEBAR NOTIFICATION
// ============================================
?>

<!-- Letakkan di sidebar/header.php -->
<div class="sidebar-notification">
    <?php
    try {
        $stats = $model->getBorrowingStats($memberId);
        
        if ($stats['actually_overdue'] > 0) {
            echo '<div class="alert alert-danger alert-sm">';
            echo '<i class="fas fa-bell"></i> ';
            echo $stats['actually_overdue'] . ' buku telat dikembalikan!';
            echo '</div>';
        } elseif ($stats['borrowed'] > 0) {
            $upcoming = $pdo->prepare("
                SELECT MIN(DATEDIFF(due_at, NOW())) as days_until_due
                FROM borrows 
                WHERE member_id = ? AND status = 'borrowed'
            ");
            $upcoming->execute([$memberId]);
            $result = $upcoming->fetch(PDO::FETCH_ASSOC);
            
            if ($result['days_until_due'] <= 3 && $result['days_until_due'] > 0) {
                echo '<div class="alert alert-warning alert-sm">';
                echo '<i class="fas fa-exclamation-triangle"></i> ';
                echo 'Buku Anda akan jatuh tempo dalam ' . $result['days_until_due'] . ' hari';
                echo '</div>';
            }
        }
    } catch (Exception $e) {
        // Silent fail untuk notification
    }
    ?>
</div>

<?php
// ============================================
// 3. AJAX UNTUK LOAD DATA DINAMIS
// ============================================
?>

<!-- Script untuk load current borrows via AJAX -->
<script>
// Refresh data setiap 5 menit
setInterval(function() {
    fetch('/perpustakaan-online/public/api/borrowing-history.php?status=borrowed')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const borrows = data.data;
                
                // Update notification
                let overdueCount = 0;
                let warningCount = 0;
                
                borrows.forEach(item => {
                    if (item.days_remaining < 0) overdueCount++;
                    else if (item.days_remaining <= 3) warningCount++;
                });
                
                // Update badge di navbar
                const badge = document.querySelector('[data-borrow-count]');
                if (badge) {
                    let count = overdueCount + warningCount;
                    badge.textContent = count;
                    badge.style.display = count > 0 ? 'block' : 'none';
                }
            }
        })
        .catch(error => console.log('Update check failed'));
}, 5 * 60 * 1000); // 5 menit
</script>

<?php
// ============================================
// 4. EXPORT REPORT
// ============================================
?>

<!-- Button untuk export riwayat -->
<a href="/perpustakaan-online/public/api/borrowing-history.php?format=csv" 
   class="btn btn-sm btn-outline-secondary">
    <i class="fas fa-download"></i> Export CSV
</a>

<?php
// ============================================
// 5. HELPER FUNCTIONS UNTUK VIEW
// ============================================

/**
 * Render status badge HTML
 */
function renderStatusBadge($status) {
    $badges = [
        'borrowed' => '<span class="badge badge-primary">Dipinjam</span>',
        'returned' => '<span class="badge badge-success">Dikembalikan</span>',
        'overdue' => '<span class="badge badge-danger">Telat</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge badge-secondary">' . htmlspecialchars($status) . '</span>';
}

/**
 * Render days remaining dengan warna
 */
function renderDaysRemaining($days) {
    if ($days < 0) {
        return '<span class="text-danger"><i class="fas fa-exclamation"></i> ' . abs($days) . ' hari telat</span>';
    } elseif ($days <= 3) {
        return '<span class="text-warning"><i class="fas fa-exclamation-triangle"></i> ' . $days . ' hari</span>';
    } else {
        return '<span class="text-success"><i class="fas fa-check"></i> ' . $days . ' hari</span>';
    }
}

/**
 * Get urgency color class
 */
function getUrgencyClass($daysRemaining) {
    if ($daysRemaining < 0) return 'danger';
    if ($daysRemaining <= 3) return 'warning';
    return 'success';
}

// ============================================
// 6. CUSTOM CSS UNTUK WIDGET
// ============================================
?>

<style>
.stat-box {
    padding: 1rem;
    border-radius: 8px;
    background: #f8f9fa;
    transition: all 0.3s ease;
}

.stat-box:hover {
    background: #e9ecef;
    transform: translateY(-2px);
}

.stat-box h3 {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
}

.stat-box small {
    display: block;
    color: #6c757d;
    font-size: 0.85rem;
    margin-top: 0.5rem;
}

.sidebar-notification {
    padding: 0.5rem;
    margin-bottom: 1rem;
}

.alert-sm {
    padding: 0.5rem;
    margin-bottom: 0;
    font-size: 0.85rem;
}
</style>

<?php
// ============================================
// 7. REMINDER EMAIL/NOTIFICATION
// ============================================

/**
 * Function untuk send reminder ke siswa
 * (jika Anda ingin menggunakan PHPMailer atau similar)
 */
function sendBorrowingReminder($memberId, $model) {
    try {
        $current = $model->getCurrentBorrows($memberId);
        
        // Check jika ada buku yang akan jatuh tempo dalam 2 hari
        foreach ($current as $borrow) {
            if ($borrow['days_remaining'] <= 2 && $borrow['days_remaining'] > 0) {
                // Kirim email reminder
                // sendEmail($memberEmail, "Reminder: Buku Anda akan jatuh tempo", ...);
            } elseif ($borrow['days_remaining'] < 0) {
                // Kirim email warning - buku telat
                // sendEmail($memberEmail, "PENTING: Buku Anda telat dikembalikan", ...);
            }
        }
    } catch (Exception $e) {
        // Log error
    }
}

?>
