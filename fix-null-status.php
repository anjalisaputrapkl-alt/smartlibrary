<?php
$config = require __DIR__ . '/src/config.php';
$dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
$pdo = new PDO($dsn, $config['db_user'], $config['db_pass']);

echo "Fixing NULL status records...\n";
$affected = $pdo->exec('UPDATE borrows SET status = "borrowed" WHERE status IS NULL OR status = ""');
echo "✓ Fixed $affected record(s)\n";
?>