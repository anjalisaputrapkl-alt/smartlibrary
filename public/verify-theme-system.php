<?php
/**
 * VERIFICATION: Test Alur Tema Lengkap
 * Step-by-step untuk verifikasi sistem tema bekerja
 */
session_start();
$pdo = require __DIR__ . '/../src/db.php';
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Sistem Tema</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
        }

        .card {
            background: white;
            margin-bottom: 15px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .card-title {
            background: #34495e;
            color: white;
            padding: 15px;
            font-weight: 600;
            font-size: 16px;
        }

        .card-body {
            padding: 15px;
        }

        .step {
            margin: 15px 0;
            padding: 12px;
            background: #f8f9fa;
            border-left: 4px solid #3498db;
            border-radius: 4px;
        }

        .step-num {
            display: inline-block;
            background: #3498db;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            margin-right: 10px;
            font-weight: 600;
        }

        code {
            background: #f1f1f1;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New';
        }

        .success {
            color: #27ae60;
            font-weight: 600;
        }

        .warning {
            color: #e74c3c;
            font-weight: 600;
        }

        .info {
            color: #2980b9;
            font-weight: 600;
        }

        button {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }

        button:hover {
            background: #2980b9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #ecf0f1;
            font-weight: 600;
        }

        .ok {
            color: #27ae60;
        }

        .fail {
            color: #e74c3c;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <div class="card-title">🎨 Verifikasi Sistem Tema Multi-Tenant</div>
            <div class="card-body">
                <div style="margin-bottom: 20px;">
                    <h3>📋 Checklist Implementasi</h3>
                    <table>
                        <tr>
                            <th>Komponen</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                        </tr>
                        <tr>
                            <td>ThemeModel.php</td>
                            <td class="ok">✓</td>
                            <td>Class untuk manage tema di database</td>
                        </tr>
                        <tr>
                            <td>API student-theme.php</td>
                            <td class="ok">✓</td>
                            <td>Fetch tema dari database</td>
                        </tr>
                        <tr>
                            <td>db-theme-loader.js</td>
                            <td class="ok">✓</td>
                            <td>Load tema + apply CSS variables</td>
                        </tr>
                        <tr>
                            <td>Settings.php UI</td>
                            <td class="ok">✓</td>
                            <td>Tombol tema untuk admin</td>
                        </tr>
                        <tr>
                            <td>Halaman Siswa</td>
                            <td class="ok">✓</td>
                            <td>Script injected di semua halaman</td>
                        </tr>
                    </table>
                </div>

                <div style="margin: 20px 0;">
                    <h3>🧪 Test Steps</h3>

                    <div class="step">
                        <span class="step-num">1</span>
                        <strong>Admin Set Tema</strong>
                        <p style="margin-top: 8px;">
                            Buka: <code>/public/settings.php</code><br>
                            Login sebagai admin<br>
                            Pilih tema: <strong>Dark</strong> (atau tema lain)<br>
                            Klik tombol tema tersebut<br>
                            Tunggu pesan success: "Tema sekolah berhasil disimpan..."
                        </p>
                    </div>

                    <div class="step">
                        <span class="step-num">2</span>
                        <strong>Verifikasi Database</strong>
                        <p style="margin-top: 8px;">
                            Buka phpMyAdmin atau tools database lain<br>
                            Cek tabel: <code>school_themes</code><br>
                            Lihat record dengan school_id kamu<br>
                            <strong class="success">✓ theme_name harus = "dark"</strong>
                        </p>
                    </div>

                    <div class="step">
                        <span class="step-num">3</span>
                        <strong>Logout Admin & Login Siswa</strong>
                        <p style="margin-top: 8px;">
                            Logout dari admin<br>
                            Login sebagai siswa (sekolah yang sama)<br>
                            Buka halaman siswa apapun: <code>/public/student-dashboard.php</code>
                        </p>
                    </div>

                    <div class="step">
                        <span class="step-num">4</span>
                        <strong>Verifikasi Tema Berubah</strong>
                        <p style="margin-top: 8px;">
                            <strong class="success">✓ Halaman harus berubah ke tema Dark</strong><br>
                            Sidebar, background, text color semuanya berubah<br>
                            Design/layout tetap sama, hanya warna yang berubah
                        </p>
                    </div>

                    <div class="step">
                        <span class="step-num">5</span>
                        <strong>Browser Console Check (F12)</strong>
                        <p style="margin-top: 8px;">
                            Tekan F12 → buka Console tab<br>
                            Lihat pesan: <code class="success">✓ Theme applied: dark</code><br>
                            Jalankan: <code>localStorage.getItem('theme')</code><br>
                            Output harus: <code>"dark"</code>
                        </p>
                    </div>

                    <div class="step">
                        <span class="step-num">6</span>
                        <strong>Test Multi-Tenant (Optional)</strong>
                        <p style="margin-top: 8px;">
                            Jika punya sekolah lain (school_id = 2):<br>
                            Set tema sekolah 2 = "Light"<br>
                            Login siswa sekolah 2<br>
                            <strong class="success">✓ Tema harus Light (bukan Dark)</strong>
                        </p>
                    </div>
                </div>

                <div style="margin-top: 20px; padding: 15px; background: #ecf0f1; border-radius: 4px;">
                    <h3>⚙️ Debug Info</h3>
                    <p><strong>Current User Session:</strong></p>
                    <pre style="background: white; padding: 10px; border-radius: 4px; overflow-x: auto;">
<?php
if (isset($_SESSION['user'])) {
    echo "✓ School ID: " . htmlspecialchars($_SESSION['user']['school_id']) . "\n";
    echo "✓ User: " . htmlspecialchars($_SESSION['user']['nama_lengkap'] ?? 'N/A') . "\n";
} else {
    echo "✗ Not logged in (session empty)\n";
    echo "   To test, login first!\n";
}
?>
                    </pre>
                </div>

                <div style="margin-top: 20px; padding: 15px; background: #d5f4e6; border-radius: 4px;">
                    <h3>✅ Alur Kerja Final</h3>
                    <ol style="margin-left: 20px; line-height: 1.8;">
                        <li><strong>Admin</strong> ubah tema di <code>/settings.php</code></li>
                        <li>Tema disimpan ke <code>school_themes</code> table dengan <code>school_id</code></li>
                        <li><strong>Siswa</strong> buka halaman apapun</li>
                        <li>Script <code>db-theme-loader.js</code> otomatis:
                            <ul style="margin: 10px 0 0 20px;">
                                <li>Fetch tema dari API <code>/api/student-theme.php</code></li>
                                <li>Apply CSS variables ke halaman</li>
                                <li>Halaman langsung berubah warna sesuai tema admin!</li>
                            </ul>
                        </li>
                        <li>Design/layout tetap sama, hanya warna yang mengikuti tema</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</body>

</html>