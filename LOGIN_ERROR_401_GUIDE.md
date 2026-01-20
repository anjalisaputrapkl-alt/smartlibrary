# 🔐 Fix Error 401 Unauthorized pada Login Siswa

Error **401 (Unauthorized)** berarti server menolak login karena:

1. NISN tidak ditemukan di database
2. Password tidak sesuai
3. Role siswa bukan 'student'

## ✅ Langkah-Langkah untuk Mengatasi

### Langkah 1: Cek Siswa Terdaftar

Jalankan di terminal:

```bash
C:\xampp\php\php.exe check-students.php
```

**Contoh Output:**

```
=== CHECKING STUDENTS IN DATABASE ===

1️⃣  USERS table (role = 'student'):
   Total: 1 students

1. ID: 6
   NISN: 111111
   Name: Anjali Saputra
   Email: anjalisaputra@gmail.com
   Role: student
```

**Apa yang dicari:**

- ✅ Siswa muncul dengan NISN yang benar
- ✅ Role adalah 'student'
- ❌ NISN NULL = masalah data sync
- ❌ Role bukan 'student' = salah konfigurasi

---

### Langkah 2: Test Login di Browser

Buka di browser:

```
http://sekolah.localhost/test-api-login.html
```

**Fitur yang tersedia:**

**1. Load Data Siswa**

- Klik tombol "📊 Load Data Siswa"
- Lihat daftar siswa dengan NISN mereka
- **COPY NISN yang akan ditest**

**2. Manual Test API Login**

- Paste NISN dari daftar
- Input Password = **SAMA dengan NISN**
- Klik "🔓 Test Login API"
- Lihat response JSON

**3. Test Verifikasi Password**

- Input NISN
- Klik "🔐 Test Password Hash"
- Lihat apakah password cocok dengan hash di database

---

### Langkah 3: Test Login dari Command Line

Untuk testing lebih detail:

```bash
C:\xampp\php\php.exe test-login-cli.php NISN PASSWORD
```

**Contoh:**

```bash
C:\xampp\php\php.exe test-login-cli.php 111111 111111
```

**Output:**

```
=== TEST LOGIN SISWA ===
NISN: 111111
Password: ******

1️⃣  Checking NISN in database...
✅ NISN found!
   ID: 6
   Name: Anjali Saputra
   Email: anjalisaputra@gmail.com
   Role: student

2️⃣  Checking role...
✅ Role is 'student'

3️⃣  Verifying password...
   Input password: 111111
   DB hash: $2y$10$qAC3LyD.kq/jFmdqOVvOTu8...
✅ Password verified successfully!

=== RESULT ===
✅ Login would SUCCEED
   NISN: 111111
   Password: ******
   User: Anjali Saputra
```

---

### Langkah 4: Test API Secara Langsung (CLI)

```bash
C:\xampp\php\php.exe test-api-direct.php NISN PASSWORD
```

**Contoh:**

```bash
C:\xampp\php\php.exe test-api-direct.php 111111 111111
```

**Output (Success):**

```json
{
  "success": true,
  "message": "Login berhasil",
  "redirect_url": "student-dashboard.php",
  "user": {
    "id": 6,
    "name": "Anjali Saputra",
    "nisn": "111111",
    "school_id": 2
  }
}
```

**Output (Failed):**

```json
{
  "success": false,
  "message": "NISN atau password salah"
}
```

---

## 🔍 Troubleshooting Umum

### Problem 1: ❌ NISN NOT FOUND

**Penyebab:** NISN tidak ada di database users
**Solusi:**

1. Cek apakah siswa sudah ditambahkan di Kelola Murid
2. Jika ada di Kelola Murid tapi tidak di Users → run fix-nisn-sync.php

```bash
C:\xampp\php\php.exe fix-nisn-sync.php
```

### Problem 2: ❌ Role bukan 'student'

**Penyebab:** Siswa terdaftar dengan role 'admin' atau 'librarian'
**Solusi:**

1. Hapus akun di Users table
2. Tambah ulang siswa di Kelola Murid
3. Atau run fix-nisn-sync.php

### Problem 3: ❌ Password tidak match

**Penyebab:** Password tidak sama dengan NISN
**Solusi:**

- **Password HARUS sama dengan NISN**
- Contoh: NISN = 1234567890, maka password = 1234567890
- Hanya angka, tidak ada spasi atau karakter lain

### Problem 4: ❌ Credential benar tapi tetap gagal login

**Penyebab:** Kemungkinan API endpoint tidak accessible
**Solusi:**

1. Cek di browser Developer Tools (F12)
2. Buka tab Network
3. Cek response dari POST http://localhost/perpustakaan-online/public/api/login.php
4. Apakah status 200 atau 401?

Jika error 401 tapi test-api-direct.php berhasil → ada masalah dengan form submission atau API access

---

## 📝 Contoh Workflow Lengkap

**Skenario:** Tambah siswa baru, lalu test login

### 1. Tambah Siswa via Halaman Admin

- Login sebagai admin/sekolah
- Buka "Kelola Murid"
- Klik "Tambah Murid"
- Input: Nama, Email, No Murid, **NISN**
- Klik Simpan

**Expected:**

```
✓ Murid berhasil ditambahkan.
Akun siswa otomatis terbuat dengan NISN: 1234567890
dan Password: 1234567890
```

### 2. Cek Data Tersimpan

```bash
C:\xampp\php\php.exe check-students.php
```

**Expected:** Siswa muncul di Users table dengan NISN dan role='student'

### 3. Test Login

```bash
C:\xampp\php\php.exe test-login-cli.php 1234567890 1234567890
```

**Expected:**

```
✅ Login would SUCCEED
   NISN: 1234567890
   Password: ****
   User: Nama Siswa
```

### 4. Test Login di Browser

- Buka http://localhost/perpustakaan-online
- Klik tab "Siswa"
- Input: NISN = 1234567890
- Input: Password = 1234567890
- Klik Login

**Expected:** Redirect ke student-dashboard.php

---

## 🛠️ File-File Debugging

### check-students.php

Lihat semua siswa yang terdaftar di database

```bash
php check-students.php
```

### test-login-cli.php

Test login dari command line dengan detail output

```bash
php test-login-cli.php NISN PASSWORD
```

### test-api-direct.php

Simulasi API login request langsung

```bash
php test-api-direct.php NISN PASSWORD
```

### test-api-login.html

Browser-based testing interface

```
http://sekolah.localhost/test-api-login.html
```

### fix-nisn-sync.php

Sinkronisasi NISN dari members ke users table

```bash
php fix-nisn-sync.php
```

---

## 📋 Checklist Debugging

- [ ] Jalankan `check-students.php` → lihat siswa terdaftar
- [ ] NISN ada di database dengan role='student' ✓
- [ ] Jalankan `test-login-cli.php` dengan NISN siswa
- [ ] Output menunjukkan "✅ Login would SUCCEED" ✓
- [ ] Buka `test-api-login.html` di browser
- [ ] Test login di halaman utama dengan NISN dan password yang sama
- [ ] Verifikasi redirect ke student-dashboard.php ✓

---

## ⚠️ Common Mistakes

| ❌ Salah                            | ✅ Benar                                |
| ----------------------------------- | --------------------------------------- |
| Password = nama siswa               | Password = NISN                         |
| Password punya spasi                | Password tanpa spasi                    |
| NISN punya leading zero yang hilang | Gunakan NISN persis seperti di database |
| Coba login sebelum tambah siswa     | Tambah siswa dulu di Kelola Murid       |
| Ganti NISN di Users table manual    | Selalu update via Kelola Murid          |

---

## 🆘 Masih Error?

Jika masih error setelah semua langkah di atas:

1. **Cek PHP error log** (jika ada):
   - Lihat di C:\xampp\logs atau error_log milik server
   - error_log() akan mencatat setiap percobaan login

2. **Cek network di browser (F12)**:
   - Tab Network → filter XHR
   - Lihat request ke public/api/login.php
   - Status harusnya 200 (success) atau 401 (failed)

3. **Cek database manual**:
   - Buka phpMyAdmin
   - Tabel `users` → cari row dengan nisn yang ditest
   - Lihat apakah role adalah 'student'
   - Lihat apakah password hash ada

4. **Jalankan fix script**:

   ```bash
   C:\xampp\php\php.exe fix-nisn-sync.php
   ```

5. **Contact Support dengan informasi**:
   - Output dari check-students.php
   - Output dari test-login-cli.php
   - NISN yang dicoba login

---

**Dibuat:** 2025-01-20
**Status:** ✅ Dokumentasi Lengkap
