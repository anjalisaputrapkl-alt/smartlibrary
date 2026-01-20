# 🛠️ Debugging Tools Reference

Panduan lengkap semua tools untuk debug login error 401.

---

## 📚 Dokumentasi Files

### 1. LOGIN_401_QUICKFIX.md ⚡
**Kapan baca:** Butuh fix cepat (5 menit)
**Isi:** 
- Langkah-langkah quick start
- 4 langkah utama
- Checklist singkat

👉 **Mulai dari sini!**

---

### 2. LOGIN_ERROR_401_GUIDE.md 📖
**Kapan baca:** Butuh penjelasan detail & troubleshooting comprehensive
**Isi:**
- 4 langkah debugging detail
- 4 problem umum + solusi
- Workflow lengkap
- Common mistakes
- Checklist lengkap

👉 **Untuk troubleshooting mendalam**

---

### 3. ERROR_401_EXPLANATION.md 🔍
**Kapan baca:** Ingin memahami sistem login secara mendalam
**Isi:**
- Penjelasan error 401
- Database structure
- Alur membuat akun siswa
- Alur login siswa
- Debugging penyebab error
- Contoh error vs success
- Workflow lengkap

👉 **Untuk pemahaman sistem**

---

## 🖥️ Command Line Tools

### Tool 1: check-students.php
**Tujuan:** Lihat semua siswa di database

```bash
C:\xampp\php\php.exe check-students.php
```

**Kapan gunakan:**
- Cek apakah siswa sudah terdaftar
- Lihat NISN yang benar
- Cek role siswa

**Output example:**
```
1️⃣  USERS table (role = 'student'):
   Total: 1 students
   
   1. ID: 6
      NISN: 111111
      Name: Anjali Saputra
      Role: student
```

---

### Tool 2: test-login-cli.php
**Tujuan:** Test login dari command line dengan detail output

```bash
C:\xampp\php\php.exe test-login-cli.php NISN PASSWORD
```

**Contoh:**
```bash
C:\xampp\php\php.exe test-login-cli.php 111111 111111
```

**Kapan gunakan:**
- Verify apakah NISN dan password cocok
- Debug password verification
- Lihat detail error

**Output example (Success):**
```
✅ NISN found!
   ID: 6
   Name: Anjali Saputra
   Role: student

✅ Role is 'student'
✅ Password verified successfully!

=== RESULT ===
✅ Login would SUCCEED
```

**Output example (Failed):**
```
❌ NISN '9999999999' NOT FOUND in database!

📊 All students in database:
   1. NISN: 111111
      Name: Anjali Saputra
```

---

### Tool 3: test-api-direct.php
**Tujuan:** Simulasi API login request langsung (bukan via browser)

**CLI Mode:**
```bash
C:\xampp\php\php.exe test-api-direct.php NISN PASSWORD
```

**Web Mode:**
```
http://sekolah.localhost/test-api-direct.php?nisn=111111&password=111111
```

**Kapan gunakan:**
- Test API secara isolated dari browser
- Debug request/response JSON
- Verify API logic

**Output example:**
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

---

### Tool 4: fix-nisn-sync.php
**Tujuan:** Sinkronisasi dan repair NISN data dari members ke users table

```bash
C:\xampp\php\php.exe fix-nisn-sync.php
```

**Kapan gunakan:**
- Members punya NISN tapi users tidak
- Perlu membuat akun untuk members yang ada
- Role tidak 'student' dan perlu diperbaiki
- Data sync error setelah bulk operations

**Yang dilakukan:**
1. Cek members tanpa user → CREATE user
2. Cek users tanpa NISN → UPDATE NISN dari members
3. Verify role = 'student'
4. Display laporan hasil

**Output example:**
```
📊 Sinkronisasi NISN Complete!

✅ Created user accounts: 2
✅ Updated NISN fields: 1
⚠️  Role mismatches fixed: 0

Verification:
✓ All students have NISN
✓ All students have role = 'student'
```

---

## 🌐 Browser Tools

### test-api-login.html
**URL:**
```
http://sekolah.localhost/test-api-login.html
```

**Fitur:**

#### 1. Load Data Siswa
- Klik "📊 Load Data Siswa"
- Lihat tabel semua siswa dengan NISN mereka
- Copy NISN untuk test selanjutnya

#### 2. Manual Test API Login
- Input NISN (dari load data)
- Input Password = NISN
- Klik "🔓 Test Login API"
- Lihat response JSON

#### 3. Test Verifikasi Password
- Input NISN
- Klik "🔐 Test Password Hash"
- Lihat apakah password match dengan hash database

#### 4. Test Login Form
- Pilih "Student (NISN)" atau "Admin (Email)"
- Input credentials
- Klik Login
- Lihat success/error

**Kapan gunakan:**
- Lebih suka GUI daripada command line
- Ingin test interaktif
- Browser sudah buka, terminal belum
- Debugging dari perspektif user

---

## 📱 Testing Workflow

### Workflow 1: Cepat Check Data (2 menit)
```bash
# Lihat semua siswa
C:\xampp\php\php.exe check-students.php

# Copy NISN dari output
# Test login
C:\xampp\php\php.exe test-login-cli.php [NISN] [NISN]
```

---

### Workflow 2: Detailed Debugging (5-10 menit)

```bash
# 1. Lihat data siswa
C:\xampp\php\php.exe check-students.php

# 2. Cari NISN yang ingin test
# 3. Test dengan CLI
C:\xampp\php\php.exe test-login-cli.php NISN PASSWORD

# 4. Jika gagal, cek dengan API direct
C:\xampp\php\php.exe test-api-direct.php NISN PASSWORD

# 5. Jika ada sync issue, fix
C:\xampp\php\php.exe fix-nisn-sync.php

# 6. Ulangi step 3
C:\xampp\php\php.exe test-login-cli.php NISN PASSWORD
```

---

### Workflow 3: Browser Testing (GUI)

1. Buka http://sekolah.localhost/test-api-login.html
2. Klik "Load Data Siswa" → copy NISN
3. Input NISN dan Password di form
4. Klik "Test Login API"
5. Lihat response

---

## 🚨 Troubleshooting Decision Tree

```
Error 401?
│
├─ Buka check-students.php
│  │
│  ├─ Tidak ada siswa?
│  │  └─ Tambah siswa di Kelola Murid
│  │
│  ├─ Ada siswa tapi NISN NULL?
│  │  └─ Jalankan fix-nisn-sync.php
│  │
│  ├─ Ada siswa dengan NISN?
│  │  └─ Lanjut ke step 2
│  │
│  └─ Role bukan 'student'?
│     └─ Jalankan fix-nisn-sync.php
│
├─ Jalankan test-login-cli.php NISN PASSWORD
│  │
│  ├─ ✅ Login would SUCCEED?
│  │  └─ Error di browser
│  │     ├─ Cek DevTools (F12)
│  │     ├─ Cek Network tab
│  │     └─ Lihat response status
│  │
│  └─ ❌ Login gagal?
│     ├─ NISN not found?
│     │  └─ Gunakan NISN dari check-students.php
│     │
│     ├─ Password not match?
│     │  └─ Password HARUS sama dengan NISN
│     │
│     └─ Role error?
│        └─ Jalankan fix-nisn-sync.php
│
├─ Jika sudah fix, ulangi test-login-cli.php
│
└─ Jika masih error, cek documentation
   └─ ERROR_401_EXPLANATION.md
```

---

## 🎯 Quick Decision: Mana Tools Harus Dijalankan?

| Skenario | Tools | Urutan |
|----------|-------|--------|
| Pertama kali check data | check-students.php | Langsung |
| Test login siswa | test-login-cli.php | Setelah check-students |
| Test API langsung | test-api-direct.php | Jika perlu isolasi |
| Sinkronisasi NISN | fix-nisn-sync.php | Jika ada masalah sync |
| Prefer GUI | test-api-login.html | Setiap saat |
| Butuh reference | Documentation files | Setiap waktu |

---

## 💾 File Locations

```
C:\xampp\htdocs\perpustakaan-online\
├── check-students.php ← list siswa
├── test-login-cli.php ← test login CLI
├── test-api-direct.php ← test API langsung
├── fix-nisn-sync.php ← sinkronisasi NISN
├── test-api-login.html ← browser testing
├── test-password-hash.php ← test hash
│
├── LOGIN_401_QUICKFIX.md ← quick reference
├── LOGIN_ERROR_401_GUIDE.md ← detailed guide
├── ERROR_401_EXPLANATION.md ← system explanation
├── NISN_LOGIN_TROUBLESHOOTING.md ← initial guide
└── TOOLS_REFERENCE.md ← file ini
```

---

## 📞 Jika Masih Stuck

**Lakukan ini secara berurutan:**

1. **Baca dokumentasi singkat**
   ```
   LOGIN_401_QUICKFIX.md (5 menit)
   ```

2. **Jalankan tools basic**
   ```bash
   check-students.php
   test-login-cli.php [NISN] [NISN]
   ```

3. **Baca dokumentasi detail**
   ```
   LOGIN_ERROR_401_GUIDE.md (10 menit)
   ```

4. **Jalankan fix jika perlu**
   ```bash
   fix-nisn-sync.php
   ```

5. **Ulangi testing**
   ```bash
   test-login-cli.php [NISN] [NISN]
   ```

6. **Jika masih error, baca system explanation**
   ```
   ERROR_401_EXPLANATION.md
   ```

---

**Version:** 1.0
**Last Updated:** 2025-01-20
**Status:** ✅ Complete Reference
