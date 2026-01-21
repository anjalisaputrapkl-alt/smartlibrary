<?php
/**
 * Migration: Remove NIS column from siswa table
 */

$pdo = require 'src/db.php';

try {
    echo "Dropping NIS column from siswa table...\n";

    // Check if column exists before dropping
    $columns = $pdo->query("DESCRIBE siswa")->fetchAll(PDO::FETCH_ASSOC);
    $hasNisColumn = false;

    foreach ($columns as $col) {
        if ($col['Field'] === 'nis') {
            $hasNisColumn = true;
            break;
        }
    }

    if ($hasNisColumn) {
        $pdo->exec("ALTER TABLE siswa DROP COLUMN nis");
        echo "✅ NIS column dropped successfully\n";
    } else {
        echo "ℹ️ NIS column doesn't exist\n";
    }

    echo "\n=== Current siswa table structure ===\n";
    $columns = $pdo->query("DESCRIBE siswa")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
