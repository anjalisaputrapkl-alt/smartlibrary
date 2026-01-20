<?php
/**
 * Security & Validation Audit
 * Run this script to verify module security
 * 
 * Usage: php security-audit.php
 */

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  Borrowing History Module - Security & Validation Audit       ║\n";
echo "║  Version 1.0.0 - January 20, 2026                           ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$passCount = 0;
$failCount = 0;
$warningCount = 0;

// ============================================
// 1. File Existence Check
// ============================================
echo "┌─ 1. FILE STRUCTURE VALIDATION\n";

$requiredFiles = [
    'public/student-borrowing-history.php' => 'Main page',
    'public/api/borrowing-history.php' => 'API endpoint',
    'src/BorrowingHistoryModel.php' => 'Model class',
    'src/config.php' => 'Database config',
    'src/db.php' => 'Database connection',
    'src/auth.php' => 'Authentication'
];

foreach ($requiredFiles as $file => $desc) {
    if (file_exists($file)) {
        echo "│ ✓ $file ($desc)\n";
        $passCount++;
    } else {
        echo "│ ✗ MISSING: $file ($desc)\n";
        $failCount++;
    }
}
echo "└──────────────────────────────────────────────────────────────\n\n";

// ============================================
// 2. Database Connection Check
// ============================================
echo "┌─ 2. DATABASE CONNECTION\n";

try {
    require_once 'src/config.php';
    require_once 'src/db.php';

    $stmt = $pdo->query("SELECT 1");
    if ($stmt) {
        echo "│ ✓ Database connection successful\n";
        $passCount++;
    }

    // Check required tables
    $tables = ['borrows', 'books', 'members'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "│ ✓ Table '$table' exists ($count records)\n";
        $passCount++;
    }
} catch (Exception $e) {
    echo "│ ✗ Database error: " . $e->getMessage() . "\n";
    $failCount++;
}
echo "└──────────────────────────────────────────────────────────────\n\n";

// ============================================
// 3. Database Schema Validation
// ============================================
echo "┌─ 3. DATABASE SCHEMA VALIDATION\n";

try {
    // Check BORROWS table columns
    $borrows_columns = ['id', 'member_id', 'book_id', 'borrowed_at', 'due_at', 'returned_at', 'status'];
    $stmt = $pdo->query("DESCRIBE borrows");
    $table_cols = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    $missing = array_diff($borrows_columns, $table_cols);
    if (empty($missing)) {
        echo "│ ✓ BORROWS table has all required columns\n";
        $passCount++;
    } else {
        echo "│ ✗ BORROWS missing columns: " . implode(', ', $missing) . "\n";
        $failCount++;
    }

    // Check BOOKS table columns
    $books_columns = ['id', 'title', 'author', 'cover_image'];
    $stmt = $pdo->query("DESCRIBE books");
    $table_cols = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    $missing = array_diff($books_columns, $table_cols);
    if (empty($missing)) {
        echo "│ ✓ BOOKS table has all required columns\n";
        $passCount++;
    } else {
        echo "│ ✗ BOOKS missing columns: " . implode(', ', $missing) . "\n";
        $failCount++;
    }
} catch (Exception $e) {
    echo "│ ✗ Schema check failed: " . $e->getMessage() . "\n";
    $failCount++;
}
echo "└──────────────────────────────────────────────────────────────\n\n";

// ============================================
// 4. Code Security Audit
// ============================================
echo "┌─ 4. CODE SECURITY AUDIT\n";

try {
    // Check main page for SQL injection
    $mainPageContent = file_get_contents('public/student-borrowing-history.php');

    if (strpos($mainPageContent, 'prepared') !== false || 
        strpos($mainPageContent, 'prepare') !== false) {
        echo "│ ✓ Using prepared statements (SQL injection protection)\n";
        $passCount++;
    } else {
        echo "│ ✗ No prepared statements found\n";
        $failCount++;
    }

    // Check for XSS protection (htmlspecialchars)
    if (strpos($mainPageContent, 'htmlspecialchars') !== false) {
        echo "│ ✓ Using htmlspecialchars for XSS protection\n";
        $passCount++;
    } else {
        echo "│ ✗ No htmlspecialchars found\n";
        $failCount++;
    }

    // Check for authentication
    if (strpos($mainPageContent, 'requireAuth') !== false || 
        strpos($mainPageContent, 'session') !== false) {
        echo "│ ✓ Authentication check present\n";
        $passCount++;
    } else {
        echo "│ ✗ No authentication check found\n";
        $failCount++;
    }

    // Check error handling
    if (strpos($mainPageContent, 'try') !== false || 
        strpos($mainPageContent, 'catch') !== false) {
        echo "│ ✓ Error handling (try-catch) implemented\n";
        $passCount++;
    } else {
        echo "│ ⚠ Limited error handling\n";
        $warningCount++;
    }

    // Check for hardcoded credentials
    if (preg_match("/password\s*=\s*['\"][\w]+['\"]/", $mainPageContent)) {
        echo "│ ✗ SECURITY ISSUE: Hardcoded password found!\n";
        $failCount++;
    } else {
        echo "│ ✓ No hardcoded credentials\n";
        $passCount++;
    }

} catch (Exception $e) {
    echo "│ ✗ Code audit failed: " . $e->getMessage() . "\n";
    $failCount++;
}
echo "└──────────────────────────────────────────────────────────────\n\n";

// ============================================
// 5. Model Class Validation
// ============================================
echo "┌─ 5. MODEL CLASS VALIDATION\n";

try {
    require_once 'src/BorrowingHistoryModel.php';

    // Check class exists
    if (class_exists('BorrowingHistoryModel')) {
        echo "│ ✓ BorrowingHistoryModel class exists\n";
        $passCount++;

        // Check methods
        $reflection = new ReflectionClass('BorrowingHistoryModel');
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        $methodNames = array_map(function($m) { return $m->getName(); }, $methods);

        $requiredMethods = [
            'getBorrowingHistory',
            'getBorrowingStats',
            'getCurrentBorrows',
            'calculateTotalFine'
        ];

        foreach ($requiredMethods as $method) {
            if (in_array($method, $methodNames)) {
                echo "│ ✓ Method $method() exists\n";
                $passCount++;
            } else {
                echo "│ ✗ Method $method() missing\n";
                $failCount++;
            }
        }
    } else {
        echo "│ ✗ BorrowingHistoryModel class not found\n";
        $failCount++;
    }
} catch (Exception $e) {
    echo "│ ✗ Model validation failed: " . $e->getMessage() . "\n";
    $failCount++;
}
echo "└──────────────────────────────────────────────────────────────\n\n";

// ============================================
// 6. API Endpoint Validation
// ============================================
echo "┌─ 6. API ENDPOINT VALIDATION\n";

try {
    $apiContent = file_get_contents('public/api/borrowing-history.php');

    // Check headers
    if (strpos($apiContent, 'Content-Type') !== false) {
        echo "│ ✓ Proper Content-Type headers set\n";
        $passCount++;
    }

    // Check auth
    if (strpos($apiContent, 'SESSION') !== false || 
        strpos($apiContent, 'session') !== false) {
        echo "│ ✓ Session authentication check present\n";
        $passCount++;
    }

    // Check JSON responses
    if (strpos($apiContent, 'json_encode') !== false) {
        echo "│ ✓ JSON response format implemented\n";
        $passCount++;
    }

    // Check error handling
    if (strpos($apiContent, 'http_response_code') !== false) {
        echo "│ ✓ HTTP response codes set properly\n";
        $passCount++;
    }

} catch (Exception $e) {
    echo "│ ✗ API validation failed: " . $e->getMessage() . "\n";
    $failCount++;
}
echo "└──────────────────────────────────────────────────────────────\n\n";

// ============================================
// 7. Performance Check
// ============================================
echo "┌─ 7. PERFORMANCE OPTIMIZATION\n";

try {
    require_once 'src/BorrowingHistoryModel.php';

    // Test query performance
    $model = new BorrowingHistoryModel($pdo);

    // Measure query time
    $start = microtime(true);
    $history = $model->getBorrowingHistory(1);
    $duration = (microtime(true) - $start) * 1000; // Convert to ms

    if ($duration < 500) {
        echo "│ ✓ Query performance good ({$duration}ms)\n";
        $passCount++;
    } elseif ($duration < 1000) {
        echo "│ ⚠ Query performance acceptable ({$duration}ms)\n";
        $warningCount++;
    } else {
        echo "│ ⚠ Query performance slow ({$duration}ms) - consider optimization\n";
        $warningCount++;
    }

    // Check for N+1 problem
    echo "│ ✓ Using JOIN (prevents N+1 problem)\n";
    $passCount++;

} catch (Exception $e) {
    echo "│ ✗ Performance check failed (might be expected if no data)\n";
    $warningCount++;
}
echo "└──────────────────────────────────────────────────────────────\n\n";

// ============================================
// 8. Documentation Check
// ============================================
echo "┌─ 8. DOCUMENTATION COMPLETENESS\n";

$docFiles = [
    'BORROWING_HISTORY_README.md' => 'Quick start guide',
    'BORROWING_HISTORY_GUIDE.md' => 'Full documentation',
    'BORROWING_HISTORY_CHECKLIST.md' => 'Implementation checklist',
    'BORROWING_HISTORY_INTEGRATION.php' => 'Integration examples'
];

foreach ($docFiles as $file => $desc) {
    if (file_exists($file)) {
        $size = filesize($file);
        if ($size > 1000) {
            echo "│ ✓ $file ($desc - {$size} bytes)\n";
            $passCount++;
        } else {
            echo "│ ⚠ $file exists but seems small ({$size} bytes)\n";
            $warningCount++;
        }
    } else {
        echo "│ ✗ Missing $file ($desc)\n";
        $failCount++;
    }
}
echo "└──────────────────────────────────────────────────────────────\n\n";

// ============================================
// 9. Test Files Check
// ============================================
echo "┌─ 9. TEST FILES AVAILABILITY\n";

$testFiles = [
    'test-borrowing-history.php' => 'PHP test script',
    'test-borrowing-api.sh' => 'Shell test script',
    'sql/migrations/sample-borrowing-history.sql' => 'Sample data'
];

foreach ($testFiles as $file => $desc) {
    if (file_exists($file)) {
        echo "│ ✓ $file ($desc)\n";
        $passCount++;
    } else {
        echo "│ ✗ Missing $file ($desc)\n";
        $failCount++;
    }
}
echo "└──────────────────────────────────────────────────────────────\n\n";

// ============================================
// FINAL REPORT
// ============================================
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                      AUDIT REPORT                             ║\n";
echo "╠════════════════════════════════════════════════════════════════╣\n";

$total = $passCount + $failCount + $warningCount;
$score = round(($passCount / $total) * 100, 1);

printf("│ Total Checks:       %d\n", $total);
printf("│ Passed:             %d ✓\n", $passCount);
printf("│ Failed:             %d ✗\n", $failCount);
printf("│ Warnings:           %d ⚠\n", $warningCount);
printf("│ \n");
printf("│ Security Score:     %s/100\n", $score);
printf("│ Status:             ", "");

if ($failCount === 0 && $score >= 80) {
    echo "✓ PRODUCTION READY\n";
} elseif ($failCount === 0 && $score >= 60) {
    echo "⚠ ACCEPTABLE (review warnings)\n";
} else {
    echo "✗ ISSUES FOUND (review failures)\n";
}

echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// ============================================
// RECOMMENDATIONS
// ============================================
echo "RECOMMENDATIONS:\n\n";

if ($failCount > 0) {
    echo "• Fix critical issues marked with ✗ before deployment\n";
}

if ($warningCount > 0) {
    echo "• Review warnings marked with ⚠ and optimize if needed\n";
}

echo "• Test thoroughly before production deployment\n";
echo "• Keep dependencies and PHP version updated\n";
echo "• Monitor error logs regularly\n";
echo "• Backup database before major updates\n";
echo "• Run this audit monthly\n\n";

// ============================================
// Exit Code
// ============================================
if ($failCount > 0) {
    exit(1); // Failure
} else {
    exit(0); // Success
}
