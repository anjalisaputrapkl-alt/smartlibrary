<?php
// Minimal test - no auth, just check if API logic works
header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', '1');

try {
    // Test 1: Check if files load
    echo json_encode(['step' => 'Loading files...']);
    
    require __DIR__ . '/src/db.php';
    require_once __DIR__ . '/src/BarcodeModel.php';
    
    // Test 2: Create instance
    echo json_encode(['step' => 'Creating BarcodeModel instance...']);
    $barcode = new BarcodeModel($pdo, 4);
    
    // Test 3: Search
    echo json_encode(['step' => 'Searching for "te"...']);
    $results = $barcode->searchBooks('te', 10);
    
    echo json_encode([
        'success' => true,
        'message' => 'Search completed',
        'count' => count($results),
        'results' => $results
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
