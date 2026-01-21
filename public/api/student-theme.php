<?php
/**
 * Student Theme API
 * Endpoint untuk siswa ambil tema sekolah mereka berdasarkan school_id
 * Diakses dari halaman siswa
 */

// Start session dengan robust handling
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get school_id dari session
$school_id = $_SESSION['user']['school_id'] ?? null;

if (!$school_id) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized', 'debug' => 'No school_id in session']);
    exit;
}

try {
    $pdo = require __DIR__ . '/../../src/db.php';
    require __DIR__ . '/../../src/ThemeModel.php';

    header('Content-Type: application/json');

    $themeModel = new ThemeModel($pdo);
    $theme = $themeModel->getThemeData($school_id);

    echo json_encode([
        'success' => true,
        'theme_name' => $theme['theme_name'],
        'custom_colors' => $theme['custom_colors'],
        'typography' => $theme['typography']
    ]);
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => 'Exception in student-theme.php'
    ]);
}
?>