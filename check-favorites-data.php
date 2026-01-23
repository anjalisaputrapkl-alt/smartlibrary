<?php
session_start();
$pdo = require 'src/db.php';
require_once 'src/FavoriteModel.php';

// Set demo user
$_SESSION['user'] = [
    'id' => 15,
    'school_id' => 9,
    'nisn' => '111111'
];

$studentId = $_SESSION['user']['id'];

try {
    $model = new FavoriteModel($pdo);
    $favorites = $model->getFavorites($studentId);

    echo "=== FAVORITES DATA ===\n";
    foreach ($favorites as $fav) {
        echo "ID: {$fav['id_buku']}, Title: {$fav['judul']}, Cover: {$fav['cover']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>