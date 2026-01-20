<?php
/**
 * BorrowingHistoryModel
 * Class untuk menangani semua operasi terkait riwayat peminjaman buku
 * 
 * Fitur:
 * - Ambil riwayat peminjaman dengan filter
 * - Hitung statistik peminjaman
 * - Validasi data
 * - Error handling
 */

class BorrowingHistoryModel
{
    private $pdo;

    /**
     * Constructor
     * @param PDO $pdo Database connection object
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Ambil riwayat peminjaman untuk satu member
     * 
     * @param int $memberId ID member
     * @param array $filters Filter options (status, limit, offset)
     * @return array Array riwayat peminjaman
     * @throws Exception Jika ada error database
     */
    public function getBorrowingHistory($memberId, $filters = [])
    {
        // Validasi input
        if (!is_numeric($memberId) || $memberId <= 0) {
            throw new Exception('Invalid member ID');
        }

        // Inisialisasi filter default
        $status = isset($filters['status']) ? trim($filters['status']) : null;
        $limit = isset($filters['limit']) ? (int)$filters['limit'] : 100;
        $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;

        // Query dasar
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
                DATEDIFF(b.due_at, NOW()) AS days_remaining,
                CASE 
                    WHEN b.status = 'borrowed' AND DATEDIFF(b.due_at, NOW()) < 0 THEN 'overdue'
                    ELSE b.status
                END AS actual_status
            FROM borrows b
            LEFT JOIN books bk ON b.book_id = bk.id
            WHERE b.member_id = ?
        ";

        $params = [$memberId];

        // Tambahkan filter status jika ada
        if (!empty($status)) {
            $validStatus = ['borrowed', 'returned', 'overdue'];
            if (!in_array($status, $validStatus)) {
                throw new Exception('Invalid status filter');
            }
            $query .= " AND b.status = ?";
            $params[] = $status;
        }

        // Urutkan dari terbaru
        $query .= " ORDER BY b.borrowed_at DESC";

        // Tambahkan limit dan offset
        if ($limit > 0) {
            $query .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }

        // Execute query
        $stmt = $this->pdo->prepare($query);
        if (!$stmt->execute($params)) {
            throw new Exception('Failed to fetch borrowing history');
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil statistik peminjaman untuk satu member
     * 
     * @param int $memberId ID member
     * @return array Array dengan statistik (total, borrowed, returned, overdue)
     * @throws Exception Jika ada error database
     */
    public function getBorrowingStats($memberId)
    {
        // Validasi input
        if (!is_numeric($memberId) || $memberId <= 0) {
            throw new Exception('Invalid member ID');
        }

        $query = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'borrowed' THEN 1 ELSE 0 END) as borrowed,
                SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) as returned,
                SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue,
                SUM(CASE WHEN returned_at IS NULL AND due_at < NOW() THEN 1 ELSE 0 END) as actually_overdue
            FROM borrows
            WHERE member_id = ?
        ";

        $stmt = $this->pdo->prepare($query);
        if (!$stmt->execute([$memberId])) {
            throw new Exception('Failed to fetch borrowing statistics');
        }

        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Ensure numeric values
        return [
            'total' => (int)($stats['total'] ?? 0),
            'borrowed' => (int)($stats['borrowed'] ?? 0),
            'returned' => (int)($stats['returned'] ?? 0),
            'overdue' => (int)($stats['overdue'] ?? 0),
            'actually_overdue' => (int)($stats['actually_overdue'] ?? 0)
        ];
    }

    /**
     * Ambil detail satu peminjaman
     * 
     * @param int $borrowId ID peminjaman
     * @param int $memberId ID member (untuk validasi security)
     * @return array Detail peminjaman atau null jika tidak ditemukan
     * @throws Exception Jika ada error database
     */
    public function getBorrowDetail($borrowId, $memberId)
    {
        // Validasi input
        if (!is_numeric($borrowId) || !is_numeric($memberId)) {
            throw new Exception('Invalid parameters');
        }

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
                bk.shelf,
                bk.row_number,
                DATEDIFF(b.due_at, NOW()) AS days_remaining
            FROM borrows b
            LEFT JOIN books bk ON b.book_id = bk.id
            WHERE b.id = ? AND b.member_id = ?
        ";

        $stmt = $this->pdo->prepare($query);
        if (!$stmt->execute([$borrowId, $memberId])) {
            throw new Exception('Failed to fetch borrow detail');
        }

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Hitung total denda untuk member (jika buku telat)
     * 
     * @param int $memberId ID member
     * @param int $finePerDay Denda per hari (dalam rupiah)
     * @return float Total denda
     * @throws Exception Jika ada error database
     */
    public function calculateTotalFine($memberId, $finePerDay = 5000)
    {
        // Validasi input
        if (!is_numeric($memberId) || !is_numeric($finePerDay)) {
            throw new Exception('Invalid parameters');
        }

        $query = "
            SELECT 
                SUM(
                    CASE 
                        WHEN returned_at IS NULL AND due_at < NOW() 
                        THEN DATEDIFF(NOW(), due_at) * ?
                        WHEN returned_at IS NOT NULL AND returned_at > due_at 
                        THEN DATEDIFF(returned_at, due_at) * ?
                        ELSE 0
                    END
                ) as total_fine
            FROM borrows
            WHERE member_id = ?
        ";

        $stmt = $this->pdo->prepare($query);
        if (!$stmt->execute([$finePerDay, $finePerDay, $memberId])) {
            throw new Exception('Failed to calculate fine');
        }

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($result['total_fine'] ?? 0);
    }

    /**
     * Ambil buku yang sedang dipinjam
     * 
     * @param int $memberId ID member
     * @return array Array buku yang sedang dipinjam
     * @throws Exception Jika ada error database
     */
    public function getCurrentBorrows($memberId)
    {
        // Validasi input
        if (!is_numeric($memberId) || $memberId <= 0) {
            throw new Exception('Invalid member ID');
        }

        $query = "
            SELECT 
                b.id AS borrow_id,
                b.book_id,
                b.borrowed_at,
                b.due_at,
                bk.title AS book_title,
                bk.author,
                bk.cover_image,
                DATEDIFF(b.due_at, NOW()) AS days_remaining,
                CASE 
                    WHEN DATEDIFF(b.due_at, NOW()) < 0 THEN 'overdue'
                    WHEN DATEDIFF(b.due_at, NOW()) <= 3 THEN 'warning'
                    ELSE 'normal'
                END AS urgency
            FROM borrows b
            LEFT JOIN books bk ON b.book_id = bk.id
            WHERE b.member_id = ? AND b.status = 'borrowed' AND b.returned_at IS NULL
            ORDER BY b.due_at ASC
        ";

        $stmt = $this->pdo->prepare($query);
        if (!$stmt->execute([$memberId])) {
            throw new Exception('Failed to fetch current borrows');
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Format tanggal untuk tampilan
     * 
     * @param string $date Tanggal dalam format database
     * @param string $format Format output (default: 'd M Y H:i')
     * @return string Tanggal yang sudah diformat atau '-' jika kosong
     */
    public static function formatDate($date, $format = 'd M Y H:i')
    {
        if (empty($date) || $date === '0000-00-00 00:00:00' || $date === '0000-00-00') {
            return '-';
        }
        
        try {
            $dateObj = new DateTime($date);
            return $dateObj->format($format);
        } catch (Exception $e) {
            return '-';
        }
    }

    /**
     * Get status text dalam bahasa Indonesia
     * 
     * @param string $status Status dalam database
     * @return string Status text
     */
    public static function getStatusText($status)
    {
        $statusMap = [
            'borrowed' => 'Dipinjam',
            'returned' => 'Dikembalikan',
            'overdue' => 'Telat',
            'warning' => 'Perhatian'
        ];

        return $statusMap[$status] ?? ucfirst($status);
    }

    /**
     * Get status badge CSS class
     * 
     * @param string $status Status dalam database
     * @return string CSS class
     */
    public static function getStatusBadgeClass($status)
    {
        $classMap = [
            'borrowed' => 'badge badge-primary',
            'returned' => 'badge badge-success',
            'overdue' => 'badge badge-danger',
            'warning' => 'badge badge-warning'
        ];

        return $classMap[$status] ?? 'badge badge-secondary';
    }
}
