<?php
/**
 * ADMIN CONFIRM RETURN
 * Admin mengkonfirmasi pengembalian buku dari siswa
 * Update status: pending_return → returned
 * Isi returned_at = NOW()
 * Update stok buku +1
 * Buat notifikasi return_confirm
 */

require __DIR__ . '/../../src/auth.php';
header('Content-Type: application/json');

// Check if user is authenticated
requireAuth();

// Only POST method allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$pdo = require __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/NotificationsHelper.php';

try {
    $user = $_SESSION['user'];
    $school_id = $user['school_id'] ?? null;
    
    if (!$school_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid session data']);
        exit;
    }

    // Get borrow_id from POST
    $borrow_id = (int) ($_POST['borrow_id'] ?? 0);
    
    if ($borrow_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Borrow ID tidak valid']);
        exit;
    }

    // Start transaction
    $pdo->beginTransaction();

    // Check if borrow exists and status is pending_return
    $borrowStmt = $pdo->prepare(
        'SELECT b.id, b.book_id, b.member_id, b.status, b.due_at, bk.title FROM borrows b
         JOIN books bk ON b.book_id = bk.id
         WHERE b.id = :borrow_id 
         AND b.school_id = :school_id
         AND b.status = "pending_return"'
    );
    $borrowStmt->execute([
        'borrow_id' => $borrow_id,
        'school_id' => $school_id
    ]);
    $borrow = $borrowStmt->fetch();

    if (!$borrow) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Permintaan pengembalian tidak ditemukan atau sudah diproses']);
        exit;
    }

    // Calculate Fine
    $schoolStmt = $pdo->prepare('SELECT late_fine FROM schools WHERE id = :sid');
    $schoolStmt->execute(['sid' => $school_id]);
    $late_fine = (int) ($schoolStmt->fetchColumn() ?: 500);

    $fineAmount = 0;
    if ($borrow['due_at']) {
        $dueDate = new DateTime($borrow['due_at']);
        $now = new DateTime();
        if ($now > $dueDate) {
            $diff = $now->diff($dueDate); // Absolute diff
            $daysLate = $diff->days;
            $fineAmount = $daysLate * $late_fine;
        }
    }

    // Update borrow status to returned and save fine
    $updateBorrowStmt = $pdo->prepare(
        'UPDATE borrows SET returned_at = NOW(), status = "returned", fine_amount = :fine 
         WHERE id = :borrow_id'
    );
    $updateBorrowStmt->execute(['borrow_id' => $borrow_id, 'fine' => $fineAmount]);

    // Update book stock (Reset to 1)
    $updateBookStmt = $pdo->prepare(
        'UPDATE books SET copies = 1 
         WHERE id = :book_id'
    );
    $updateBookStmt->execute(['book_id' => $borrow['book_id']]);

    // Create notification for return confirmation
    $helper = new NotificationsHelper($pdo);
    $notification_message = 'Admin telah mengonfirmasi pengembalian buku "' . htmlspecialchars($borrow['title']) . '". Terima kasih!';
    
    $helper->createNotification(
        $school_id,
        $borrow['member_id'],
        'return_confirm',
        'Pengembalian Disetujui',
        $notification_message
    );

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Pengembalian buku telah dikonfirmasi'
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}
?>
