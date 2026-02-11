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
require_once __DIR__ . '/../../src/MemberHelper.php';

try {
    // Get student data from session
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
        'SELECT id, title, copies, access_level FROM books WHERE id = :book_id AND school_id = :school_id'
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

    // Check Access Level
    if (isset($book['access_level']) && $book['access_level'] === 'teacher_only') {
        $memStmt = $pdo->prepare('SELECT role FROM members WHERE id = :mid');
        $memStmt->execute(['mid' => $member_id]);
        $role = $memStmt->fetchColumn();

        if ($role === 'student') {
            $pdo->rollBack();
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Buku ini KHUSUS untuk Guru/Karyawan']);
            exit;
        }
    }

    if ($book['copies'] <= 0) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Stok buku habis']);
        exit;
    }

    // 1.5 Check Access Level
    if (isset($book['access_level']) && $book['access_level'] === 'teacher_only') {
        $role = $student['role'] ?? 'student';
        if ($role === 'student') {
            $pdo->rollBack();
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Buku ini KHUSUS untuk Guru/Karyawan']);
            exit;
        }
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

    // 2.5 Check total active loans (Dynamic Limit from DB)
    // Fetch max_pinjam from members first
    $memberMetaStmt = $pdo->prepare('SELECT max_pinjam FROM members WHERE id = :member_id');
    $memberMetaStmt->execute(['member_id' => $member_id]);
    $memberMeta = $memberMetaStmt->fetch();
    $max_pinjam = (int) ($memberMeta['max_pinjam'] ?? 2);

    // Get school generic duration
    $schoolSettingsStmt = $pdo->prepare('SELECT borrow_duration FROM schools WHERE id = :school_id');
    $schoolSettingsStmt->execute(['school_id' => $school_id]);
    $durasi_pinjam = (int) ($schoolSettingsStmt->fetchColumn() ?: 7);

    $countStmt = $pdo->prepare(
        'SELECT COUNT(*) as total FROM borrows 
         WHERE school_id = :school_id 
         AND member_id = :member_id 
         AND (status = "borrowed" OR status = "overdue")'
    );
    $countStmt->execute([
        'school_id' => $school_id,
        'member_id' => $member_id
    ]);
    $loanCount = (int) ($countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    if ($loanCount >= $max_pinjam) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => "Anda telah mencapai batas maksimal peminjaman ($max_pinjam buku)."
        ]);
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
    // Log error untuk debugging
    error_log('Borrow Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>