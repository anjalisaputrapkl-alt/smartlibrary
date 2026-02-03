<?php
// Test script untuk verify BarcodeModel dengan actual database
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Testing BarcodeModel</h1>";

try {
    require __DIR__ . '/src/db.php';
    require_once __DIR__ . '/src/BarcodeModel.php';
    
    echo "<h2>✓ Files loaded successfully</h2>";
    
    // Create instance
    $barcode = new BarcodeModel($pdo, 4); // School ID 4 has books
    echo "<h2>✓ BarcodeModel instantiated</h2>";
    
    // Test search
    echo "<h3>Testing search for 'Sang':</h3>";
    $results = $barcode->searchBooks('Sang', 10);
    
    echo "<pre>";
    echo "Found: " . count($results) . " results\n\n";
    echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "</pre>";
    
    // Test getBookById
    if (!empty($results)) {
        echo "<h3>Testing getBookById for book ID " . $results[0]['id'] . ":</h3>";
        $book = $barcode->getBookById($results[0]['id']);
        echo "<pre>";
        echo json_encode($book, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo "</pre>";
        
        // Test generate combined barcode
        echo "<h3>Testing barcode generation for kode_buku: " . $book['kode_buku'] . "</h3>";
        try {
            $barcodeResult = $barcode->generateCombinedBarcode($book);
            echo "<h4>Result:</h4>";
            echo "<pre>";
            if ($barcodeResult['success']) {
                echo "Success: Generated QR and Barcode\n";
                echo "QR Code length: " . strlen($barcodeResult['qr_code']) . " chars\n";
                echo "Barcode length: " . strlen($barcodeResult['barcode']) . " chars\n";
            } else {
                echo "Error: " . $barcodeResult['error'];
            }
            echo "</pre>";
        } catch (Exception $e) {
            echo "<h4 style='color: red;'>Error: " . $e->getMessage() . "</h4>";
        }
    }
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>✗ Error: " . $e->getMessage() . "</h2>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
