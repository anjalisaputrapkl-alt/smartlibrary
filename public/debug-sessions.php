<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];
?>
<!DOCTYPE html>
<html>

<head>
    <title>Debug Barcode Sessions</title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
        }

        table {
            border-collapse: collapse;
            margin: 20px 0;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        th {
            background: #f5f5f5;
        }

        .active {
            background: #d4edda;
        }

        .expired {
            background: #f8d7da;
        }
    </style>
</head>

<body>
    <h1>Debug: Barcode Sessions</h1>

    <h2>Latest Sessions (School ID: <?php echo $sid; ?>)</h2>
    <?php
    try {
        $stmt = $pdo->prepare('
            SELECT id, session_token, status, created_at, expires_at 
            FROM barcode_sessions 
            WHERE school_id = :sid
            ORDER BY created_at DESC
            LIMIT 10
        ');
        $stmt->execute(['sid' => $sid]);
        $sessions = $stmt->fetchAll();

        if ($sessions) {
            echo '<table>';
            echo '<tr><th>ID</th><th>Token</th><th>Status</th><th>Created</th><th>Expires</th></tr>';
            foreach ($sessions as $s) {
                $class = $s['status'] === 'active' ? 'active' : ($s['status'] === 'expired' ? 'expired' : '');
                echo "<tr class='$class'>";
                echo '<td>' . $s['id'] . '</td>';
                echo '<td>' . substr($s['session_token'], 0, 8) . '...</td>';
                echo '<td>' . $s['status'] . '</td>';
                echo '<td>' . $s['created_at'] . '</td>';
                echo '<td>' . $s['expires_at'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p>No sessions found</p>';
        }
    } catch (Exception $e) {
        echo '<p style="color: red;">Error: ' . $e->getMessage() . '</p>';
    }
    ?>

    <h2>Test Query</h2>
    <form method="POST">
        <input type="text" name="token" placeholder="Enter session token" value="<?php echo $_POST['token'] ?? ''; ?>">
        <button>Test Lookup</button>
    </form>

    <?php
    if ($_POST['token'] ?? false) {
        $token = $_POST['token'];
        $stmt = $pdo->prepare('
            SELECT * FROM barcode_sessions 
            WHERE session_token = :token
        ');
        $stmt->execute(['token' => $token]);
        $found = $stmt->fetch();

        if ($found) {
            echo '<p style="color: green;"><strong>✓ Session found!</strong></p>';
            echo '<pre>';
            print_r($found);
            echo '</pre>';
        } else {
            echo '<p style="color: red;"><strong>✗ Session NOT found</strong></p>';
            echo '<p>Token searched: ' . htmlspecialchars($token) . '</p>';
        }
    }
    ?>
</body>

</html>