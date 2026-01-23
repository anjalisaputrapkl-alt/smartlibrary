<?php
session_start();
header('Content-Type: application/json');

$pdo = require __DIR__ . '/../../src/db.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

$user = $_SESSION['user'];
$school_id = $user['school_id'];
$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$book_id) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Book ID required'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare(
        'SELECT id, title, author, category, isbn, copies, shelf, row_number, cover_image
         FROM books
         WHERE id = :id AND school_id = :school_id'
    );

    $stmt->execute([
        'id' => $book_id,
        'school_id' => $school_id
    ]);

    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($book) {
        echo json_encode([
            'success' => true,
            'data' => $book
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Book not found'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>