# Sistem Multi-Tenant Perpustakaan Online

## Ringkasan Konsep

Sistem perpustakaan multi-tenant dengan isolasi data berbasis `school_id`. Setiap sekolah memiliki data terpisah dan status yang menentukan akses ke fitur:

- **Trial**: Terbatas fitur, hard limit pada kuantitas
- **Active**: Akses penuh tanpa pembatasan
- **Suspended**: Tidak bisa mengakses sistem

## Status Sekolah

### Trial

Default saat sekolah baru mendaftar.

**Hard Limits:**

- Maksimal 50 buku
- Maksimal 100 siswa
- Maksimal 200 transaksi peminjaman
- Waktu trial: 14 hari dari `created_at`

**Pembatasan Fitur:**

- Tidak bisa export/print laporan resmi
- Tidak bisa bulk operations
- Tidak bisa mengaktifkan siswa tanpa verifikasi kode sekolah

**Pengalaman:**

- Bisa login dashboard
- Bisa coba semua fitur utama
- Mendapat notifikasi tentang batas kapasitas
- Tombol "Ajukan Aktivasi" selalu terlihat

### Active

Dihasilkan dari trust score yang mencukupi atau manual admin platform.

**Akses Penuh:**

- Tidak ada limit jumlah data
- Semua fitur tersedia
- Bisa digunakan production

### Suspended

Manual trigger atau pelanggaran sistem.

**Akses Ditolak:**

- Tidak bisa login
- Admin sekolah melihat notifikasi
- Pembatasan hingga status diperbaharui

---

## Trust Score System

**Automatic Activation Mechanism**

Sekolah trial dapat naik ke status `active` otomatis berdasarkan trust score.

### Faktor Trust Score

| Faktor                      | Skor | Deskripsi                      |
| --------------------------- | ---- | ------------------------------ |
| Sekolah mengajukan aktivasi | +10  | Tombol submit di dashboard     |
| Email admin .sch.id         | +15  | Domain sekolah resmi           |
| Kode aktivasi dimasukkan    | +20  | Bukti akses administratif      |
| Aktivitas sistem wajar      | +25  | Tidak ada anomali selama trial |
| Umur trial > 7 hari         | +10  | Cukup waktu untuk testing      |
| Minimal 5 transaksi         | +10  | Penggunaan sistem aktif        |
| Email verified              | +5   | Verifikasi tambahan            |

**Total: 95 poin (ambang batas = 70)**

Jika skor >= 70, otomatis status berubah menjadi `active`.

### Aktivitas Anomali (Score Penalty)

- Bulk upload buku > 100 dalam 1 jam: -20
- Login gagal > 10x: -15
- Delete data masif > 50 dalam 1 hari: -25
- SQL injection detected: -50 (suspend)

---

## Kode Aktivasi Sekolah

### Generate

```
Saat sekolah baru terdaftar:
- Generate kode 12 digit alphanumeric
- Simpan di tabel activation_codes
- Encoded di dashboard admin (tidak menampilkan full)
```

### Usage

```
Saat siswa mendaftar:
- Harus input kode sekolah
- Sistem verify: kode sekulah valid + sekolah aktif
- Mencegah sekolah palsu
```

### Display

```
Dashboard admin sekolah:
- Masker: ****-****-XXXX (3 digit akhir visible)
- Bisa regenerate (invalid kode lama)
```

---

## Alur Registrasi Siswa (New Student Registration Flow)

### Before (Sistem Lama)

```
Student Register
  -> Sekolah apa? (dropdown semua sekolah)
  -> Nama, Email, Password
  -> Register
```

**Problem:** Siswa iseng bisa register ke sekolah palsu, membuat sekolah fake terlihat aktif.

### After (Multi-Tenant)

```
Student Register
  1. Pilih sekolah dari dropdown
  2. Input kode sekolah (12 digit)
  3. Verifikasi kode:
     - Apakah kode valid? (di tabel activation_codes)
     - Apakah sekolah status tidak suspended?
     - Match dengan school_id?
  4. Input NISN
  5. Input nama, email, password
  6. Register
     -> Created dengan status 'active' (sudah verified via kode)
```

**Keamanan:**

- Hanya siswa yang tahu kode sekolah bisa register
- Kode sekolah adalah trust indicator
- Mengurangi fake school registrations

---

## Sistem Pembatasan Trial (Hard Limits)

### Hard Limit Check Points

#### 1. Saat Tambah Buku

```php
// Pre-insert check
if (isSchoolTrial($school_id)) {
    $bookCount = countBooks($school_id);
    if ($bookCount >= 50) {
        throw new TrialLimitException("Maksimal 50 buku untuk trial");
    }
}
```

#### 2. Saat Tambah Siswa

```php
if (isSchoolTrial($school_id)) {
    $studentCount = countStudents($school_id);
    if ($studentCount >= 100) {
        throw new TrialLimitException("Maksimal 100 siswa untuk trial");
    }
}
```

#### 3. Saat Buat Peminjaman

```php
if (isSchoolTrial($school_id)) {
    $borrowCount = countBorrows($school_id, 'current month');
    if ($borrowCount >= 200) {
        throw new TrialLimitException("Maksimal 200 transaksi/bulan untuk trial");
    }
}
```

#### 4. Saat Request Laporan

```
if (isSchoolTrial($school_id)) {
    // Tidak boleh export/print resmi
    throw new TrialFeatureException("Laporan resmi hanya untuk sekolah active");
}
```

#### 5. Saat Check Trial Expired

```php
// Check setiap request ke page tertentu
$trialExpiry = getSchoolCreatedAt($school_id) + 14 days;
if (now() > $trialExpiry && isSchoolTrial($school_id)) {
    // Blokir akses atau tampilkan warning
    handleTrialExpiry($school_id);
}
```

---

## Database Schema Tambahan

### schools (Modified)

```sql
ALTER TABLE schools ADD COLUMN (
    status ENUM('trial', 'active', 'suspended') DEFAULT 'trial',
    trial_started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    trust_score INT DEFAULT 0,
    activation_requested_at TIMESTAMP NULL
);
```

### activation_codes (New)

```sql
CREATE TABLE activation_codes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    code VARCHAR(12) NOT NULL UNIQUE,
    is_active BOOLEAN DEFAULT TRUE,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id)
);
```

### trust_scores (New)

```sql
CREATE TABLE trust_scores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    score INT DEFAULT 0,
    factors JSON,
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id)
);
```

### trial_limits (New)

```sql
CREATE TABLE trial_limits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    limit_type ENUM('books', 'students', 'borrows') NOT NULL,
    max_allowed INT NOT NULL,
    current_count INT DEFAULT 0,
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id)
);
```

### school_activities (New - for anomaly detection)

```sql
CREATE TABLE school_activities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    activity_type VARCHAR(100),
    action VARCHAR(50),
    data_count INT,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id)
);
```

---

## Security Rules (Golden Rules)

1. **Setiap Query Filter by school_id**

   ```php
   // BENAR
   $stmt = $pdo->prepare('SELECT * FROM books WHERE school_id = :school_id');

   // SALAH
   $stmt = $pdo->prepare('SELECT * FROM books');
   ```

2. **Setiap Request Check Session school_id**

   ```php
   $sessionSchoolId = $_SESSION['user']['school_id'];
   // Validasi input school_id sama dengan session
   ```

3. **Setiap Akses Fitur Check Status Sekolah**

   ```php
   $school = getSchool($school_id);
   if ($school['status'] === 'suspended') {
       throw new AccessDeniedException("Sekolah suspended");
   }
   ```

4. **Jangan Trust User Input Untuk school_id**

   ```php
   // BENAR
   $school_id = $_SESSION['user']['school_id'];

   // SALAH
   $school_id = $_POST['school_id']; // User bisa tamper
   ```

5. **Log Semua Aktivitas Untuk Anomaly Detection**
   ```php
   logActivity($school_id, 'create_book', 1); // 1 = count
   ```

---

## Implementation Roadmap

### Phase 1: Database & Core Functions

- [ ] Migrate database (kolom status, tabel baru)
- [ ] Buat file: `MultiTenantManager.php`
- [ ] Buat file: `TrialLimitsManager.php`
- [ ] Buat file: `TrustScoreCalculator.php`

### Phase 2: Refactor Existing Features

- [ ] Audit semua query add school_id filter
- [ ] Refactor login.php: add school_id ke session
- [ ] Refactor register.php: add kode sekolah verification
- [ ] Refactor semua controller: add status check

### Phase 3: New Features

- [ ] Dashboard activation button
- [ ] Activation code display & regenerate
- [ ] Student registration form dengan school code
- [ ] Admin dashboard: activation status & trust score display

### Phase 4: Monitoring & Admin Panel

- [ ] Activity logging system
- [ ] Anomaly detection system
- [ ] Simple admin panel untuk review & manual override
- [ ] Dashboard metrics

---

## File-File Penting

```
src/
  ├── MultiTenantManager.php       (Core multi-tenant functions)
  ├── TrialLimitsManager.php       (Hard limits enforcement)
  ├── TrustScoreCalculator.php     (Score calculation logic)
  ├── ActivationCodeManager.php    (Code generation & validation)
  ├── ActivityLogger.php           (Activity logging)
  └── AnomalyDetector.php          (Anomaly detection)

public/
  ├── api/
  │   ├── school-activation.php    (POST request handler)
  │   └── activation-status.php    (GET status)
  └── admin/
      ├── activation-requests.php  (Admin platform - optional)
      └── school-status.php        (View status & metrics)

migrations/
  └── 02-multi-tenant-schema.sql   (Database additions)
```

---

## Testing Checklist

- [ ] Sekolah trial tidak bisa exceed limit buku
- [ ] Sekolah trial tidak bisa exceed limit siswa
- [ ] Sekolah trial tidak bisa exceed limit transaksi
- [ ] Trust score otomatis update saat aktivasi diajukan
- [ ] Trust score otomatis activate sekolah saat >= 70
- [ ] Siswa harus input kode sekolah valid
- [ ] Sekolah suspended tidak bisa login
- [ ] Setiap query filter by school_id
- [ ] Session school_id tidak bisa di-override via GET/POST
