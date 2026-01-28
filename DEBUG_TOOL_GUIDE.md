# 🔍 Debug Tool - Test Kamera Barcode Scanner

## 🎯 Akses Halaman Debug

Untuk test semua komponen barcode scanner, akses halaman debug ini:

```
http://localhost/perpustakaan-online/public/debug-barcode.php
```

Atau jika pakai ngrok:

```
https://[nama-ngrok].ngrok.io/public/debug-barcode.php
```

---

## 📝 Step-by-Step Testing

### STEP 1️⃣: Check Environment

- Lihat status mediaDevices dan getUserMedia
- Jika ada ✗, browser tidak support camera

### STEP 2️⃣: Test Library Load

- Klik tombol "Load Html5Qrcode Library"
- Tunggu sampai status berubah ✓
- Jika gagal, CDN tidak accessible

### STEP 3️⃣: Test API Connection

- Buka halaman barcode-scan.php di browser lain/tab lain
- Admin klik "Mulai Peminjaman Barcode"
- Copy token yang muncul
- Paste ke input di debug tool
- Klik tombol "Test API"
- Lihat hasilnya (harus success dengan session ID)

### STEP 4️⃣: Test Camera Access

- Klik tombol "Request Camera Access"
- Browser akan tanya izin → **TAP ALLOW**
- Preview kamera harusnya muncul di box hitam
- Jika gagal, lihat error message

---

## 🔴 Common Results & Solutions

### Result 1: Step 1 Semua ✓

✅ Browser support camera → Lanjut ke Step 2

### Result 2: Step 2 Gagal (Library Not Loaded)

❌ CDN tidak accessible

- **Solusi:** Cek internet connection, refresh page, coba WiFi lain

### Result 3: Step 3 Gagal (API Error 404 / 500)

❌ API endpoint tidak ditemukan/error

- **Solusi:** Check API path correct, restart server, check logs

### Result 4: Step 3 Gagal (Connection Error)

❌ Network issue

- **Solusi:** Cek internet, coba URL lain, check proxy/firewall

### Result 5: Step 4 Gagal (NotAllowedError)

❌ User reject atau browser block permission

- **Solusi:** Refresh, allow permission, restart browser

### Result 6: Step 4 Gagal (NotFoundError)

❌ Device tidak ada kamera

- **Solusi:** Device must have camera, use different device

### Result 7: Step 4 Gagal (NotReadableError)

❌ App lain pakai kamera

- **Solusi:** Tutup apps lain yang pakai kamera (Zoom, WhatsApp, etc)

---

## 📋 How to Report Issue

Jika ada yang fail:

1. **Screenshot hasil dari debug tool** (semua 4 step)
2. **Catat error message spesifik**
3. **Device & browser info:**
   - Device: Samsung A50 / iPhone 12 / etc
   - Browser: Chrome / Safari / Firefox / etc
   - OS: Android 12 / iOS 14 / etc
4. **Share dengan developer**

---

## ✅ Expected Results (All Pass)

Jika semua step pass:

```
✓ mediaDevices: Available
✓ getUserMedia: Available
✓ Html5Qrcode: Loaded
✓ API: Success (Session ID: 123)
✓ Camera: Working
```

Ini berarti barcode scanner seharusnya bisa dipakai tanpa masalah!

---

## 🎯 Action Items

**Untuk User:**

- Buka debug tool
- Follow 4 step
- Screenshot hasil
- Share dengan admin jika ada yang fail

**Untuk Admin:**

- Jalankan debug tool di device yang bermasalah
- Identify mana step yang fail
- Refer solutions di atas
- Report ke developer jika butuh help

---

**Debug Tool Version:** 1.0  
**Last Updated:** January 28, 2026
