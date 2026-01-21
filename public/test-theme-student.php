<?php
/**
 * TEST: Simple Student Page with Theme
 * Akses: http://localhost/perpustakaan-online/public/test-theme-student.php
 */
session_start();

// Simulasi login siswa
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'id' => 1,
        'school_id' => 1,
        'nama_lengkap' => 'Test Student',
        'role' => 'student'
    ];
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Test Tema Siswa</title>
    <script src="../assets/js/db-theme-loader.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg: #f1f4f8;
            --surface: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --border: #e5e7eb;
            --accent: #2563eb;
            --danger: #dc2626;
            --success: #16a34a;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: var(--surface);
            padding: 20px;
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        h1 {
            color: var(--accent);
            margin-bottom: 20px;
        }

        .info-box {
            background: var(--bg);
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            border-left: 4px solid var(--accent);
        }

        .color-sample {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .color-item {
            padding: 10px 15px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .bg {
            background: var(--bg);
            border: 1px solid var(--border);
        }

        .surface {
            background: var(--surface);
            border: 1px solid var(--border);
        }

        .accent {
            background: var(--accent);
            color: white;
        }

        .success {
            background: var(--success);
            color: white;
        }

        .danger {
            background: var(--danger);
            color: white;
        }

        .debug-info {
            background: #f0f0f0;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            margin-top: 20px;
        }

        button {
            background: var(--accent);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }

        button:hover {
            opacity: 0.9;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🎨 Test Tema Siswa</h1>

        <div class="info-box">
            <h2>Informasi Halaman</h2>
            <p><strong>Status Login:</strong>
                <?php echo isset($_SESSION['user']) ? '✓ Logged In' : '✗ Not Logged In'; ?></p>
            <p><strong>School ID:</strong> <?php echo $_SESSION['user']['school_id'] ?? 'N/A'; ?></p>
            <p><strong>User:</strong> <?php echo $_SESSION['user']['nama_lengkap'] ?? 'Student'; ?></p>
        </div>

        <div class="info-box">
            <h2>Status Script</h2>
            <p><strong>db-theme-loader.js:</strong> ✓ Loaded</p>
            <p><strong>Script akan:</strong></p>
            <ol style="margin-left: 20px;">
                <li>Fetch tema dari <code>./api/student-theme.php</code></li>
                <li>Simpan ke localStorage sebagai: <code>theme</code></li>
                <li>Apply CSS variables ke halaman</li>
            </ol>
        </div>

        <div class="info-box">
            <h2>Color Samples</h2>
            <div class="color-sample">
                <div class="color-item bg">BG</div>
                <div class="color-item surface">SURFACE</div>
                <div class="color-item accent">ACCENT</div>
                <div class="color-item success">SUCCESS</div>
                <div class="color-item danger">DANGER</div>
            </div>
        </div>

        <div style="margin-top: 20px;">
            <button onclick="testAPI()">Test API Call</button>
            <button onclick="checkLocalStorage()">Check LocalStorage</button>
            <button onclick="checkCSSVars()">Check CSS Variables</button>
        </div>

        <div class="debug-info" id="debug"></div>
    </div>

    <script>
        function testAPI() {
            const debug = document.getElementById('debug');
            debug.innerHTML = '<strong>Testing API...</strong><br>';

            fetch('./api/student-theme.php')
                .then(r => r.json())
                .then(data => {
                    debug.innerHTML += '<strong>✓ API Response:</strong><br>';
                    debug.innerHTML += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                })
                .catch(e => {
                    debug.innerHTML += '<strong style="color: red;">✗ Error:</strong> ' + e.message;
                });
        }

        function checkLocalStorage() {
            const debug = document.getElementById('debug');
            const theme = localStorage.getItem('theme');
            debug.innerHTML = '<strong>LocalStorage:</strong><br>';
            debug.innerHTML += 'theme = ' + (theme || 'NOT SET') + '<br>';
            debug.innerHTML += '<pre>' + JSON.stringify(localStorage, null, 2) + '</pre>';
        }

        function checkCSSVars() {
            const debug = document.getElementById('debug');
            const root = document.documentElement;
            const vars = ['--bg', '--surface', '--text', '--accent', '--success', '--danger'];
            debug.innerHTML = '<strong>CSS Variables:</strong><br>';
            vars.forEach(v => {
                const val = root.style.getPropertyValue(v).trim();
                debug.innerHTML += `${v} = ${val || 'DEFAULT'}<br>`;
            });
        }

        // Auto check on load
        window.addEventListener('load', function () {
            setTimeout(() => {
                checkCSSVars();
                checkLocalStorage();
            }, 500);
        });
    </script>
</body>

</html>