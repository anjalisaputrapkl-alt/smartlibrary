# 🚀 QUICK START - FITUR BARCODE SCANNER

## ⚡ Setup Cepat (5 menit)

### 1️⃣ Update Database

```bash
# Login ke MySQL
mysql -u root -p perpustakaan_online < sql/perpustakaan_online.sql
```

✅ Table `barcode_sessions` sudah ter-create

### 2️⃣ Verifikasi File

- ✅ `public/barcode-scan.php` - Halaman scanner (baru)
- ✅ `public/borrows.php` - Halaman admin (updated)
- ✅ `public/api/create-barcode-session.php` - API endpoint (baru)
- ✅ `public/api/verify-barcode-session.php` - API endpoint (baru)
- ✅ `public/api/process-barcode-scan.php` - API endpoint (baru)
- ✅ `public/api/get-barcode-session-data.php` - API endpoint (baru)
- ✅ `public/api/complete-barcode-borrowing.php` - API endpoint (baru)
- ✅ `assets/css/barcode-scan.css` - Styling (baru)
- ✅ `assets/js/barcode-scan.js` - JavaScript (baru)

### 3️⃣ Test

#### Desktop Admin

```
http://localhost/perpustakaan-online/public/borrows.php
```

👉 Lihat tombol "Mulai Peminjaman Barcode"

#### Smartphone

```
http://localhost/perpustakaan-online/public/barcode-scan.php
```

👉 Bisa input kode sesi & scan barcode

---

## 📱 Alur Penggunaan

```
[Desktop Admin]
    ↓
Klik "Mulai Peminjaman Barcode"
    ↓
Copy token yang ditampilkan
    ↓
Buka di smartphone: barcode-scan.php
    ↓
Input token → Verifikasi
    ↓
Scan anggota → Scan buku (bisa multiple)
    ↓
Desktop: lihat live update
    ↓
Admin: set tanggal jatuh tempo
    ↓
Admin: klik "Simpan Peminjaman"
    ↓
✓ Peminjaman tercatat!
```

---

## 🔧 Troubleshooting

| Problem              | Solusi                                           |
| -------------------- | ------------------------------------------------ |
| Token tidak muncul   | Refresh halaman / Login ulang                    |
| Camera tidak bisa    | Izinkan akses kamera di browser settings         |
| Barcode tidak scan   | Pastikan barcode jelas, gunakan pencahayaan baik |
| Polling tidak update | Cek browser console (F12), refresh halaman       |
| API error 401        | Admin harus login terlebih dahulu                |

---

## 📚 Dokumentasi Lengkap

Baca file: `BARCODE_SCANNER_DOCUMENTATION.md`

---

## 💡 Tips & Tricks

- **Barcode Format**: Gunakan QR Code untuk reliabilitas maksimal
- **Token Sharing**: Petugas bisa memindahkan token via messaging/chat
- **Session Timeout**: Token berlaku 30 menit (auto expire)
- **Multiple Books**: Bisa scan banyak buku dalam 1 session
- **Real-time Sync**: Admin lihat progress scanning otomatis
- **No Hardware**: Tidak perlu scanner fisik, cukup smartphone

---

## 🎯 Fitur Highlight

✨ **Camera-based Scanning** - Gunakan smartphone standar\
✨ **Session Token Security** - Unique token per session\
✨ **Real-time Polling** - Admin lihat progress live\
✨ **Responsive Design** - Mobile scanner responsive, admin desktop-only\
✨ **Smart Validation** - Cek member, stok, duplikasi otomatis\
✨ **Auto Integration** - Langsung terintegrasi dengan sistem borrows\

---

## ❓ FAQ

**Q: Apakah harus ngrok?**\
A: Tidak. Bisa pakai localhost jika smartphone & desktop di jaringan yang sama.

**Q: Bisa offline?**\
A: Tidak. Harus terkoneksi ke server untuk API calls.

**Q: Apakah barcode harus QR?**\
A: Tidak, bisa barcode 1D juga, tapi QR lebih reliable.

**Q: Berapa lama session berlaku?**\
A: 30 menit dari dibuat, auto-expire setelah itu.

**Q: Bisa custom tanggal jatuh tempo?**\
A: Ya, admin input tanggal apapun sebelum simpan.

---

## 📞 Support

Untuk bantuan lebih lanjut, baca dokumentasi lengkap atau hubungi tim development.

**Version:** 1.0 | **Date:** 28 January 2026
