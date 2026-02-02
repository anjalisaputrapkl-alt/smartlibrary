<?php
require 'src/auth.php';
$pdo = require 'src/db.php';

// Test query untuk user pertama
$stmt = $pdo->prepare('
    SELECT u.id, u.name, u.school_id,
           sch.name AS school_name, sch.address AS location,
           s.student_uuid, s.foto
    FROM users u
    LEFT JOIN schools sch ON u.school_id = sch.id
    LEFT JOIN siswa s ON s.id_siswa = u.id
    LIMIT 1
');
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo '<pre>';
echo 'Query Result:' . PHP_EOL;
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
echo '</pre>';
