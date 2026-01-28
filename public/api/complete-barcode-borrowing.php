<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Only POST method allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$pdo = require __DIR__ . '/../../src/db.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $session_id = (int) ($data['session_id'] ?? 0);
    $due_date = $data['due_date'] ?? null;

    if (!$session_id || !$due_date) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Session ID and due date are required']);
        exit;
    }

    // Validate due_date format (Y-m-d)
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $due_date)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid due date format']);
        exit;
    }

    // Get session data
    $sessionStmt = $pdo->prepare(
        'SELECT id, school_id, member_id, books_scanned FROM barcode_sessions 
         WHERE id = :id AND status = "active"'
    );
    $sessionStmt->execute(['id' => $session_id]);
    $session = $sessionStmt->fetch();

    if (!$session) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Session not found or inactive']);
        exit;
    }

    if (!$session['member_id']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No member scanned']);
        exit;
    }

    $scannedBooks = json_decode($session['books_scanned'] ?? '[]', true);
    if (empty($scannedBooks)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No books scanned']);
        exit;
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        $createdBorrows = [];

        // Create borrow records for each scanned book
        foreach ($scannedBooks as $book) {
            $book_id = (int) $book['book_id'];

            // Insert borrow record
            $borrowStmt = $pdo->prepare(
                'INSERT INTO borrows (school_id, book_id, member_id, borrowed_at, due_at, status)
                 VALUES (:school_id, :book_id, :member_id, NOW(), :due_date, "borrowed")'
            );
            $borrowStmt->execute([
                'school_id' => $session['school_id'],
                'book_id' => $book_id,
                'member_id' => $session['member_id'],
                'due_date' => $due_date . ' 23:59:59'
            ]);

            $borrow_id = $pdo->lastInsertId();

            // Update book copies (decrement)
            $updateBookStmt = $pdo->prepare(
                'UPDATE books SET copies = copies - 1 WHERE id = :id AND school_id = :school_id'
            );
            $updateBookStmt->execute([
                'id' => $book_id,
                'school_id' => $session['school_id']
            ]);

            $createdBorrows[] = [
                'borrow_id' => $borrow_id,
                'book_id' => $book_id,
                'title' => $book['title'],
                'due_date' => $due_date
            ];
        }

        // Update session status to completed and set due_date
        $completeStmt = $pdo->prepare(
            'UPDATE barcode_sessions 
             SET status = "completed", due_date = :due_date, updated_at = NOW()
             WHERE id = :id'
        );
        $completeStmt->execute([
            'due_date' => $due_date,
            'id' => $session_id
        ]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Borrowing completed successfully',
            'data' => [
                'session_id' => $session_id,
                'borrows_created' => count($createdBorrows),
                'borrows' => $createdBorrows
            ]
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
}
