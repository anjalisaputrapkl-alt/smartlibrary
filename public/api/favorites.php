<?php
session_start();
$pdo = require __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/FavoriteModel.php';
require_once __DIR__ . '/../../src/NotificationsHelper.php';

// Check authentication
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$studentId = $_SESSION['user']['id'];
$schoolId = $_SESSION['user']['school_id'];
$action = $_GET['action'] ?? null;

try {
    $model = new FavoriteModel($pdo);
    $helper = new NotificationsHelper($pdo);

    switch ($action) {
        case 'categories':
            // Ambil daftar kategori
            $categories = $model->getCategories();
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $categories
            ]);
            break;

        case 'books_by_category':
            // Ambil buku berdasarkan kategori
            $category = $_GET['category'] ?? null;
            $books = $model->getBooksByCategory($category);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $books,
                'total' => count($books)
            ]);
            break;

        case 'add':
            // Tambah ke favorit
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                exit;
            }

            $bookId = $_POST['book_id'] ?? $_POST['id_buku'] ?? null;
            $category = $_POST['category'] ?? $_POST['kategori'] ?? null;

            if (!$bookId || !is_numeric($bookId)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID buku tidak valid']);
                exit;
            }

            // Cek duplikasi
            $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM favorites WHERE student_id = ? AND book_id = ?');
            $stmt->execute([$studentId, (int) $bookId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['total'] > 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Buku sudah ada di favorit Anda']);
                exit;
            }

            // Get book title for notification
            $bookStmt = $pdo->prepare('SELECT title FROM books WHERE id = ?');
            $bookStmt->execute([(int) $bookId]);
            $book = $bookStmt->fetch(PDO::FETCH_ASSOC);
            $bookTitle = $book['title'] ?? 'Buku';

            // Insert ke tabel favorites
            $insertStmt = $pdo->prepare(
                'INSERT INTO favorites (student_id, book_id, category, created_at) 
                 VALUES (?, ?, ?, NOW())'
            );
            $insertStmt->execute([$studentId, (int) $bookId, $category]);

            // Create notification
            $helper->createNotification(
                $schoolId,
                $studentId,
                'info',
                'Buku Ditambahkan ke Favorit',
                'Anda telah menambahkan "' . htmlspecialchars($bookTitle) . '" ke koleksi favorit Anda.'
            );

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Buku berhasil ditambahkan ke favorit'
            ]);
            break;

        case 'get_favorites':
            // Ambil list favorit untuk dropdown/javascript
            $stmt = $pdo->prepare(
                'SELECT f.id as id_favorit, f.book_id as id_buku, f.category, f.created_at, 
                        b.title as judul, b.author, b.category as kategori, b.cover_image as cover
                 FROM favorites f
                 JOIN books b ON f.book_id = b.id
                 WHERE f.student_id = ?
                 ORDER BY f.created_at DESC'
            );
            $stmt->execute([$studentId]);
            $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $favorites,
                'total' => count($favorites)
            ]);
            break;

        case 'list':
            // Ambil list favorit
            $category = $_GET['category'] ?? null;
            $stmt = $pdo->prepare(
                'SELECT f.id, f.student_id, f.book_id, f.category, f.created_at, 
                        b.title, b.author, b.category as book_category, b.cover_image
                 FROM favorites f
                 JOIN books b ON f.book_id = b.id
                 WHERE f.student_id = ?
                 ' . ($category ? ' AND f.category = ?' : '') . '
                 ORDER BY f.created_at DESC'
            );

            if ($category) {
                $stmt->execute([$studentId, $category]);
            } else {
                $stmt->execute([$studentId]);
            }

            $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $favorites,
                'total' => count($favorites)
            ]);
            break;

        case 'remove':
            // Hapus dari favorit
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                exit;
            }

            // Support both favorite_id dan book_id
            $favoriteId = $_POST['id'] ?? $_POST['favorite_id'] ?? $_POST['id_favorit'] ?? null;
            $bookId = $_POST['book_id'] ?? $_POST['id_buku'] ?? null;

            // If book_id provided, find the favorite record
            if (!$favoriteId && $bookId && is_numeric($bookId)) {
                $findStmt = $pdo->prepare('SELECT id FROM favorites WHERE student_id = ? AND book_id = ?');
                $findStmt->execute([$studentId, (int) $bookId]);
                $fav = $findStmt->fetch(PDO::FETCH_ASSOC);
                if ($fav) {
                    $favoriteId = $fav['id'];
                }
            }

            if (!$favoriteId || !is_numeric($favoriteId)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID favorit tidak valid']);
                exit;
            }

            // Verify ownership
            $checkStmt = $pdo->prepare('SELECT student_id FROM favorites WHERE id = ?');
            $checkStmt->execute([(int) $favoriteId]);
            $favorite = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$favorite || $favorite['student_id'] != $studentId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit;
            }

            $deleteStmt = $pdo->prepare('DELETE FROM favorites WHERE id = ? AND student_id = ?');
            $deleteStmt->execute([(int) $favoriteId, $studentId]);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Buku berhasil dihapus dari favorit'
            ]);
            break;

        case 'count':
            // Hitung total favorit
            $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM favorites WHERE student_id = ?');
            $stmt->execute([$studentId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'count' => (int) $result['total']
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => htmlspecialchars($e->getMessage())
    ]);
}
?>