<?php
// API endpoint untuk search books dan generate barcode
// Set error handling
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Set content type FIRST sebelum output apapun
header('Content-Type: application/json; charset=utf-8');

// Capture all output to prevent whitespace from breaking JSON
ob_start();

try {
    require __DIR__ . '/../src/auth.php';
    
    // Check if requireAuth function exists
    if (!function_exists('requireAuth')) {
        throw new Exception('Auth function not found');
    }
    
    @requireAuth();

    require_once __DIR__ . '/../src/db.php';
    require_once __DIR__ . '/../src/BarcodeModel.php';

    // Check if PDO exists
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception('Database connection failed - PDO not initialized');
    }

    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    $school_id = $_SESSION['user']['school_id'] ?? null;
    $user_id = $_SESSION['user']['id'] ?? null;

    // For debugging - log incoming request
    error_log("Barcode API Request: action=$action, school_id=$school_id, user_id=$user_id");

    if (!$school_id) {
        http_response_code(403);
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'School ID not found']);
        exit;
    }

    $barcode = new BarcodeModel($pdo, $school_id);

    if ($action === 'search') {
        $query = trim($_GET['q'] ?? '');
        
        error_log("Search query: '$query' (length: " . strlen($query) . ")");
        
        if (strlen($query) < 2) {
            ob_end_clean();
            echo json_encode(['success' => false, 'error' => 'Minimal 2 karakter']);
            exit;
        }
        
        try {
            $results = $barcode->searchBooks($query, 20);
            error_log("Search results: " . count($results) . " books found");
            
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'count' => count($results),
                'books' => $results
            ]);
        } catch (Exception $e) {
            error_log("Search error: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Search failed: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'generate') {
        $book_id = intval($_POST['book_id'] ?? 0);
        
        error_log("Generate barcode for book_id: $book_id");
        
        if ($book_id <= 0) {
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid book ID']);
            exit;
        }
        
        try {
            $book = $barcode->getBookById($book_id);
            
            if (!$book) {
                ob_end_clean();
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Buku tidak ditemukan']);
                exit;
            }
            
            error_log("Book found: " . json_encode($book));
            
            $result = $barcode->generateCombinedBarcode($book);
            
            if ($result['success']) {
                // Log generation (non-blocking)
                try {
                    $barcode->logBarcodeGeneration($book_id, $user_id);
                } catch (Exception $logError) {
                    error_log("Barcode log error: " . $logError->getMessage());
                }
                
                ob_end_clean();
                echo json_encode($result);
            } else {
                ob_end_clean();
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $result['error']]);
            }
        } catch (Exception $e) {
            error_log("Generate error: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Generation failed: ' . $e->getMessage()]);
        }
        exit;
    }
    
    // Default: show available actions
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Invalid action',
        'available_actions' => ['search', 'generate']
    ]);
    
} catch (Exception $e) {
    error_log("Barcode API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
