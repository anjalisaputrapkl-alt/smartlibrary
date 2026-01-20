# 🚀 QUICK START: Fix Error 401 Login Siswa

Error 401 = Credentials tidak valid. Ikuti panduan cepat ini:

## 1️⃣ Cek Siswa di Database (1 menit)

```bash
C:\xampp\php\php.exe check-students.php
```

Catat NISN siswa yang muncul. Contoh: `111111`

## 2️⃣ Test Login dengan NISN Tersebut (1 menit)

```bash
C:\xampp\php\php.exe test-login-cli.php 111111 111111
```

Jika output menunjukkan `✅ Login would SUCCEED` → sistem login OK

## 3️⃣ Test di Browser (2 menit)

Buka: http://localhost/perpustakaan-online

- Pilih tab "Siswa"
- NISN: `111111` (dari step 1)
- Password: `111111` (HARUS sama dengan NISN!)
- Klik Login

## 4️⃣ Jika Masih Error 401

Buka di browser:

```
http://sekolah.localhost/test-api-login.html
```

Gunakan interface testing di sana untuk debug lebih detail.

---

## 🎯 Key Points

✅ **Password HARUS sama dengan NISN**
✅ **NISN tanpa spasi atau karakter khusus**
✅ **Gunakan NISN dari daftar students yang muncul di check-students.php**

---

## 📚 Dokumentasi Lengkap

Baca: [LOGIN_ERROR_401_GUIDE.md](LOGIN_ERROR_401_GUIDE.md)

---

## 🛠️ Jika Siswa Belum Terdaftar

1. Login sebagai admin/sekolah
2. Buka menu "Kelola Murid"
3. Klik "Tambah Murid"
4. Isi: Nama, Email, No Murid, **NISN**
5. Klik Simpan

Kemudian ulangi langkah 1 di atas.
