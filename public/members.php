<?php
require __DIR__ . '/../src/auth.php';
requireAuth();

$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];

$action = $_GET['action'] ?? 'list';

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    // Insert into members table
    $stmt = $pdo->prepare(
      'INSERT INTO members (school_id,name,email,nisn,role,max_pinjam)
       VALUES (:sid,:name,:email,:nisn,:role,:max_pinjam)'
    );
    $stmt->execute([
      'sid' => $sid,
      'name' => $_POST['name'],
      'email' => $_POST['email'],
      'nisn' => $_POST['nisn'],
      'role' => $_POST['role'] ?? 'student',
      'max_pinjam' => (int) ($_POST['max_pinjam'] ?? 2)
    ]);

    // Get the inserted NISN for password generation
    $nisn = $_POST['nisn'];
    $password = $_POST['password'];
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Create account in users table
    $userStmt = $pdo->prepare(
      'INSERT INTO users (school_id, name, email, password, role, nisn)
       VALUES (:sid, :name, :email, :password, :role, :nisn)'
    );
    $userStmt->execute([
      'sid' => $sid,
      'name' => $_POST['name'],
      'email' => $_POST['email'],
      'password' => $hashed_password,
      'role' => $_POST['role'] ?? 'student',
      'nisn' => $nisn
    ]);

    // Success message
    $_SESSION['success'] = 'Anggota berhasil ditambahkan. Akun otomatis terbuat dengan ' . ($_POST['role'] === 'student' ? 'NISN' : 'ID') . ': ' . $nisn;
    header('Location: members.php');
    exit;
  } catch (Exception $e) {
    $_SESSION['error'] = 'Gagal menambahkan murid: ' . $e->getMessage();
    header('Location: members.php');
    exit;
  }
}

if ($action === 'edit' && isset($_GET['id'])) {
  $id = (int) $_GET['id'];
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Ambil NISN lama sebelum update untuk acuan update ke tabel users
    $oldMemberStmt = $pdo->prepare('SELECT nisn FROM members WHERE id=:id AND school_id=:sid');
    $oldMemberStmt->execute(['id' => $id, 'sid' => $sid]);
    $oldMember = $oldMemberStmt->fetch();
    $oldNisn = $oldMember['nisn'] ?? $_POST['nisn'];

    // 2. Update tabel members
    $stmt = $pdo->prepare(
      'UPDATE members SET name=:name,email=:email,nisn=:nisn,role=:role,max_pinjam=:max_pinjam
       WHERE id=:id AND school_id=:sid'
    );
    $stmt->execute([
      'name' => $_POST['name'],
      'email' => $_POST['email'],
      'nisn' => $_POST['nisn'],
      'role' => $_POST['role'] ?? 'student',
      'max_pinjam' => (int) ($_POST['max_pinjam'] ?? 2),
      'id' => $id,
      'sid' => $sid
    ]);

    // 3. Update tabel users & siswa (Sinkronisasi Data)
    // Ambil user_id dulu berdasarkan NISN lama
    $getUserStmt = $pdo->prepare('SELECT id FROM users WHERE nisn = :nisn AND (role = "student" OR role = "teacher" OR role = "employee")');
    $getUserStmt->execute(['nisn' => $oldNisn]);
    $user = $getUserStmt->fetch();

    if ($user) {
        $userId = $user['id'];
        
        // A. Update Users
        $updateUserSql = 'UPDATE users SET name=:name, email=:email, nisn=:new_nisn';
        $updateUserParams = [
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'new_nisn' => $_POST['nisn'],
            'id' => $userId
        ];

        if (!empty($_POST['password'])) {
            $hashed_password = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $updateUserSql .= ', password=:password';
            $updateUserParams['password'] = $hashed_password;
        }

        $updateUserSql .= ' WHERE id=:id';
        $updateUserStmt = $pdo->prepare($updateUserSql);
        $updateUserStmt->execute($updateUserParams);

        // B. Update Siswa (Profile Data)
        // Periksa apakah record siswa ada
        $checkSiswa = $pdo->prepare('SELECT id_siswa FROM siswa WHERE id_siswa = :id');
        $checkSiswa->execute(['id' => $userId]);
        
        if ($checkSiswa->fetch()) {
            // Update existing
            $updateSiswaStmt = $pdo->prepare('UPDATE siswa SET nama_lengkap = :name, email = :email, nisn = :nisn WHERE id_siswa = :id');
            $updateSiswaStmt->execute([
                'name' => $_POST['name'],
                'email' => $_POST['email'],
                'nisn' => $_POST['nisn'],
                'id' => $userId
            ]);
        } else {
            // Create new if not exists (Lazy create)
            $insertSiswa = $pdo->prepare('INSERT INTO siswa (id_siswa, nama_lengkap, email, nisn) VALUES (:id, :name, :email, :nisn)');
            $insertSiswa->execute([
                'id' => $userId,
                'name' => $_POST['name'],
                'email' => $_POST['email'],
                'nisn' => $_POST['nisn']
            ]);
        }
    }

    header('Location: members.php');
    exit;
  }
  $stmt = $pdo->prepare('SELECT * FROM members WHERE id=:id AND school_id=:sid');
  $stmt->execute(['id' => $id, 'sid' => $sid]);
  $member = $stmt->fetch();
}

if ($action === 'delete' && isset($_GET['id'])) {
  try {
    // Get member data to find associated user
    $getMemberStmt = $pdo->prepare('SELECT email, nisn FROM members WHERE id=:id AND school_id=:sid');
    $getMemberStmt->execute(['id' => (int) $_GET['id'], 'sid' => $sid]);
    $member = $getMemberStmt->fetch();

    if ($member) {
      // Delete user account if exists (by NISN)
      $deleteUserStmt = $pdo->prepare('DELETE FROM users WHERE nisn=:nisn AND role=:role');
      $deleteUserStmt->execute(['nisn' => $member['nisn'], 'role' => 'student']);

      // Delete member
      $stmt = $pdo->prepare('DELETE FROM members WHERE id=:id AND school_id=:sid');
      $stmt->execute(['id' => (int) $_GET['id'], 'sid' => $sid]);
    }

    $_SESSION['success'] = 'Murid dan akun siswa berhasil dihapus';
    header('Location: members.php');
    exit;
  } catch (Exception $e) {
    $_SESSION['error'] = 'Gagal menghapus murid: ' . $e->getMessage();
    header('Location: members.php');
    exit;
  }
}

// Get school info
$schoolStmt = $pdo->prepare('SELECT * FROM schools WHERE id = :sid');
$schoolStmt->execute(['sid' => $sid]);
$school = $schoolStmt->fetch();

// Update query to join with users and siswa to get photo
$stmt = $pdo->prepare('
    SELECT m.*, s.foto 
    FROM members m
    LEFT JOIN users u ON u.nisn = m.nisn AND u.school_id = m.school_id AND (u.role = "student" OR u.role = "teacher" OR u.role = "employee")
    LEFT JOIN siswa s ON s.id_siswa = u.id
    WHERE m.school_id = :sid 
    ORDER BY m.id DESC
');
$stmt->execute(['sid' => $sid]);
$members = $stmt->fetchAll();
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kelola Anggota</title>
  <script src="../assets/js/theme-loader.js"></script>
  <script src="../assets/js/theme.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
  <link rel="stylesheet" href="../assets/css/animations.css">
  <link rel="stylesheet" href="../assets/css/members.css">
  <!-- JsBarcode for client-side barcode generation -->
  <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
  <style>
    /* Library Card Modal Styles */
    .modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      justify-content: center;
      align-items: center;
      backdrop-filter: blur(4px);
    }

    .modal-card {
      background: white;
      padding: 24px;
      border-radius: 16px;
      max-width: 500px;
      width: 90%;
      position: relative;
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    .modal-close-btn {
      position: absolute;
      top: 16px;
      right: 16px;
      background: #f1f5f9;
      border: none;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #64748b;
      transition: all 0.2s;
    }

    .modal-close-btn:hover {
      background: #e2e8f0;
      color: #0f172a;
    }

        /* Library Card Design */
        .library-card-wrapper {
            margin: 20px 0;
            display: flex;
            justify-content: center;
            width: 100%;
            overflow-x: auto; /* Allow scroll on small screens */
            padding: 10px;
        }

        .library-card {
            width: 550px; /* Fixed width to guarantee aspect ratio fits content */
            min-width: 550px; /* Force minimum width */
            aspect-ratio: 1.586 / 1;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            border-radius: 20px;
            padding: 24px; /* Restored to 24px */
            position: relative;
            box-shadow: 0 20px 40px -10px rgba(30, 58, 138, 0.4);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.1);
        }

        /* Decorative patterns */
        .library-card::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .library-card::after {
            content: '';
            position: absolute;
            bottom: -30px;
            left: -30px;
            width: 180px;
            height: 180px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
            pointer-events: none;
        }

        .card-header-bg {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-bottom: 16px; /* Restored to 16px */
            border-bottom: 1px solid rgba(255,255,255,0.15);
            position: relative;
            z-index: 2;
            background: transparent;
        }

        .school-logo-frame {
            width: 48px; /* Restored to 48px */
            height: 48px;
            background: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 0;
        }

        .school-logo-frame img {
            width: 32px;
            height: 32px;
            object-fit: contain;
        }
        
        .school-logo-frame iconify-icon {
            font-size: 24px !important;
            color: #1e3a8a !important;
        }

        .school-info-text h2 {
            font-size: 16px; /* Restored to 16px */
            font-weight: 700;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin: 0;
        }

        .school-info-text p {
            display: none;
        }

        .card-body-content {
            display: flex;
            gap: 20px; /* Restored to 20px */
            align-items: center;
            position: relative;
            z-index: 2;
            flex: 1;
            padding: 10px 0; /* Restored to 10px 0 */
        }

        .student-profile-row {
            display: flex;
            gap: 20px; /* Restored gap */
            align-items: center;
            width: 100%;
        }

        .student-avatar-img {
            width: 90px; /* Restored to 90px */
            height: 110px; /* Restored to 110px */
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 12px;
            object-fit: cover;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            backdrop-filter: blur(4px);
        }

        .student-data-text {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .st-name {
            font-size: 22px; /* Restored to 22px */
            font-weight: 800;
            color: white;
            margin-bottom: 6px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            letter-spacing: -0.01em;
            line-height: 1.2;
        }

        .st-id-label {
            font-size: 14px; /* Restored to 14px */
            color: rgba(255,255,255,0.9);
            margin: 0;
            font-weight: 500;
        }

        .st-id-value {
            font-size: 14px; /* Restored to 14px */
            color: rgba(255,255,255,0.9);
            font-weight: 500;
            font-family: inherit;
            background: transparent;
            padding: 0;
        }

        .barcode-container {
            background: white;
            padding: 8px 20px; /* Slightly reduced top/bottom padding to ensure fit */
            border-radius: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            z-index: 2;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-top: auto;
            height: auto;
            border: none;
        }

        #card-barcode {
            width: 100%;
            height: 45px; /* Restored to 45px */
        }

        .card-footer-strip {
            display: none;
        }
        
        /* Modal sizing fix */
        .modal-card {
             width: auto !important;
             max-width: 95vw !important;
             min-width: 600px; /* Ensure modal is wider than card */
        }
        
        @media (max-width: 640px) {
             .modal-card {
                 min-width: 95% !important;
             }
             /* On very small screens, allow card to scale down if absolutely needed, but prefer scroll */
        }


        .btn-primary { 
            background: #3A7FF2; 
            color: white; 
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
    
        .btn-secondary { 
            background: #E6EEF8; 
            color: #0F172A; 
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-secondary:hover {
            background: #d1d5db;
        }
        
        @media print {
            @page { size: auto; margin: 0mm; }
            body { background: white; -webkit-print-color-adjust: exact; }
            
            /* Ensure correct print sizing regardless of screen tweaks */
            .library-card {
                width: 85.6mm !important;
                height: 53.98mm !important;
                padding: 4mm !important;
                aspect-ratio: auto !important;
            }
            .card-header-bg { padding-bottom: 2mm !important; }
            .student-avatar-img { width: 18mm !important; height: 22mm !important; }
            .barcode-container { padding: 2mm !important; }
        }

        /* ------------------------------------------------------------------ */
        /* EXACT COPY FROM STUDENT-BARCODES.PHP FOR 100% MATCH */
        /* ------------------------------------------------------------------ */

        .id-card-mockup {
            width: 500px;
            min-width: 500px;
            aspect-ratio: 1.586 / 1;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            border-radius: 20px;
            padding: 24px;
            position: relative;
            box-shadow: 0 20px 40px -10px rgba(30, 58, 138, 0.4);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.1);
        }

        /* Decorative patterns */
        .id-card-mockup::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .id-card-mockup::after {
            content: '';
            position: absolute;
            bottom: -30px;
            left: -30px;
            width: 180px;
            height: 180px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
            pointer-events: none;
        }
        
        .id-card-header {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255,255,255,0.15);
            position: relative;
            z-index: 2;
        }
        
        .school-logo {
            width: 48px;
            height: 48px;
            background: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #1e3a8a;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .school-logo img {
            width: 32px;
            height: 32px;
            object-fit: contain;
        }
        
        .school-name {
            font-size: 16px;
            font-weight: 700;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .id-card-body {
            display: flex;
            gap: 20px;
            align-items: center;
            position: relative;
            z-index: 2;
            flex: 1;
            padding: 10px 0;
        }
        
        .id-card-photo {
            width: 90px;
            height: 110px;
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: white;
            font-weight: 700;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            backdrop-filter: blur(4px);
            object-fit: cover;
        }
        
        .id-card-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .id-card-details h3 {
            font-size: 22px;
            font-weight: 800;
            color: white;
            margin-bottom: 6px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            letter-spacing: -0.01em;
        }
        
        .id-card-details p {
            font-size: 14px;
            color: rgba(255,255,255,0.9);
            margin: 0;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .id-card-barcode-area {
            background: white;
            padding: 12px 20px;
            border-radius: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            z-index: 2;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        #card-barcode {
            width: 100%;
            height: 60px; /* Force match student-barcodes.php inline style */
        }

        /* Modal sizing fix override */
        .modal-card {
             width: auto !important;
             max-width: 95vw !important;
             min-width: 600px;
        }

        @media print {
            .id-card-mockup {
                width: 85.6mm !important;
                height: 53.98mm !important;
                min-width: 0 !important;
                padding: 4mm !important;
                box-shadow: none !important;
                aspect-ratio: auto !important;
            }
            .id-card-header { padding-bottom: 2mm !important; gap: 3mm !important; }
            .school-logo { width: 8mm !important; height: 8mm !important; border-radius: 2mm !important; } 
            .school-name { font-size: 11pt !important; }
            .id-card-body { padding: 2mm 0 !important; gap: 3mm !important; }
            .id-card-photo { width: 18mm !important; height: 22mm !important; }
            .id-card-details h3 { font-size: 14pt !important; margin-bottom: 1mm !important; }
            .id-card-details p { font-size: 9pt !important; }
            .id-card-barcode-area { padding: 2mm !important; }
            #card-barcode { height: 10mm !important; }
        }
  </style>
</head>

<body>
  <?php require __DIR__ . '/partials/sidebar.php'; ?>

  <div class="app">

    <div class="topbar">
      <strong>Kelola Anggota</strong>
    </div>

    <div class="content">
      <div class="main">

        <?php if (isset($_SESSION['success'])): ?>
          <div class="alert alert-success">
            <?php echo htmlspecialchars($_SESSION['success']); ?>
            <?php unset($_SESSION['success']); ?>
          </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
          <div class="alert alert-error">
            <?php echo htmlspecialchars($_SESSION['error']); ?>
            <?php unset($_SESSION['error']); ?>
          </div>
        <?php endif; ?>

        <div class="card">
          <h2><?= $action === 'edit' ? 'Edit Anggota' : 'Tambah Anggota' ?></h2>
          <?php if ($action === 'add'): ?>
            <div
              style="background: #e0f2fe; border-left: 4px solid #0284c7; padding: 12px; border-radius: 6px; margin-bottom: 16px; font-size: 12px; color: #0c4a6e;">
              <strong>ℹ️ Info:</strong> Ketika anggota ditambahkan, akun akan otomatis terbuat. <strong>Anggota login
                dengan NISN/ID sebagai username and password yang Anda buat</strong>.
            </div>
          <?php endif; ?>
          <form method="post" action="<?= $action === 'edit' ? '' : 'members.php?action=add' ?>" autocomplete="off"
            id="member-form">
            <div class="form-group">
              <label>Role Anggota</label>
              <select name="role" id="role-select" required onchange="updateMemberLabels()">
                <option value="student" <?= ($action === 'edit' && isset($member['role']) && $member['role'] === 'student') ? 'selected' : '' ?>>Siswa</option>
                <option value="teacher" <?= ($action === 'edit' && isset($member['role']) && $member['role'] === 'teacher') ? 'selected' : '' ?>>Guru</option>
                <option value="employee" <?= ($action === 'edit' && isset($member['role']) && $member['role'] === 'employee') ? 'selected' : '' ?>>Karyawan</option>
              </select>
            </div>
            <div class="form-group">
              <label>Nama Lengkap</label>
              <input type="text" name="name" required autocomplete="off"
                value="<?= $action === 'edit' && isset($member['name']) ? htmlspecialchars($member['name']) : '' ?>">
            </div>
            <div class="form-group">
              <label>Email</label>
              <input type="email" name="email" required autocomplete="off"
                value="<?= $action === 'edit' && isset($member['email']) ? htmlspecialchars($member['email']) : '' ?>">
            </div>
            <div class="form-group">
              <label id="id-label">NISN Siswa</label>
              <input type="text" name="nisn" id="id-input" required placeholder="Nomor Induk Siswa Nasional" autocomplete="off"
                value="<?= $action === 'edit' && isset($member['nisn']) ? htmlspecialchars($member['nisn']) : '' ?>">
            </div>
            <div class="form-group">
              <label>Batas Pinjam Buku (Maksimal)</label>
              <input type="number" name="max_pinjam" min="1" required placeholder="Default: 2" autocomplete="off"
                value="<?= $action === 'edit' && isset($member['max_pinjam']) ? (int)$member['max_pinjam'] : '2' ?>">
            </div>
            <div class="form-group">
              <label>Password</label>
              <input type="password" name="password" autocomplete="new-password" <?= $action === 'edit' ? '' : 'required' ?>
                placeholder="<?= $action === 'edit' ? 'Kosongkan jika tidak ingin mengubah password' : 'Buat password untuk siswa' ?>"
                value="">
            </div>
            <button class="btn" type="submit">
              <?= $action === 'edit' ? 'Simpan Perubahan' : 'Tambah Anggota' ?>
            </button>
          </form>
        </div>

        <div class="card">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">Daftar Anggota (<?= count($members) ?>)</h2>
            <button class="btn btn-sm btn-secondary" onclick="printAllCards()">
              <iconify-icon icon="mdi:card-multiple-outline" style="vertical-align: middle; margin-right: 4px;"></iconify-icon>
              Cetak Semua Kartu
            </button>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Nama</th>
                  <th>Email</th>
                  <th>ID / NISN</th>
                  <th>Role</th>
                  <th>Status Akun</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($members as $m):
                  // Check if account exists
                  $checkUserStmt = $pdo->prepare('SELECT id FROM users WHERE nisn = :nisn AND (role = "student" OR role = "teacher" OR role = "employee")');
                  $checkUserStmt->execute(['nisn' => $m['nisn']]);
                  $userExists = $checkUserStmt->fetch() ? true : false;
                  ?>
                  <tr>
                    <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
                    <td><?= htmlspecialchars($m['email']) ?></td>
                    <td><strong><?= htmlspecialchars($m['nisn']) ?></strong></td>
                    <td>
                      <span style="display: inline-block; background: rgba(59, 130, 246, 0.1); color: #1e40af; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; text-transform: capitalize;">
                        <?= htmlspecialchars($m['role'] ?? 'student') ?>
                      </span>
                    </td>
                    <td>
                      <?php if ($userExists): ?>
                        <span
                          style="display: inline-block; background: rgba(16, 185, 129, 0.1); color: #065f46; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500;"><iconify-icon
                            icon="mdi:check-circle" style="vertical-align: middle; margin-right: 4px;"></iconify-icon> Akun
                          Terbuat</span>
                      <?php else: ?>
                        <span
                          style="display: inline-block; background: rgba(107, 114, 128, 0.1); color: #374151; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500;"><iconify-icon
                            icon="mdi:minus-circle" style="vertical-align: middle; margin-right: 4px;"></iconify-icon>
                          Belum</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="actions">
                        <button class="btn btn-sm btn-primary" onclick="showLibraryCard({
                          name: '<?= addslashes($m['name']) ?>',
                          nisn: '<?= addslashes($m['nisn']) ?>',
                          foto: '<?= !empty($m['foto']) ? $m['foto'] : '' ?>'
                        })">
                          <iconify-icon icon="mdi:card-account-details-outline" style="vertical-align: middle;"></iconify-icon> Kartu Perpus
                        </button>
                        <a class="btn btn-sm btn-secondary"
                          href="members.php?action=edit&id=<?= $m['id'] ?>"><iconify-icon icon="mdi:pencil"
                            style="vertical-align: middle;"></iconify-icon> Edit</a>
                        <a class="btn btn-sm btn-danger"
                          onclick="return confirm('Hapus anggota ini? Akun juga akan dihapus.')"
                          href="members.php?action=delete&id=<?= $m['id'] ?>"><iconify-icon icon="mdi:trash-can"
                            style="vertical-align: middle;"></iconify-icon> Hapus</a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card" style="grid-column: 1/-1">
          <h2>Statistik Anggota</h2>
          <div class="stats-container" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
            <div class="stat-card">
              <div class="stat-label">Total Anggota</div>
              <div class="stat-value"><?= count($members) ?></div>
            </div>
            <div class="stat-card">
              <div class="stat-label">Email Terdaftar</div>
              <div class="stat-value"><?= count(array_filter($members, fn($m) => !empty($m['email']))) ?></div>
            </div>
          </div>
        </div>

        <div class="card" style="grid-column: 1/-1">
          <h2>Pertanyaan Umum</h2>
          <div class="faq-item">
            <div class="faq-question">Bagaimana cara menambah anggota baru? <span>+</span></div>
            <div class="faq-answer">Pilih Role (Siswa/Guru/Karyawan), isi nama lengkap, email, dan ID (NISN/NIP/NUPTK), lalu klik "Tambah Anggota". Akun akan otomatis terbuat dengan ID tersebut sebagai username.</div>
          </div>
          <div class="faq-item">
            <div class="faq-question">Apa yang dimaksud dengan ID Anggota? <span>+</span></div>
            <div class="faq-answer"><strong>ID Anggota</strong> bisa berupa NISN untuk Siswa, NUPTK untuk Guru, atau NIP untuk Karyawan. ID ini digunakan sebagai username login.</div>
          </div>
          <div class="faq-item">
            <div class="faq-question">Apa itu "Status Akun"? <span>+</span></div>
            <div class="faq-answer">Status Akun menunjukkan apakah kredensial login sudah aktif. Secara default, password awal sama dengan ID Anggota.</div>
          </div>
          <div class="faq-item">
            <div class="faq-question">Bagaimana anggota login? <span>+</span></div>
            <div class="faq-answer">Anggota login menggunakan <strong>ID (NISN/NIP/NUPTK) sebagai username</strong> and 
              <strong>Password = ID</strong>. Kami sarankan untuk segera mengubah password setelah login pertama kali.
            </div>
          </div>
          <div class="faq-item">
            <div class="faq-question">Bisakah saya mengedit data murid? <span>+</span></div>
            <div class="faq-answer">Ya, klik "Edit" pada baris murid yang ingin diubah. Anda bisa mengubah nama, email,
              no murid, dan NISN. Perubahan NISN juga akan mengubah kredensial login siswa.</div>
          </div>
          <div class="faq-item">
            <div class="faq-question">Apa yang terjadi jika saya menghapus murid? <span>+</span></div>
            <div class="faq-answer">Murid dan akun siswa akan dihapus dari sistem. Siswa tidak bisa login lagi. Pastikan
              murid tidak memiliki peminjaman aktif sebelum menghapus.</div>
          </div>
          <div class="faq-item">
            <div class="faq-question">Apakah NISN harus unik? <span>+</span></div>
            <div class="faq-answer">Ya, NISN harus unik karena digunakan sebagai identitas login siswa. Setiap siswa
              hanya memiliki satu NISN yang valid secara nasional.</div>
          </div>
        </div>

      </div>

    </div>
  </div>

  <div id="libraryCardModal" class="modal-overlay">
    <div class="modal-card">
      <button class="modal-close-btn" onclick="closeLibraryCardModal()">
        <iconify-icon icon="mdi:close"></iconify-icon>
      </button>
      
      <h3 style="margin-bottom: 20px; font-size: 18px;">Pratinjau Kartu Perpustakaan</h3>

      <div class="library-card-wrapper">
        <div class="id-card-mockup" id="printableCard">
             <div class="id-card-header">
                <div class="school-logo">
                    <?php if (!empty($school['logo'])): ?>
                        <img src="<?= htmlspecialchars($school['logo']) ?>" alt="Logo">
                    <?php else: ?>
                        <iconify-icon icon="mdi:school"></iconify-icon>
                    <?php endif; ?>
                </div>
                <div class="school-name"><?= htmlspecialchars($school['name'] ?? 'PERPUSTAKAAN DIGITAL') ?></div>
             </div>
             
             <div class="id-card-body">
                 <img id="modal-photo" src="../assets/images/default-avatar.svg" alt="Foto" class="id-card-photo" style="display:block;">
                 
                 <div class="id-card-details">
                     <p style="font-size: 10px; margin-bottom: 4px; opacity: 0.6; text-transform: uppercase;">Student Name</p>
                     <h3 id="modal-name">-</h3>
                     <p id="modal-nisn">NISN: -</p>
                 </div>
             </div>

             <div class="id-card-barcode-area">
                 <svg id="card-barcode" style="width: 100%; height: 60px;"></svg>
             </div>
        </div>
      </div>

      <div class="modal-footer" style="display: flex; gap: 12px; margin-top: 24px;">
        <button onclick="window.print()" class="btn btn-primary" style="flex: 1;">
          <iconify-icon icon="mdi:printer" style="font-size: 18px;"></iconify-icon>
          Cetak Kartu
        </button>
        <button onclick="closeLibraryCardModal()" class="btn btn-secondary" style="flex: 1;">
          Tutup
        </button>
      </div>
    </div>
  </div>

  <script src="../assets/js/members.js"></script>
  <script>
    // Inject PHP members data to JS for bulk printing
    const allMembersData = <?= json_encode($members) ?>;
    const schoolData = <?= json_encode($school) ?>;
    let currentMemberData = null;

    function showLibraryCard(data) {
      currentMemberData = data;
      document.getElementById('modal-name').textContent = data.name;
      document.getElementById('modal-nisn').textContent = 'ID: ' + data.nisn;
      
      const photoEl = document.getElementById('modal-photo');
      if (data.foto) {
        photoEl.src = data.foto;
      } else {
        photoEl.src = '../assets/images/default-avatar.svg';
      }
 
      try {
        // EXACT options from student-barcodes.php
        JsBarcode("#card-barcode", data.nisn, {
            format: "CODE128",
            displayValue: true,
            fontSize: 14,
            width: 2.5,
            height: 50,
            margin: 5
        });
      } catch (e) {
        console.error("Barcode error:", e);
      }
 
      document.getElementById('libraryCardModal').style.display = 'flex';
    }

    function printLibraryCard() {
      if (!currentMemberData) return;
      renderPrintWindow([currentMemberData]);
    }

    function printAllCards() {
      if (confirm(`Cetak kartu untuk ${allMembersData.length} anggota?`)) {
        renderPrintWindow(allMembersData);
      }
    }

    function renderPrintWindow(members) {
        const printWindow = window.open('', '_blank', 'width=900,height=600');
        const schoolName = schoolData.name || 'PERPUSTAKAAN DIGITAL';
        const schoolLogo = schoolData.logo || '';
        
        // Build card HTML with new design
        const cardsHtml = members.map(m => {
            const photoSrc = m.foto ? m.foto : '../assets/images/default-avatar.svg';
            const barcodeValue = m.nisn || m.id;
            
            // Generate barcode off-screen for each card
            const tempSvg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
            try {
                JsBarcode(tempSvg, barcodeValue, {
                    format: "CODE128",
                    displayValue: true,
                    width: 2,
                    height: 40,
                    fontSize: 14,
                    margin: 0
                });
            } catch(e) { console.error(e); }

            return `
                <div class="library-card">
                    <!-- Decor -->
                    <div class="decor-circle-1"></div>
                    <div class="decor-circle-2"></div>

                    <div class="card-header">
                        <div class="school-logo">
                            ${schoolLogo ? `<img src="${schoolLogo}">` : '<div style="font-size:20px; color:#1e3a8a;">🏫</div>'}
                        </div>
                        <div class="school-info">
                            <h2>${schoolName.toUpperCase()}</h2>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="profile-row">
                            <img src="${photoSrc}" class="avatar">
                            <div class="data">
                                <div class="name">${m.name}</div>
                                <div class="label">Nomor Anggota</div>
                                <div class="value">ID: ${barcodeValue}</div>
                            </div>
                        </div>
                    </div>
                    <div class="barcode-area">
                        ${tempSvg.outerHTML}
                    </div>
                </div>
            `;
        }).join('');

        printWindow.document.write(`
            <html>
            <head>
                <title>Cetak Kartu Perpustakaan</title>
                <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">
                <style>
                    @page { margin: 10mm; size: A4; }
                    body { font-family: 'Inter', sans-serif; background: #fff; margin: 0; padding: 20px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                    .print-grid { 
                        display: grid; 
                        grid-template-columns: repeat(2, 1fr); 
                        gap: 20px; 
                        justify-content: center;
                    }
                    .library-card {
                        width: 90mm;
                        height: 57mm; /* Aspect ratio roughly 1.58 */
                        border: 1px solid #ddd;
                        border-radius: 8mm;
                        overflow: hidden;
                        display: flex;
                        flex-direction: column;
                        background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%) !important;
                        color: white !important;
                        padding: 5mm;
                        position: relative;
                        box-shadow: none;
                        page-break-inside: avoid;
                    }
                    
                    /* Decor for print */
                    .decor-circle-1 {
                        position: absolute; top: -15mm; right: -15mm; width: 60mm; height: 60mm;
                        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
                        border-radius: 50%; pointer-events: none;
                    }
                    .decor-circle-2 {
                        position: absolute; bottom: -10mm; left: -10mm; width: 40mm; height: 40mm;
                        background: rgba(255,255,255,0.05);
                        border-radius: 50%; pointer-events: none;
                    }

                    .card-header {
                        display: flex;
                        align-items: center;
                        gap: 4mm;
                        padding-bottom: 3mm;
                        border-bottom: 0.5px solid rgba(255,255,255,0.2);
                        position: relative; z-index: 2;
                        margin-bottom: 3mm;
                    }
                    .school-logo {
                        width: 10mm; height: 10mm;
                        background: white; border-radius: 3mm;
                        display: flex; align-items: center; justify-content: center;
                        padding: 1mm;
                    }
                    .school-logo img { width: 100%; height: 100%; object-fit: contain; }
                    .school-info h2 { font-size: 10pt; margin: 0; font-weight: 800; text-transform: uppercase; color: white; }
                    
                    .card-body { flex: 1; display: flex; flex-direction: column; position: relative; z-index: 2; }
                    .profile-row { display: flex; gap: 4mm; align-items: center; }
                    .avatar { 
                        width: 22mm; height: 26mm; 
                        border-radius: 3mm; object-fit: cover; 
                        border: 1mm solid rgba(255,255,255,0.3); 
                        background: rgba(255,255,255,0.1);
                    }
                    .data { flex: 1; display: flex; flex-direction: column; justify-content: center; }
                    .name { font-size: 14pt; font-weight: 800; color: white; margin-bottom: 1mm; line-height: 1.1; }
                    .label { font-size: 8pt; color: rgba(255,255,255,0.9); margin-bottom: 0; }
                    .value { font-size: 9pt; font-weight: 500; color: rgba(255,255,255,0.9); }
                    
                    .barcode-area { 
                        margin-top: auto; 
                        background: white; 
                        border-radius: 3mm; 
                        padding: 2mm 4mm; 
                        text-align: center; 
                        position: relative; z-index: 2;
                        display: flex; justify-content: center; align-items: center;
                        height: 10mm;
                    }
                    .barcode-area svg { width: 100%; height: 100%; }
                </style>
            </head>
            <body>
                <div class="print-grid">
                    ${cardsHtml}
                </div>
                <script>
                    window.onload = function() {

                        setTimeout(() => {
                            window.print();
                            window.close();
                        }, 500);
                    };
                <\/script>
            </body>
            </html>
        `);
        printWindow.document.close();
    }
 
    function closeLibraryCardModal() {
      document.getElementById('libraryCardModal').style.display = 'none';
      currentMemberData = null;
    }

    // Close on outside click
    window.onclick = function(event) {
      const modal = document.getElementById('libraryCardModal');
      if (event.target == modal) {
        closeLibraryCardModal();
      }
    }

    function updateMemberLabels() {
      const roleSelect = document.getElementById('role-select');
      const idLabel = document.getElementById('id-label');
      const idInput = document.getElementById('id-input');
      const maxPinjamInput = document.querySelector('input[name="max_pinjam"]');
      
      if (roleSelect.value === 'teacher') {
        idLabel.textContent = 'NUPTK / ID Guru';
        idInput.placeholder = 'Nomor Unik Pendidik dan Tenaga Kependidikan';
        if (!maxPinjamInput.value || maxPinjamInput.value == '2') maxPinjamInput.value = '5';
      } else if (roleSelect.value === 'employee') {
        idLabel.textContent = 'NIP / ID Karyawan';
        idInput.placeholder = 'Nomor Induk Pegawai';
        if (!maxPinjamInput.value || maxPinjamInput.value == '2') maxPinjamInput.value = '3';
      } else {
        idLabel.textContent = 'NISN Siswa';
        idInput.placeholder = 'Nomor Induk Siswa Nasional';
        if (!maxPinjamInput.value || maxPinjamInput.value == '5' || maxPinjamInput.value == '3') maxPinjamInput.value = '2';
      }
    }

    // Call on load
    document.addEventListener('DOMContentLoaded', function() {
      if (document.getElementById('role-select')) {
        updateMemberLabels();
      }
    });

    // Only reset form fields when in ADD mode, not EDIT mode
    document.addEventListener('DOMContentLoaded', function () {
      const form = document.getElementById('member-form');
      if (form) {
        // Check if we're in edit mode by checking if name field has a value
        const nameField = form.querySelector('input[name="name"]');
        const isEditMode = nameField && nameField.value.trim() !== '';

        // Only clear password field (always reset password on load)
        const passwordField = form.querySelector('input[name="password"]');
        if (passwordField) {
          passwordField.value = '';
        }

        // If in ADD mode, clear all fields
        if (!isEditMode) {
          form.reset();
          const inputs = form.querySelectorAll('input');
          inputs.forEach(input => {
            input.value = '';
          });
        }
      }
    });
  </script>

</body>

</html>