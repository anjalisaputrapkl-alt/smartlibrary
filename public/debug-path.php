<?php
// debug-path.php
require __DIR__ . '/../src/auth.php';
$pdo = require __DIR__ . '/../src/db.php';

$user = $_SESSION['user'] ?? null;
if (!$user) die("Login first");

$stmt = $pdo->prepare("SELECT * FROM siswa WHERE id_siswa = ?");
$stmt->execute([$user['id']]);
$siswa = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h1>Debug Info</h1>";
echo "User ID: " . $user['id'] . "<br>";
echo "Current DB Foto Value: '" . htmlspecialchars($siswa['foto'] ?? 'NULL') . "'<br>";

if (!empty($siswa['foto'])) {
    $path = $siswa['foto'];
    
    // Simulate student-card logic
    $photoSrc = '';
    if (strpos($path, 'uploads/') === 0) {
        $photoSrc = './' . $path;
        echo "Detected relative path, converting to: $photoSrc<br>";
    } else {
        echo "Path format not matching 'uploads/' prefix check<br>";
        $photoSrc = $path;
    }
    
    // Check extraction
    $cleanPath = str_replace('./', '', str_replace('../', '../', $photoSrc));
    echo "Cleaned Path for Check: $cleanPath<br>";
    
    // Check absolute construction
    $fullCheckPath = __DIR__ . '/' . $cleanPath;
    echo "Full Check Path: $fullCheckPath<br>";
    
    if (file_exists($fullCheckPath)) {
        echo "<h2 style='color:green'>FILE EXISTS!</h2>";
    } else {
        echo "<h2 style='color:red'>FILE DOES NOT EXIST!</h2>";
        
        // Debug dir content
        echo "<h3>Checking directory content:</h3>";
        $dir = dirname($fullCheckPath);
        if (is_dir($dir)) {
            echo "Directory $dir exists.<br>";
            $files = scandir($dir);
            echo "Files in dir: " . implode(", ", $files) . "<br>";
        } else {
            echo "Directory $dir DOES NOT EXIST.<br>";
        }
    }
} else {
    echo "No foto in DB";
}
