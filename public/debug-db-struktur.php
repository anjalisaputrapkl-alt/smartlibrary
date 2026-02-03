<?php
// Script untuk check database struktur
header('Content-Type: text/plain; charset=utf-8');

try {
    require __DIR__ . '/../src/db.php';
    
    echo "=== DATABASE STRUKTUR ===\n\n";
    
    // Check buku table
    echo "1. Checking 'buku' table structure:\n";
    $columns = $pdo->query("DESCRIBE buku")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($columns)) {
        echo "   ✗ Table 'buku' tidak ditemukan!\n";
    } else {
        echo "   Columns:\n";
        foreach ($columns as $col) {
            echo "   - {$col['Field']} ({$col['Type']})\n";
        }
    }
    
    echo "\n2. Checking data in 'buku':\n";
    $count = $pdo->query("SELECT COUNT(*) as count FROM buku")->fetch(PDO::FETCH_ASSOC);
    echo "   Total records: " . $count['count'] . "\n";
    
    if ($count['count'] > 0) {
        echo "\n3. Sample books:\n";
        $samples = $pdo->query("SELECT id, judul, kode_buku, penulis FROM buku LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($samples as $book) {
            echo "   - [{$book['id']}] {$book['judul']} (Kode: {$book['kode_buku']})\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
