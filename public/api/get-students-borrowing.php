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
                m.id,
                m.name,
                m.nisn,
                m.email,
                m.status,
                m.created_at,
                (SELECT COUNT(*) FROM borrows WHERE member_id = m.id AND returned_at IS NULL AND school_id = :sid1) as current_borrows
            FROM members m
            WHERE m.school_id = :sid2 
            AND EXISTS (SELECT 1 FROM borrows WHERE member_id = m.id AND returned_at IS NULL AND school_id = :sid3)
            ORDER BY m.name ASC
        ");
        
        $stmt->execute(['sid1' => $school_id, 'sid2' => $school_id, 'sid3' => $school_id]);
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $data = [];
        foreach ($members as $member) {
            $data[] = [
                'id' => $member['id'],
                'name' => htmlspecialchars($member['name']),
                'nisn' => htmlspecialchars($member['nisn'] ?? '-'),
                'email' => htmlspecialchars($member['email'] ?? '-'),
                'status' => $member['status'] == 'active' ? 'Aktif' : 'Nonaktif',
                'current_borrows' => $member['current_borrows'],
                'joined_date' => date('d M Y', strtotime($member['created_at']))
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $data,
            'total' => count($data)
        ]);
    } catch (Exception $e) {
        error_log('get-students-borrowing.php Error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
} catch (Exception $e) {
    error_log('get-students-borrowing.php Fatal Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred'
    ]);
}
?>
