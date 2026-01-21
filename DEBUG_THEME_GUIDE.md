## 🐛 DEBUGGING TEMA SISWA - QUICK GUIDE

### Status Perbaikan

✅ Script `db-theme-loader.js` sudah update untuk:

- Fetch tema dari API
- **APPLY CSS variables** ke halaman (ini yang tadinya missing)
- Fallback ke localStorage

✅ API endpoint `student-theme.php` sudah diperkuat

✅ Semua halaman siswa sudah inject script

---

## 🧪 TEST THEME

### Test 1: Simple Test Page

```
Buka: http://localhost/perpustakaan-online/public/test-theme-student.php
```

- Halaman akan:
  - Load script db-theme-loader.js
  - Auto apply tema dari database
  - Show debug info

**Yang harus terjadi:**

- CSS variables berubah (warna background/text berubah)
- Console log: "✓ Theme applied: [nama-tema]"
- localStorage.theme = "[nama-tema]"

---

### Test 2: Admin Set Tema

```
1. Login sebagai admin
2. Buka: /perpustakaan-online/public/settings.php
3. Scroll ke "Pengaturan Tema"
4. Klik salah satu tema (misal "Dark")
5. Lihat pesan: "Tema sekolah berhasil disimpan..."
```

**Verifikasi di Database:**

```sql
SELECT * FROM school_themes WHERE school_id = 1;
-- Harus ada: theme_name = 'dark'
```

---

### Test 3: Siswa Load Tema

```
1. Logout admin
2. Login sebagai siswa di sekolah yang sama
3. Buka: /perpustakaan-online/public/student-dashboard.php
4. Halaman harus apply tema "dark"
```

**Check di Browser Console (F12 > Console):**

```javascript
// Harus ada pesan:
✓ Theme applied: dark

// Cek localStorage:
localStorage.getItem('theme')  // Harus return: "dark"

// Cek CSS variables:
getComputedStyle(document.documentElement).getPropertyValue('--bg')
// Harus return: warna dark (#1f2937)
```

---

## 🔍 TROUBLESHOOTING

### Masalah 1: Tema tidak berubah di siswa

**Penyebab:** Script tidak load atau API gagal

**Fix:**

1. Cek browser F12 > Network tab
   - Lihat apakah ada request ke `api/student-theme.php`
   - Lihat response-nya (harus JSON dengan `"success": true`)

2. Cek Console tab
   - Lihat apakah ada error
   - Lihat apakah ada log: "✓ Theme applied: ..."

3. Cek localStorage:
   ```javascript
   localStorage.getItem("theme");
   ```

---

### Masalah 2: API return 401 Unauthorized

**Penyebab:** Session siswa tidak valid

**Fix:**

```php
// Di /public/api/student-theme.php baris 18
// Cek session:
var_dump($_SESSION);  // Pastikan ada 'user' dan 'school_id'
```

---

### Masalah 3: Tema berbeda untuk setiap browser tab

**Penyebab:** Dimungkinkan localStorage per tab atau session berbeda

**Fix:**

- Clear localStorage: `localStorage.clear()`
- Re-login siswa
- Refresh halaman

---

## 📝 CHECKLIST IMPLEMENTASI

- [x] ThemeModel.php dibuat ✓
- [x] API student-theme.php dibuat ✓
- [x] db-theme-loader.js update untuk APPLY tema ✓
- [x] Settings.php update dengan tombol tema ✓
- [x] Semua halaman siswa inject script ✓
- [ ] **TEST** di browser (kamu lakukan ini!)

---

## 🎯 ALUR KERJANYA SEKARANG

```
Admin klik "Dark" di settings.php
    ↓
ThemeModel→saveSchoolTheme($school_id, 'dark')
    ↓
INSERT INTO school_themes: theme_name = 'dark'
    ↓
Siswa refresh halaman / login ulang
    ↓
db-theme-loader.js: fetch('./api/student-theme.php')
    ↓
API return: { "success": true, "theme_name": "dark" }
    ↓
Script apply CSS variables: --bg="#1f2937", --text="#f3f4f6", dst
    ↓
✓ Halaman siswa jadi dark!
```

---

## 💡 KUNCI PERBEDAANNYA

**Sebelum:** Script hanya simpan ke localStorage, tidak apply
**Sekarang:** Script fetch → apply CSS variables → render

**Jadi halaman siswa akan otomatis berubah tema!**

---

## 🚀 NEXT STEP

1. Test di browser dengan halaman test:
   - `/public/test-theme-student.php`
   - `/public/test-theme-api.php`

2. Pastikan browser console tidak ada error

3. Test di halaman siswa real:
   - `/public/student-dashboard.php`
   - `/public/profil.php`
   - `/public/favorites.php`

4. Report jika masih ada error atau tema belum berubah!
