# 🔐 Error 401 Login - Penjelasan & Solusi

## 📌 Ringkas

**Error 401 Unauthorized** muncul ketika login siswa dengan NISN gagal.

**Solusi cepat:**

1. Pastikan NISN siswa sudah terdaftar (Kelola Murid)
2. Password HARUS sama dengan NISN
3. Jalankan `check-students.php` untuk verifikasi
4. Jalankan `test-login-cli.php` untuk test

---

## ⚡ Quick Start (5 menit)

### Step 1: Cek Siswa

```bash
C:\xampp\php\php.exe check-students.php
```

Catat NISN siswa. Contoh: `111111`

### Step 2: Test Login

```bash
C:\xampp\php\php.exe test-login-cli.php 111111 111111
```

Jika output: `✅ Login would SUCCEED` → OK!

### Step 3: Test di Browser

- Buka: http://localhost/perpustakaan-online
- Tab "Siswa"
- NISN: `111111`
- Password: `111111` ← HARUS SAMA!
- Klik Login

---

## 📚 Dokumentasi

| File                                                           | Tujuan                 | Waktu  |
| -------------------------------------------------------------- | ---------------------- | ------ |
| [LOGIN_401_QUICKFIX.md](LOGIN_401_QUICKFIX.md)                 | Solusi cepat           | 5 min  |
| [LOGIN_ERROR_401_GUIDE.md](LOGIN_ERROR_401_GUIDE.md)           | Troubleshooting detail | 15 min |
| [ERROR_401_EXPLANATION.md](ERROR_401_EXPLANATION.md)           | Penjelasan sistem      | 20 min |
| [NISN_LOGIN_TROUBLESHOOTING.md](NISN_LOGIN_TROUBLESHOOTING.md) | Debugging tools        | 10 min |
| [TOOLS_REFERENCE.md](TOOLS_REFERENCE.md)                       | Referensi semua tools  | 10 min |

👉 **Mulai dari:** LOGIN_401_QUICKFIX.md

---

## 🛠️ Tools Debugging

### CLI Tools

```bash
# Lihat siswa di database
php check-students.php

# Test login dengan NISN
php test-login-cli.php NISN PASSWORD

# Test API request
php test-api-direct.php NISN PASSWORD

# Sinkronisasi NISN data
php fix-nisn-sync.php
```

### Browser Tools

```
http://sekolah.localhost/test-api-login.html
```

📖 Detail: [TOOLS_REFERENCE.md](TOOLS_REFERENCE.md)

---

## 🎯 Common Issues

### ❌ Password Salah

```
❌ NISN: 111111, Password: anjali
✅ NISN: 111111, Password: 111111 ← Harus sama!
```

### ❌ NISN Tidak Ada

- Siswa belum ditambahkan di Kelola Murid
- Jalankan: `check-students.php`

### ❌ Role Bukan 'student'

- Jalankan: `fix-nisn-sync.php`

### ❌ Credential Benar Tapi Tetap Gagal

- Cek browser DevTools (F12)
- Tab Network → lihat request ke `/public/api/login.php`

---

## 🚀 Next Steps

1. **Baca:** [LOGIN_401_QUICKFIX.md](LOGIN_401_QUICKFIX.md)
2. **Jalankan:** `check-students.php`
3. **Test:** `test-login-cli.php NISN PASSWORD`
4. **Jika error:** Baca [LOGIN_ERROR_401_GUIDE.md](LOGIN_ERROR_401_GUIDE.md)
5. **Jika fix:** Jalankan `fix-nisn-sync.php`

---

## 📋 Checklist

- [ ] Baca dokumentasi quick fix
- [ ] Jalankan check-students.php
- [ ] NISN ada di database? ✓
- [ ] Jalankan test-login-cli.php
- [ ] Output menunjukkan ✅ Login OK? ✓
- [ ] Test login di browser dengan NISN
- [ ] Password = NISN? ✓
- [ ] Login berhasil? ✓

---

## 🆘 Support

**Jika masih error:**

1. Baca: ERROR_401_EXPLANATION.md
2. Cek DevTools browser (F12)
3. Lihat Network tab → response dari login API
4. Report error dengan:
   - Output dari check-students.php
   - Output dari test-login-cli.php
   - NISN yang dicoba
   - Error message dari browser

---

**Last Updated:** 2025-01-20  
**Status:** ✅ Ready to Use
