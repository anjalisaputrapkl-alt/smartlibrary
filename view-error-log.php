<?php
// View PHP error log
header('Content-Type: text/plain; charset=utf-8');

echo "=== PHP ERROR LOG ===\n\n";

// Try to find error log
$possiblePaths = [
    php_ini_get('error_log'),
    __DIR__ . '/../logs/php-error.log',
    __DIR__ . '/../logs/error.log',
    __DIR__ . '/../../logs/php_errors.log',
    'C:/xampp/php/logs/error.log',
    'C:/xampp/php/logs/php_error.log',
];

echo "PHP Error Log Location (configured): " . (php_ini_get('error_log') ?: 'NOT SET') . "\n\n";

foreach ($possiblePaths as $path) {
    if ($path && file_exists($path)) {
        echo "Found log at: $path\n";
        echo "---\n";
        $content = file_get_contents($path);
        // Show last 50 lines
        $lines = array_slice(explode("\n", $content), -50);
        echo implode("\n", $lines);
        echo "\n---\n\n";
        break;
    }
}

echo "\n\nFolder structure check:\n";
$logsFolder = __DIR__ . '/../logs';
if (is_dir($logsFolder)) {
    echo "Logs folder exists: $logsFolder\n";
    $files = scandir($logsFolder);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "  - $file\n";
        }
    }
} else {
    echo "Logs folder not found: $logsFolder\n";
}
