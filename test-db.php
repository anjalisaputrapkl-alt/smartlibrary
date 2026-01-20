<?php
$pdo = require 'src/db.php';

try {
    $stmt = $pdo->query("SELECT 1");
    echo "✓ Database connected OK";
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage();
}
?>