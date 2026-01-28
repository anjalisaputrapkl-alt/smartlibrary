# ✅ TESTING CHECKLIST - Kamera Blank Issue

## 📋 Pre-Testing Setup

- [ ] Device: **\_\_\_** (contoh: Samsung A50, iPhone 12)
- [ ] Browser: **\_\_\_** (Chrome, Firefox, Safari)
- [ ] OS Version: **\_\_\_** (Android 12, iOS 14)
- [ ] Connection: [ ] WiFi [ ] Mobile Data
- [ ] URL: **\_\_\_** (http://localhost/... atau ngrok.io/...)

---

## 🔍 Testing Steps

### STEP 1: Initial Page Load

- [ ] Halaman barcode-scan.php terbuka
- [ ] Header "📖 Pemindai Barcode" terlihat
- [ ] Input field untuk "Masukkan Kode Sesi" visible
- [ ] Button "Verifikasi Sesi" visible

**Status:** ✅ Pass / ❌ Fail

---

### STEP 2: Check Console Logs (F12)

Buka Console (F12) dan cari:

- [ ] `=== BARCODE SCANNER INITIALIZATION ===` (warna biru)
- [ ] `API Base Path: /public/api/` atau `/[path]/public/api/`
- [ ] `mediaDevices: ✓ Available` (atau berwarna biru)
- [ ] `Html5Qrcode: ✓ LOADED` (atau berwarna biru)
- [ ] `=== READY FOR INPUT ===` (warna biru)

**Expected:** Semua log berwarna biru, tidak ada merah/error

**Status:** ✅ Pass / ❌ Fail

**Screenshot Console:** [Capture here]

---

### STEP 3: Input Session Token

- [ ] Input 32-character token (dari admin)
- [ ] Token panjangnya tepat 32 karakter
- [ ] Token format hex (0-9, a-f)

**Status:** ✅ Pass / ❌ Fail

---

### STEP 4: Click "Verifikasi Sesi"

- [ ] Loading overlay muncul (dengan spinner)
- [ ] Button disabled saat loading

**Check Console for:**

- [ ] `[VERIFY] Starting session verification...`
- [ ] `[VERIFY] Token: [token]`
- [ ] `[VERIFY] API URL: [url]`
- [ ] `[VERIFY] Response status: 200`
- [ ] `[VERIFY] Session verified! ID: [number]`

**Expected:** No errors, session verified message

**Status:** ✅ Pass / ❌ Fail

**Error Message (jika ada):** ****\_\_\_****

---

### STEP 5: Wait for Camera Step

- [ ] Page berubah ke Step 2 (Scanner)
- [ ] Header berubah menjadi "Pemindai"
- [ ] Close button (✕) visible
- [ ] "Scan Anggota" dan "Scan Buku" buttons visible

**Check Console for:**

- [ ] `[SCANNER] Initializing scanner...`
- [ ] `[SCANNER] mediaDevices API available`
- [ ] `[SCANNER] Html5Qrcode library available`
- [ ] `[SCANNER] Starting camera with config:`

**Status:** ✅ Pass / ❌ Fail

---

### STEP 6: Camera Initialization

**Visual Check:**

- [ ] `<div id="qr-reader">` tidak blank (ada preview)
- [ ] Preview terlihat (tidak black box kosong)
- [ ] Camera streaming (bukan freeze frame)

**Check Console for:**

- [ ] `[SCANNER] ✓ Camera started successfully`

**Status:** ✅ Pass / ❌ Fail

**If Camera Blank - Check Console for Error:**

- [ ] `[SCANNER] Camera start error: [error name]`

**Error Details:** ****\_\_\_****

---

### STEP 7: Test Barcode Scanning

- [ ] Arahkan kamera ke barcode/QR code
- [ ] Tunggu 2-3 detik

**Expected:**

- [ ] Barcode terbaca
- [ ] Item ditambah ke "Hasil Pemindaian"
- [ ] No error messages

**Check Console for:**

- [ ] `[PROCESS] processMemberScan` atau `processBookScan`
- [ ] Response dengan data anggota/buku

**Status:** ✅ Pass / ❌ Fail

---

### STEP 8: Test Complete Flow

- [ ] Scan anggota berhasil (nama terlihat)
- [ ] Auto-switch ke "Scan Buku" mode
- [ ] Scan 1-3 buku berhasil
- [ ] Buku muncul di list
- [ ] Click "Selesai Pemindaian"
- [ ] Summary page muncul dengan member name & book count

**Status:** ✅ Pass / ❌ Fail

---

## 🐛 Troubleshooting (Jika Ada Issues)

### Issue 1: Console Blank (Tidak Ada Logs)

**Penyebab:** JavaScript file tidak load

**Action:**

- [ ] Check if `assets/js/barcode-scan.js` exists
- [ ] Check if `<script src="../assets/js/barcode-scan.js" defer></script>` di HTML
- [ ] Reload page, check for error messages

---

### Issue 2: Html5Qrcode NOT LOADED

**Penyebab:** CDN tidak accessible

**Action:**

- [ ] Check internet connection (open google.com)
- [ ] Check browser firewall/proxy
- [ ] Try alternative network (WiFi → Mobile data, atau sebaliknya)
- [ ] Try in incognito/private mode

**Test CDN Access:**

```javascript
// Ketik di console:
fetch("https://unpkg.com/html5-qrcode@2.2.0/minified/html5-qrcode.min.js")
  .then((r) => console.log("CDN Status:", r.status))
  .catch((e) => console.error("CDN Error:", e.message));
```

---

### Issue 3: Response Status 404 or 500

**Penyebab:** API endpoint error

**Action:**

- [ ] Verify API file exists: `public/api/verify-barcode-session.php`
- [ ] Check `API_BASE_PATH` in console
- [ ] Check server logs for errors
- [ ] Restart server

---

### Issue 4: Camera Blank (Blank QR Reader Area)

**Penyebab:** Camera initialization failed

**Action:**

- [ ] Check console for specific error
- [ ] Give camera permission
- [ ] Close other apps using camera
- [ ] Restart browser
- [ ] Try different browser
- [ ] Check device camera works (use native camera app)

---

## 📊 Results Summary

| Component             | Status | Notes |
| --------------------- | ------ | ----- |
| Page Load             | ✅/❌  |       |
| Console Logs          | ✅/❌  |       |
| Token Input           | ✅/❌  |       |
| Session Verification  | ✅/❌  |       |
| Camera Initialization | ✅/❌  |       |
| Camera Preview        | ✅/❌  |       |
| Barcode Scanning      | ✅/❌  |       |
| Complete Flow         | ✅/❌  |       |

---

## 🎯 Overall Result

### ✅ PASS (All Green)

- Barcode scanner fully functional
- Ready for production use

### ⚠️ PARTIAL (Mixed)

- Some features work, some don't
- Need specific debugging

### ❌ FAIL (All Red)

- Major issue found
- Cannot use without fix

---

## 📝 Notes & Observations

```
[Tulis hasil pengamatan, error messages, dan apa yang terjadi]




```

---

## 📞 Report Format (Jika Bug)

Jika ada bug/issue, report dengan format ini:

```markdown
**Device:** [Device model]
**OS:** [OS version]
**Browser:** [Browser name & version]

**Failed Step:** [Which step failed?]

**Expected:** [What should happen?]

**Actual:** [What actually happened?]

**Error Message:**
[Copy-paste exact error from console]

**Console Logs:**
[Screenshot or copy full console output]

**Steps to Reproduce:**

1. [Step 1]
2. [Step 2]
3. [Step 3]

**Attachments:**

- Screenshot of issue
- Console screenshot
```

---

**Test Date:** ****\_\_\_****  
**Tester Name:** ****\_\_\_****  
**Final Status:** ✅ Pass / ⚠️ Partial / ❌ Fail

---

**Document Version:** 1.0  
**Last Updated:** January 28, 2026
