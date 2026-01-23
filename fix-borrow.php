<?php
$pdo = require 'src/db.php';

// Update status lama menjadi returned
$update = $pdo->prepare(
    'UPDATE borrows SET status = "returned", returned_at = NOW() WHERE id = 25'
);
$update->execute();

echo "Peminjaman ID 25 sudah diupdate menjadi returned.\n";

// Check result
$check = $pdo->prepare('SELECT id, status FROM borrows WHERE id = 25');
$check->execute();
$result = $check->fetch();
echo "Status sekarang: " . $result['status'] . "\n";
?>