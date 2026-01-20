🔧 TROUBLESHOOTING: LOGIN NISN GAGAL

═══════════════════════════════════════════════════════════════════════════════

MASALAH: "NISN dan Password sudah benar tapi login masih salah"

═══════════════════════════════════════════════════════════════════════════════

✅ CHECKLIST DEBUGGING

1️⃣ APAKAH SISWA SUDAH TERDAFTAR?

   Buka di browser: http://sekolah.localhost/debug-nisn.php
   
   Lihat output:
   - Struktur tabel users & members
   - Daftar semua siswa yang terdaftar
   - Test login manual
   
   ✓ Jika siswa ada & NISN muncul di database → Lanjut step 2
   ✗ Jika siswa tidak ada → Admin harus menambah siswa di "Kelola Murid"

═══════════════════════════════════════════════════════════════════════════════

2️⃣ PASTIKAN KOLOM NISN ADA DI TABEL

   Buka browser console (F12) → Console tab
   
   Paste JavaScript ini:
   ```
   fetch('debug-nisn.php')
     .then(r => r.text())
     .then(html => console.log(html))
   ```
   
   Cek apakah output menunjukkan kolom NISN di struktur tabel:
   ✓ Output menunjukkan NISN column → Database OK
   ✗ Tidak ada NISN → Migration gagal, jalankan:
      C:\xampp\php\php.exe fix-nisn-sync.php

═══════════════════════════════════════════════════════════════════════════════

3️⃣ TEST LOGIN INTERAKTIF

   Buka di browser: http://sekolah.localhost/test-login.html
   
   Halaman ini akan:
   ✓ Menampilkan semua siswa yang terdaftar
   ✓ Memudahkan test login tanpa modal
   ✓ Menunjukkan error message yang detail
   ✓ Link ke error log jika ada issue
   
   Cara test:
   1. Klik "Lihat Data Siswa"
   2. Lihat NISN mana yang ada
   3. Isi form dengan NISN & password (harus sama)
   4. Lihat hasilnya

═══════════════════════════════════════════════════════════════════════════════

4️⃣ COMMON PROBLEMS & SOLUSI

PROBLEM A: "NISN tidak ditemukan"
   
   Penyebab:
   ✗ Siswa belum ditambah di admin panel
   ✗ NISN yang diinput salah (case sensitive)
   ✗ Ada spasi di awal/akhir NISN
   
   Solusi:
   1. Pastikan siswa ada di tabel members
   2. Salin NISN langsung dari database (jangan manual)
   3. Cek apakah ada spasi/karakter aneh
   4. Jalankan: php fix-nisn-sync.php

PROBLEM B: "Password tidak cocok"
   
   Penyebab:
   ✗ Password salah (harus sama dengan NISN awal)
   ✗ Password berubah tapi user tidak tahu
   ✗ Password hashing error
   
   Solusi:
   1. Password default = NISN (awal)
   2. Contoh: NISN 1234567890 → Password 1234567890
   3. Jika lupa, admin reset via members.php
   4. Untuk reset: hapus dan tambah ulang siswa

PROBLEM C: "Database connection error"
   
   Penyebab:
   ✗ MySQL/MariaDB belum start
   ✗ Kredensial koneksi salah (di src/db.php)
   
   Solusi:
   1. Pastikan Apache + MySQL berjalan di XAMPP
   2. Cek src/db.php → localhost, root, password
   3. Jalankan XAMPP Control Panel → Start MySQL

PROBLEM D: "Role masih admin atau librarian"
   
   Penyebab:
   ✗ Siswa ditambah tapi role tidak 'student'
   ✗ Migration role enum tidak jalan
   
   Solusi:
   1. Jalankan: php fix-nisn-sync.php
   2. Periksa tabel users → kolom role harus 'student'
   3. Jika masih admin: UPDATE users SET role='student' WHERE nisn='...';

═══════════════════════════════════════════════════════════════════════════════

5️⃣ CECK ERROR LOG

   File error log biasanya di:
   - Windows: C:\xampp\apache\logs\error.log
   - Output: pesan "LOGIN FAILED" atau "LOGIN ERROR"
   
   Buka PowerShell:
   ```
   Get-Content "C:\xampp\apache\logs\error.log" -Tail 50
   ```
   
   Lihat pesan error terbaru untuk detail masalah.

═══════════════════════════════════════════════════════════════════════════════

6️⃣ MANUAL FIX: RESET NISN SISWA

   Jika data berantakan, admin bisa reset via database:
   
   Buka MySQL:
   ```
   mysql -u root -proot perpustakaan_online
   
   -- Lihat semua siswa
   SELECT id, name, nisn, role FROM users;
   
   -- Update NISN untuk siswa tertentu
   UPDATE users SET nisn='1234567890' WHERE name='Budi Santoso';
   
   -- Reset password siswa (password = NISN)
   UPDATE users SET password=PASSWORD(CONCAT(SUBSTRING(nisn,1))) 
   WHERE role='student' AND nisn IS NOT NULL;
   ```

═══════════════════════════════════════════════════════════════════════════════

7️⃣ JIKA MASIH GAGAL: KAPAN HARUS BUAT ULANG

   Nuclear option jika semua cara di atas tidak work:
   
   1. Hapus semua siswa di admin panel (Kelola Murid)
   2. Jalankan: php fix-nisn-sync.php
   3. Tambah siswa baru dengan NISN
   4. Test login dengan NISN yang baru
   
   Atau untuk fix yang lebih aggressive:
   
   ```
   -- Hapus semua student accounts
   DELETE FROM users WHERE role='student';
   
   -- Kemudian re-add dengan fix-nisn-sync.php
   php fix-nisn-sync.php
   ```

═══════════════════════════════════════════════════════════════════════════════

📋 QUICK TEST CHECKLIST

□ NISN ada di database (cek dengan debug-nisn.php)
□ Kolom NISN ada di tabel users (cek struktur)
□ Role siswa = 'student' (bukan admin/librarian)
□ Password = NISN (harus sama)
□ Tidak ada spasi di awal/akhir
□ Database MySQL sudah start
□ API login.php tidak error (cek console browser)
□ Error log tidak menunjukkan error (cek apache error.log)

═══════════════════════════════════════════════════════════════════════════════

📞 JIKA MASIH STUCK:

1. Share screenshot error message yang muncul
2. Jalankan: php debug-nisn.php → copy output
3. Buka browser F12 → Console → Share error
4. Share Apache error.log (tail 50 lines)
5. Minta developer check kembali login logic

═══════════════════════════════════════════════════════════════════════════════
