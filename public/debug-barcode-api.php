<?php
// Debug script untuk test barcode API
header('Content-Type: text/plain; charset=utf-8');

echo "=== BARCODE API DEBUG ===\n\n";

// Test 1: Check if files exist
echo "1. Checking files...\n";
echo "   BarcodeModel.php: " . (file_exists(__DIR__ . '/../src/BarcodeModel.php') ? "✓ EXISTS" : "✗ NOT FOUND") . "\n";
echo "   db.php: " . (file_exists(__DIR__ . '/../src/db.php') ? "✓ EXISTS" : "✗ NOT FOUND") . "\n";
echo "   auth.php: " . (file_exists(__DIR__ . '/../src/auth.php') ? "✓ EXISTS" : "✗ NOT FOUND") . "\n\n";

// Test 2: Try loading files
echo "2. Testing includes...\n";
try {
    require __DIR__ . '/../src/db.php';
    echo "   ✓ Database connected\n";
} catch (Exception $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n";
}

try {
    require_once __DIR__ . '/../src/BarcodeModel.php';
    echo "   ✓ BarcodeModel loaded\n";
} catch (Exception $e) {
    echo "   ✗ BarcodeModel error: " . $e->getMessage() . "\n";
}

// Test 3: Check PDO
echo "\n3. Checking PDO...\n";
if (isset($pdo)) {
    echo "   ✓ PDO object exists\n";
    
    // Test query
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM buku LIMIT 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "   ✓ Can query buku table: " . ($result['count'] ?? 0) . " books\n";
    } catch (Exception $e) {
        echo "   ✗ Query error: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ✗ PDO not initialized\n";
}

// Test 4: Try to instantiate BarcodeModel
echo "\n4. Testing BarcodeModel instantiation...\n";
try {
    if (isset($pdo)) {
        $barcode = new BarcodeModel($pdo);
        echo "   ✓ BarcodeModel instantiated\n";
        
        // Try search
        $results = $barcode->searchBooks('test', 5);
        echo "   ✓ Search method works, found: " . count($results) . " results\n";
    } else {
        echo "   ✗ Cannot instantiate - PDO not available\n";
    }
} catch (Exception $e) {
    echo "   ✗ Instantiation error: " . $e->getMessage() . "\n";
    echo "      Stack: " . $e->getTraceAsString() . "\n";
}

echo "\n=== END DEBUG ===\n";
