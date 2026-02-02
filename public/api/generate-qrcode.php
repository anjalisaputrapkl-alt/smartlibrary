<?php
/**
 * Generate QR Code for Member (NISN) or Book (ISBN)
 * Using external QR code API services (no external library)
 * 
 * Public endpoint - no auth required
 */

// Set error handling - no output before headers
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set content type and cache headers
header('Content-Type: image/png');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Get parameters
$type = $_GET['type'] ?? 'member'; // 'member' or 'book'
$value = $_GET['value'] ?? null;
$size = (int) ($_GET['size'] ?? 200);

// Validate input
if (!$value || !preg_match('/^[a-zA-Z0-9\-_.]+$/', $value)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid value']);
    exit;
}

// Limit size to reasonable bounds
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

// Use QR code API service
// Primary: api.qrserver.com
$apiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($data);

// Fetch QR code from service
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$imageData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $imageData) {
    // Success - output PNG
    echo $imageData;
    exit;
}

// Fallback: try alternative service
$fallbackUrl = 'https://quickchart.io/qr?text=' . urlencode($data) . '&size=' . $size;
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $fallbackUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$imageData = curl_exec($ch);
curl_close($ch);

if ($imageData) {
    echo $imageData;
    exit;
}

// All services failed
http_response_code(500);
header('Content-Type: application/json');
echo json_encode(['error' => 'Failed to generate QR code']);
exit;
?>