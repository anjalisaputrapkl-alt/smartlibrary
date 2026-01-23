# Fix: Invalid Session Data & Foreign Key Constraint Error

## Masalah

Siswa mendapat error ketika meminjam buku:

1. **"Invalid session data"** - Data siswa tidak sesuai
2. **Foreign Key Constraint Violation** - `member_id` tidak ada di tabel `members`

## Penyebab

1. **Dua tabel terpisah** - Data siswa di `users` dan `members`
2. **Foreign key constraint ketat** - `borrows.member_id` harus ada di `members.id`
3. **Sinkronisasi tidak otomatis** - Saat siswa daftar/login, tidak ada member record

## Solusi

Implementasi **auto-create member** saat siswa login:

### Flow

```
User Login
  ↓
Check Session
  ↓
MemberHelper→getMemberId()
  ├─ Cari di tabel members dengan NISN
  ├─ Jika tidak ada → INSERT ke members otomatis
  └─ Return member_id yang valid
  ↓
Gunakan member_id untuk transaksi peminjaman
```

### Code Pattern

```php
require_once __DIR__ . '/../src/MemberHelper.php';

$memberHelper = new MemberHelper($pdo);
$member_id = $memberHelper->getMemberId($_SESSION['user']);
// member_id dijamin valid, ada di tabel members
```

## File yang Diperbaiki

✅ `src/MemberHelper.php` - Helper class untuk lookup & auto-create member
✅ `public/api/borrow-book.php` - Gunakan MemberHelper
✅ `public/api/student-request-return.php` - Gunakan MemberHelper  
✅ `public/student-dashboard.php` - Gunakan MemberHelper
✅ `public/student-borrowing-history.php` - Gunakan MemberHelper

## Cara Kerja MemberHelper

```php
getMemberId($userSession)
├─ Input: $_SESSION['user']
├─ Process:
│  ├─ Cek NISN di session
│  ├─ Query tabel members dengan NISN
│  ├─ Jika tidak ada:
│  │  └─ INSERT member baru dengan data dari user
│  └─ Return member_id
└─ Output: integer member_id (dijamin ada di members table)
```

## Error Resolution

### Sebelum Fix

```
❌ Foreign Key Error 1452
   Cannot add or update a child row
   member_id tidak ada di tabel members
```

### Sesudah Fix

```
✅ Member otomatis dibuat
✅ member_id valid
✅ Peminjaman berhasil
```

## Fitur Tambahan

- Fallback ke `user_id` jika auto-create gagal
- Error logging untuk debugging
- Validasi session yang fleksibel

## Testing

1. Login dengan akun siswa baru (belum ada member record)
2. Lihat di tabel `members` - otomatis terisi
3. Coba pinjam buku - seharusnya berhasil
4. Check `borrows` table - data tercatat dengan member_id yang valid

## Database Impact

Minimal - hanya INSERT ke tabel `members` saat login pertama kali. Data struktur tidak berubah, hanya logic yang diupdate.
