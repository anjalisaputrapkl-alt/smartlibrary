<?php
// Debug endpoint - shows actual error from barcode-api
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION)) {
    session_start();
}

// Mock session
$_SESSION['user'] = ['school_id' => 4, 'id' => 1];

try {
    require_once __DIR__ . '/../../src/db.php';
    require_once __DIR__ . '/../../src/BarcodeModel.php';
    
    $query = $_GET['q'] ?? 'te';
    $school_id = 4;
    
    echo json_encode([
        'step' => 'Initialized',
        'query' => $query,
        'school_id' => $school_id
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => explode("\n", $e->getTraceAsString())
    ]);
}
