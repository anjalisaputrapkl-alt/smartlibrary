# Fix: Error "Peminjaman tidak bisa dikembalikan (status: )"

## Masalah

Ketika siswa mencoba mengajukan pengembalian buku, muncul error:

```
"Peminjaman tidak bisa dikembalikan (status: )"
```

Status kosong atau tidak valid.

## Penyebab

Ada 2 masalah:

### 1. Database Enum Tidak Lengkap

File: `sql/migrations/perpustakaan_online (2).sql`

```sql
status ENUM('borrowed','returned','overdue')  -- ❌ Tidak ada 'pending_return'
```

Tapi kode mencoba update ke `'pending_return'` yang tidak ada di enum → Database error.

### 2. Variable Undefined

File: `public/api/student-request-return.php` (Line 100)

```php
$helper->createNotification(
    $school_id,
    $student_id,  // ❌ Tidak didefinisikan, seharusnya $student['id']
    ...
);
```

## Solusi Implementasi

### 1. Tambah Status ke Enum

**Jalankan migration:**

```php
sql/migrations/add_pending_return_status.php
```

**Hasil:**

```sql
-- Sebelum
ENUM('borrowed','returned','overdue')

-- Sesudah
ENUM('borrowed','returned','overdue','pending_return')
```

### 2. Fix Bug Variable

File: `public/api/student-request-return.php`

```php
// Sebelum
$student_id  // ❌ Undefined

// Sesudah
$student['id']  // ✓ Correct
```

## Flow Pengembalian Buku

```
Siswa View Riwayat Peminjaman
    ↓
Klik "Ajukan Pengembalian"
    ↓
POST to student-request-return.php
    ├─ Cek borrow_id dan member_id
    ├─ Validasi status (borrowed atau overdue)
    └─ ✓ Valid → Update status ke 'pending_return'
    ↓
Notifikasi terkirim ke admin
    ↓
Admin View borrows.php (Manajemen Peminjaman)
    ↓
Lihat section "Permintaan Pengembalian Menunggu Konfirmasi"
    ↓
Klik "Konfirmasi Pengembalian"
    ↓
Status berubah ke 'returned'
```

## Status Lifecycle

```
BORROWED
   ↓
[Siswa ajukan return]
   ↓
PENDING_RETURN (menunggu konfirmasi admin)
   ↓
[Admin konfirmasi]
   ↓
RETURNED (selesai)

---

BORROWED
   ↓
[Terlambat]
   ↓
OVERDUE
   ↓
[Siswa ajukan return]
   ↓
PENDING_RETURN
   ↓
[Admin konfirmasi]
   ↓
RETURNED
```

## File yang Diperbaiki

✅ `sql/migrations/add_pending_return_status.php` - Migration untuk update enum
✅ `public/api/student-request-return.php` - Fix $student_id bug

## Database Changes

- **Table:** `borrows`
- **Column:** `status`
- **Old:** `ENUM('borrowed','returned','overdue')`
- **New:** `ENUM('borrowed','returned','overdue','pending_return')`

## Testing

1. Login siswa
2. Buka "Riwayat Peminjaman"
3. Klik tombol "Ajukan Pengembalian" pada salah satu buku
4. **Seharusnya berhasil** - Tidak ada error
5. Admin buka "Manajemen Peminjaman"
6. Lihat section "Permintaan Pengembalian Menunggu Konfirmasi"
7. Status berubah dari "Dipinjam" → "Menunggu Konfirmasi"

## Admin Flow

Di `public/borrows.php`:

- **Section 1:** "Permintaan Pengembalian Menunggu Konfirmasi" - Tampil status `'pending_return'`
- **Section 2:** "Daftar Peminjaman Aktif" - Exclude status `'returned'` dan `'pending_return'`
- **Section 3:** "Riwayat Pengembalian" - Tampil status `'returned'` saja

## Verifikasi Database

```sql
-- Check enum values
SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME='borrows' AND COLUMN_NAME='status';

-- Should return:
-- ENUM('borrowed','returned','overdue','pending_return')
```
