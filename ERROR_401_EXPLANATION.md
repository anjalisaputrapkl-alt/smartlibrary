# 🔐 Penjelasan Error 401 & Sistem Login NISN

## ❌ Error 401 Apa?

HTTP Status Code **401 Unauthorized** berarti:
> Server menolak request karena authentication gagal

Dalam konteks login siswa:
- ❌ NISN tidak ditemukan di database
- ❌ Password tidak sesuai dengan hash di database
- ❌ User tidak memiliki role 'student'

---

## 🏗️ Bagaimana Sistem Login NISN Bekerja?

### 1. Database Structure
```
Tabel: users
├── id (INT)
├── nisn (VARCHAR 20) ← NISN Siswa
├── name (VARCHAR)
├── email (VARCHAR)
├── password (VARCHAR) ← Hash bcrypt dari NISN
├── role (ENUM: 'student', 'admin', 'librarian')
└── school_id (INT)

Tabel: members
├── id (INT)
├── nisn (VARCHAR 20) ← Mirror dari users.nisn
├── name (VARCHAR)
├── email (VARCHAR)
├── member_no (VARCHAR)
└── school_id (INT)
```

### 2. Alur Membuat Akun Siswa

**Di Admin Panel (Kelola Murid):**

```
Admin masukkan:
├── Nama: Anjali Saputra
├── Email: anjalisaputra@gmail.com
├── No Murid: 081292593620
└── NISN: 111111 ← PENTING!

Sistem otomatis:
├── INSERT ke members table
│   └── nisn = 111111
├── Hash password = bcrypt('111111')
└── INSERT ke users table
    ├── nisn = 111111
    ├── password = hashed_password
    └── role = 'student'
```

**Hasil di Database:**

```
users table:
┌─────┬─────────────────────┬────────────────┬───────────────┬──────────┬──────────┐
│ id  │ nisn                │ name           │ email          │ password │ role     │
├─────┼─────────────────────┼────────────────┼────────────────┼──────────┼──────────┤
│ 6   │ 111111              │ Anjali Saputra │ anjali@gm.com  │ $2y$10$qA... │ student  │
└─────┴─────────────────────┴────────────────┴────────────────┴──────────┴──────────┘
```

### 3. Alur Login Siswa

**Di Halaman Login (index.php):**

```
Siswa masukkan:
├── NISN: 111111
└── Password: 111111 ← HARUS SAMA DENGAN NISN!

Klik: Login
```

**Di API (public/api/login.php):**

```
1. Receive POST request
   ├── user_type: 'student'
   ├── nisn: '111111'
   └── password: '111111'

2. Query database
   └── SELECT * FROM users 
       WHERE nisn = '111111' 
       AND role = 'student'

3. Validasi password
   └── password_verify('111111', '$2y$10$qA...')

4. Jika semua OK
   ├── Set $_SESSION['user']
   └── Return { success: true, redirect_url: 'student-dashboard.php' }

5. Jika gagal
   └── Return { success: false, message: 'NISN atau password salah' }
       + HTTP Status 401
```

---

## 🔍 Debugging Error 401

### Kemungkinan Penyebab (Urutan Likelihood)

#### 1️⃣ **Password Salah** (PALING SERING)
```
Siswa input: Password = nama siswa / sembarang
Seharusnya:  Password = NISN siswa

Contoh:
❌ NISN: 111111, Password: anjali (SALAH!)
✅ NISN: 111111, Password: 111111 (BENAR!)
```

**Fix:**
```bash
# Test dengan password = NISN
C:\xampp\php\php.exe test-login-cli.php 111111 111111
```

#### 2️⃣ **NISN Tidak Ada di Database**
```
Penyebab:
- Siswa belum ditambahkan di Kelola Murid
- NISN tidak disave saat membuat member
- Ada typo di NISN yang diinput

Cek:
C:\xampp\php\php.exe check-students.php
```

**Fix:**
```
1. Tambah siswa lagi di Kelola Murid
2. Pastikan NISN diisi
3. Klik Simpan
```

#### 3️⃣ **NISN Format Salah**
```
Masalah: NISN punya spasi, zero leading, atau format berbeda

Contoh:
DB:    '111111' (10 karakter)
Input: '0111111' (11 karakter) ← TIDAK MATCH!
```

**Fix:**
```bash
# Lihat format NISN yang tepat
C:\xampp\php\php.exe check-students.php

# Copy NISN persis dari output
C:\xampp\php\php.exe test-login-cli.php [NISN DARI OUTPUT] [NISN DARI OUTPUT]
```

#### 4️⃣ **Role Bukan 'student'**
```
Masalah: User terdaftar sebagai 'admin' bukan 'student'

Cek:
C:\xampp\php\php.exe check-students.php
```

**Fix:**
```bash
# Jalankan sync script
C:\xampp\php\php.exe fix-nisn-sync.php
```

#### 5️⃣ **NISN Tidak Tersinkronisasi**
```
Masalah: Member punya NISN tapi user account tidak punya NISN

Cek:
C:\xampp\php\php.exe check-students.php
→ members punya NISN
→ users tidak punya NISN
```

**Fix:**
```bash
C:\xampp\php\php.exe fix-nisn-sync.php
```

---

## 📊 Testing Tools

### Tool 1: check-students.php
**Gunakan untuk:** Lihat semua siswa di database
```bash
C:\xampp\php\php.exe check-students.php
```

**Output:** Daftar lengkap users dan members dengan NISN mereka

---

### Tool 2: test-login-cli.php
**Gunakan untuk:** Test login dari command line
```bash
C:\xampp\php\php.exe test-login-cli.php 111111 111111
```

**Output:**
```
✅ Login would SUCCEED
   NISN: 111111
   Password: ****
   User: Anjali Saputra
   School ID: 2
```

---

### Tool 3: test-api-direct.php
**Gunakan untuk:** Simulasi API request langsung
```bash
# CLI Mode
C:\xampp\php\php.exe test-api-direct.php 111111 111111

# Web Mode
http://sekolah.localhost/test-api-direct.php?nisn=111111&password=111111&user_type=student
```

**Output:** JSON response sama seperti API asli

---

### Tool 4: test-api-login.html
**Gunakan untuk:** Browser-based testing interface
```
http://sekolah.localhost/test-api-login.html
```

**Fitur:**
- Load daftar siswa dari database
- Test login dengan form
- Verifikasi password hash
- Debug detail API response

---

### Tool 5: fix-nisn-sync.php
**Gunakan untuk:** Sinkronisasi dan repair NISN data
```bash
C:\xampp\php\php.exe fix-nisn-sync.php
```

**Apa yang dilakukan:**
1. Cek members tanpa NISN → create users
2. Cek users tanpa NISN → update dari members
3. Verify semua student punya role 'student'
4. Display laporan hasil sinkronisasi

---

## 🎓 Workflow Lengkap

### Skenario: Tambah Siswa Baru & Test Login

#### Step 1: Admin Tambah Siswa
1. Login di admin panel
2. Buka "Kelola Murid"
3. Klik "Tambah Murid"
4. Isi form:
   ```
   Nama: Budi Santoso
   Email: budi@gmail.com
   No Murid: 0812345678
   NISN: 9876543210 ← PENTING!
   ```
5. Klik "Simpan"
6. Lihat pesan: "✓ Murid berhasil ditambahkan..."

#### Step 2: Verify Data Tersimpan
```bash
C:\xampp\php\php.exe check-students.php
```

**Expected Output:**
```
NISN: 9876543210
Name: Budi Santoso
Role: student
```

#### Step 3: Test Login CLI
```bash
C:\xampp\php\php.exe test-login-cli.php 9876543210 9876543210
```

**Expected Output:**
```
✅ Login would SUCCEED
```

#### Step 4: Test Login di Browser
1. Buka: http://localhost/perpustakaan-online
2. Pilih tab "Siswa"
3. Input:
   ```
   NISN: 9876543210
   Password: 9876543210
   ```
4. Klik Login
5. Lihat redirect ke student-dashboard.php

---

## 🧪 Contoh Error vs Success

### ❌ ERROR 401 - NISN Tidak Ditemukan
```bash
C:\xampp\php\php.exe test-login-cli.php 9999999999 9999999999
```

**Output:**
```
❌ NISN '9999999999' NOT FOUND in database!

📊 All students in database:
   1. ID: 6
      NISN: 111111
      Name: Anjali Saputra
```

---

### ❌ ERROR 401 - Password Salah
```bash
C:\xampp\php\php.exe test-login-cli.php 111111 salah
```

**Output:**
```
1️⃣  Checking NISN in database...
✅ NISN found!

2️⃣  Checking role...
✅ Role is 'student'

3️⃣  Verifying password...
❌ Password does NOT match!

💡 Hint: Password should match NISN (111111)
   Try with password: 111111
```

---

### ✅ SUCCESS - Login OK
```bash
C:\xampp\php\php.exe test-login-cli.php 111111 111111
```

**Output:**
```
=== RESULT ===
✅ Login would SUCCEED
   NISN: 111111
   Password: ****
   User: Anjali Saputra
   School ID: 2

📱 Student dapat login di halaman utama
```

---

## 📋 Checklist: Jika Error 401

- [ ] Jalankan `check-students.php`
- [ ] Lihat apakah NISN ada di database
- [ ] Lihat apakah role = 'student'
- [ ] Jalankan `test-login-cli.php` dengan NISN yang benar
- [ ] Cek apakah output menunjukkan "✅ Login would SUCCEED"
- [ ] Cek di browser: NISN dan Password HARUS SAMA
- [ ] Jika masih error, jalankan `fix-nisn-sync.php`
- [ ] Ulangi test login

---

## 🔗 Related Documentation

- [LOGIN_401_QUICKFIX.md](LOGIN_401_QUICKFIX.md) - Quick reference
- [LOGIN_ERROR_401_GUIDE.md](LOGIN_ERROR_401_GUIDE.md) - Detailed troubleshooting
- [NISN_LOGIN_TROUBLESHOOTING.md](NISN_LOGIN_TROUBLESHOOTING.md) - Initial debugging guide

---

**Last Updated:** 2025-01-20
**Status:** ✅ Complete Documentation
