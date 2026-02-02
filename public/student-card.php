<?php
// No output before this point
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Load auth helpers (this will handle session_start internally)
require __DIR__ . '/../src/auth.php';

// Initialize database
try {
    $pdo = require __DIR__ . '/../src/db.php';
} catch (Exception $e) {
    error_log("DB Error: " . $e->getMessage());
}

// Check if preview mode is enabled (localhost only, for development)
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost']);
$isPreviewMode = isset($_GET['preview']) && $_GET['preview'] === '1' && $isLocalhost;

$member = null;
$user = $_SESSION['user'] ?? null;

// Route 1: Normal authenticated user - get from session
if ($user && !empty($user['id'])) {
    $member = $user; // Use session data directly
    
    // Enrich with database data if available
    if (isset($pdo)) {
        try {
            // Fetch school info and siswa-specific fields from database
            $stmt = $pdo->prepare(
                'SELECT u.id, u.name, u.nisn, u.school_id,
                        s.student_uuid AS student_uuid, s.foto AS foto,
                        sch.name AS school_name, sch.address AS location, sch.logo AS school_logo
                 FROM users u
                 LEFT JOIN siswa s ON s.id_siswa = u.id
                 LEFT JOIN schools sch ON u.school_id = sch.id
                 WHERE u.id = :id
                 LIMIT 1'
            );
            $stmt->execute(['id' => (int)$user['id']]);
            $dbData = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($dbData) {
                // Merge database data with session data
                $member = array_merge($member, $dbData);
            }
        } catch (Exception $e) {
            error_log("Database query error in student-card: " . $e->getMessage());
        }
    }
}

// Route 2: Preview mode (localhost development only)
if (!$member && $isPreviewMode && isset($pdo)) {
    try {
            $stmt = $pdo->query(
            'SELECT u.id, u.name, u.nisn, u.school_id,
                s.student_uuid AS student_uuid, s.foto AS foto,
                sch.name AS school_name, sch.address AS location, sch.logo AS school_logo
             FROM users u
             LEFT JOIN siswa s ON s.id_siswa = u.id
             LEFT JOIN schools sch ON u.school_id = sch.id
             ORDER BY u.id ASC LIMIT 1'
        );
        if ($stmt) {
            $member = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($member) {
                // Ensure session user has minimal fields
                $_SESSION['user'] = ['id' => $member['id'], 'school_id' => $member['school_id'] ?? null, 'name' => $member['name'], 'nisn' => $member['nisn'] ?? null];
            }
        }
    } catch (Exception $e) {
        error_log("Preview mode query error: " . $e->getMessage());
    }
}

// No member found: require authentication
if (!$member) {
    // Redirect ke login
    header('Location: index.php', true, 302);
    exit;
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kartu Pelajar Digital - Perpustakaan Online</title>
    <script src="../assets/js/theme-loader.js"></script>
    <script src="../assets/js/theme.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../assets/css/animations.css">
    <style>
        * { box-sizing: border-box; margin:0; padding:0 }
        html,body { height:100% }
        body {
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
            background: #f9fafb;
            color: #1f1f1f;
            padding: 24px;
        }

        .container { max-width:900px; margin:0 auto }

        .header { text-align:left; margin: 0 0 24px }
        .header h1 { font-size:18px; font-weight:700; color:#1f1f1f; }
        .header p { color:#6b6b6b; font-size:13px }

        .card-container { display:flex; justify-content:center; padding:24px 0 }

        /* Rectangular Student Card - ID Card Style */
        .student-card {
            width:100%; max-width:900px; position:relative; overflow:hidden;
            background: #f5f7fa;
            border: 1px solid #e1e4e8;
            border-radius: 12px;
            padding: 32px 36px;
            display:flex; flex-direction:row; gap:36px; align-items:center;
        }

        .student-card::before,
        .student-card::after { display:none }

        /* Left: photo area */
        .card-left { 
            flex-shrink: 0;
            display:flex; flex-direction:column; align-items:center; gap:12px;
        }
        .photo-frame { 
            width:140px; height:140px; border-radius:50%;
            background: white;
            display:flex; align-items:center; justify-content:center;
            border: 2px solid #e1e4e8;
            position:relative; overflow:hidden;
        }
        .photo-frame img { width:100%; height:100%; object-fit:cover; border-radius:50%; display:block }
        .photo-ring { display:none }

        /* Middle: main info */
        .card-middle { 
            flex:1; display:flex; flex-direction:column; gap:14px;
        }
        .brand { 
            display:flex; flex-direction:column; gap:6px;
        }
        .brand-logo { 
            width:44px; height:44px; border-radius:8px; overflow:hidden; display:flex; 
            align-items:center; justify-content:center; background:white; border:1px solid #e1e4e8;
        }
        .brand-logo img { width:100%; height:100%; object-fit:contain; padding:4px }
        .brand-text { display:flex; flex-direction:column; gap:2px }
        .school-name { font-size:15px; font-weight:700; color:#1f1f1f; line-height:1.3 }
        .school-location { font-size:12px; color:#6b6b6b }

        .student-name { font-size:18px; font-weight:700; color:#1f1f1f }
        .student-nisn { font-size:12px; font-weight:600; color:#3a7afe; letter-spacing:0.5px }

        .info-grid { 
            display:grid; grid-template-columns:1fr 1fr; gap:18px 24px; margin-top:10px;
        }
        .info-item { display:flex; flex-direction:column; gap:3px }
        .info-label { font-size:10px; color:#6b6b6b; text-transform:uppercase; font-weight:700; letter-spacing:0.3px }
        .info-value { font-size:13px; font-weight:700; color:#1f1f1f }

        /* Right: QR box */
        .card-right { 
            flex-shrink: 0;
            display:flex; flex-direction:column; align-items:center; gap:10px;
        }
        .qr-box { background:white; padding:8px; border-radius:8px; border: 1px solid #e1e4e8 }
        .qr-box img { width:120px; height:120px; object-fit:contain; display:block }
        .card-footer { text-align:center; font-size:11px; color:#6b6b6b }


        /* Buttons area below card */
        .actions { display:flex; gap:10px; margin-top:24px; justify-content:center; flex-wrap:wrap }
        .btn { padding:10px 16px; border-radius:8px; font-weight:600; font-size:13px; cursor:pointer; text-decoration:none; transition:all .2s ease; border:none; display:inline-flex; align-items:center; gap:6px }
        .btn-primary { background:#3a7afe; color:white; }
        .btn-primary:hover { background:#2563eb; }
        .btn-secondary { background:white; color:#1f1f1f; border:1px solid #e1e4e8; }
        .btn-secondary:hover { background:#f5f7fa; }

        .back-link { color:#3a7afe; text-decoration:none; font-size:13px; font-weight:600; display:inline-flex; align-items:center; gap:6px; margin-bottom:16px }
        .back-link:hover { text-decoration:underline }

        .info-box { background:white; border:1px solid #e1e4e8; border-radius:8px; padding:16px; margin-top:24px }
        .info-box h3 { font-size:13px; font-weight:700; color:#1f1f1f; display:flex; align-items:center; gap:8px; margin-bottom:8px }
        .info-box p { font-size:12px; color:#6b6b6b; line-height:1.5 }

        /* Responsive - stack on mobile */
        @media (max-width:768px){
            .student-card{ 
                flex-direction:column; 
                text-align:center;
                gap:16px;
                padding:24px 20px;
            }
            .card-left { align-items:center }
            .card-middle { gap:12px }
            .brand-text { align-items:center }
            .info-grid { grid-template-columns:1fr 1fr; gap:12px 16px }
            .card-right { gap:12px; margin-top:8px; padding-top:16px; border-top:1px solid #e1e4e8 }
        }

        /* Dark mode */
        @media (prefers-color-scheme: dark){
            body{ background:#0f172a; color:#f1f5f9 }
            .student-card{ background:#1e293b; border-color:#334155 }
            .photo-frame, .qr-box, .brand-logo, .info-box { background:#334155; border-color:#475569 }
            .school-name, .student-name, .info-value, .info-box h3 { color:#f1f5f9 }
            .school-location, .card-footer, .info-label, .info-box p { color:#cbd5e1 }
            .student-nisn { color:#60a5fa }
            .btn-secondary { background:#334155; color:#f1f5f9; border-color:#475569 }
            .btn-secondary:hover { background:#475569 }
            .back-link { color:#60a5fa }
        }

        /* Print rules */
        @media print{
            body{ background:white; padding:0 }
            .header, .actions, .back-link, .info-box { display:none }
            .student-card{ box-shadow:none; border-color:#d0d0d0; background:white }
            .container { max-width:100% }
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="profil.php" class="back-link">
            <iconify-icon icon="mdi:arrow-left"></iconify-icon>
            Kembali ke Profil
        </a>

        <div class="header">
            <h1>Kartu Pelajar Digital</h1>
            <p>Tunjukkan barcode untuk peminjaman buku di perpustakaan</p>
        </div>

        <div class="card-container">
            <div class="student-card" id="card-root">
                <!-- Photo Section -->
                <div class="card-left">
                    <div class="photo-frame">
                        <?php
                        // Handle both users.foto and siswa.foto paths
                        $photoSrc = '';
                        if (!empty($member['foto'])) {
                            $photoPath = $member['foto'];
                            if (strpos($photoPath, 'http') === 0 || strpos($photoPath, '/') === 0) {
                                $photoSrc = htmlspecialchars($photoPath);
                            } elseif (strpos($photoPath, 'uploads/') === 0) {
                                $photoSrc = htmlspecialchars('../' . $photoPath);
                            } elseif (strpos($photoPath, '../uploads/') === 0) {
                                $photoSrc = htmlspecialchars($photoPath);
                            } else {
                                $photoSrc = htmlspecialchars($photoPath);
                            }
                        } else {
                            $photoSrc = '../assets/images/default-avatar.svg';
                        }
                        ?>
                        <img id="studentPhoto" src="<?= $photoSrc ?>" alt="Foto Siswa">
                    </div>
                </div>

                <!-- Info Section -->
                <div class="card-middle">
                    <!-- School Brand -->
                    <div class="brand">
                        <div style="display:flex; align-items:center; gap:10px; width:100%;">
                            <div class="brand-logo">
                                <?php if (!empty($member['school_logo'])): ?>
                                    <img src="<?= htmlspecialchars($member['school_logo']) ?>" alt="Logo">
                                <?php else: ?>
                                    <div style="font-size:18px;">📚</div>
                                <?php endif; ?>
                            </div>
                            <div class="brand-text">
                                <div class="school-name">
                                    <?php 
                                    $schoolName = $member['school_name'] ?? null;
                                    if (empty($schoolName)) {
                                        $schoolName = 'Perpustakaan Sekolah';
                                    }
                                    echo htmlspecialchars($schoolName);
                                    ?>
                                </div>
                                <div class="school-location"><?= htmlspecialchars($member['location'] ?? '') ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Student Name & NISN -->
                    <div>
                        <div class="student-name"><?= htmlspecialchars($member['name']) ?></div>
                        <div class="student-nisn">NISN: <?= htmlspecialchars($member['nisn'] ?? '-') ?></div>
                    </div>

                    <!-- Info Grid -->
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Kelas</div>
                            <div class="info-value"><?= htmlspecialchars($member['class_field'] ?? $member['grade_field'] ?? '-') ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">ID Siswa</div>
                            <div class="info-value"><?= htmlspecialchars($member['student_uuid'] ?? $member['id']) ?></div>
                        </div>
                    </div>
                </div>

                <!-- QR Section -->
                <div class="card-right">
                    <div class="qr-box">
                        <img id="studentQR" src="api/generate-qrcode.php?type=member&value=<?= urlencode((string)($member['student_uuid'] ?? $member['id'])) ?>&size=100" alt="QR">
                    </div>
                    <div class="card-footer"><?= date('Y') ?></div>
                </div>
            </div>
        </div>

        <div class="actions">
            <button id="downloadPdfBtn" class="btn btn-primary">
                <iconify-icon icon="mdi:download"></iconify-icon>
                Download PDF
            </button>
            <label for="photoUploadInput" class="btn btn-secondary" style="cursor:pointer;">
                <iconify-icon icon="mdi:camera"></iconify-icon>
                Unggah Foto
            </label>
            <a href="student-dashboard.php" class="btn btn-secondary">
                <iconify-icon icon="mdi:home"></iconify-icon>
                Kembali
            </a>
        </div>

        <div class="info-box">
            <h3>
                <iconify-icon icon="mdi:information-outline"></iconify-icon>
                Panduan Penggunaan
            </h3>
            <p>
                Gunakan kartu ini untuk memproses peminjaman buku di perpustakaan. Petugas perpustakaan akan memindai QR code untuk memverifikasi identitas Anda.
            </p>
        </div>

        <input id="photoUploadInput" type="file" accept="image/*" style="display:none"> 

    </div>

    <script>
        // Download printable view (user can Save as PDF in browser)
        document.getElementById('downloadPdfBtn').addEventListener('click', function () {
            const card = document.getElementById('card-root');
            const w = window.open('', '_blank');
            const styles = Array.from(document.querySelectorAll('style, link[rel="stylesheet"]')).map(n => n.outerHTML).join('\n');
            const html = `<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">${styles}<title>Kartu Pelajar</title></head><body style="margin:0;padding:20px;background:#fff;">${card.outerHTML}</body></html>`;
            w.document.open();
            w.document.write(html);
            w.document.close();
            // Wait for images to load then print
            w.onload = function () {
                setTimeout(() => { w.print(); }, 500);
            };
        });

        // Photo upload handler
        const photoInput = document.getElementById('photoUploadInput');
        photoInput.addEventListener('change', async function (e) {
            const file = this.files[0];
            if (!file) return;
            if (!confirm('Unggah foto baru? Pastikan foto jelas dan sesuai.')) return;

            const fd = new FormData();
            fd.append('photo', file);
            fd.append('action', 'upload_photo');

            try {
                const res = await fetch('api/upload-photo.php', {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                });
                
                // Check response status first
                if (!res.ok) {
                    const errorText = await res.text();
                    console.error('HTTP Error:', res.status, errorText);
                    alert('Gagal: HTTP ' + res.status);
                    return;
                }
                
                // Parse JSON
                let data;
                try {
                    data = await res.json();
                } catch (parseErr) {
                    const responseText = await res.clone().text();
                    console.error('Parse error:', parseErr);
                    console.error('Response was:', responseText);
                    alert('Respons tidak valid. Cek console untuk detail.');
                    return;
                }
                
                if (data.success && data.path) {
                    // Update photo on card with cache buster
                    const photoImg = document.getElementById('studentPhoto');
                    const newSrc = data.path + '?t=' + Date.now();
                    console.log('Photo updated:', newSrc);
                    photoImg.src = newSrc;
                    alert('Foto berhasil diperbarui!');
                } else {
                    alert('Gagal: ' + (data.message || 'Kesalahan tidak diketahui'));
                }
            } catch (err) {
                console.error('Upload error:', err);
                alert('Terjadi kesalahan: ' + err.message);
            }
        });

        // Clicking the label opens the file selector
        document.querySelector('label[for="photoUploadInput"]').addEventListener('click', function () {
            photoInput.value = null;
        });
    </script>
</body>

</html>
