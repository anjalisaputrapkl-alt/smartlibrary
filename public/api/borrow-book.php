<?php
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

try {
    // Get student data from session
    $student = $_SESSION['user'];
    $school_id = $student['school_id'] ?? null;
    $nisn = $student['nisn'] ?? null;

    if (!$nisn || !$school_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid session data']);
        exit;
    }

    // Lookup member_id using NISN (connects users and members tables)
    $memberStmt = $pdo->prepare(
        'SELECT id FROM members WHERE nisn = :nisn AND school_id = :school_id LIMIT 1'
    );
    $memberStmt->execute([
        'nisn' => $nisn,
        'school_id' => $school_id
    ]);
    $member = $memberStmt->fetch();

    if (!$member) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Profil anggota tidak ditemukan']);
        exit;
    }

    $member_id = $member['id'];

    // Get book_id from POST
    $book_id = (int) ($_POST['book_id'] ?? 0);

    if ($book_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Book ID tidak valid']);
        exit;
    }

    // Start transaction
    $pdo->beginTransaction();

    // 1. Validate book exists and has stock
    $bookStmt = $pdo->prepare(
        'SELECT id, title, copies FROM books WHERE id = :book_id AND school_id = :school_id'
    );
    $bookStmt->execute([
        'book_id' => $book_id,
        'school_id' => $school_id
    ]);
    $book = $bookStmt->fetch();

    if (!$book) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Buku tidak ditemukan']);
        exit;
    }

    if ($book['copies'] <= 0) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Stok buku habis']);
        exit;
    }

    // 2. Check if student already borrowed this book and hasn't returned it
    $existingStmt = $pdo->prepare(
        'SELECT id FROM borrows 
         WHERE school_id = :school_id 
         AND member_id = :member_id 
         AND book_id = :book_id 
         AND (status = "borrowed" OR status = "overdue")'
    );
    $existingStmt->execute([
        'school_id' => $school_id,
        'member_id' => $member_id,
        'book_id' => $book_id
    ]);

    if ($existingStmt->rowCount() > 0) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Anda sudah meminjam buku ini dan belum mengembalikannya']);
        exit;
    }

    // 3. Insert into borrows table
    // due_at = NOW() + 7 DAYS
    $insertStmt = $pdo->prepare(
        'INSERT INTO borrows (school_id, book_id, member_id, borrowed_at, due_at, status)
         VALUES (:school_id, :book_id, :member_id, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), "borrowed")'
    );
    $insertStmt->execute([
        'school_id' => $school_id,
        'book_id' => $book_id,
        'member_id' => $member_id
    ]);

    $borrow_id = $pdo->lastInsertId();

    // 4. Update book copies
    $updateStmt = $pdo->prepare(
        'UPDATE books SET copies = copies - 1 WHERE id = :book_id'
    );
    $updateStmt->execute(['book_id' => $book_id]);

    // 5. Create notification for borrow event
    $helper = new NotificationsHelper($pdo);
    $due_date = date('d/m/Y', strtotime('+7 days'));
    $notification_message = 'Anda telah meminjam buku "' . htmlspecialchars($book['title']) . '". Harap dikembalikan sebelum tanggal ' . $due_date . '.';

    $helper->createNotification(
        $school_id,
        $student['id'],
        'borrow',
        'Peminjaman Berhasil',
        $notification_message
    );

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Buku berhasil dipinjam!',
        'borrow_id' => $borrow_id
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