<?php
// Test API response exactly seperti yang dikirim dari barcode-api.php
header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

ob_start();

try {
    // Simulate session (without actual auth)
    if (!isset($_SESSION)) {
        session_start();
    }
    
    // Mock session data
    $_SESSION['user'] = [
        'id' => 1,
        'school_id' => 4
    ];
    
    require_once __DIR__ . '/src/db.php';
    require_once __DIR__ . '/src/BarcodeModel.php';

    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    $school_id = $_SESSION['user']['school_id'] ?? null;

    error_log("TEST API: action=$action, school_id=$school_id");

    if (!$school_id) {
        http_response_code(403);
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'School ID not found']);
        exit;
    }

    $barcode = new BarcodeModel($pdo, $school_id);

    if ($action === 'search') {
        $query = trim($_GET['q'] ?? '');
        
        error_log("TEST API: Search query='$query'");
        
        if (strlen($query) < 2) {
            ob_end_clean();
            echo json_encode(['success' => false, 'error' => 'Minimal 2 karakter']);
            exit;
        }
        
        try {
            $results = $barcode->searchBooks($query, 20);
            error_log("TEST API: Found " . count($results) . " results");
            
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'count' => count($results),
                'books' => $results
            ]);
        } catch (Exception $e) {
            error_log("TEST API Search error: " . $e->getMessage());
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Search error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    
} catch (Exception $e) {
    error_log("TEST API Exception: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
