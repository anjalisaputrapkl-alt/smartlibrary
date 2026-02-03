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
      'INSERT INTO members (school_id,name,email,nisn)
       VALUES (:sid,:name,:email,:nisn)'
    );
    $stmt->execute([
      'sid' => $sid,
      'name' => $_POST['name'],
      'email' => $_POST['email'],
      'nisn' => $_POST['nisn']
    ]);

    // Get the inserted NISN for password generation
    $nisn = $_POST['nisn'];
    $password = $_POST['password'];
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Create student account in users table
    $userStmt = $pdo->prepare(
      'INSERT INTO users (school_id, name, email, password, role, nisn)
       VALUES (:sid, :name, :email, :password, :role, :nisn)'
    );
    $userStmt->execute([
      'sid' => $sid,
      'name' => $_POST['name'],
      'email' => $_POST['email'],
      'password' => $hashed_password,
      'role' => 'student',
      'nisn' => $nisn
    ]);

    // Success message
    $_SESSION['success'] = 'Murid berhasil ditambahkan. Akun siswa otomatis terbuat dengan NISN: ' . $nisn;
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
    $stmt = $pdo->prepare(
      'UPDATE members SET name=:name,email=:email,nisn=:nisn
       WHERE id=:id AND school_id=:sid'
    );
    $stmt->execute([
      'name' => $_POST['name'],
      'email' => $_POST['email'],
      'nisn' => $_POST['nisn'],
      'id' => $id,
      'sid' => $sid
    ]);

    // Update password jika diisi
    if (!empty($_POST['password'])) {
      $hashed_password = password_hash($_POST['password'], PASSWORD_BCRYPT);
      $updatePasswordStmt = $pdo->prepare(
        'UPDATE users SET password=:password WHERE nisn=:nisn AND role=:role'
      );
      $updatePasswordStmt->execute([
        'password' => $hashed_password,
        'nisn' => $_POST['nisn'],
        'role' => 'student'
      ]);
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
    LEFT JOIN users u ON u.nisn = m.nisn AND u.school_id = m.school_id AND u.role = "student"
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
  <title>Kelola Murid</title>
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

    /* Library Card Design (Same as student-card.php) */
    .library-card-wrapper {
        margin: 20px 0;
        display: flex;
        justify-content: center;
    }

    .library-card {
        width: 100%;
        max-width: 400px;
        background: linear-gradient(135deg, #ffffff 0%, #f8faff 100%);
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(58, 127, 242, 0.15);
        overflow: hidden;
        border: 1px solid rgba(226, 232, 240, 0.8);
        position: relative;
        text-align: left;
    }

    .card-header-bg {
        background: #3A7FF2;
        color: white;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .school-logo-frame {
        width: 40px;
        height: 40px;
        background: white;
        border-radius: 10px;
        padding: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .school-logo-frame img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .school-info-text h2 {
        font-size: 14px;
        font-weight: 700;
        margin: 0;
        line-height: 1.2;
        color: white;
    }

    .school-info-text p {
        font-size: 10px;
        opacity: 0.9;
        margin: 2px 0 0 0;
        color: white;
    }

    .card-body-content {
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .student-profile-row {
        display: flex;
        gap: 16px;
        align-items: center;
    }

    .student-avatar-img {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        border: 3px solid white;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        object-fit: cover;
        background: #eee;
    }

    .student-data-text {
        flex: 1;
    }

    .st-name {
        font-size: 16px;
        font-weight: 700;
        color: #0F172A;
        margin-bottom: 2px;
    }

    .st-id-label {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        font-weight: 600;
    }

    .st-id-value {
        font-size: 12px;
        font-weight: 600;
        color: #3A7FF2;
        font-family: monospace;
        background: rgba(58, 127, 242, 0.1);
        padding: 1px 6px;
        border-radius: 4px;
    }

    .barcode-container {
        text-align: center;
        background: white;
        padding: 12px;
        border-radius: 10px;
        border: 1px solid #E6EEF8;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    #card-barcode {
        max-width: 100%;
        height: auto;
    }

    .card-footer-strip {
        background: #f1f5f9;
        padding: 8px;
        text-align: center;
        font-size: 9px;
        color: #64748b;
        border-top: 1px solid #E6EEF8;
    }

    @media print {
        body * { visibility: hidden; }
        #libraryCardModal, #libraryCardModal * { visibility: visible; }
        #libraryCardModal { position: absolute; left: 0; top: 0; width: 100%; padding: 0; margin: 0; background: white; }
        .modal-card { box-shadow: none; border: none; max-width: 100%; width: 100%; }
        .modal-close-btn, .modal-footer { display: none !important; }
        .library-card { box-shadow: none; border: 1px solid #ddd; }
    }
  </style>
</head>

<body>
  <?php require __DIR__ . '/partials/sidebar.php'; ?>

  <div class="app">

    <div class="topbar">
      <strong>Kelola Murid</strong>
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
          <h2><?= $action === 'edit' ? 'Edit Murid' : 'Tambah Murid' ?></h2>
          <?php if ($action === 'add'): ?>
            <div
              style="background: #e0f2fe; border-left: 4px solid #0284c7; padding: 12px; border-radius: 6px; margin-bottom: 16px; font-size: 12px; color: #0c4a6e;">
              <strong>ℹ️ Info:</strong> Ketika murid ditambahkan, akun siswa akan otomatis terbuat. <strong>Siswa login
                dengan NISN sebagai username dan password yang Anda buat</strong>.
            </div>
          <?php endif; ?>
          <form method="post" action="<?= $action === 'edit' ? '' : 'members.php?action=add' ?>" autocomplete="off"
            id="member-form">
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
              <label>NISN Siswa</label>
              <input type="text" name="nisn" required placeholder="Nomor Induk Siswa Nasional" autocomplete="off"
                value="<?= $action === 'edit' && isset($member['nisn']) ? htmlspecialchars($member['nisn']) : '' ?>">
            </div>
            <div class="form-group">
              <label>Password</label>
              <input type="password" name="password" autocomplete="new-password" <?= $action === 'edit' ? '' : 'required' ?>
                placeholder="<?= $action === 'edit' ? 'Kosongkan jika tidak ingin mengubah password' : 'Buat password untuk siswa' ?>"
                value="">
            </div>
            <button class="btn" type="submit">
              <?= $action === 'edit' ? 'Simpan Perubahan' : 'Tambah Murid' ?>
            </button>
          </form>
        </div>

        <div class="card">
          <h2>Daftar Murid (<?= count($members) ?>)</h2>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Nama</th>
                  <th>Email</th>
                  <th>NISN</th>
                  <th>Status Akun</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($members as $m):
                  // Check if student account exists
                  $checkUserStmt = $pdo->prepare('SELECT id FROM users WHERE nisn = :nisn AND role = :role');
                  $checkUserStmt->execute(['nisn' => $m['nisn'], 'role' => 'student']);
                  $userExists = $checkUserStmt->fetch() ? true : false;
                  ?>
                  <tr>
                    <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
                    <td><?= htmlspecialchars($m['email']) ?></td>
                    <td><strong><?= htmlspecialchars($m['nisn']) ?></strong></td>
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
                          onclick="return confirm('Hapus murid ini? Akun siswa juga akan dihapus.')"
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
          <h2>Statistik Murid</h2>
          <div class="stats-container" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
            <div class="stat-card">
              <div class="stat-label">Total Murid</div>
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
            <div class="faq-question">Bagaimana cara menambah murid baru? <span>+</span></div>
            <div class="faq-answer">Isi form dengan nama lengkap, email, no murid, dan NISN siswa, lalu klik "Tambah
              Murid". Akun siswa akan otomatis terbuat dengan NISN sebagai username dan password.</div>
          </div>
          <div class="faq-item">
            <div class="faq-question">Apa perbedaan No Murid dan NISN? <span>+</span></div>
            <div class="faq-answer"><strong>No Murid</strong> adalah nomor internal sekolah (ex: 001, 002).
              <strong>NISN</strong> adalah Nomor Induk Siswa Nasional yang unik dan digunakan untuk login. Siswa login
              menggunakan NISN sebagai username.
            </div>
          </div>
          <div class="faq-item">
            <div class="faq-question">Apa itu "Status Akun"? <span>+</span></div>
            <div class="faq-answer">Status Akun menunjukkan apakah akun siswa sudah terbuat di sistem. Ketika Anda
              menambah murid, akun siswa otomatis terbuat dengan NISN dan Password = NISN.</div>
          </div>
          <div class="faq-item">
            <div class="faq-question">Bagaimana siswa login ke dashboard? <span>+</span></div>
            <div class="faq-answer">Siswa login di halaman siswa menggunakan <strong>NISN sebagai username</strong> dan
              <strong>Password = NISN</strong> (sama dengan username). Siswa sangat disarankan untuk mengubah password
              setelah login pertama kali.
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
        <div class="library-card" id="printableCard">
          <div class="card-header-bg">
            <div class="school-logo-frame">
              <?php if (!empty($school['logo'])): ?>
                <img src="<?= htmlspecialchars($school['logo']) ?>" alt="Logo">
              <?php else: ?>
                <iconify-icon icon="mdi:school" style="color:#3A7FF2; font-size:20px;"></iconify-icon>
              <?php endif; ?>
            </div>
            <div class="school-info-text">
              <h2><?= htmlspecialchars($school['name'] ?? 'Perpustakaan Digital') ?></h2>
              <p><?= htmlspecialchars($school['address'] ?? 'Kartu Anggota Resmi') ?></p>
            </div>
          </div>

          <div class="card-body-content">
            <div class="student-profile-row">
              <img id="modal-photo" src="../assets/images/default-avatar.svg" alt="Foto" class="student-avatar-img">
              <div class="student-data-text">
                <div id="modal-name" class="st-name">-</div>
                <div class="st-id-label">Nomor Anggota</div>
                <div id="modal-nisn" class="st-id-value">-</div>
              </div>
            </div>

            <div class="barcode-container">
              <svg id="card-barcode"></svg>
            </div>
          </div>

          <div class="card-footer-strip">
            Berlaku selama menjadi siswa aktif di sekolah ini.
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
    function showLibraryCard(data) {
      document.getElementById('modal-name').textContent = data.name;
      document.getElementById('modal-nisn').textContent = data.nisn;
      
      const photoEl = document.getElementById('modal-photo');
      if (data.foto) {
        photoEl.src = data.foto;
      } else {
        photoEl.src = '../assets/images/default-avatar.svg';
      }

      try {
        JsBarcode("#card-barcode", data.nisn, {
            format: "CODE128",
            lineColor: "#000",
            width: 2,
            height: 40,
            displayValue: true,
            font: "monospace",
            fontSize: 12,
            marginTop: 5,
            marginBottom: 5
        });
      } catch (e) {
        console.error("Barcode error:", e);
      }

      document.getElementById('libraryCardModal').style.display = 'flex';
    }

    function closeLibraryCardModal() {
      document.getElementById('libraryCardModal').style.display = 'none';
    }

    // Close on outside click
    window.onclick = function(event) {
      const modal = document.getElementById('libraryCardModal');
      if (event.target == modal) {
        closeLibraryCardModal();
      }
    }

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