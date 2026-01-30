<?php
/**
 * Generate Student Barcode - Generate barcode untuk siswa
 * Returns SVG barcode yang bisa langsung ditampilkan
 */

require __DIR__ . '/../../src/auth.php';

$member_id = $_GET['member_id'] ?? $_POST['member_id'] ?? null;
$format = $_GET['format'] ?? 'svg'; // svg or png

if (!$member_id) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Member ID tidak ditemukan'
    ]);
    exit;
}

try {
    $pdo = require __DIR__ . '/../../src/db.php';

    // Get member data with school info
    $stmt = $pdo->prepare(
        'SELECT m.*, s.name AS school_name
         FROM members m
         LEFT JOIN schools s ON m.school_id = s.id
         WHERE m.id = :id'
    );
    $stmt->execute(['id' => (int)$member_id]);
    $member = $stmt->fetch();

    if (!$member) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Siswa tidak ditemukan'
        ]);
        exit;
    }

    // Generate barcode data - Format: MEMBERID-NISN-SCHOOLID
    $barcode_data = $member['id'] . '-' . $member['nisn'] . '-' . $member['school_id'];

    // Generate Code128 barcode using library
    $barcode_svg = generateCode128($barcode_data);

    header('Content-Type: image/svg+xml');
    echo $barcode_svg;

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    error_log("[GENERATE-BARCODE-ERROR] " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}

/**
 * Generate Code128 barcode as SVG
 */
function generateCode128($data) {
    $data = strtoupper($data);
    
    // Code128B character set
    $charset = ' !"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~';
    
    // Code128B start code
    $barcode = '11010010000'; // START_B (104)
    
    $checksum = 104;
    $position = 0;

    // Encode each character
    foreach (str_split($data) as $char) {
        $code = strpos($charset, $char);
        if ($code === false) {
            $code = 0;
        }
        $barcode .= code128Encode($code);
        $checksum += ($position + 1) * $code;
        $position++;
    }

    // Add checksum
    $checksum = $checksum % 103;
    $barcode .= code128Encode($checksum);

    // Stop code
    $barcode .= '1100011101011'; // STOP

    // Convert to SVG
    $barWidth = 2;
    $barHeight = 50;
    $width = strlen($barcode) * $barWidth;
    $height = $barHeight + 40; // Extra space untuk text

    $svg = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $svg .= '<svg width="' . ($width + 20) . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">' . "\n";
    $svg .= '<rect width="100%" height="100%" fill="white"/>' . "\n";

    $x = 10;
    foreach (str_split($barcode) as $bit) {
        if ($bit == '1') {
            $svg .= '<rect x="' . $x . '" y="10" width="' . $barWidth . '" height="' . $barHeight . '" fill="black"/>' . "\n";
        }
        $x += $barWidth;
    }

    // Add text label
    $svg .= '<text x="' . ($width / 2 + 10) . '" y="' . ($barHeight + 30) . '" font-family="Arial, sans-serif" font-size="12" text-anchor="middle" fill="black">' . htmlspecialchars($data) . '</text>' . "\n";
    $svg .= '</svg>';

    return $svg;
}

/**
 * Code128 encode table
 */
function code128Encode($value) {
    static $encodings = [
        // 0-9
        '11011001100', '11001101100', '11001100110', '10010011000', '10010001100',
        '10001001100', '10011001000', '10011000100', '10110001000', '10100011000',
        // 10-19
        '10001011000', '10010110000', '10010011000', '10111001100', '10100111100',
        '10001110110', '10111010110', '10111100100', '10110110100', '10110010110',
        // 20-29
        '10011010110', '10011110100', '10110100110', '10110010100', '10011011000',
        '10011101100', '10011100110', '10111011100', '10111100110', '10100110110',
        // 30-39
        '10100011110', '10010110110', '10010011110', '10111010100', '10111010010',
        '10110110010', '10110011010', '10110101010', '10010101010', '10010110010',
        // 40-49
        '10010011010', '10101011000', '10100110100', '10100011010', '10101001100',
        '10100101100', '10100100110', '10101010000', '10100001010', '10101001010',
        // 50-59
        '10101000110', '10100010110', '10101101000', '10100100010', '10101011000',
        '10010110100', '10010110010', '10010101100', '10010100110', '10110010010',
        // 60-69
        '10110001010', '10101100100', '10101001000', '10100101000', '10101010100',
        '10101001110', '10100101110', '10010111010', '10010110110', '10010110011',
        // 70-79
        '10010011011', '10011001011', '10011010011', '10011010110', '10011101011',
        '10011110110', '11001011101', '11001101101', '11001110110', '11010011101',
        // 80-89
        '11010110101', '11010110110', '11010111010', '11010111011', '11101011101',
        '11101101101', '11101110101', '11101110110', '11101111010', '11101111011',
        // 90-99
        '11110101011', '11110110101', '11110110110', '11110111010', '11110111011',
        '11111010110', '11111011010', '11111011011', '11111101010', '11111101011',
        // 100-102
        '11111110101', '11100101011', '11100110101'
    ];

    return isset($encodings[$value]) ? $encodings[$value] : '11011001100';
}
?>
