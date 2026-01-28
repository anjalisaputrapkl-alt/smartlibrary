<?php
session_start();
header('Content-Type: application/json');

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
    $barcode = $data['barcode'] ?? '';
    $scan_type = $data['type'] ?? 'book'; // 'member' or 'book'

    // Debug logging
    error_log('[BARCODE] Raw data: ' . json_encode($data));
    error_log('[BARCODE] Parsed session_id: ' . $session_id . ', barcode: ' . $barcode . ', type: ' . $scan_type);

    if (!$session_id || !$barcode) {
        http_response_code(400);
        error_log('[BARCODE] Missing required fields: session_id=' . $session_id . ', barcode=' . $barcode);
        echo json_encode(['success' => false, 'message' => 'Session ID and barcode are required']);
        exit;
    }

    // Parse barcode format (e.g., "NISN:0094234" or "ISBN:9786020656069" or plain barcode)
    $barcodeValue = $barcode;
    $detectedType = $scan_type;

    if (strpos($barcode, 'NISN:') === 0) {
        $barcodeValue = substr($barcode, 5); // Remove "NISN:" prefix
        $detectedType = 'member';
    } elseif (strpos($barcode, 'ISBN:') === 0) {
        $barcodeValue = substr($barcode, 5); // Remove "ISBN:" prefix
        $detectedType = 'book';
    }

    // Use detected type if barcode has prefix, otherwise use provided type
    $scan_type = (strpos($barcode, ':') !== false) ? $detectedType : $scan_type;

    // Ensure barcode value is not empty after parsing
    if (empty($barcodeValue)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid barcode format']);
        exit;
    }

    // Validate session exists and is active
    $sessionStmt = $pdo->prepare(
        'SELECT id, school_id, member_id, books_scanned, status FROM barcode_sessions 
         WHERE id = :id AND status = "active"'
    );
    $sessionStmt->execute(['id' => $session_id]);
    $session = $sessionStmt->fetch();

    if (!$session) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Session not found or inactive']);
        exit;
    }

    $school_id = $session['school_id'];

    // Process barcode based on type
    if ($scan_type === 'member') {
        // Scan member barcode (NISN or member ID)
        $memberStmt = $pdo->prepare(
            'SELECT id, name, nisn FROM members 
             WHERE school_id = :school_id AND (nisn = :barcode OR id = :id)'
        );
        $memberStmt->execute([
            'school_id' => $school_id,
            'barcode' => $barcodeValue,
            'id' => is_numeric($barcodeValue) ? (int) $barcodeValue : 0
        ]);
        $member = $memberStmt->fetch();

        if (!$member) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Member not found', 'type' => 'member']);
            exit;
        }

        // Update session with member data
        $updateStmt = $pdo->prepare(
            'UPDATE barcode_sessions 
             SET member_id = :member_id, member_barcode = :barcode, updated_at = NOW()
             WHERE id = :id'
        );
        $updateStmt->execute([
            'member_id' => $member['id'],
            'barcode' => $barcodeValue,
            'id' => $session_id
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Member scanned successfully',
            'type' => 'member',
            'data' => [
                'member_id' => $member['id'],
                'name' => $member['name'],
                'nisn' => $member['nisn']
            ]
        ]);

    } elseif ($scan_type === 'book') {
        // Scan book barcode (ISBN or book ID)
        $bookStmt = $pdo->prepare(
            'SELECT id, title, isbn, copies FROM books 
             WHERE school_id = :school_id AND (isbn = :barcode OR id = :id)'
        );
        $bookStmt->execute([
            'school_id' => $school_id,
            'barcode' => $barcodeValue,
            'id' => is_numeric($barcodeValue) ? (int) $barcodeValue : 0
        ]);
        $book = $bookStmt->fetch();

        if (!$book) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Book not found', 'type' => 'book']);
            exit;
        }

        // Check if member is already scanned
        if (!$session['member_id']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Please scan member first', 'type' => 'book']);
            exit;
        }

        // Check book stock
        if ($book['copies'] <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Book stock is empty', 'type' => 'book']);
            exit;
        }

        // Check if member already borrowed this book
        $existingStmt = $pdo->prepare(
            'SELECT id FROM borrows 
             WHERE school_id = :school_id 
             AND member_id = :member_id 
             AND book_id = :book_id 
             AND status IN ("borrowed", "overdue")'
        );
        $existingStmt->execute([
            'school_id' => $school_id,
            'member_id' => $session['member_id'],
            'book_id' => $book['id']
        ]);

        if ($existingStmt->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Member already borrowed this book', 'type' => 'book']);
            exit;
        }

        // Get current scanned books
        $scannedBooks = json_decode($session['books_scanned'] ?? '[]', true);

        // Check if book already scanned in this session
        $bookExists = false;
        foreach ($scannedBooks as $sb) {
            if ($sb['book_id'] == $book['id']) {
                $bookExists = true;
                break;
            }
        }

        if ($bookExists) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'This book already scanned', 'type' => 'book']);
            exit;
        }

        // Add book to scanned books
        $scannedBooks[] = [
            'book_id' => $book['id'],
            'title' => $book['title'],
            'isbn' => $book['isbn'],
            'scanned_at' => date('Y-m-d H:i:s')
        ];

        // Update session with book data
        $updateStmt = $pdo->prepare(
            'UPDATE barcode_sessions 
             SET books_scanned = :books, updated_at = NOW()
             WHERE id = :id'
        );
        $updateStmt->execute([
            'books' => json_encode($scannedBooks),
            'id' => $session_id
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Book scanned successfully',
            'type' => 'book',
            'data' => [
                'book_id' => $book['id'],
                'title' => $book['title'],
                'isbn' => $book['isbn'],
                'copies_left' => $book['copies'] - 1
            ]
        ]);

    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid scan type']);
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
}
