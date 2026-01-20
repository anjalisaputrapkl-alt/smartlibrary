# Modul Riwayat Peminjaman Buku - Quick Start Guide

## 📋 Overview

Modul lengkap untuk menampilkan riwayat peminjaman buku untuk siswa di sistem perpustakaan digital. 

**Fitur utama:**
- ✅ Halaman riwayat peminjaman dengan UI modern
- ✅ Statistik peminjaman (Total, Dipinjam, Dikembalikan, Telat)
- ✅ Tabel detail dengan cover buku, judul, penulis, status
- ✅ API JSON untuk integrasi
- ✅ Export CSV
- ✅ Hitung denda otomatis
- ✅ Responsive design (mobile-friendly)

---

## 🚀 Setup (5 Menit)

### 1. Pastikan Database Sudah Ada
Database `perpustakaan_online` dengan tabel:
- `members` (siswa)
- `books` (buku)
- `borrows` (peminjaman) ← PALING PENTING

Jika belum, buka phpMyAdmin dan import: `sql/migrations/perpustakaan_online.sql`

### 2. Copy File-File Berikut

**File yang sudah dibuat:**

```
public/student-borrowing-history.php       ← Halaman utama
public/api/borrowing-history.php           ← API endpoint
src/BorrowingHistoryModel.php              ← Class model
sql/migrations/sample-borrowing-history.sql ← Sample data
test-borrowing-history.php                 ← Test script
```

### 3. Insert Sample Data (Optional tapi recommended)

Buka phpMyAdmin → SQL → Paste kode dari:
```
sql/migrations/sample-borrowing-history.sql
```

Atau jalankan via terminal:
```bash
mysql -u root perpustakaan_online < sql/migrations/sample-borrowing-history.sql
```

### 4. Test
Akses di browser:
```
http://localhost/perpustakaan-online/public/student-borrowing-history.php
```

Atau jalankan test script:
```bash
php test-borrowing-history.php
```

---

## 📁 File Structure

```
perpustakaan-online/
├── public/
│   ├── student-borrowing-history.php    [HALAMAN UTAMA]
│   └── api/
│       └── borrowing-history.php        [API ENDPOINT]
├── src/
│   ├── config.php                       [DB CONFIG]
│   ├── db.php                           [DB CONNECTION]
│   ├── auth.php                         [AUTH HELPER]
│   └── BorrowingHistoryModel.php        [MODEL CLASS]
├── sql/
│   └── migrations/
│       └── sample-borrowing-history.sql [SAMPLE DATA]
└── test-borrowing-history.php           [TEST SCRIPT]
```

---

## 🎯 Penggunaan

### A. Akses Halaman (Untuk Siswa)

1. **Login** sebagai siswa
2. **Kunjungi:** `student-borrowing-history.php`
3. **Lihat** riwayat peminjaman dengan statistik lengkap

### B. Gunakan Class Model (Untuk Developer)

```php
<?php
require_once 'src/db.php';
require_once 'src/BorrowingHistoryModel.php';

$model = new BorrowingHistoryModel($pdo);

// Ambil riwayat
$history = $model->getBorrowingHistory($memberId);

// Ambil statistik
$stats = $model->getBorrowingStats($memberId);

// Ambil buku yang dipinjam
$current = $model->getCurrentBorrows($memberId);

// Hitung denda
$fine = $model->calculateTotalFine($memberId, 5000); // Rp 5000/hari
```

### C. Gunakan API (Untuk Frontend/JS)

```javascript
// Ambil riwayat JSON
fetch('/perpustakaan-online/public/api/borrowing-history.php')
    .then(r => r.json())
    .then(data => console.log(data));

// Filter status
fetch('/perpustakaan-online/public/api/borrowing-history.php?status=borrowed')
    .then(r => r.json())
    .then(data => console.log(data));

// Export CSV
window.location.href = '/perpustakaan-online/public/api/borrowing-history.php?format=csv';
```

---

## 🔒 Keamanan

✅ **Session Check** - Hanya siswa yang login bisa akses
✅ **SQL Injection Prevention** - Prepared statements
✅ **XSS Prevention** - Output di-escape dengan htmlspecialchars()
✅ **Input Validation** - Semua input di-validasi
✅ **Data Isolation** - Siswa hanya bisa lihat data mereka
✅ **Error Handling** - Error ditampilkan aman tanpa debug info

---

## 🐛 Troubleshooting

### Error: "Akses Ditolak"
→ Logout dan login kembali, atau clear cookies

### Error: "DB Connection failed"
→ Cek konfigurasi di `src/config.php`
→ Pastikan MySQL running di XAMPP Control Panel

### Error: "Belum Ada Riwayat Peminjaman"
→ Insert sample data dulu (lihat setup #3)

### Cover Buku Tidak Muncul
→ Pastikan folder `img/covers/` ada dan file image sudah di-upload
→ Cek nama file di database column `cover_image`

### API Error 401
→ Pastikan sudah login di browser yang sama
→ Cek session belum expired

---

## 📊 Database Schema

**Tabel BORROWS** (Data Peminjaman)
```sql
id              INT         ← ID peminjaman
school_id       INT         ← ID sekolah
book_id         INT         ← ID buku (FK ke books)
member_id       INT         ← ID siswa (FK ke members)
borrowed_at     DATETIME    ← Tanggal pinjam
due_at          DATETIME    ← Tenggat kembali
returned_at     DATETIME    ← Tanggal kembali
status          ENUM        ← borrowed/returned/overdue
```

**Tabel BOOKS** (Data Buku)
```sql
id              INT         ← ID buku
school_id       INT         ← ID sekolah
title           VARCHAR     ← Judul buku
author          VARCHAR     ← Penulis
cover_image     VARCHAR     ← Nama file cover
```

**Tabel MEMBERS** (Data Siswa)
```sql
id              INT         ← ID siswa
school_id       INT         ← ID sekolah
name            VARCHAR     ← Nama siswa
email           VARCHAR     ← Email
```

---

## 🎨 Fitur & Customization

### Ubah Warna
Edit di `student-borrowing-history.php` bagian `:root`:
```css
:root {
    --primary-color: #667eea;      ← Ubah warna utama
    --danger-color: #f56565;       ← Ubah warna danger
    --success-color: #48bb78;      ← Ubah warna success
}
```

### Ubah Tarif Denda
Edit di Model atau langsung di class:
```php
$fine = $model->calculateTotalFine($memberId, 5000); // Rp 5000/hari
```

### Ubah Durasi Peminjaman Default
Edit saat create borrow:
```php
due_at = DATE_ADD(NOW(), INTERVAL 7 DAY); // 7 hari
```

### Add Filter by Date Range
Edit method di `BorrowingHistoryModel.php`:
```php
if (!empty($filters['from_date'])) {
    $query .= " AND b.borrowed_at >= ?";
    $params[] = $filters['from_date'];
}
```

---

## 📈 Query SQL Useful

### Auto Update Status Overdue
```sql
UPDATE borrows 
SET status = 'overdue'
WHERE status = 'borrowed' 
  AND due_at < NOW() 
  AND returned_at IS NULL;
```

### Return Buku
```sql
UPDATE borrows 
SET returned_at = NOW(), status = 'returned'
WHERE id = ?;
```

### Hitung Denda per Member
```sql
SELECT 
    m.id, m.name,
    SUM(CASE 
        WHEN b.returned_at > b.due_at 
        THEN DATEDIFF(b.returned_at, b.due_at) * 5000
        ELSE 0
    END) as total_fine
FROM members m
LEFT JOIN borrows b ON m.id = b.member_id
GROUP BY m.id;
```

---

## 🔄 Integration dengan Dashboard

Copy kode dari `BORROWING_HISTORY_INTEGRATION.php` untuk:
- Widget statistik di dashboard
- Widget buku sedang dipinjam
- Notification badge
- AJAX auto-refresh

---

## 📚 Documentation

**Dokumentasi Lengkap:**
→ Buka file `BORROWING_HISTORY_GUIDE.md`

**Panduan Integrasi:**
→ Buka file `BORROWING_HISTORY_INTEGRATION.php`

---

## 📞 Support

Jika ada error atau pertanyaan:

1. **Check Console** (F12 → Console tab)
2. **Check Server Error Log** (`php error_log`)
3. **Check Database** (phpMyAdmin → Query)
4. **Run Test Script** (`php test-borrowing-history.php`)

---

## ✨ Features Summary

| Feature | Status | Notes |
|---------|--------|-------|
| List riwayat peminjaman | ✅ | Dengan cover, judul, penulis |
| Filter by status | ✅ | API & Model |
| Statistik | ✅ | Total, Dipinjam, Dikembalikan, Telat |
| Hitung hari sisa | ✅ | Auto update |
| Hitung denda | ✅ | Customizable per hari |
| Export CSV | ✅ | Download langsung |
| Export PDF | ❌ | Bisa ditambah |
| Reminder email | ❌ | Bisa ditambah |
| Renew peminjaman | ❌ | Bisa ditambah |
| Payment integration | ❌ | Bisa ditambah |

---

## 📝 Version

- **Version:** 1.0.0
- **Last Updated:** 20 January 2026
- **Status:** Production Ready
- **License:** MIT / Open Source

---

## 🎉 Done!

Modul siap digunakan. Jika ada pertanyaan, check dokumentasi lengkap di `BORROWING_HISTORY_GUIDE.md`

Happy coding! 🚀
