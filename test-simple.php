<?php
// Ultra simple test - no classes, direct SQL
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Direct Database Test</h1>";

try {
    require __DIR__ . '/src/db.php';
    
    echo "<p>✓ Database connected</p>";
    
    // Test 1: Simple count
    echo "<h2>Test 1: Count books</h2>";
    $result = $pdo->query("SELECT COUNT(*) as total FROM books");
    $row = $result->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total books: " . $row['total'] . "</p>";
    
    // Test 2: Search with prepared statement
    echo "<h2>Test 2: Search with prepared statement</h2>";
    $sql = "SELECT id, title, author, isbn, copies FROM books WHERE school_id = ? LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([4]);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($books) . " books for school 4:</p>";
    echo "<pre>";
    var_dump($books);
    echo "</pre>";
    
    // Test 3: Search with LIKE
    echo "<h2>Test 3: Search with LIKE</h2>";
    $sql = "SELECT id, title, author, isbn, copies FROM books 
            WHERE (title LIKE ? OR author LIKE ? OR isbn LIKE ?) 
            AND school_id = ? 
            LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['%te%', '%te%', '%te%', 4]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Search results for 'te': " . count($results) . "</p>";
    echo "<pre>";
    var_dump($results);
    echo "</pre>";
    
    // Test 4: Try BarcodeModel
    echo "<h2>Test 4: BarcodeModel</h2>";
    require_once __DIR__ . '/src/BarcodeModel.php';
    
    $barcode = new BarcodeModel($pdo, 4);
    $searchResults = $barcode->searchBooks('te', 10);
    
    echo "<p>BarcodeModel search results: " . count($searchResults) . "</p>";
    echo "<pre>";
    echo json_encode($searchResults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<h2 style='color:red;'>ERROR: " . htmlspecialchars($e->getMessage()) . "</h2>";
    echo "<pre style='background:#fee; padding:10px; overflow:auto;'>";
    echo htmlspecialchars($e->getTraceAsString());
    echo "</pre>";
}
