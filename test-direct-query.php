<?php
// Direct test without auth
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Testing Database Query</h1>";

try {
    require __DIR__ . '/src/db.php';
    
    echo "<h2>✓ Database connected</h2>";
    
    // Check if books table exists
    echo "<h3>1. Checking 'books' table...</h3>";
    try {
        $result = $pdo->query("SELECT COUNT(*) as count FROM books");
        $row = $result->fetch(PDO::FETCH_ASSOC);
        echo "<p>Total books: <strong>" . $row['count'] . "</strong></p>";
    } catch (Exception $e) {
        echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
    }
    
    // Test search query
    echo "<h3>2. Testing search query with 'te'...</h3>";
    try {
        $sql = "SELECT id, title, author, isbn, copies as stok, category FROM books WHERE (title LIKE ? OR isbn LIKE ? OR author LIKE ?) AND school_id = ? ORDER BY title ASC LIMIT 20";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, '%te%', PDO::PARAM_STR);
        $stmt->bindValue(2, '%te%', PDO::PARAM_STR);
        $stmt->bindValue(3, '%te%', PDO::PARAM_STR);
        $stmt->bindValue(4, 4, PDO::PARAM_INT);
        
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Found <strong>" . count($results) . "</strong> results</p>";
        
        if (count($results) > 0) {
            echo "<table border='1' cellpadding='10'>";
            echo "<tr><th>ID</th><th>Title</th><th>Author</th><th>ISBN</th><th>Stok</th></tr>";
            foreach ($results as $book) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($book['id']) . "</td>";
                echo "<td>" . htmlspecialchars($book['title']) . "</td>";
                echo "<td>" . htmlspecialchars($book['author']) . "</td>";
                echo "<td>" . htmlspecialchars($book['isbn']) . "</td>";
                echo "<td>" . htmlspecialchars($book['stok']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red;'>Query error: " . $e->getMessage() . "</p>";
        echo "<p>Trace: <pre>" . $e->getTraceAsString() . "</pre></p>";
    }
    
    // Test BarcodeModel
    echo "<h3>3. Testing BarcodeModel...</h3>";
    try {
        require_once __DIR__ . '/src/BarcodeModel.php';
        $barcode = new BarcodeModel($pdo, 4);
        $results = $barcode->searchBooks('te', 10);
        
        echo "<p>BarcodeModel search returned <strong>" . count($results) . "</strong> results</p>";
        
        if (count($results) > 0) {
            echo "<pre>";
            echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            echo "</pre>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red;'>BarcodeModel error: " . $e->getMessage() . "</p>";
        echo "<p>Trace: <pre>" . $e->getTraceAsString() . "</pre></p>";
    }
    
} catch (Exception $e) {
    echo "<h2 style='color:red;'>✗ Error: " . $e->getMessage() . "</h2>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
