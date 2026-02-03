<?php
/**
 * BarcodeModel - Generate QR Codes and Barcodes for books
 */
class BarcodeModel
{
    private $pdo;
    private $school_id;

    public function __construct($pdo, $school_id = null)
    {
        $this->pdo = $pdo;
        $this->school_id = $school_id;
    }

    /**
     * Search books by title, code, or author
     */
    public function searchBooks($query, $limit = 10)
    {
        if (strlen($query) < 2) {
            return [];
        }

        $searchTerm = '%' . $query . '%';
        
        // Build query - search in title, isbn, author
        // Using 'books' table with columns: id, title, author, isbn, copies, school_id
        $sql = 'SELECT id, title, author, isbn, copies as stok, category, cover_image
            FROM books
            WHERE (title LIKE ? OR isbn LIKE ? OR author LIKE ?)';
        
        $params = [$searchTerm, $searchTerm, $searchTerm];
        
        // Filter by school if school_id provided
        if ($this->school_id) {
            $sql .= ' AND school_id = ?';
            $params[] = $this->school_id;
        }
        
        $sql .= ' ORDER BY title ASC LIMIT ?';
        $params[] = $limit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Map columns to expected names for frontend
        return array_map(function($book) {
            return [
                'id' => $book['id'],
                'judul' => $book['title'],
                'kode_buku' => $book['isbn'],
                'penulis' => $book['author'],
                'stok' => $book['stok'],
                'penerbit' => $book['category'],
                'cover' => $book['cover_image'] ?? null
            ];
        }, $results);
    }

    /**
     * Get single book by ID
     */
    public function getBookById($id)
    {
        $sql = 'SELECT id, title, author, isbn, copies as stok, category as penerbit, cover_image
            FROM books WHERE id = ?';
        
        $params = [$id];
        
        if ($this->school_id) {
            $sql .= ' AND school_id = ?';
            $params[] = $this->school_id;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return null;
        }
        
        // Map columns to expected names
        return [
            'id' => $result['id'],
            'judul' => $result['title'],
            'kode_buku' => $result['isbn'],
            'penulis' => $result['author'],
            'stok' => $result['stok'],
            'penerbit' => $result['penerbit'],
            'cover' => $result['cover_image'] ?? null
        ];
    }

    /**
     * Generate QR Code as PNG
     * Returns base64 encoded PNG
     */
    public function generateQRCode($text, $size = 200)
    {
        // Create QR Code using simple library approach
        // Using inline base64 QR generation or calling external API
        
        // For simplicity, using QR Server API (free, no library needed)
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/';
        $params = [
            'size' => $size . 'x' . $size,
            'data' => urlencode($text),
            'format' => 'png'
        ];
        
        $fullUrl = $qrUrl . '?' . http_build_query($params);
        
        // Try with curl first, then fallback to file_get_contents
        $imageData = null;
        
        // Try cURL
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $fullUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $imageData = curl_exec($ch);
            curl_close($ch);
        }
        
        // Fallback to file_get_contents if curl fails
        if (!$imageData && function_exists('stream_context_create')) {
            $ctx = stream_context_create(['ssl' => ['verify_peer' => false]]);
            $imageData = @file_get_contents($fullUrl, false, $ctx);
        }
        
        if (!$imageData) {
            throw new Exception('Failed to generate QR code - Check internet connection or server settings');
        }
        
        return base64_encode($imageData);
    }

    /**
     * Generate Code128 Barcode as PNG
     * Using picqer/php-barcode-generator approach
     */
    public function generateBarcode($text, $width = 2, $height = 50)
    {
        // Using an online barcode generator for simplicity
        // Alternative: implement picqer library
        
        $barcodeUrl = 'https://barcode.tec-it.com/barcode.ashx';
        $params = [
            'data' => urlencode($text),
            'code' => 'Code128',
            'dpi' => 72,
            'print' => false,
            'eclevel' => 'M'
        ];
        
        $fullUrl = $barcodeUrl . '?' . http_build_query($params);
        
        // Try with curl first, then fallback to file_get_contents
        $imageData = null;
        
        // Try cURL
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $fullUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $imageData = curl_exec($ch);
            curl_close($ch);
        }
        
        // Fallback to file_get_contents if curl fails
        if (!$imageData && function_exists('stream_context_create')) {
            $ctx = stream_context_create(['ssl' => ['verify_peer' => false]]);
            $imageData = @file_get_contents($fullUrl, false, $ctx);
        }
        
        if (!$imageData) {
            throw new Exception('Failed to generate barcode - Check internet connection or server settings');
        }
        
        return base64_encode($imageData);
    }

    /**
     * Create combined barcode image (QR + Code128)
     * Returns array with both base64 images
     */
    public function generateCombinedBarcode($bookData)
    {
        try {
            $qrCode = $this->generateQRCode($bookData['kode_buku'], 200);
            $barcode = $this->generateBarcode($bookData['kode_buku'], 2, 40);
            
            return [
                'success' => true,
                'qr_code' => $qrCode,
                'barcode' => $barcode,
                'book' => $bookData
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Save barcode generation to logs (optional)
     */
    public function logBarcodeGeneration($book_id, $generated_by)
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO barcode_logs (book_id, generated_by, generated_at)
                 VALUES (?, ?, NOW())'
            );
            $stmt->execute([$book_id, $generated_by]);
            return true;
        } catch (Exception $e) {
            error_log("Barcode log error: " . $e->getMessage());
            return false;
        }
    }
}
