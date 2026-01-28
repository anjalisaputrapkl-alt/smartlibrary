# 🔧 Quick Testing Guide - Kamera Smartphone

## ⚡ Testing Cepat (5 Menit)

### Step 1: Clear Cache Browser

```
Desktop:  Ctrl+Shift+Delete atau Cmd+Shift+Delete
Smartphone: Settings → Storage → Clear Cache
```

### Step 2: Akses Halaman Barcode Scanner

```
URL: http://localhost/perpustakaan-online/public/barcode-scan.php
atau: https://nama-ngrok.ngrok.io/public/barcode-scan.php
```

### Step 3: Buka Developer Console (F12)

Lihat bagian "Console" untuk melihat:

```
✓ Barcode Scanner initialized
✓ API Base Path: /public/api/
✓ mediaDevices API tersedia
✓ Html5Qrcode library dimuat
```

### Step 4: Izinkan Akses Kamera

Ketika browser bertanya → **ALLOW** kamera

### Step 5: Input Token & Verifikasi

- Buka admin page (borrows.php) di desktop
- Klik "Mulai Peminjaman Barcode"
- Copy token yang muncul
- Paste di halaman smartphone
- Klik "Verifikasi Sesi"

### Step 6: Scan Barcode

- Kamera seharusnya sudah terbuka
- Arahkan ke barcode anggota atau buku
- Tunggu 2-3 detik untuk scan

---

## 🐛 Debugging Issues

### Issue 1: Console Error "Api path not detected"

**Diagnosis:**

- Page URL tidak standar
- Path tidak mengandung `/public/`

**Solution:**

```javascript
// Di console, ketik:
getApiBasePath();
// Lihat hasilnya, pastikan return path yang benar
```

### Issue 2: "Html5Qrcode library belum dimuat"

**Diagnosis:**

- CDN tidak accessible
- Offline mode active
- Network issue

**Solution:**

```bash
# Cara 1: Refresh page
Ctrl+F5 atau Cmd+Shift+R

# Cara 2: Check internet
- Buka Google untuk verifikasi
- Tunggu 10 detik
- Refresh barcode scanner page
```

### Issue 3: "Akses kamera ditolak"

**Diagnosis:**

- Browser izin tidak diberikan
- Device permission diblokir

**Solution:**

```
Android:
1. Settings → Apps → [Browser]
2. Permissions → Camera
3. Select "Allow" atau "Allow only while using the app"
4. Restart browser

iOS:
1. Settings → [Browser name]
2. Camera toggle → ON
3. Restart Safari/browser
```

### Issue 4: "Kamera sedang digunakan app lain"

**Diagnosis:**

- WhatsApp, Zoom, Instagram, dll masih open

**Solution:**

```
1. Swipe up/down untuk buka recent apps
2. Tutup semua apps yang tidak perlu
3. Tutup camera app jika ada
4. Restart browser
5. Coba lagi
```

---

## ✅ Verification Checklist

- [ ] Console menampilkan "Barcode Scanner initialized" (bukan error)
- [ ] Api Base Path terdeteksi dengan benar
- [ ] Html5Qrcode library status: loaded ✓
- [ ] Browser izin untuk akses kamera sudah diberikan
- [ ] Saat klik "Verifikasi Sesi", kamera terbuka dalam 2-3 detik
- [ ] Bisa scan barcode (lihat hasil di list)

---

## 📱 Device-Specific Tips

### Android (Chrome/Firefox):

```
✓ Gunakan Chrome (lebih compatible)
✓ Izin kamera di Settings → Apps → Chrome → Permissions
✓ Jika pake Samsung, bisa pake Samsung Internet juga
✓ Pastikan tidak pake mode "Private" atau "Incognito"
```

### iOS (Safari):

```
✓ Hanya bisa pake Safari (Chrome iOS terbatas)
✓ Minimal iOS 14 required
✓ Izin di Settings → Safari → Camera
✓ Pastikan HTTPS (http lokal tidak bisa)
```

### Via ngrok:

```
✓ ngrok URL otomatis HTTPS
✓ Format: https://[random].ngrok.io/public/barcode-scan.php
✓ Pastikan ngrok tunnel masih active
✓ Jika timeout, restart ngrok
```

---

## 🎯 Test Scenarios

### Scenario 1: Basic Flow

1. Admin create session di desktop
2. Copy token
3. Smartphone input token
4. Scan member barcode
5. Scan book barcodes
6. Admin see real-time update
7. Complete borrowing

**Expected:** Semua step berjalan smooth tanpa error

### Scenario 2: Permission Denied

1. Lakukan test scenario 1
2. Saat kamera mau buka, tap "DENY"
3. Error message muncul dengan instruksi

**Expected:** Error message jelas dan user tahu harus apa

### Scenario 3: Switch Camera Type

1. Dalam scanner, ada 2 buttons: "Scan Anggota" dan "Scan Buku"
2. Scan anggota dulu
3. Button auto-switch ke "Scan Buku"
4. Scan beberapa buku

**Expected:** Auto-switch bekerja, instruksi berubah sesuai mode

### Scenario 4: Network Issue (Simulate)

1. Turn off WiFi / data
2. Try input session token
3. Error message muncul: "Koneksi gagal"

**Expected:** Error message informatif, user tahu masalahnya

---

## 📊 Test Results Template

Ketika test, dokumentasi hasil dengan template ini:

```markdown
## Test Results - [Date]

**Environment:**

- Device: [iPhone 12 / Samsung A50 / dll]
- OS: [iOS 14 / Android 12 / dll]
- Browser: [Safari / Chrome / Firefox]
- Connection: [WiFi / Mobile Data]
- URL: [http://localhost / ngrok / domain]

**Test Scenario:** [Which scenario tested]

**Result:**

- Console Errors: [None / screenshot]
- Camera Opens: [Yes/No] After [X] seconds
- Scanning Works: [Yes/No]
- Admin Updates: [Real-time / Delayed / Not working]

**Issues Found:**

- [Issue 1]
- [Issue 2]

**Notes:**
[Anything additional]
```

---

## 🆘 Emergency Troubleshooting

Jika semua tidak berfungsi:

### Nuclear Option 1: Complete Restart

```
1. Clear browser cache (Ctrl+Shift+Delete)
2. Close browser completely
3. Restart device
4. Open fresh browser window
5. Go to barcode scanner
6. Try again
```

### Nuclear Option 2: Different Browser

```
Android:
- Try Chrome if using Firefox
- Try Firefox if using Chrome

iOS:
- Only Safari works
- Try clearing Safari cache: Settings → Safari → Clear History
```

### Nuclear Option 3: Check Network

```
1. Open Google.com - should load
2. Check speed: Fast.com or Speedtest
3. If slow (<2 Mbps):
   - Move closer to WiFi router
   - Restart router
   - Try mobile data instead
```

### Nuclear Option 4: Check Device Camera

```
1. Open native Camera app
2. Verify camera works
3. If doesn't work: Hardware issue, contact device maker

If works:
- Issue is with barcode scanner, not hardware
- Check browser permissions
```

---

## 📞 When to Report Issue

Laporkan issue jika setelah mencoba semua di atas masih tidak beres.

**Include dalam laporan:**

1. Screenshot dari Console (F12)
2. Device & browser info
3. URL yang diakses
4. Steps yang sudah dicoba
5. Exact error message
6. Hasil dari diagnostic script:

```javascript
// Paste di console dan screenshot hasilnya:
console.log("API Path:", API_BASE_PATH);
console.log("Browser:", navigator.userAgent);
console.log("Page:", window.location.href);
console.log("HTTPS:", window.location.protocol);
```

---

## 💡 Pro Tips

**Tip 1: Bagus untuk QA Testing**

- Setup 2 devices: 1 desktop admin, 1 smartphone user
- Test real-time sync antara keduanya
- Verify inventory updates correctly

**Tip 2: Ngrok Local Testing**

```bash
# Terminal:
ngrok http 80

# Then use:
https://[generated-ngrok].ngrok.io/perpustakaan-online/public/barcode-scan.php
```

**Tip 3: Multiple Session Testing**

- Create multiple sessions
- Test apakah session isolated dengan benar
- Verify data tidak mixed-up

**Tip 4: Edge Cases**

- Scan barcode 2x → harusnya duplicate error
- Scan buku dengan stock 0 → harusnya error
- Timeout 30 menit → session should expire
- Input invalid token → reject dengan jelas

---

**Status:** ✅ Ready for Testing  
**Last Updated:** January 28, 2026
