# Perpustakaan Online (Native PHP)

Sistem perpustakaan multi-tenant sederhana. Ketika sebuah sekolah mendaftar, dibuatkan data sekolahnya dan admin awal.

## Fitur awal

- Pendaftaran sekolah (tenant)
- Login admin per sekolah
- CRUD Buku
- CRUD Anggota
- Pinjam / Kembalikan buku

## Persyaratan

- PHP 7.4+ (PDO extension)
- MySQL
- Web server (Apache/Nginx) atau PHP built-in server

## Instalasi (cepat)

1. Salin repo ke server, misal `c:\Users\...\perpustakaan-online`
2. Edit `src/config.php` dan atur kredensial MySQL
3. Import database schema:
   - via CLI: `mysql -u root -p < sql/schema.sql` (atau gunakan Workbench/phpMyAdmin)
   - atau buat database `perpustakaan_online` dan jalankan `sql/schema.sql`
4. (Opsional) Hapus placeholder user dalam `sql/schema.sql` atau ganti password placeholder
5. Jalankan server development:
   - `php -S localhost:8000 -t public`
   - Buka `http://localhost:8000` atau sesuai `base_url` di `src/config.php`

## Penggunaan

- Gunakan menu `Daftarkan Sekolah` untuk membuat sekolah baru dan admin.
- Login dengan akun admin yang dibuat tadi.

## Catatan keamanan & lanjutan

- Saat produksi, gunakan HTTPS
- Tambahkan CSRF protection dan validasi input lebih ketat
- Pertimbangkan multi-tenant pilihan: satu DB + `school_id` (digunakan sekarang) atau DB terpisah per tenant

## Langkah berikutnya yang bisa saya kerjakan

- Tambah pagination dan pencarian
- Export/Report peminjaman
- Perbaiki UI dengan Bootstrap
