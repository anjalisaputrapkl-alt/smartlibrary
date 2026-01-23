<?php
/**
 * STUDENT REQUEST RETURN
 * Siswa mengajukan pengembalian buku
 * Update status: borrowed/overdue → pending_return
 * Stok buku tidak berubah
 * Buat notifikasi return_request
 */

session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// Only POST method allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get database connection
$pdo = require __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/NotificationsHelper.php';
require_once __DIR__ . '/../../src/MemberHelper.php';

try {
    $student = $_SESSION['user'];
    $school_id = $student['school_id'] ?? null;

    if (!$school_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid session data']);
        exit;
    }

    // Get member_id dengan auto-create jika belum ada
    $memberHelper = new MemberHelper($pdo);
    $member_id = $memberHelper->getMemberId($student);

    // Get borrow_id from POST
    $borrow_id = (int) ($_POST['borrow_id'] ?? 0);

    if ($borrow_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Borrow ID tidak valid']);
        exit;
    }

    // Check if borrow exists and belongs to this student
    $borrowStmt = $pdo->prepare(
        'SELECT b.id, b.status, b.book_id, bk.title FROM borrows b
         JOIN books bk ON b.book_id = bk.id
         WHERE b.id = :borrow_id 
         AND b.school_id = :school_id 
         AND b.member_id = :member_id'
    );
    $borrowStmt->execute([
        'borrow_id' => $borrow_id,
        'school_id' => $school_id,
        'member_id' => $member_id
    ]);
    $borrow = $borrowStmt->fetch();

    if (!$borrow) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Peminjaman tidak ditemukan']);
        exit;
    }

    // Only allow request return if status is borrowed or overdue
    if ($borrow['status'] !== 'borrowed' && $borrow['status'] !== 'overdue') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Peminjaman tidak bisa dikembalikan (status: ' . $borrow['status'] . ')']);
        exit;
    }

    // Update status to pending_return
    $updateStmt = $pdo->prepare(
        'UPDATE borrows SET status = "pending_return" 
         WHERE id = :borrow_id'
    );
    $updateStmt->execute(['borrow_id' => $borrow_id]);

    // Create notification for return request
    $helper = new NotificationsHelper($pdo);
    $notification_message = 'Permintaan pengembalian untuk buku "' . htmlspecialchars($borrow['title']) . '" menunggu konfirmasi admin.';

    $helper->createNotification(
        $school_id,
        $student['id'],
        'return_request',
        'Permintaan Pengembalian Dikirim',
        $notification_message
    );

    echo json_encode([
        'success' => true,
        'message' => 'Permintaan pengembalian telah dikirim ke admin'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}
?>