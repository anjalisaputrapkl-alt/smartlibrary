# Modul Riwayat Peminjaman Buku - Dokumentasi

## Daftar Isi
1. [Gambaran Umum](#gambaran-umum)
2. [Struktur File](#struktur-file)
3. [Instalasi & Setup](#instalasi--setup)
4. [Fitur](#fitur)
5. [Penggunaan](#penggunaan)
6. [API Endpoint](#api-endpoint)
7. [Class Model](#class-model)
8. [Keamanan](#keamanan)
9. [Troubleshooting](#troubleshooting)
10. [Contoh Query SQL](#contoh-query-sql)

---

## Gambaran Umum

Modul **Riwayat Peminjaman Buku** adalah fitur lengkap untuk siswa di sistem perpustakaan digital yang memungkinkan mereka melihat:

✅ Semua buku yang pernah dipinjam
✅ Tanggal peminjaman dan tenggat kembali
✅ Status peminjaman (Dipinjam/Dikembalikan/Telat)
✅ Statistik peminjaman (Total, Sedang Dipinjam, Sudah Dikembalikan, Telat)
✅ Cover buku dan informasi lengkap

**Teknologi yang digunakan:**
- Backend: PHP (PDO)
- Database: MySQL/MariaDB
- Frontend: HTML5, Bootstrap 5, CSS3
- API: RESTful JSON & CSV Export

---

## Struktur File

```
perpustakaan-online/
├── public/
│   ├── student-borrowing-history.php    ← Halaman utama
│   └── api/
│       └── borrowing-history.php        ← API endpoint
├── src/
│   ├── config.php                       ← Database config
│   ├── db.php                           ← Database connection
│   ├── auth.php                         ← Authentication helpers
│   └── BorrowingHistoryModel.php        ← Model class
└── img/
    └── covers/                          ← Folder untuk cover buku
```

---

## Instalasi & Setup

### 1. Requirement Database
Pastikan tabel berikut sudah ada di database `perpustakaan_online`:

**Tabel `borrows`** (untuk data peminjaman)
```sql
CREATE TABLE `borrows` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `borrowed_at` datetime DEFAULT current_timestamp(),
  `due_at` datetime DEFAULT NULL,
  `returned_at` datetime DEFAULT NULL,
  `status` enum('borrowed','returned','overdue') DEFAULT 'borrowed',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`book_id`) REFERENCES `books`(`id`),
  FOREIGN KEY (`member_id`) REFERENCES `members`(`id`),
  INDEX (`member_id`),
  INDEX (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Tabel `books`** (untuk data buku)
```sql
CREATE TABLE `books` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `isbn` varchar(100) DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `cover_image` varchar(225) DEFAULT NULL,
  `copies` int(11) DEFAULT 1,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Tabel `members`** (untuk data siswa)
```sql
CREATE TABLE `members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `member_no` varchar(100) DEFAULT NULL,
  `nisn` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2. Konfigurasi Database
Edit file `src/config.php`:

```php
<?php
return [
    'db_host' => '127.0.0.1',
    'db_name' => 'perpustakaan_online',
    'db_user' => 'root',
    'db_pass' => '',
    'base_url' => 'http://localhost/perpustakaan-online/public'
];
```

### 3. Akses File
Setelah setup, akses halaman dari:
```
http://localhost/perpustakaan-online/public/student-borrowing-history.php
```

---

## Fitur

### 1. Tampilan Halaman Utama
- **Header Responsif**: Navigasi lengkap dengan link ke dashboard, katalog, dan logout
- **Statistik Cards**: Menampilkan 4 kartu statistik:
  - Total Peminjaman
  - Sedang Dipinjam
  - Sudah Dikembalikan
  - Telat Dikembalikan
- **Tabel Riwayat**: Menampilkan daftar semua peminjaman dengan kolom:
  - Cover Buku (gambar thumbnail)
  - Judul & Penulis
  - Tanggal Pinjam
  - Tenggat Kembali (dengan perhitungan sisa hari)
  - Tanggal Kembali
  - Status (dengan badge warna)
- **Empty State**: Pesan jika belum ada riwayat peminjaman dengan CTA ke katalog
- **Responsive Design**: Optimal di desktop, tablet, dan mobile

### 2. Keamanan
- ✅ Session check - hanya siswa yang login bisa akses
- ✅ Sanitasi input - semua input di-sanitasi dengan `htmlspecialchars()`
- ✅ Prepared statements - mencegah SQL injection
- ✅ Data isolation - siswa hanya bisa lihat data mereka sendiri
- ✅ Error handling - error ditampilkan dengan aman tanpa debug info

### 3. Performa
- ✅ Optimized queries dengan INDEX pada `member_id` dan `status`
- ✅ LEFT JOIN untuk data buku (tidak error jika buku dihapus)
- ✅ Lazy loading cover image
- ✅ CSS inline untuk style cepat

### 4. UI/UX
- ✅ Gradient modern dengan warna yang konsisten
- ✅ Animasi fade-in saat load
- ✅ Hover effect pada cards dan cover buku
- ✅ Color-coded status (Biru=Dipinjam, Hijau=Dikembalikan, Merah=Telat)
- ✅ Tooltip untuk info sisa hari
- ✅ Font dan spacing yang legible

---

## Penggunaan

### A. Akses Langsung (Browser)

1. **Login sebagai siswa**
2. **Kunjungi halaman riwayat:**
   ```
   http://localhost/perpustakaan-online/public/student-borrowing-history.php
   ```

3. **Lihat riwayat peminjaman Anda** dengan semua detail dan status

### B. Menggunakan Class Model

```php
<?php
require_once 'src/config.php';
require_once 'src/db.php';
require_once 'src/BorrowingHistoryModel.php';

// Inisialisasi model
$model = new BorrowingHistoryModel($pdo);

// Ambil riwayat peminjaman
$history = $model->getBorrowingHistory($memberId);
foreach ($history as $item) {
    echo $item['book_title'] . " - " . $item['status'];
}

// Ambil statistik
$stats = $model->getBorrowingStats($memberId);
echo "Total: " . $stats['total'];
echo "Dipinjam: " . $stats['borrowed'];
echo "Dikembalikan: " . $stats['returned'];
echo "Telat: " . $stats['overdue'];

// Ambil buku yang sedang dipinjam
$current = $model->getCurrentBorrows($memberId);

// Hitung total denda (5000 per hari)
$fine = $model->calculateTotalFine($memberId, 5000);
echo "Total denda: Rp " . number_format($fine);
```

### C. Menggunakan API

```javascript
// Fetch dengan JavaScript
fetch('/perpustakaan-online/public/api/borrowing-history.php')
    .then(response => response.json())
    .then(data => {
        console.log('Total peminjaman:', data.total);
        console.log('Data:', data.data);
    });

// Filter berdasarkan status
fetch('/perpustakaan-online/public/api/borrowing-history.php?status=borrowed')
    .then(response => response.json())
    .then(data => console.log(data));

// Export ke CSV
window.location.href = '/perpustakaan-online/public/api/borrowing-history.php?format=csv';
```

---

## API Endpoint

### Base URL
```
http://localhost/perpustakaan-online/public/api/borrowing-history.php
```

### 1. GET - Ambil Semua Riwayat
**Request:**
```
GET /api/borrowing-history.php
```

**Response (JSON):**
```json
{
    "success": true,
    "data": [
        {
            "borrow_id": 11,
            "member_id": 3,
            "book_id": 18,
            "borrowed_at": "2026-01-19 10:51:43",
            "due_at": "2026-01-20 00:00:00",
            "returned_at": null,
            "status": "borrowed",
            "book_title": "The Art of Stoicism",
            "author": "Andora",
            "cover_image": "book_1768793058_696da3e2b0ead.jpg",
            "isbn": "598467",
            "category": "Non-Fiksi",
            "days_remaining": 1
        }
    ],
    "total": 1,
    "timestamp": "2026-01-20 15:30:00"
}
```

### 2. GET - Filter Berdasarkan Status
**Request:**
```
GET /api/borrowing-history.php?status=borrowed
GET /api/borrowing-history.php?status=returned
GET /api/borrowing-history.php?status=overdue
```

**Valid Status Values:**
- `borrowed` - Sedang dipinjam
- `returned` - Sudah dikembalikan
- `overdue` - Telat dikembalikan

### 3. GET - Export ke CSV
**Request:**
```
GET /api/borrowing-history.php?format=csv
```

**Response:**
File CSV dengan nama `riwayat_peminjaman.csv` akan didownload dengan kolom:
- ID Peminjaman
- Judul Buku
- Penulis
- ISBN
- Kategori
- Tanggal Pinjam
- Tenggat Kembali
- Tanggal Kembali
- Status
- Hari Sisa

### 4. GET - Filter + Pagination (di Model)
```php
$filters = [
    'status' => 'borrowed',
    'limit' => 10,
    'offset' => 0
];
$history = $model->getBorrowingHistory($memberId, $filters);
```

### Error Responses

**401 - Unauthorized:**
```json
{
    "success": false,
    "message": "Unauthorized: Silakan login terlebih dahulu"
}
```

**400 - Bad Request:**
```json
{
    "success": false,
    "message": "Invalid member ID"
}
```

**500 - Server Error:**
```json
{
    "success": false,
    "message": "Database Error",
    "error": "Error details..."
}
```

---

## Class Model

### BorrowingHistoryModel

#### Constructor
```php
__construct($pdo)
```
- Parameter: `$pdo` - PDO database connection object

#### Methods

##### 1. getBorrowingHistory($memberId, $filters = [])
Ambil riwayat peminjaman dengan filter

**Parameters:**
- `$memberId` (int) - ID member siswa
- `$filters` (array) - Optional filters:
  - `status` (string) - 'borrowed', 'returned', atau 'overdue'
  - `limit` (int) - Batas jumlah hasil (default: 100)
  - `offset` (int) - Offset untuk pagination (default: 0)

**Returns:** Array riwayat peminjaman

**Example:**
```php
$history = $model->getBorrowingHistory(3, ['status' => 'borrowed']);
```

##### 2. getBorrowingStats($memberId)
Hitung statistik peminjaman

**Parameters:**
- `$memberId` (int) - ID member siswa

**Returns:** Array dengan key:
- `total` - Total peminjaman
- `borrowed` - Sedang dipinjam
- `returned` - Sudah dikembalikan
- `overdue` - Telat
- `actually_overdue` - Benar-benar telat (masih dipinjam, tapi past due)

**Example:**
```php
$stats = $model->getBorrowingStats(3);
echo $stats['borrowed']; // 2
```

##### 3. getBorrowDetail($borrowId, $memberId)
Ambil detail satu peminjaman

**Parameters:**
- `$borrowId` (int) - ID peminjaman
- `$memberId` (int) - ID member (untuk security)

**Returns:** Array detail atau null

**Example:**
```php
$detail = $model->getBorrowDetail(11, 3);
```

##### 4. calculateTotalFine($memberId, $finePerDay = 5000)
Hitung total denda untuk member

**Parameters:**
- `$memberId` (int) - ID member siswa
- `$finePerDay` (int) - Tarif denda per hari (default: 5000)

**Returns:** Float total denda dalam rupiah

**Example:**
```php
$totalFine = $model->calculateTotalFine(3, 5000); // Rp 5000/hari
echo "Denda: Rp " . number_format($totalFine);
```

##### 5. getCurrentBorrows($memberId)
Ambil buku yang sedang dipinjam dengan status urgency

**Parameters:**
- `$memberId` (int) - ID member siswa

**Returns:** Array buku yang sedang dipinjam

**Example:**
```php
$current = $model->getCurrentBorrows(3);
// ['urgency'] = 'normal', 'warning', atau 'overdue'
```

#### Static Methods

##### formatDate($date, $format = 'd M Y H:i')
Format tanggal untuk tampilan

```php
echo BorrowingHistoryModel::formatDate('2026-01-19 10:51:43');
// Output: 19 Jan 2026 10:51
```

##### getStatusText($status)
Konversi status ke bahasa Indonesia

```php
echo BorrowingHistoryModel::getStatusText('borrowed');
// Output: Dipinjam
```

##### getStatusBadgeClass($status)
Dapatkan CSS class untuk status badge

```php
echo BorrowingHistoryModel::getStatusBadgeClass('overdue');
// Output: badge badge-danger
```

---

## Keamanan

### 1. Session Check
```php
requireAuth(); // Di dalam auth.php
// Akan redirect ke login jika belum autentikasi
```

### 2. SQL Injection Prevention
Semua query menggunakan **prepared statements**:
```php
$stmt = $pdo->prepare("SELECT * FROM borrows WHERE member_id = ?");
$stmt->execute([$memberId]);
```

### 3. XSS Prevention
Semua output ke HTML di-escape dengan `htmlspecialchars()`:
```php
echo htmlspecialchars($item['book_title']);
```

### 4. Input Validation
Semua input di-validate sebelum digunakan:
```php
if (!is_numeric($memberId) || $memberId <= 0) {
    throw new Exception('Invalid member ID');
}
```

### 5. Data Isolation
Siswa hanya bisa akses data mereka sendiri:
```php
WHERE b.member_id = ?  // Menggunakan session member_id
```

### 6. CORS & CSRF
- API endpoint memerlukan session yang valid
- Semua request harus dari domain yang sama

---

## Troubleshooting

### Problem 1: "Akses Ditolak: Member ID tidak valid"
**Penyebab:** User belum login atau session corrupted
**Solusi:**
1. Logout dan login kembali
2. Clear browser cache dan cookies
3. Periksa file `src/auth.php`

### Problem 2: "DB Connection failed"
**Penyebab:** Koneksi database gagal
**Solusi:**
1. Periksa database sudah running (Apache + MySQL)
2. Verifikasi config di `src/config.php`:
   - Host: `127.0.0.1`
   - Database: `perpustakaan_online`
   - User: `root` (default XAMPP)
   - Password: kosong (default XAMPP)
3. Test connection dengan phpMyAdmin

### Problem 3: "Belum Ada Riwayat Peminjaman"
**Penyebab:** Belum ada data peminjaman di database
**Solusi:**
1. Hubungi admin untuk menambah data peminjaman
2. Atau test dengan insert manual:
   ```sql
   INSERT INTO borrows (school_id, book_id, member_id, borrowed_at, due_at, status)
   VALUES (7, 18, 3, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), 'borrowed');
   ```

### Problem 4: Cover Buku Tidak Muncul
**Penyebab:** Path atau nama file salah
**Solusi:**
1. Periksa folder `img/covers/` ada
2. Verify nama file di database kolom `cover_image` benar
3. File image sudah upload ke folder `img/covers/`

### Problem 5: Error di API Endpoint
**Penyebab:** Session tidak valid
**Solusi:**
1. Pastikan sudah login terlebih dahulu
2. Akses API dari browser yang sama (tidak bisa cross-domain)
3. Check developer console (F12) untuk error detail

---

## Contoh Query SQL

### Insert Data Peminjaman
```sql
INSERT INTO borrows (school_id, book_id, member_id, borrowed_at, due_at, status)
VALUES (
    7,                                  -- school_id
    18,                                 -- book_id (ID buku yang dipinjam)
    3,                                  -- member_id (ID siswa)
    NOW(),                              -- borrowed_at (tanggal pinjam)
    DATE_ADD(NOW(), INTERVAL 7 DAY),   -- due_at (tenggat 7 hari)
    'borrowed'                          -- status
);
```

### Return Buku
```sql
UPDATE borrows 
SET 
    returned_at = NOW(),
    status = 'returned'
WHERE id = 11;
```

### Mark as Overdue
```sql
UPDATE borrows 
SET status = 'overdue'
WHERE status = 'borrowed' AND due_at < NOW() AND returned_at IS NULL;
```

### Ambil Semua Peminjaman dengan Detail
```sql
SELECT 
    b.id,
    b.borrowed_at,
    b.due_at,
    b.returned_at,
    b.status,
    bk.title,
    bk.author,
    bk.cover_image,
    m.name as member_name,
    DATEDIFF(b.due_at, NOW()) as days_remaining
FROM borrows b
LEFT JOIN books bk ON b.book_id = bk.id
LEFT JOIN members m ON b.member_id = m.id
WHERE m.id = 3
ORDER BY b.borrowed_at DESC;
```

### Hitung Denda Member
```sql
SELECT 
    m.id,
    m.name,
    COUNT(*) as total_overdue,
    SUM(
        CASE 
            WHEN b.returned_at IS NULL AND b.due_at < NOW()
            THEN DATEDIFF(NOW(), b.due_at)
            WHEN b.returned_at IS NOT NULL AND b.returned_at > b.due_at
            THEN DATEDIFF(b.returned_at, b.due_at)
            ELSE 0
        END
    ) * 5000 as total_fine
FROM members m
LEFT JOIN borrows b ON m.id = b.member_id
WHERE b.status IN ('borrowed', 'overdue')
GROUP BY m.id
HAVING total_fine > 0;
```

---

## Support & Maintenance

### Regular Maintenance Tasks
1. **Backup database** secara berkala
2. **Monitor table size** jika sudah banyak record
3. **Add index** jika query slow:
   ```sql
   CREATE INDEX idx_member_status ON borrows(member_id, status);
   CREATE INDEX idx_book_cover ON books(cover_image);
   ```
4. **Archive old records** untuk performa (optional)

### Future Improvements
- [ ] Filter by date range
- [ ] Export to PDF
- [ ] WhatsApp notification untuk reminder kembali buku
- [ ] Fine payment integration
- [ ] Renew borrow period
- [ ] Book rating & review

---

**Terakhir diupdate: 20 Januari 2026**
**Versi: 1.0.0**
