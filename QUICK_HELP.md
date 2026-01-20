# 🔐 Error 401 - SOLUSI RINGKAS

## ❌ Masalah
Login siswa gagal dengan error **401 Unauthorized**

## ✅ Solusi (5 Menit)

### Step 1: Cek Database
```bash
php check-students.php
```
**Catat NISN siswa** (contoh: `111111`)

### Step 2: Test Login
```bash
php test-login-cli.php 111111 111111
```
**Harus output:** `✅ Login would SUCCEED`

### Step 3: Test di Browser
- URL: `http://localhost/perpustakaan-online`
- Tab: "Siswa"
- NISN: `111111`
- **Password: `111111`** ← HARUS SAMA DENGAN NISN!
- Klik: Login

---

## 🚨 Jika Masih Error

| Problem | Solution |
|---------|----------|
| ❌ NISN tidak ada | Tambah siswa di "Kelola Murid" |
| ❌ NISN NULL di DB | `php fix-nisn-sync.php` |
| ❌ Password salah | Password HARUS = NISN |
| ❌ Role bukan 'student' | `php fix-nisn-sync.php` |

---

## 📚 Dokumentasi Lengkap

👉 **[INDEX.md](INDEX.md)** - Panduan lengkap & tools reference

---

## 🌐 Browser Testing

```
http://sekolah.localhost/test-api-login.html
```

---

**Tanpa hasil?** → Baca [INDEX.md](INDEX.md)
