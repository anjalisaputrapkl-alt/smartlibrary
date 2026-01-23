<?php
$config = require __DIR__ . '/src/config.php';
$dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
$pdo = new PDO($dsn, $config['db_user'], $config['db_pass']);

echo "========== VERIFICATION STATUS ENUM ==========\n\n";

// Check current enum
$result = $pdo->query("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='borrows' AND COLUMN_NAME='status' AND TABLE_SCHEMA='{$config['db_name']}'");
$row = $result->fetch(PDO::FETCH_ASSOC);
echo "✓ Current status enum: " . $row['COLUMN_TYPE'] . "\n\n";

// Check borrows data
$result = $pdo->query("SELECT COUNT(*) as total FROM borrows");
$row = $result->fetch(PDO::FETCH_ASSOC);
echo "✓ Total borrows records: " . $row['total'] . "\n\n";

// Check status distribution
echo "Status distribution:\n";
$result = $pdo->query("SELECT status, COUNT(*) as count FROM borrows GROUP BY status ORDER BY status");
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo "  - {$row['status']}: {$row['count']} records\n";
}

echo "\n✓ Verification complete!\n";
?>