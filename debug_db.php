<?php
$pdo = require __DIR__ . '/src/db.php';
$stmt = $pdo->query("SELECT id, name, borrow_duration, late_fine, max_books, max_books_student, max_books_teacher, max_books_employee FROM schools");
$schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($schools, JSON_PRETTY_PRINT);
