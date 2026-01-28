<?php
/**
 * Generate QR Code for Member (NISN) or Book (ISBN)
 * Using pure PHP without external library dependencies
 */

require_once __DIR__ . '/../../src/config.php';

header('Content-Type: image/png');
header('Cache-Control: no-cache, must-revalidate');

// Get parameters
$type = $_GET['type'] ?? 'member'; // 'member' or 'book'
$value = $_GET['value'] ?? null;
$size = (int) ($_GET['size'] ?? 200);

// Validate input
if (!$value || !preg_match('/^[a-zA-Z0-9\-]+$/', $value)) {
    http_response_code(400);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Invalid value']));
}

// Limit size
$size = max(50, min(500, $size));

// Prepare data to encode
$data = '';
if ($type === 'member') {
    $data = 'NISN:' . $value;
} else if ($type === 'book') {
    $data = 'ISBN:' . $value;
} else {
    $data = $value;
}

// Use QR code API service (no external library needed)
// Using qrserver.com which is reliable and fast
$apiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($data);

// Fetch QR code from service and output
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$imageData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $imageData) {
    echo $imageData;
} else {
    // Fallback: generate simple QR code using alternative service
    $fallbackUrl = 'https://quickchart.io/qr?text=' . urlencode($data) . '&size=' . $size;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fallbackUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $imageData = curl_exec($ch);
    curl_close($ch);

    if ($imageData) {
        echo $imageData;
    } else {
        http_response_code(500);
        header('Content-Type: application/json');
        exit(json_encode(['error' => 'Failed to generate QR code']));
    }
}
?>