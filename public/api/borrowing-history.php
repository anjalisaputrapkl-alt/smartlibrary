<?php
/**
 * API Endpoint - Riwayat Peminjaman Buku
 * Untuk mengambil data riwayat peminjaman dalam format JSON
 * 
 * Endpoint:
 * GET /api/borrowing-history.php - Ambil semua riwayat
 * GET /api/borrowing-history.php?status=borrowed - Filter berdasarkan status
 * GET /api/borrowing-history.php?format=csv - Export ke CSV
 */

require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';

header('Content-Type: application/json');

// Cek autentikasi
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized: Silakan login terlebih dahulu'
    ]);
    exit;
}

$memberId = $_SESSION['user']['id'] ?? null;

// Validasi member ID
if (!$memberId || !is_numeric($memberId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid member ID'
    ]);
    exit;
}

try {
    // Ambil parameter filter
    $status = isset($_GET['status']) ? trim($_GET['status']) : null;
    $format = isset($_GET['format']) ? trim($_GET['format']) : 'json';

    // Base query
    $query = "
        SELECT 
            b.id AS borrow_id,
            b.member_id,
            b.book_id,
            b.borrowed_at,
            b.due_at,
            b.returned_at,
            b.status,
            bk.title AS book_title,
            bk.author,
            bk.cover_image,
            bk.isbn,
            bk.category,
            DATEDIFF(b.due_at, NOW()) AS days_remaining
        FROM borrows b
        LEFT JOIN books bk ON b.book_id = bk.id
        WHERE b.member_id = ?
    ";

    $params = [$memberId];

    // Filter berdasarkan status jika ada
    if (!empty($status)) {
        // Validasi status - hanya allow status yang valid
        $validStatus = ['borrowed', 'returned', 'overdue'];
        if (!in_array($status, $validStatus)) {
            throw new Exception('Invalid status parameter');
        }
        $query .= " AND b.status = ?";
        $params[] = $status;
    }

    // Order by
    $query .= " ORDER BY b.borrowed_at DESC";

    // Execute query
    $stmt = $pdo->prepare($query);
    if (!$stmt->execute($params)) {
        throw new Exception('Database query failed');
    }

    $borrowingHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format response
    if ($format === 'csv') {
        // Export CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="riwayat_peminjaman.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM untuk UTF-8

        // Header CSV
        fputcsv($output, [
            'ID Peminjaman',
            'Judul Buku',
            'Penulis',
            'ISBN',
            'Kategori',
            'Tanggal Pinjam',
            'Tenggat Kembali',
            'Tanggal Kembali',
            'Status',
            'Hari Sisa'
        ]);

        // Data CSV
        foreach ($borrowingHistory as $row) {
            fputcsv($output, [
                $row['borrow_id'],
                $row['book_title'] ?? '-',
                $row['author'] ?? '-',
                $row['isbn'] ?? '-',
                $row['category'] ?? '-',
                $row['borrowed_at'],
                $row['due_at'],
                $row['returned_at'] ?? '-',
                $row['status'],
                $row['days_remaining'] ?? '-'
            ]);
        }

        fclose($output);
        exit;
    }

    // Default JSON response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $borrowingHistory,
        'total' => count($borrowingHistory),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database Error',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
