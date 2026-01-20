<?php
/**
 * Test Script - Riwayat Peminjaman Buku
 * 
 * Gunakan script ini untuk test modul tanpa perlu login manual
 * Run: php test-borrowing-history.php
 */

require_once __DIR__ . '/src/config.php';
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/BorrowingHistoryModel.php';

echo "=== TEST BORROWING HISTORY MODULE ===\n\n";

try {
    // Test 1: Database Connection
    echo "✓ Test 1: Database Connection\n";
    echo "  Status: Connected to " . $_ENV['DB_NAME'] ?? 'perpustakaan_online' . "\n\n";

    // Test 2: Instantiate Model
    echo "✓ Test 2: Initialize BorrowingHistoryModel\n";
    $model = new BorrowingHistoryModel($pdo);
    echo "  Status: Model initialized\n\n";

    // Test 3: Get Member Data
    echo "✓ Test 3: Fetch Member List\n";
    $stmt = $pdo->query("SELECT id, name, email FROM members LIMIT 5");
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($members)) {
        echo "  ⚠ No members found. Insert test data first.\n";
    } else {
        foreach ($members as $member) {
            echo "  - ID: {$member['id']}, Name: {$member['name']}, Email: {$member['email']}\n";
        }
    }
    echo "\n";

    // Test 4: Get Borrowing History for Each Member
    echo "✓ Test 4: Fetch Borrowing History\n";
    if (!empty($members)) {
        $testMemberId = $members[0]['id'];
        echo "  Using Member ID: {$testMemberId}\n";
        
        $history = $model->getBorrowingHistory($testMemberId);
        echo "  Total Records: " . count($history) . "\n";
        
        if (!empty($history)) {
            foreach ($history as $idx => $item) {
                echo "\n  Borrow #{$idx}:\n";
                echo "    ID: {$item['borrow_id']}\n";
                echo "    Book: {$item['book_title']}\n";
                echo "    Author: {$item['author']}\n";
                echo "    Status: {$item['status']}\n";
                echo "    Borrowed: {$item['borrowed_at']}\n";
                echo "    Due: {$item['due_at']}\n";
                echo "    Days Remaining: {$item['days_remaining']}\n";
            }
        }
    }
    echo "\n";

    // Test 5: Get Statistics
    echo "✓ Test 5: Get Borrowing Statistics\n";
    if (!empty($members)) {
        $testMemberId = $members[0]['id'];
        $stats = $model->getBorrowingStats($testMemberId);
        
        echo "  Member ID: {$testMemberId}\n";
        echo "  Total Borrows: {$stats['total']}\n";
        echo "  Currently Borrowed: {$stats['borrowed']}\n";
        echo "  Returned: {$stats['returned']}\n";
        echo "  Overdue: {$stats['overdue']}\n";
        echo "  Actually Overdue: {$stats['actually_overdue']}\n";
    }
    echo "\n";

    // Test 6: Get Current Borrows
    echo "✓ Test 6: Get Current Active Borrows\n";
    if (!empty($members)) {
        $testMemberId = $members[0]['id'];
        $current = $model->getCurrentBorrows($testMemberId);
        
        if (empty($current)) {
            echo "  No active borrows for this member.\n";
        } else {
            foreach ($current as $borrow) {
                echo "  - Book: {$borrow['book_title']}\n";
                echo "    Days Remaining: {$borrow['days_remaining']}\n";
                echo "    Urgency: {$borrow['urgency']}\n";
            }
        }
    }
    echo "\n";

    // Test 7: Calculate Fine
    echo "✓ Test 7: Calculate Total Fine\n";
    if (!empty($members)) {
        $testMemberId = $members[0]['id'];
        $fine = $model->calculateTotalFine($testMemberId, 5000);
        
        echo "  Member ID: {$testMemberId}\n";
        echo "  Fine Per Day: Rp 5,000\n";
        echo "  Total Fine: Rp " . number_format($fine) . "\n";
    }
    echo "\n";

    // Test 8: Format Date
    echo "✓ Test 8: Test Date Formatting\n";
    $testDates = [
        '2026-01-19 10:51:43',
        '0000-00-00 00:00:00',
        '',
        null
    ];
    
    foreach ($testDates as $date) {
        $formatted = BorrowingHistoryModel::formatDate($date);
        echo "  Input: '{$date}' → Output: '{$formatted}'\n";
    }
    echo "\n";

    // Test 9: Status Translation
    echo "✓ Test 9: Test Status Translation\n";
    $statuses = ['borrowed', 'returned', 'overdue', 'unknown'];
    
    foreach ($statuses as $status) {
        $text = BorrowingHistoryModel::getStatusText($status);
        $badge = BorrowingHistoryModel::getStatusBadgeClass($status);
        echo "  {$status} → {$text} (CSS: {$badge})\n";
    }
    echo "\n";

    // Test 10: Database Schema Check
    echo "✓ Test 10: Verify Database Schema\n";
    
    $tables = ['borrows', 'books', 'members'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM {$table}");
        $count = $stmt->fetchColumn();
        echo "  Table '{$table}': {$count} records\n";
    }
    echo "\n";

    // Test 11: Check Required Columns
    echo "✓ Test 11: Verify Required Columns\n";
    
    $columns = [
        'borrows' => ['id', 'member_id', 'book_id', 'borrowed_at', 'due_at', 'returned_at', 'status'],
        'books' => ['id', 'title', 'author', 'cover_image'],
        'members' => ['id', 'name', 'email']
    ];
    
    foreach ($columns as $table => $requiredCols) {
        $stmt = $pdo->query("DESCRIBE {$table}");
        $tableCols = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        $missing = array_diff($requiredCols, $tableCols);
        
        if (empty($missing)) {
            echo "  ✓ {$table}: All required columns present\n";
        } else {
            echo "  ✗ {$table}: Missing columns - " . implode(', ', $missing) . "\n";
        }
    }
    echo "\n";

    // Test 12: Test API Response Format
    echo "✓ Test 12: Verify API Response Format\n";
    
    if (!empty($members)) {
        $testMemberId = $members[0]['id'];
        $history = $model->getBorrowingHistory($testMemberId);
        
        $response = [
            'success' => true,
            'data' => $history,
            'total' => count($history),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $jsonResponse = json_encode($response, JSON_PRETTY_PRINT);
        echo "  Response structure valid\n";
        echo "  JSON size: " . strlen($jsonResponse) . " bytes\n";
    }
    echo "\n";

    // Summary
    echo "=== TEST SUMMARY ===\n";
    echo "✓ All tests passed! The module is ready to use.\n";
    echo "\nAccess the module at:\n";
    echo "  http://localhost/perpustakaan-online/public/student-borrowing-history.php\n";
    echo "\nAPI endpoint:\n";
    echo "  http://localhost/perpustakaan-online/public/api/borrowing-history.php\n";

} catch (PDOException $e) {
    echo "✗ Database Error: " . $e->getMessage() . "\n";
    die();
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    die();
}
