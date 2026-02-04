<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    require __DIR__ . '/../../src/auth.php';
    requireAuth();
    
    $pdo = require __DIR__ . '/../../src/db.php';
$user = $_SESSION['user'];
$school_id = $user['school_id'];

try {
    $stmt = $pdo->prepare("
        SELECT 
            b.id,
            b.title,
            b.author,
            b.category,
            b.copies,
            (SELECT COUNT(*) FROM borrows WHERE book_id = b.id AND returned_at IS NULL AND school_id = :sid1) as borrowed_count
        FROM books b
        WHERE b.school_id = :sid2
        ORDER BY b.created_at DESC
    ");
    
    $stmt->execute(['sid1' => $school_id, 'sid2' => $school_id]);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $data = [];
    foreach ($books as $book) {
        $available = max(0, $book['copies'] - $book['borrowed_count']);
        $data[] = [
            'id' => $book['id'],
            'title' => htmlspecialchars($book['title']),
            'author' => htmlspecialchars($book['author'] ?? '-'),
            'category' => htmlspecialchars($book['category']),
            'total' => $book['copies'],
            'borrowed' => $book['borrowed_count'],
            'available' => $available,
            'status' => $available > 0 ? 'Tersedia' : 'Habis'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'total' => count($data)
    ]);
} catch (Exception $e) {
    error_log('get-stats-books.php Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
} catch (Exception $e) {
    error_log('get-stats-books.php Fatal Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred'
    ]);
}
?>
