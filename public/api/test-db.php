<?php
// Simple test to see database connection
require __DIR__ . '/../../src/db.php';

try {
    // Test query
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM books");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Database connection: OK\n";
    echo "Total books in database: " . $result['count'] . "\n";
    
    // Test members table
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM members");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Total members in database: " . $result['count'] . "\n";
    
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
