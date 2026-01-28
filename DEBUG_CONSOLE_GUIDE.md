# 🔧 DEBUGGING - Kamera Blank di Smartphone

## 🎯 Diagnosis Masalah

User laporan: "Halaman barcode scanner terbuka tapi camera area **blank aja**"

Ini artinya: Halaman loaded tapi camera initialization tidak berhasil atau library tidak loaded.

---

## 🚀 Step-by-Step Debugging

### STEP 1: Buka Halaman dan Check Console

1. **Akses halaman barcode scanner:**

   ```
   http://localhost/perpustakaan-online/public/barcode-scan.php
   ```

2. **Buka Console (F12)** dan lihat output:

   ```
   BACA DARI ATAS KE BAWAH - CATAT SETIAP PESAN
   ```

3. **Catat log yang keluar:**
   - ✓ `=== BARCODE SCANNER INITIALIZATION ===` - Halaman loaded
   - ✓ `API Base Path: /public/api/` - Path detected
   - ✓ `Html5Qrcode: ✓ LOADED` - Library loaded
   - ✗ Atau ada pesan error merah

---

### STEP 2: Cek Library Loaded

Di console, ketik:

```javascript
typeof Html5Qrcode;
```

**Hasil yang diharapkan:**

- ✅ `"function"` → Library berhasil loaded
- ❌ `"undefined"` → Library TIDAK loaded (masalah utama!)

---

### STEP 3: Cek API Path Correct

Di console, ketik:

```javascript
API_BASE_PATH;
```

**Hasil yang diharapkan:**

- ✅ `/public/api/` → Correct
- ✅ `/perpustakaan-online/public/api/` → Correct (jika di subdirectory)
- ❌ `undefined` → Error!

---

### STEP 4: Input Token & Verify

1. **Input session token** (32 karakter dari admin)
2. **Klik "Verifikasi Sesi"**
3. **Buka Console** dan cari:
   - ✓ `[VERIFY] Starting session verification...`
   - ✓ `[VERIFY] Response status: 200`
   - ✗ Atau error message

---

### STEP 5: Check Camera Initialization

Setelah verify berhasil:

1. **Halaman seharusnya switch ke Step 2 (Scanner)**
2. **Buka Console** dan cari:
   - ✓ `[SCANNER] Initializing scanner...`
   - ✓ `[SCANNER] mediaDevices API available`
   - ✓ `[SCANNER] Html5Qrcode library available`
   - ✓ `[SCANNER] Starting camera with config:`
   - ✓ `[SCANNER] ✓ Camera started successfully`
   - ❌ Atau ada error message

---

## 🐛 Common Issues & Solutions

### Issue 1: `Html5Qrcode: ✗ NOT LOADED`

**Penyebab:** Library dari CDN gagal di-load

**Solusi:**

```
1. Cek internet connection (buka google.com)
2. Refresh halaman (Ctrl+F5)
3. Coba dengan WiFi yang lebih baik
4. Coba di browser berbeda
5. Check browser firewall/proxy settings
```

**Diagnosis Command:**

```javascript
// Di console, ketik:
fetch("https://unpkg.com/html5-qrcode@2.2.0/minified/html5-qrcode.min.js")
  .then((r) => console.log("CDN accessible:", r.status))
  .catch((e) => console.error("CDN not accessible:", e));
```

---

### Issue 2: `[VERIFY] Response status: 404` atau `500`

**Penyebab:** API endpoint tidak ditemukan atau error

**Solusi:**

```
1. Cek API_BASE_PATH di console (harus /public/api/)
2. Verifikasi file ada: public/api/verify-barcode-session.php
3. Cek server error logs
4. Coba restart server
```

---

### Issue 3: `[SCANNER] mediaDevices API not available`

**Penyebab:** Browser tidak support camera API

**Solusi:**

```
1. Gunakan Chrome atau Firefox (bukan browser lama)
2. Update browser ke versi terbaru
3. Restart browser
4. Coba di browser berbeda
```

---

### Issue 4: `[SCANNER] Camera start error: NotAllowedError`

**Penyebab:** User reject atau browser block camera permission

**Solusi:**

```
1. Refresh halaman
2. Ketika browser tanya izin → TAP "ALLOW" atau "Izinkan"
3. Jika sudah pernah tap "Block", reset di Settings:
   - Chrome: Settings → Privacy → Site settings → Camera
   - Safari: Settings → Safari → Camera
4. Restart browser
5. Coba lagi
```

---

### Issue 5: `[SCANNER] Camera start error: NotReadableError`

**Penyebab:** Kamera sedang dipakai aplikasi lain

**Solusi:**

```
1. Tutup aplikasi: Kamera, WhatsApp, Instagram, Zoom, dll
2. Pastikan hanya browser yang buka (tutup apps lain)
3. Restart browser
4. Coba lagi
```

---

### Issue 6: Halaman Blank Total (Tidak Ada Apapun)

**Penyebab:** CSS atau HTML error

**Solusi:**

```
1. Buka console (F12) → cek error messages
2. Refresh halaman (Ctrl+F5)
3. Clear browser cache
4. Buka di incognito/private mode (untuk tes)
5. Coba di browser berbeda
```

---

## 📋 Full Diagnostic Checklist

Jalankan script ini di console untuk full diagnostic:

```javascript
console.log("=== FULL DIAGNOSTIC ===");
console.log("1. URL:", window.location.href);
console.log("2. API Path:", typeof API_BASE_PATH, API_BASE_PATH);
console.log(
  "3. Html5Qrcode loaded:",
  typeof Html5Qrcode !== "undefined" ? "YES" : "NO",
);
console.log("4. mediaDevices:", navigator.mediaDevices ? "YES" : "NO");
console.log(
  "5. getUserMedia:",
  navigator.mediaDevices?.getUserMedia ? "YES" : "NO",
);
console.log("6. Browser:", navigator.userAgent);
console.log("7. DOM elements:");
console.log("   - step-session:", !!document.getElementById("step-session"));
console.log("   - qr-reader:", !!document.getElementById("qr-reader"));
console.log("   - sessionToken:", !!document.getElementById("sessionToken"));
console.log("8. Session State:");
console.log("   - currentSessionId:", currentSessionId);
console.log(
  "   - currentSessionToken:",
  currentSessionToken?.substring(0, 8) + "...",
);
console.log("9. Scanner State:");
console.log(
  "   - qrcodeScanner:",
  qrcodeScanner ? "INITIALIZED" : "NOT INITIALIZED",
);
console.log("   - scanningActive:", scanningActive);
console.log("=== END DIAGNOSTIC ===");
```

**Copy-paste script di atas ke console dan lihat output!**

---

## 🎥 Testing Camera Availability

Sebelum test barcode scanner, pastikan kamera berfungsi:

### Test 1: Native Camera App

```
1. Buka aplikasi Kamera bawaan device
2. Pastikan kamera preview muncul
3. Close camera app
```

### Test 2: Browser Camera Test

```
1. Buka https://webcam-test.com (atau Google Meet)
2. Berikan izin camera
3. Pastikan camera preview muncul
4. Kalau tidak, masalah ada di device/browser, bukan barcode scanner
```

### Test 3: Barcode Scanner

```
1. Buka barcode scanner page
2. Input token
3. Cek console untuk error
4. Kamera seharusnya buka
```

---

## 🔗 Quick Links for Information

**Console Output Format:**

```
[INIT] = Initialization info
[DEBUG] = Debug info
[VERIFY] = Session verification
[SCANNER] = Camera initialization
[ERROR] = Error messages
```

---

## 📝 Report Template (Jika Butuh Help)

Kalau masih bermasalah, report dengan template ini:

```markdown
**Device:** [iPhone 12 / Samsung A50 / dll]
**OS:** [iOS 14 / Android 12 / dll]
**Browser:** [Safari / Chrome / Firefox]
**Connection:** [WiFi / Mobile Data]
**URL:** [http://... atau ngrok.io/...]

**Step yang sudah dicoba:**

- [ ] Clear cache
- [ ] Refresh page
- [ ] Restart browser
- [ ] Give camera permission
- [ ] Close other apps
- [ ] Try different browser

**Console Output:**
[Paste console log output here]

**Error Message:**
[Paste any error message]

**Observations:**

- [What exactly is shown on screen?]
- [When does error appear?]
```

---

## 🎯 Quick Fix Checklist

Coba ini secara berurutan (jangan skip!):

1. [ ] **Clear Cache** → Ctrl+Shift+Delete
2. [ ] **Refresh Page** → Ctrl+F5
3. [ ] **Restart Browser** → Close & open again
4. [ ] **Give Permission** → Allow camera when browser ask
5. [ ] **Close Other Apps** → WhatsApp, Zoom, etc
6. [ ] **Check Internet** → Open google.com
7. [ ] **Open Console** → F12, look for errors
8. [ ] **Try Different Browser** → Chrome instead of Firefox, etc
9. [ ] **Test Camera First** → Open native Camera app
10. [ ] **Restart Device** → Power off & on (last resort)

---

## 🚀 Performance Tips

Jika camera lambat atau stuttering:

```javascript
// Camera performance config (in console):
// Reduce FPS untuk performa lebih baik:
// fps: 10 (lebih slow, tapi smooth)
// fps: 15 (default, balanced)
// fps: 30 (lebih responsive, tapi lebih heavy)

// Reduce qrbox untuk coverage lebih luas:
// qrbox: { width: 200, height: 200 } (lebih kecil)
// qrbox: { width: 250, height: 250 } (default)
// qrbox: { width: 300, height: 300 } (lebih besar)
```

---

**Last Updated:** January 28, 2026  
**Status:** Comprehensive Debugging Guide

📞 **Jika butuh bantuan, follow checklist di atas dulu!**
