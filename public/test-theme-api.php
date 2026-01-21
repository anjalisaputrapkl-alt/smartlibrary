<?php
/**
 * DEBUG: Test Theme API
 * Akses: http://localhost/perpustakaan-online/public/test-theme-api.php
 */

session_start();

// Simulasi login sebagai siswa
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'id' => 1,
        'school_id' => 1,
        'role' => 'student'
    ];
    echo "<p style='color: green;'>✓ Session simulasi siswa dibuat</p>";
}

$pdo = require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/ThemeModel.php';

echo "<h2>DEBUG THEME API</h2>";

try {
    $themeModel = new ThemeModel($pdo);
    $school_id = $_SESSION['user']['school_id'];

    echo "<p><strong>School ID:</strong> {$school_id}</p>";

    // Test getSchoolTheme
    $theme = $themeModel->getSchoolTheme($school_id);
    echo "<h3>getSchoolTheme() result:</h3>";
    echo "<pre>" . print_r($theme, true) . "</pre>";

    // Test getThemeData
    $themeData = $themeModel->getThemeData($school_id);
    echo "<h3>getThemeData() result:</h3>";
    echo "<pre>" . print_r($themeData, true) . "</pre>";

    // Test API call
    echo "<h3>API Call Simulation:</h3>";
    $json = json_encode([
        'success' => true,
        'theme_name' => $themeData['theme_name'],
        'custom_colors' => $themeData['custom_colors'],
        'typography' => $themeData['typography']
    ]);
    echo "<pre>" . $json . "</pre>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='student-dashboard.php'>Kembali ke Student Dashboard</a></p>";
?>