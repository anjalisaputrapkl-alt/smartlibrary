# Implementation Guide: Multi-Tenant System

## Langkah-Langkah Implementasi

### Step 1: Database Migration

1. **Login ke phpMyAdmin:**
   - Akses: http://localhost/phpmyadmin
   - Database: `perpustakaan_online`

2. **Run SQL Migration:**
   - Buka tab "SQL"
   - Copy-paste isi file: `sql/migrations/02-multi-tenant-schema.sql`
   - Execute

3. **Verify:**

   ```sql
   -- Check kolom baru di schools table
   SELECT status, trust_score, activation_code FROM schools LIMIT 1;

   -- Check tabel baru
   SELECT * FROM activation_codes LIMIT 1;
   SELECT * FROM trust_scores LIMIT 1;
   SELECT * FROM trial_limits LIMIT 1;
   ```

---

### Step 2: Copy PHP Files Ke src/

Pasang 3 file manager ke folder `src/`:

```
src/MultiTenantManager.php      ← Core multi-tenant functions
src/TrialLimitsManager.php      ← Trial hard limits enforcement
```

**Testing:**

```php
<?php
$pdo = require __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/MultiTenantManager.php';

$mtManager = new MultiTenantManager($pdo);
$status = $mtManager->getSchoolStatus(2); // Test school ID 2
echo $status; // Should output: trial or active
?>
```

---

### Step 3: Update Login System

**File: `public/login.php`**

Tambahkan validasi school status:

```php
<?php
session_start();
$pdo = require __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/MultiTenantManager.php';

// ... existing code ...

if ($user && password_verify($password, $user['password'])) {
    // NEW: Check school status
    $mtManager = new MultiTenantManager($pdo);
    if ($mtManager->isSchoolSuspended($user['school_id'])) {
        $message = 'Sekolah Anda telah dinonaktifkan. Hubungi admin.';
    } else {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'school_id' => $user['school_id'],
            'name' => $user['name'],
            'role' => $user['role']
        ];
        header('Location: /perpustakaan-online/public/index.php');
        exit;
    }
}
?>
```

---

### Step 4: Buat Student Registration Page

**File: `public/student-register.php`**

Gunakan file contoh yang sudah disediakan di workspace.

Fitur:

- Pilih sekolah dari dropdown
- Input kode aktivasi (verify dengan database)
- Input NISN, nama, email, password
- Verify kode sebelum register

---

### Step 5: Audit & Update Query di Pages Penting

Semua query HARUS filter `school_id`:

**Pattern BENAR:**

```php
$stmt = $pdo->prepare('
    SELECT * FROM books
    WHERE school_id = :school_id
');
$stmt->execute(['school_id' => $_SESSION['user']['school_id']]);
```

**Pattern SALAH (FIX INI):**

```php
// JANGAN
$stmt = $pdo->prepare('SELECT * FROM books');
```

**Pages untuk di-audit:**

- `public/books.php` - list & manage buku
- `public/members.php` - manage siswa
- `public/borrows.php` - manage peminjaman
- `public/reports.php` - reports
- Semua file di `public/api/`

**Checklist audit:**

1. Tambahkan filter `WHERE school_id = :school_id` ke setiap SELECT
2. Tambahkan parameter `school_id` dari session
3. Jangan pernah trust `school_id` dari GET/POST

---

### Step 6: Integrasikan Trial Limits

**Di file add-book.php atau form tambah buku:**

```php
<?php
require_once __DIR__ . '/../src/TrialLimitsManager.php';

$limitsManager = new TrialLimitsManager($pdo, $mtManager);

if ($_POST) {
    try {
        // Check limit sebelum insert
        $limitsManager->checkBeforeAddBook($_SESSION['user']['school_id']);

        // ... insert buku ...

        $mtManager->logActivity(
            $_SESSION['user']['school_id'],
            'book_creation',
            'create',
            1,
            $_SESSION['user']['id']
        );
    } catch (TrialLimitException $e) {
        $error = $e->getMessage();
    }
}
?>
```

**Dengan style notification:**

```php
<?php if ($error): ?>
    <div style="background: #fee; padding: 12px; border-radius: 4px; color: #c33;">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>
```

---

### Step 7: Buat Admin Dashboard

**File: `public/admin-dashboard.php`**

Gunakan file contoh yang sudah disediakan.

Fitur:

- Display status sekolah (trial/active)
- Display trust score + factors
- Display kode aktivasi (masked)
- Display trial days remaining
- Display capacity limits dengan progress bar
- Tombol "Ajukan Aktivasi" (hanya trial)
- Warning messages

---

### Step 8: Implementasi Activation Request Handler

**File: `public/api/school-activation.php`** (New)

```php
<?php
session_start();
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/MultiTenantManager.php';

header('Content-Type: application/json');

// Require admin role
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$pdo = require __DIR__ . '/../../src/db.php';
$mtManager = new MultiTenantManager($pdo);
$school_id = $_SESSION['user']['school_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $mtManager->requestActivation($school_id, $_SESSION['user']['id']);
        $newScore = $mtManager->recalculateTrustScore($school_id);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Permintaan aktivasi diterima',
            'new_trust_score' => $newScore
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// GET - return current status
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $school = $mtManager->getSchool($school_id);
    $trustScore = $mtManager->getTrustScore($school_id);

    echo json_encode([
        'status' => $school['status'],
        'trust_score' => $trustScore,
        'activation_requested_at' => $school['activation_requested_at']
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>
```

---

### Step 9: Update public/index.php (Dashboard)

Pastikan dashboard hanya show data sekolah user:

```php
<?php
session_start();
$pdo = require __DIR__ . '/../src/db.php';

// Require auth
if (empty($_SESSION['user'])) {
    header('Location: /perpustakaan-online/public/login.php');
    exit;
}

$school_id = $_SESSION['user']['school_id'];

// Query only untuk sekolah ini
$stmt = $pdo->prepare('
    SELECT COUNT(*) as count FROM books WHERE school_id = :school_id
');
$stmt->execute(['school_id' => $school_id]);
$bookCount = $stmt->fetch()['count'];

// ... dst ...
?>
```

---

### Step 10: Test Flow Lengkap

**Scenario 1: Sekolah Baru Trial**

1. Register sekolah baru di landing page
2. Login sebagai admin sekolah
3. Cek status = "trial"
4. Cek kode aktivasi ada dan masked
5. Klik "Ajukan Aktivasi"
6. Cek trust score meningkat

**Scenario 2: Siswa Register**

1. Buka `/public/student-register.php`
2. Pilih sekolah
3. Input kode sekolah (salah) → error
4. Input kode sekolah (benar) → success
5. Login sebagai siswa

**Scenario 3: Trial Limits**

1. Login sebagai admin sekolah trial
2. Coba tambah buku > 50 → error
3. Coba tambah siswa > 100 → error
4. Coba buat peminjaman > 200/bulan → error

**Scenario 4: Auto Activation**

1. Login admin sekolah trial
2. Ajukan aktivasi
3. Wait atau manual recalculate trust score
4. Jika score >= 70 → otomatis jadi "active"
5. Verify: bisa add unlimited buku, siswa, peminjaman

---

## File Structure After Implementation

```
perpustakaan-online/
├── src/
│   ├── MultiTenantManager.php         ✓ BARU
│   ├── TrialLimitsManager.php         ✓ BARU
│   ├── auth.php
│   ├── config.php
│   ├── db.php
│   └── ...
├── public/
│   ├── student-register.php           ✓ BARU
│   ├── admin-dashboard.php            ✓ BARU
│   ├── login.php                      ✓ UPDATE
│   ├── index.php                      ✓ UPDATE (audit query)
│   ├── books.php                      ✓ UPDATE (audit query)
│   ├── members.php                    ✓ UPDATE (audit query)
│   ├── borrows.php                    ✓ UPDATE (audit query)
│   ├── api/
│   │   ├── school-activation.php      ✓ BARU
│   │   └── ...
│   └── ...
├── sql/
│   └── migrations/
│       ├── perpustakaan_online.sql
│       └── 02-multi-tenant-schema.sql ✓ BARU
├── docs/
│   ├── MULTI_TENANT_SYSTEM_DESIGN.md  ✓ BARU
│   └── IMPLEMENTATION_GUIDE.md         ✓ BARU (ini)
└── ...
```

---

## Security Checklist

### Query Security

- [ ] Semua SELECT filter `school_id = :school_id`
- [ ] Semua UPDATE/DELETE filter `school_id = :school_id`
- [ ] Tidak ada hardcoded nilai di WHERE clause
- [ ] Gunakan prepared statement (`:param`)

### Session Security

- [ ] Jangan trust `$_GET['school_id']` atau `$_POST['school_id']`
- [ ] Gunakan `$_SESSION['user']['school_id']` sebagai source of truth
- [ ] Validasi role sebelum akses fitur admin

### Business Logic Security

- [ ] Check school status sebelum allow aksi
- [ ] Check trial limits sebelum insert data
- [ ] Log semua aktivitas penting
- [ ] Prevent SQL injection (gunakan prepared statement)

---

## Monitoring & Debugging

### Check Activation Code

```php
<?php
$school_id = 2;
$stmt = $pdo->prepare('SELECT * FROM activation_codes WHERE school_id = :id');
$stmt->execute(['id' => $school_id]);
$code = $stmt->fetch();
echo "Code: " . $code['code'] . "\n";
echo "Active: " . ($code['is_active'] ? 'Yes' : 'No') . "\n";
?>
```

### Check Trust Score

```php
<?php
$school_id = 2;
$stmt = $pdo->prepare('SELECT total_score, factors FROM trust_scores WHERE school_id = :id');
$stmt->execute(['id' => $school_id]);
$score = $stmt->fetch();
echo "Total Score: " . $score['total_score'] . "\n";
echo "Factors: " . $score['factors'] . "\n";
?>
```

### Check School Status

```php
<?php
$school_id = 2;
$stmt = $pdo->prepare('SELECT status, trust_score, activation_requested_at FROM schools WHERE id = :id');
$stmt->execute(['id' => $school_id]);
$school = $stmt->fetch();
print_r($school);
?>
```

### Check Activity Log

```php
<?php
$school_id = 2;
$stmt = $pdo->prepare('
    SELECT * FROM school_activities
    WHERE school_id = :id
    ORDER BY recorded_at DESC
    LIMIT 20
');
$stmt->execute(['id' => $school_id]);
while ($row = $stmt->fetch()) {
    echo $row['activity_type'] . " - " . $row['action'] . "\n";
}
?>
```

---

## Common Issues & Solutions

### Issue 1: "Error: Column 'status' doesn't exist"

**Solution:** Run migration `02-multi-tenant-schema.sql` terlebih dahulu

### Issue 2: "Activation code not found when registering student"

**Solution:** Check `activation_codes` table, pastikan `is_active = TRUE`

### Issue 3: School tidak auto-activate meskipun trust score >= 70

**Solution:**

- Pastikan `recalculateTrustScore()` dipanggil setelah aktivasi request
- Check `trust_scores.total_score` manually

### Issue 4: Query return data dari sekolah lain

**Solution:** Audit query, pastikan ada `WHERE school_id = :school_id`

### Issue 5: Trial limit not enforced

**Solution:** Pastikan ada `checkBeforeAddBook()` sebelum insert buku, dst

---

## Next Steps (Future Enhancement)

1. **Admin Platform** (optional)
   - Manual review activation requests
   - Override trust score
   - Suspend/unsuspend schools
   - View activity logs

2. **Email Notifications**
   - Alert saat activation requested
   - Alert saat approaching limits
   - Alert saat trial expired

3. **Payment Integration**
   - Convert trial to paid plan
   - Subscription management
   - Renewal workflow

4. **Analytics Dashboard**
   - Usage statistics per school
   - Anomaly detection alerts
   - Compliance reports
