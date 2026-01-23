<?php
/**
 * Favorite Model
 * Menangani operasi buku favorit siswa
 */

class FavoriteModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Ambil daftar kategori unik dari tabel books
     * 
     * @return array - Daftar kategori
     */
    public function getCategories()
    {
        try {
            $query = "
                SELECT DISTINCT category
                FROM books
                WHERE category IS NOT NULL AND category != ''
                ORDER BY category ASC
            ";

            $stmt = $this->pdo->prepare($query);
            if (!$stmt->execute()) {
                throw new Exception('Gagal mengambil kategori');
            }

            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Ambil daftar buku berdasarkan kategori
     * 
     * @param string $category - Kategori buku (optional)
     * @return array - Daftar buku
     */
    public function getBooksByCategory($category = null)
    {
        try {
            if ($category) {
                $query = "
                    SELECT 
                        id as id_buku,
                        title as judul,
                        author as penulis,
                        category as kategori,
                        cover_image as cover
                    FROM books
                    WHERE category = ?
                    ORDER BY title ASC
                ";
                $stmt = $this->pdo->prepare($query);
                $stmt->execute([$category]);
            } else {
                $query = "
                    SELECT 
                        id as id_buku,
                        title as judul,
                        author as penulis,
                        category as kategori,
                        cover_image as cover
                    FROM books
                    ORDER BY title ASC
                ";
                $stmt = $this->pdo->prepare($query);
                $stmt->execute();
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Cek apakah buku sudah ada di favorit siswa
     * 
     * @param int $studentId - ID siswa
     * @param int $bookId - ID buku
     * @return bool - true jika sudah favorit
     */
    public function checkDuplicate($studentId, $bookId)
    {
        try {
            $query = "
                SELECT COUNT(*) as total
                FROM favorites
                WHERE student_id = ? AND book_id = ?
            ";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$studentId, $bookId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['total'] > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Tambah buku ke favorit siswa
     * 
     * @param int $studentId - ID siswa
     * @param int $bookId - ID buku
     * @param string $category - Kategori buku (optional)
     * @return bool - Berhasil atau tidak
     */
    public function addFavorite($studentId, $bookId, $category = null)
    {
        try {
            // Cek duplikasi
            if ($this->checkDuplicate($studentId, $bookId)) {
                throw new Exception('Buku sudah ada di favorit Anda');
            }

            // Ambil kategori dari buku jika tidak diberikan
            if (!$category) {
                $bookQuery = "SELECT category FROM books WHERE id = ?";
                $bookStmt = $this->pdo->prepare($bookQuery);
                $bookStmt->execute([$bookId]);
                $book = $bookStmt->fetch(PDO::FETCH_ASSOC);
                $category = $book['category'] ?? null;
            }

            // Insert ke tabel favorites
            $query = "
                INSERT INTO favorites (student_id, book_id, category)
                VALUES (?, ?, ?)
            ";

            $stmt = $this->pdo->prepare($query);
            if (!$stmt->execute([$studentId, $bookId, $category])) {
                throw new Exception('Gagal menambah buku ke favorit');
            }

            return true;
        } catch (Exception $e) {
            throw new Exception('Error: ' . $e->getMessage());
        }
    }

    /**
     * Ambil daftar buku favorit siswa
     * 
     * @param int $studentId - ID siswa
     * @param string $category - Filter kategori (optional)
     * @return array - Daftar favorit
     */
    public function getFavorites($studentId, $category = null)
    {
        try {
            if ($category) {
                $query = "
                    SELECT 
                        f.id as id_favorit,
                        f.student_id as id_siswa,
                        f.book_id as id_buku,
                        f.category as kategori,
                        f.created_at as tanggal_ditambahkan,
                        b.title as judul,
                        b.author as penulis,
                        b.category as buku_kategori,
                        b.cover_image as cover
                    FROM favorites f
                    JOIN books b ON f.book_id = b.id
                    WHERE f.student_id = ? AND f.category = ?
                    ORDER BY f.created_at DESC
                ";
                $stmt = $this->pdo->prepare($query);
                $stmt->execute([$studentId, $category]);
            } else {
                $query = "
                    SELECT 
                        f.id as id_favorit,
                        f.student_id as id_siswa,
                        f.book_id as id_buku,
                        f.category as kategori,
                        f.created_at as tanggal_ditambahkan,
                        b.title as judul,
                        b.author as penulis,
                        b.category as buku_kategori,
                        b.cover_image as cover
                    FROM favorites f
                    JOIN books b ON f.book_id = b.id
                    WHERE f.student_id = ?
                    ORDER BY f.created_at DESC
                ";
                $stmt = $this->pdo->prepare($query);
                $stmt->execute([$studentId]);
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Hapus buku dari favorit siswa
     * 
     * @param int $studentId - ID siswa
     * @param int $favoriteId - ID favorit
     * @return bool - Berhasil atau tidak
     */
    public function removeFavorite($studentId, $favoriteId)
    {
        try {
            $query = "
                DELETE FROM favorites
                WHERE id = ? AND student_id = ?
            ";

            $stmt = $this->pdo->prepare($query);
            if (!$stmt->execute([$favoriteId, $studentId])) {
                throw new Exception('Gagal menghapus dari favorit');
            }

            return true;
        } catch (Exception $e) {
            throw new Exception('Error: ' . $e->getMessage());
        }
    }

    /**
     * Hitung total favorit siswa
     * 
     * @param int $studentId - ID siswa
     * @return int - Total favorit
     */
    public function countFavorites($studentId)
    {
        try {
            $query = "
                SELECT COUNT(*) as total
                FROM favorites
                WHERE student_id = ?
            ";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$studentId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int) $result['total'];
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Helper: Format tanggal
     */
    public static function formatDate($date)
    {
        if (empty($date)) {
            return '-';
        }
        $timestamp = strtotime($date);
        $now = time();
        $diff = $now - $timestamp;

        if ($diff < 60) {
            return 'Baru saja';
        } elseif ($diff < 3600) {
            return floor($diff / 60) . ' menit lalu';
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . ' jam lalu';
        } elseif ($diff < 604800) {
            return floor($diff / 86400) . ' hari lalu';
        }

        return date('d M Y', $timestamp);
    }
}
?>