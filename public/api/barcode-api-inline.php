<?php
// Test API dengan minimal BarcodeModel inline
header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_SESSION)) {
        session_start();
    }

    require_once __DIR__ . '/../../src/db.php';

    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    $school_id = 4;
    $query = trim($_GET['q'] ?? '');

    if ($action === 'search') {
        if (strlen($query) < 2) {
            echo json_encode(['success' => false, 'error' => 'Minimal 2 karakter']);
            exit;
        }

        // Direct query without class
        $searchTerm = '%' . $query . '%';
        $sql = "SELECT id, title, author, isbn, copies as stok, category, cover_image
                FROM books
                WHERE (title LIKE ? OR isbn LIKE ? OR author LIKE ?)
                AND school_id = ?
                ORDER BY title ASC LIMIT 20";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $school_id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Map columns
        $books = array_map(function($book) {
            return [
                'id' => $book['id'],
                'judul' => $book['title'],
                'kode_buku' => $book['isbn'],
                'penulis' => $book['author'],
                'stok' => $book['stok'],
                'penerbit' => $book['category'],
                'cover' => $book['cover_image']
            ];
        }, $results);

        echo json_encode([
            'success' => true,
            'count' => count($books),
            'books' => $books
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Invalid action']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
