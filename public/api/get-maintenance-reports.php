<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    require __DIR__ . '/../../src/auth.php';
    requireAuth();
    
    $pdo = require __DIR__ . '/../../src/db.php';
    $user = $_SESSION['user'];
    $school_id = $user['school_id'];

    try {
        $stmt = $pdo->prepare("
            SELECT 
                dr.id,
                dr.damage_type,
                dr.damage_description,
                dr.fine_amount,
                dr.status,
                dr.created_at,
                m.name as member_name,
                m.nisn,
                b.title as book_title,
                b.author as book_author,
                br.borrowed_at,
                br.due_at
            FROM book_damage_fines dr
            JOIN borrows br ON dr.borrow_id = br.id
            JOIN members m ON br.member_id = m.id
            JOIN books b ON br.book_id = b.id
            WHERE dr.school_id = :sid
            ORDER BY dr.created_at DESC
        ");
        
        $stmt->execute(['sid' => $school_id]);
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Map damage types to match DamageFineModel
        $damageTypes = [
            'minor_tear' => 'Robekan Kecil',
            'major_tear' => 'Robekan Besar',
            'water_damage' => 'Rusak Terkena Air',
            'stain' => 'Noda/Kotoran',
            'cover_damage' => 'Kerusakan Sampul',
            'spine_damage' => 'Kerusakan Tulang Punggung',
            'missing_pages' => 'Halaman Hilang',
            'other' => 'Lainnya'
        ];
        
        $data = [];
        foreach ($reports as $report) {
            $data[] = [
                'id' => $report['id'],
                'member_name' => htmlspecialchars($report['member_name']),
                'nisn' => htmlspecialchars($report['nisn'] ?? '-'),
                'book_title' => htmlspecialchars($report['book_title']),
                'book_author' => htmlspecialchars($report['book_author'] ?? '-'),
                'damage_type' => $damageTypes[$report['damage_type']] ?? $report['damage_type'],
                'damage_description' => htmlspecialchars($report['damage_description'] ?? '-'),
                'fine_amount' => $report['fine_amount'],
                'fine_formatted' => 'Rp ' . number_format($report['fine_amount'], 0, ',', '.'),
                'status' => $report['status'] === 'paid' ? 'Lunas' : 'Tertunda',
                'status_class' => $report['status'],
                'created_at' => date('d M Y H:i', strtotime($report['created_at'])),
                'borrowed_at' => date('d M Y', strtotime($report['borrowed_at']))
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $data,
            'total' => count($data)
        ]);
    } catch (Exception $e) {
        error_log('get-maintenance-reports.php Error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
} catch (Exception $e) {
    error_log('get-maintenance-reports.php Fatal Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred'
    ]);
}
?>
