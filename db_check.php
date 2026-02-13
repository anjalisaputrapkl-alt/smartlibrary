<?php
$pdo = require 'src/db.php';
$stmt = $pdo->query('SELECT name, theme_key, date FROM special_themes WHERE is_active = 1');
echo "--- ACTIVE SPECIAL THEMES ---\n";
while($row = $stmt->fetch()) {
    echo "Name: {$row['name']} | Key: {$row['theme_key']} | Date: {$row['date']}\n";
}
?>
