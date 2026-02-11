<?php
/**
 * Submit Borrow - Insert directly to borrows table
 * Receive array of borrow records and save to database
 */

header('Content-Type: application/json');

require __DIR__ . '/../../src/auth.php';
requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$borrows = $input['borrows'] ?? [];

if (empty($borrows)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Tidak ada data peminjaman'
    ]);
    exit;
}

try {
    $pdo = require __DIR__ . '/../../src/db.php';

    // Get school_id from session
    $user = $_SESSION['user'];
    $school_id = $user['school_id'];

    $inserted = 0;
    $errors = [];

    // Start transaction
    $pdo->beginTransaction();

    foreach ($borrows as $borrow) {
        try {
            // Validate required fields
            if (empty($borrow['member_id']) || empty($borrow['book_id'])) {
                $errors[] = "Borrow record missing member_id or book_id";
                continue;
            }

            // A. Check student's current active borrow count against their personal limit
            // Fetch member's max_pinjam (synced with school max_books_{role})
            $memStmt = $pdo->prepare('SELECT m.max_pinjam, m.role, s.max_books_student, s.max_books_teacher, s.max_books_employee 
                                      FROM members m 
                                      JOIN schools s ON s.id = m.school_id 
                                      WHERE m.id = :mid');
            $memStmt->execute(['mid' => $borrow['member_id']]);
            $memberData = $memStmt->fetch();
            $memberRole = $memberData['role'] ?? 'student';
            
            // Map role to school setting column
            $roleDefaultLimit = 3;
            if ($memberRole === 'teacher') $roleDefaultLimit = $memberData['max_books_teacher'] ?? 10;
            elseif ($memberRole === 'employee') $roleDefaultLimit = $memberData['max_books_employee'] ?? 5;
            else $roleDefaultLimit = $memberData['max_books_student'] ?? 3;

            $maxLimit = $memberData['max_pinjam'] ?? $roleDefaultLimit;

            $countStmt = $pdo->prepare('SELECT COUNT(*) as total FROM borrows WHERE member_id = :mid AND status NOT IN ("returned", "rejected")');
            $countStmt->execute(['mid' => $borrow['member_id']]);
            $currentBorrows = (int)$countStmt->fetchColumn();

            if (($currentBorrows + $inserted) >= $maxLimit) {
                $errors[] = "Siswa sudah mencapai batas maksimal peminjaman ($maxLimit buku)";
                continue;
            }

            // Check if book is available and get its custom borrow limit
            $checkStmt = $pdo->prepare('SELECT copies, title, max_borrow_days, access_level FROM books WHERE id = :bid');
            $checkStmt->execute(['bid' => $borrow['book_id']]);
            $bookInfo = $checkStmt->fetch();
            
            if (!$bookInfo || $bookInfo['copies'] < 1) {
                $errors[] = "Buku '" . ($bookInfo['title'] ?? 'Unknown') . "' sedang tidak tersedia (Stok 0)";
                continue;
            }

            // Determine due date
            // 1. Priority: Book-specific limit
            // 2. Fallback: Provided date in request
            // 3. Last Fallback: Default +7 days
                // Get generic default duration
                $schoolStmt = $pdo->prepare('SELECT borrow_duration FROM schools WHERE id = :sid');
                $schoolStmt->execute(['sid' => $school_id]);
                $defaultDuration = $schoolStmt->fetchColumn() ?: 7;
                
                $dueDate = $input['due_date'] ?? date('Y-m-d H:i:s', strtotime('+' . $defaultDuration . ' days'));


            // Enforce Access Level Restriction
            if (isset($bookInfo['access_level']) && $bookInfo['access_level'] === 'teacher_only') {
                if ($memberRole === 'student') {
                    $errors[] = "Buku '" . ($bookInfo['title'] ?? 'Unknown') . "' KHUSUS untuk Guru/Karyawan.";
                    file_put_contents(__DIR__ . '/../../debug_borrow.log', date('Y-m-d H:i:s') . " [BORROW BLOCKED] Blocked student.\n", FILE_APPEND);
                    continue;
                }
            } else {
                 $logMsg = date('Y-m-d H:i:s') . " [BORROW CHECK] Book: '{$bookInfo['title']}' (ID: {$borrow['book_id']}) Access: " . ($bookInfo['access_level'] ?? 'null') . "\n";
                 file_put_contents(__DIR__ . '/../../debug_borrow.log', $logMsg, FILE_APPEND);
            }

            // Insert into borrows table with pending_confirmation status
            $stmt = $pdo->prepare(
                'INSERT INTO borrows (school_id, member_id, book_id, borrowed_at, due_at, status)
                 VALUES (:school_id, :member_id, :book_id, NOW(), :due_at, "pending_confirmation")'
            );
            $stmt->execute([
                'school_id' => $school_id,
                'member_id' => (int) $borrow['member_id'],
                'book_id' => (int) $borrow['book_id'],
                'due_at' => $dueDate
            ]);

            $inserted++;
            error_log("[BORROW] Inserted: member_id=" . $borrow['member_id'] .
                ", book_id=" . $borrow['book_id']);

        } catch (Exception $e) {
            $errors[] = "Error: " . $e->getMessage();
            error_log("[BORROW] Error: " . $e->getMessage());
        }
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'inserted' => $inserted,
        'total' => count($borrows),
        'errors' => $errors,
        'message' => "$inserted dari " . count($borrows) . " peminjaman berhasil dicatat"
    ]);

} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    error_log("[BORROW] Database error: " . $e->getMessage());
}
?>