# 📖 DOKUMENTASI FITUR BARCODE SCANNER

## Sistem Perpustakaan Online - Peminjaman via QR/Barcode

---

## 📋 DAFTAR ISI

1. [Gambaran Umum](#gambaran-umum)
2. [Arsitektur Sistem](#arsitektur-sistem)
3. [Alur Kerja Peminjaman](#alur-kerja-peminjaman)
4. [Struktur Database](#struktur-database)
5. [API Endpoints](#api-endpoints)
6. [Halaman Smartphone](#halaman-smartphone)
7. [Halaman Admin](#halaman-admin)
8. [Instalasi & Setup](#instalasi--setup)
9. [Testing Guide](#testing-guide)
10. [Troubleshooting](#troubleshooting)

---

## Gambaran Umum

### Fitur Utama

- **Barcode Scanner berbasis Kamera Smartphone** menggunakan HTML5 QRCode library
- **Session-based Workflow** dengan token unik untuk keamanan
- **Real-time Polling** untuk sinkronisasi data antara smartphone dan desktop
- **Responsive Design** - Halaman scanner hanya responsive di smartphone, halaman admin tetap desktop-only
- **Validasi Data** - Pengecekan member, stok buku, dan duplikasi peminjaman

### Keuntungan

✅ Tidak perlu hardware barcode scanner fisik\
✅ Menggunakan smartphone yang sudah ada\
✅ Session berakhir otomatis dalam 30 menit (keamanan)\
✅ Validasi real-time setiap scan\
✅ Integrasi seamless dengan sistem admin yang ada\
✅ UI halaman admin tetap tidak berubah

---

## Arsitektur Sistem

### Komponen Utama

```
┌─────────────────────────────────────────────────────────────┐
│                  DESKTOP (Admin)                            │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ public/borrows.php                                   │   │
│  │ - Tombol "Mulai Peminjaman Barcode"                 │   │
│  │ - Menampilkan kode sesi 32 karakter                 │   │
│  │ - Live panel dengan info member & buku              │   │
│  │ - Polling data setiap 2 detik                       │   │
│  │ - Input tanggal jatuh tempo                         │   │
│  │ - Tombol "Simpan Peminjaman"                        │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                          ↓ (API Call)
                  create-barcode-session.php
                          ↓ (Return Token)
┌─────────────────────────────────────────────────────────────┐
│              SMARTPHONE (Scanner)                           │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ public/barcode-scan.php                              │   │
│  │ - Input field untuk kode sesi                        │   │
│  │ - Camera scanner (html5-qrcode)                      │   │
│  │ - Toggle: Scan Member / Scan Book                    │   │
│  │ - Tampilan real-time hasil scan                      │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                   ↓ (Scan & Send)
┌─────────────────────────────────────────────────────────────┐
│                  BACKEND (PHP/MySQL)                        │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ API Endpoints:                                       │   │
│  │ 1. create-barcode-session.php      [Admin POST]      │   │
│  │ 2. verify-barcode-session.php      [Smartphone POST] │   │
│  │ 3. process-barcode-scan.php        [Smartphone POST] │   │
│  │ 4. get-barcode-session-data.php    [Admin GET]       │   │
│  │ 5. complete-barcode-borrowing.php  [Admin POST]      │   │
│  │                                                      │   │
│  │ Database:                                            │   │
│  │ - barcode_sessions (session data)                    │   │
│  │ - members (validated member info)                    │   │
│  │ - books (validated book stock)                       │   │
│  │ - borrows (created borrow records)                   │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

---

## Alur Kerja Peminjaman

### Skenario Lengkap

```
TAHAP 1: INISIASI SESI (Desktop Admin)
├─ Admin klik tombol "Mulai Peminjaman Barcode"
├─ System generate token unik 32 karakter (hex)
├─ Simpan ke database: barcode_sessions
│  ├─ session_token
│  ├─ school_id
│  ├─ status: "active"
│  └─ expires_at: NOW() + 30 minutes
└─ Tampilkan token di layar admin

TAHAP 2: VERIFIKASI SMARTPHONE
├─ Petugas buka halaman barcode-scan.php di smartphone
├─ Input token dari admin
├─ Sistem verifikasi token
│  ├─ Cek token valid
│  ├─ Cek belum expired
│  └─ Cek status "active"
└─ Inisialisasi camera

TAHAP 3: SCAN MEMBER (Smartphone)
├─ Petugas arahkan camera ke barcode anggota (NISN)
├─ QR Code ter-decode
├─ Kirim ke: process-barcode-scan.php
│  ├─ type: "member"
│  ├─ barcode: [NISN value]
│  └─ session_id: [dari session]
├─ Backend validasi:
│  ├─ Member ada
│  ├─ Member aktif
│  └─ Member tidak suspended
├─ Update barcode_sessions:
│  ├─ member_id
│  ├─ member_barcode
│  └─ updated_at: NOW()
├─ Tampilkan nama member di smartphone
└─ Auto-switch ke mode "Scan Buku"

TAHAP 4: SCAN BUKU (Smartphone)
├─ Petugas arahkan camera ke barcode buku (ISBN)
├─ QR Code ter-decode
├─ Kirim ke: process-barcode-scan.php
│  ├─ type: "book"
│  ├─ barcode: [ISBN value]
│  └─ session_id: [dari session]
├─ Backend validasi:
│  ├─ Buku ada
│  ├─ Stok > 0
│  ├─ Member sudah di-scan
│  ├─ Member belum pinjam buku ini (overdue/borrowed)
│  └─ Buku belum terscan di session ini
├─ Append ke JSON array: books_scanned
├─ Update barcode_sessions.books_scanned
├─ Tampilkan buku di smartphone
└─ Siap untuk scan buku lagi (loop)

TAHAP 5: REAL-TIME POLLING (Desktop Admin)
├─ Setiap 2 detik, desktop polling ke: get-barcode-session-data.php
├─ Fetch data:
│  ├─ member info (jika sudah di-scan)
│  ├─ books_scanned array
│  ├─ updated_at timestamp
│  └─ session status
├─ Update live panel di admin
│  ├─ Nama member muncul
│  ├─ Daftar buku yang dipindai
│  └─ Counter jumlah buku
└─ (Admin bisa lihat progress real-time)

TAHAP 6: SET TANGGAL JATUH TEMPO (Desktop Admin)
├─ Admin input tanggal jatuh tempo
├─ Default: 7 hari dari hari ini
├─ Admin validasi data sebelum simpan
└─ Siap klik "Simpan Peminjaman"

TAHAP 7: FINALISASI PEMINJAMAN (Desktop Admin)
├─ Admin klik tombol "Simpan Peminjaman"
├─ Kirim ke: complete-barcode-borrowing.php
│  ├─ session_id
│  ├─ due_date
│  └─ auth: admin session
├─ Backend start transaction:
│  ├─ Untuk setiap buku di books_scanned:
│  │  ├─ INSERT ke table borrows
│  │  │  ├─ school_id
│  │  │  ├─ book_id
│  │  │  ├─ member_id
│  │  │  ├─ borrowed_at: NOW()
│  │  │  ├─ due_at: [dari admin]
│  │  │  └─ status: "borrowed"
│  │  └─ UPDATE books.copies--
│  ├─ UPDATE barcode_sessions
│  │  ├─ status: "completed"
│  │  ├─ due_date: [dari admin]
│  │  └─ updated_at: NOW()
│  └─ COMMIT transaction
├─ Return success response
└─ Refresh halaman admin

SELESAI ✓
└─ Peminjaman tercatat di database
   ├─ Buku status berubah menjadi "dipinjam"
   ├─ Member bisa lihat di history
   └─ Notifikasi dikirim ke member
```

---

## Struktur Database

### Table: barcode_sessions

```sql
CREATE TABLE `barcode_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `session_token` varchar(32) NOT NULL UNIQUE,
  `status` enum('active','completed','expired') DEFAULT 'active',
  `member_barcode` varchar(255) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `books_scanned` longtext DEFAULT NULL COMMENT 'JSON array',
  `due_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NOT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY (`session_token`),
  KEY (`school_id`),
  KEY (`member_id`),
  FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Contoh Data

```json
{
  "id": 1,
  "session_token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6",
  "status": "active",
  "member_barcode": "0094234",
  "member_id": 1,
  "books_scanned": [
    {
      "book_id": 1,
      "title": "Mengunyah Rindu",
      "isbn": "982384",
      "scanned_at": "2026-01-28 10:15:30"
    },
    {
      "book_id": 5,
      "title": "The Psychology of Money",
      "isbn": "9786238371044",
      "scanned_at": "2026-01-28 10:16:45"
    }
  ],
  "due_date": "2026-02-04 23:59:59",
  "expires_at": "2026-01-28 10:45:00"
}
```

---

## API Endpoints

### 1. CREATE-BARCODE-SESSION.PHP

**Endpoint:** `/public/api/create-barcode-session.php`\
**Method:** `POST`\
**Auth:** Admin session required\
**Purpose:** Generate session baru untuk peminjaman barcode

#### Request

```http
POST /perpustakaan-online/public/api/create-barcode-session.php HTTP/1.1
Content-Type: application/json
Cookie: PHPSESSID=...
```

#### Response (Success)

```json
{
  "success": true,
  "message": "Session created successfully",
  "data": {
    "session_id": 1,
    "token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6",
    "expires_in": 1800
  }
}
```

#### Response (Error)

```json
{
  "success": false,
  "message": "Unauthorized"
}
```

---

### 2. VERIFY-BARCODE-SESSION.PHP

**Endpoint:** `/public/api/verify-barcode-session.php`\
**Method:** `POST`\
**Auth:** Not required\
**Purpose:** Verifikasi token di smartphone sebelum scanning

#### Request

```json
{
  "token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6"
}
```

#### Response (Success)

```json
{
  "success": true,
  "message": "Session verified",
  "data": {
    "session_id": 1,
    "school_id": 4
  }
}
```

#### Response (Error)

```json
{
  "success": false,
  "message": "Session expired"
}
```

---

### 3. PROCESS-BARCODE-SCAN.PHP

**Endpoint:** `/public/api/process-barcode-scan.php`\
**Method:** `POST`\
**Auth:** Not required\
**Purpose:** Proses scan barcode member atau buku

#### Request - Scan Member

```json
{
  "session_id": 1,
  "barcode": "0094234",
  "type": "member"
}
```

#### Response - Scan Member (Success)

```json
{
  "success": true,
  "message": "Member scanned successfully",
  "type": "member",
  "data": {
    "member_id": 1,
    "name": "Anjali Saputra",
    "nisn": "0094234"
  }
}
```

#### Request - Scan Book

```json
{
  "session_id": 1,
  "barcode": "982384",
  "type": "book"
}
```

#### Response - Scan Book (Success)

```json
{
  "success": true,
  "message": "Book scanned successfully",
  "type": "book",
  "data": {
    "book_id": 1,
    "title": "Mengunyah Rindu",
    "isbn": "982384",
    "copies_left": 0
  }
}
```

#### Response (Error - Duplicate)

```json
{
  "success": false,
  "message": "This book already scanned",
  "type": "book"
}
```

---

### 4. GET-BARCODE-SESSION-DATA.PHP

**Endpoint:** `/public/api/get-barcode-session-data.php`\
**Method:** `GET` or `POST`\
**Auth:** Not required\
**Purpose:** Polling data session untuk real-time update

#### Request

```http
GET /perpustakaan-online/public/api/get-barcode-session-data.php?session_id=1
```

#### Response

```json
{
  "success": true,
  "data": {
    "session_id": 1,
    "school_id": 4,
    "status": "active",
    "member": {
      "id": 1,
      "name": "Anjali Saputra",
      "nisn": "0094234"
    },
    "books_scanned": [
      {
        "book_id": 1,
        "title": "Mengunyah Rindu",
        "isbn": "982384",
        "scanned_at": "2026-01-28 10:15:30"
      }
    ],
    "books_count": 1,
    "due_date": null,
    "updated_at": "2026-01-28 10:15:30"
  }
}
```

---

### 5. COMPLETE-BARCODE-BORROWING.PHP

**Endpoint:** `/public/api/complete-barcode-borrowing.php`\
**Method:** `POST`\
**Auth:** Admin session required\
**Purpose:** Finalisasi peminjaman dan create borrow records

#### Request

```json
{
  "session_id": 1,
  "due_date": "2026-02-04"
}
```

#### Response (Success)

```json
{
  "success": true,
  "message": "Borrowing completed successfully",
  "data": {
    "session_id": 1,
    "borrows_created": 2,
    "borrows": [
      {
        "borrow_id": 100,
        "book_id": 1,
        "title": "Mengunyah Rindu",
        "due_date": "2026-02-04"
      },
      {
        "borrow_id": 101,
        "book_id": 5,
        "title": "The Psychology of Money",
        "due_date": "2026-02-04"
      }
    ]
  }
}
```

#### Response (Error)

```json
{
  "success": false,
  "message": "No member scanned"
}
```

---

## Halaman Smartphone

### File: public/barcode-scan.php

#### Fitur

1. **Step 1: Input Sesi**
   - Input field untuk token sesi
   - Validasi format (32 karakter)
   - Error handling

2. **Step 2: Scanner**
   - Camera real-time dengan HTML5 QRCode
   - Toggle button: Scan Member / Scan Book
   - Tampilan real-time hasil scan
   - Instruksi untuk user

3. **Step 3: Completion**
   - Ringkasan hasil scan
   - Tombol untuk sesi baru

#### CSS Classes (barcode-scan.css)

```css
.container           /* Container utama */
.step                /* Setiap step */
.card                /* Card styling */
.qr-reader           /* QR scanner container */
.scanned-item        /* Item yang sudah dipindai */
.btn-primary         /* Button utama */
.error-message       /* Pesan error */
.loading-overlay     /* Loading indicator */
```

#### JavaScript (barcode-scan.js)

```javascript
// Main functions:
-goToScanner() -
  initializeScanner() -
  onScanSuccess(decodedText, decodedResult) -
  processMemberScan(barcode) -
  processBookScan(barcode) -
  goToCompletion() -
  showError(element, message) -
  showLoading(show);
```

---

## Halaman Admin

### Modifikasi: public/borrows.php

#### UI Changes

1. **Tombol Barcode**
   - Tombol "Mulai Peminjaman Barcode" dengan styling gradient
   - Posisi di atas statistics section

2. **Session Display**
   - Token display dengan copy button
   - Status badge "AKTIF"
   - Tombol end session

3. **Live Panel**
   - Info member yang sedang di-scan
   - Daftar buku yang sudah di-scan (real-time)
   - Input tanggal jatuh tempo
   - Tombol "Simpan Peminjaman"

#### JavaScript Logic (di borrows.php)

```javascript
// Main functions:
-startPolling() /* Mulai polling setiap 2 detik */ -
  pollSessionData() /* Fetch data dari server */ -
  stopPolling() /* Stop polling */ -
  resetBarcodeSession() /* Reset UI setelah selesai */ -
  escapeHtml(text) /* Sanitasi HTML */ -
  // Event listeners:
  btnStartBarcodeSession -
  btnEndBarcodeSession -
  btnCopySessionToken -
  btnCompleteBarcodeSession;
```

#### Polling Interval

```
Setiap 2 detik (2000ms) admin desktop meminta data terbaru:
GET /api/get-barcode-session-data.php?session_id=X

Response:
- Member info (jika sudah di-scan)
- Books scanned (array)
- Updated timestamp
```

---

## Instalasi & Setup

### Prasyarat

- PHP 7.4+
- MySQL 5.7+
- ngrok atau jaringan lokal
- Browser modern dengan camera (untuk smartphone)

### Step 1: Update Database

```bash
# Login ke phpMyAdmin atau MySQL CLI
mysql -u root -p perpustakaan_online

# Jalankan SQL untuk create table barcode_sessions
# File: sql/perpustakaan_online.sql (sudah diupdate)
```

### Step 2: Verifikasi File API

```
public/api/
├─ create-barcode-session.php ✓
├─ verify-barcode-session.php ✓
├─ process-barcode-scan.php ✓
├─ get-barcode-session-data.php ✓
└─ complete-barcode-borrowing.php ✓
```

### Step 3: Verifikasi File Frontend

```
public/
├─ barcode-scan.php ✓
└─ borrows.php (sudah dimodifikasi) ✓

assets/css/
└─ barcode-scan.css ✓

assets/js/
└─ barcode-scan.js ✓
```

### Step 4: Setup ngrok (Opsional - untuk testing)

```bash
# Download dari https://ngrok.com/download
# Extract dan setup

# Jalankan ngrok
./ngrok http 80

# Akan mendapat URL: https://xxxx-xxxx-xxxx.ngrok.io
# Sebarkan URL ini ke smartphone
```

### Step 5: Akses Halaman

**Desktop Admin:**

```
http://localhost/perpustakaan-online/public/borrows.php
```

**Smartphone Scanner:**

```
http://localhost/perpustakaan-online/public/barcode-scan.php
atau
https://xxxx-xxxx-xxxx.ngrok.io/perpustakaan-online/public/barcode-scan.php
```

---

## Testing Guide

### Skenario Test 1: Alur Normal

```
1. Desktop:
   - Klik "Mulai Peminjaman Barcode"
   - Lihat token muncul di layar
   - Token: aaabbbcccdddeeefff0011223344556

2. Smartphone:
   - Buka barcode-scan.php
   - Input token: aaabbbcccdddeeefff0011223344556
   - Klik "Verifikasi Sesi"

3. Smartphone Scanner:
   - Mode: Scan Anggota
   - Scan barcode member (NISN)
   - Lihat nama member muncul

4. Desktop (Real-time):
   - Lihat nama member muncul di live panel
   - Live panel update otomatis

5. Smartphone Scanner:
   - Auto-switch ke "Scan Buku"
   - Scan barcode buku (ISBN)
   - Lihat judul buku muncul

6. Smartphone:
   - Bisa scan buku lain (repeat step 5)
   - Atau selesai

7. Smartphone:
   - Klik "Selesai Pemindaian"
   - Muncul completion screen

8. Desktop (Admin):
   - Live panel terus update
   - Lihat semua buku di daftar
   - Pilih tanggal jatuh tempo
   - Klik "Simpan Peminjaman"

9. Hasil:
   - Dialog: "✓ Peminjaman berhasil disimpan!"
   - 2 buku telah dipinjam
   - Halaman auto-refresh
   - Lihat di "Daftar Peminjaman Aktif"
```

### Skenario Test 2: Error - Member Tidak Ditemukan

```
1. Smartphone scan: "123456"
2. Error: "Member not found"
3. Coba scan member valid lagi
```

### Skenario Test 3: Error - Buku Stok Habis

```
1. Member: Scanned OK
2. Smartphone scan buku dengan stok 0
3. Error: "Book stock is empty"
4. Scan buku lain
```

### Skenario Test 4: Session Expired

```
1. Desktop: Create session
2. Tunggu > 30 menit
3. Smartphone: Coba verifikasi token
4. Error: "Session expired"
5. Desktop: Create session baru
```

### Skenario Test 5: Duplicate Barcode

```
1. Member: Scanned OK
2. Smartphone scan buku 1
3. Scan lagi buku yang sama
4. Error: "This book already scanned"
5. Scan buku berbeda
```

---

## Troubleshooting

### Problem: Camera tidak bisa diakses di smartphone

**Solusi:**

1. Pastikan browser memberikan izin akses kamera
2. Cek setting Android/iOS: Settings > Apps > Browser > Camera > Allow
3. Coba browser lain (Chrome, Firefox)
4. Pastikan halaman HTTPS (jika via ngrok)

### Problem: Barcode tidak terdeteksi

**Solusi:**

1. Pastikan barcode jelas dan tidak terlalu jauh
2. Barcode harus QR Code format untuk reliabilitas maksimal
3. Coba dengan lighting yang lebih baik
4. Cek format barcode sesuai dengan data di database

### Problem: Polling tidak update di admin

**Solusi:**

1. Cek browser console untuk error (F12)
2. Pastikan session_id ada dan valid
3. Cek database barcode_sessions record ada
4. Cek network tab - API call ke get-barcode-session-data.php
5. Refresh halaman admin

### Problem: "Unauthorized" error

**Solusi:**

1. Admin harus login terlebih dahulu
2. Cek session cookie valid
3. Cek $\_SESSION['user']['role'] === 'admin'
4. Clear browser cookies dan login ulang

### Problem: "Method not allowed"

**Solusi:**

1. Pastikan HTTP method sesuai (GET/POST)
2. Cek headers Content-Type: application/json
3. Lihat dokumentasi API untuk method yang tepat

### Problem: Database error

**Solusi:**

1. Cek table barcode_sessions sudah di-create
2. Cek constraints foreign key valid
3. Cek db.php config connection valid
4. Cek MySQL user memiliki akses ke table

---

## Security Considerations

### Token Security

✓ Token unique per session\
✓ Token 32 karakter hex (random bytes)\
✓ Token auto-expire dalam 30 menit\
✓ Token only valid untuk 1 session\

### Database Security

✓ Prepared statements (PDO prepared)\
✓ Input validation setiap endpoint\
✓ School_id verification\
✓ Admin auth check\

### Data Validation

✓ Session token format check\
✓ Barcode type check (member/book)\
✓ Member existence check\
✓ Book stock check\
✓ Duplicate scan check\

### Improvements (Future)

- [ ] Rate limiting untuk API calls
- [ ] IP whitelist untuk admin endpoints
- [ ] Audit log untuk semua transaksi
- [ ] Two-factor authentication
- [ ] Encryption untuk session data

---

## File Structure

```
perpustakaan-online/
├─ sql/
│  └─ perpustakaan_online.sql (UPDATED - barcode_sessions table)
├─ public/
│  ├─ barcode-scan.php (NEW)
│  ├─ borrows.php (MODIFIED - added barcode UI)
│  └─ api/
│     ├─ create-barcode-session.php (NEW)
│     ├─ verify-barcode-session.php (NEW)
│     ├─ process-barcode-scan.php (NEW)
│     ├─ get-barcode-session-data.php (NEW)
│     └─ complete-barcode-borrowing.php (NEW)
├─ assets/
│  ├─ css/
│  │  └─ barcode-scan.css (NEW)
│  └─ js/
│     └─ barcode-scan.js (NEW)
└─ src/
   └─ (no changes needed)
```

---

## Kontribusi & Support

Untuk pertanyaan atau bug report, silakan hubungi tim development.\
Dokumentasi ini akan di-update seiring perkembangan fitur.

**Version:** 1.0\
**Last Updated:** 28 Januari 2026\
**Developed for:** Sistem Perpustakaan Online Sekolah
