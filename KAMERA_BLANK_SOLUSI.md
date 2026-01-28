# 📱 KAMERA MASIH BLANK? COBA INI

## 🆘 Masalah: Halaman Barcode Scanner Terbuka, Tapi Camera Area BLANK

---

## ⚡ LANGKAH PERTAMA - LAKUKAN INI DULU!

### 1️⃣ TUTUP APLIKASI KAMERA LAIN

```
1. Swipe up / swipe dari bawah (lihat running apps)
2. TUTUP semua: Kamera, WhatsApp, Zoom, Instagram, dll
3. Restart browser
```

### 2️⃣ IZIN KAMERA - REFRESH PAGE

**Untuk Android:**

```
1. Settings → Apps → [Browser Name] → Permissions → Camera
2. Pastikan: Izinkan (ALLOW)
3. Refresh halaman barcode (Ctrl+F5)
```

**Untuk iOS:**

```
1. Settings → [Safari atau browser name]
2. Scroll cari Camera
3. Pastikan: ON (hijau)
4. Refresh Safari (swipe refresh atau tap ↻)
```

### 3️⃣ CLEAR CACHE & REFRESH

```
Desktop: Ctrl+Shift+Delete → Clear Cache → Refresh page
Smartphone: Settings → Storage → Clear Cache → Refresh
```

**Tekan F5 atau refresh button** berulang kali sampai berhasil.

---

## ✅ KALAU SUDAH IKUTI 3 LANGKAH DI ATAS

**Kamera seharusnya sudah keluar!** 📷

Jika MASIH BLANK, lanjut ke bagian berikutnya ↓

---

## 🔍 CEK CONSOLE UNTUK DIAGNOSTIK

**Kalau masih blank setelah 3 langkah:**

1. **Tekan F12** (buka Developer Tools)
2. **Tab: Console** (bukan Elements, bukan Network)
3. **Cari pesan MERAH atau ERROR**

### Pesan yang Sering Keluar:

| Pesan                                  | Artinya                | Solusi                         |
| -------------------------------------- | ---------------------- | ------------------------------ |
| `Html5Qrcode: ✗ NOT LOADED`            | Library tidak download | Refresh page, cek internet     |
| `Camera start error: NotAllowedError`  | Izin ditolak           | Beri izin, restart browser     |
| `Camera start error: NotReadableError` | App lain pakai kamera  | Tutup apps, restart browser    |
| `Response status: 404`                 | API tidak ditemukan    | Check URL path, restart server |
| `mediaDevices API not available`       | Browser tidak support  | Update browser                 |

---

## 🎯 Kalau Masih Stuck

**Coba berurutan:**

1. **Restart Device** (power off & on)
2. **Update Browser** (ke versi terbaru)
3. **Ganti Browser:**
   - Android: Coba Firefox atau Chrome
   - iOS: Coba Safari (harus Safari di iOS)
4. **Coba WiFi Lain** (atau mobile data)
5. **Hubungi Admin** (dengan screenshot console)

---

## 💡 HINTS

- 🌐 Barcode scanner butuh **internet** untuk download library
- 📷 Kamera minimal **Android 8 atau iOS 14**
- 🔌 Gunakan **WiFi yang bagus** atau mobile data stabil
- ⏱️ Tunggu **5-10 detik** saat camera loading
- 🔄 **Refresh berulang kali** (F5) sampai berhasil

---

## 📞 Jika Benar-Benar Tidak Bisa

Hubungi admin dengan:

1. **Screenshot layar** (yang blank itu)
2. **Screenshot console** (F12 → Console)
3. **Device & browser info** (contoh: Samsung A50 + Chrome)
4. **Apa yang sudah dicoba**

---

**JANGAN CEMAS - 99% CASES FIX dengan langkah di atas!** ✨

Good luck! 📚📱
