# Generate Barcode - Admin Panel Feature

## Deskripsi
Fitur untuk generate QR Code dan Barcode Code128 untuk buku di database secara otomatis.

## File yang Dibuat

### 1. **src/BarcodeModel.php**
Model untuk logic generate barcode:
- `searchBooks($query)` - Search buku berdasarkan judul, kode_buku, atau penulis
- `getBookById($id)` - Get single book data
- `generateQRCode($text)` - Generate QR Code menggunakan QR Server API
- `generateBarcode($text)` - Generate Code128 Barcode menggunakan Barcode API
- `generateCombinedBarcode($bookData)` - Generate QR + Barcode sekaligus
- `logBarcodeGeneration($book_id)` - Log setiap generate (opsional)

**API yang digunakan:**
- QR Code: https://api.qrserver.com/v1/create-qr-code/ (Free, no API key)
- Barcode: https://barcode.tec-it.com/barcode.ashx (Free, no API key)

### 2. **public/api/barcode-api.php**
API endpoint untuk handle search dan generate barcode:
- `GET api/barcode-api.php?action=search&q={query}` - Search books
- `POST api/barcode-api.php` dengan data `action=generate&book_id={id}` - Generate barcode

### 3. **public/generate-barcode.php**
Halaman admin untuk generate barcode:
- Search box dengan real-time results
- Generate button di setiap hasil
- Modal preview dengan QR code + Barcode
- Download PNG dan Print functionality
- Design sesuai layout admin yang sudah ada

## Cara Menggunakan

### Admin Side:
1. Buka menu "Barcode Buku" di sidebar
2. Ketik minimal 2 huruf untuk search buku
3. Pilih buku dari hasil search
4. Klik tombol "Generate"
5. Preview modal muncul
6. Pilih "Download PNG" atau "Cetak"

### API Side:
```bash
# Search books
curl "http://localhost/perpustakaan-online/public/api/barcode-api.php?action=search&q=matematika"

# Generate barcode
curl -X POST http://localhost/perpustakaan-online/public/api/barcode-api.php \
  -d "action=generate&book_id=1"
```

## Features

✅ Search real-time dengan debounce 300ms
✅ QR Code + Barcode Code128 generation
✅ Modal preview dengan info buku lengkap
✅ Download PNG dengan canvas
✅ Print functionality
✅ Responsive design
✅ Dark mode support
✅ School ID filtering (multi-tenant)
✅ Error handling & validation
✅ No external library dependency (menggunakan API online)

## Database Requirements

Pastikan tabel `buku` memiliki struktur:
```sql
CREATE TABLE buku (
    id INT PRIMARY KEY AUTO_INCREMENT,
    judul VARCHAR(255),
    kode_buku VARCHAR(50) UNIQUE,
    penulis VARCHAR(255),
    penerbit VARCHAR(255),
    tahun INT,
    kategori_id INT,
    stok INT,
    sekolah_id INT,
    ...
);

-- Optional: untuk logging
CREATE TABLE barcode_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    book_id INT,
    generated_by INT,
    generated_at TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES buku(id),
    FOREIGN KEY (generated_by) REFERENCES users(id)
);
```

## Security

✅ Requires authentication (requireAuth())
✅ School ID filtering (multi-tenant support)
✅ SQL Injection protection (prepared statements)
✅ HTML escape untuk user input

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Troubleshooting

### QR Code tidak muncul
- Periksa koneksi internet (API qrserver.com harus accessible)
- Cek console untuk error messages

### Barcode tidak muncul
- Pastikan text/kode_buku tidak kosong
- Periksa barcode.tec-it.com accessibility

### Modal tidak tampil
- Periksa JavaScript console untuk errors
- Pastikan modal HTML tidak tertimpa CSS lain

### Search tidak berfungsi
- Pastikan tabel `buku` ada dan punya data
- Check database connection di api/barcode-api.php

## Future Improvements

1. Cache generated barcodes di disk
2. Batch generate untuk multiple books
3. Custom barcode design/template
4. Export to PDF dengan header/footer
5. Historical log dengan timestamp
6. QR code analytics (scan tracking)

## Integration Points

- **Database**: `buku`, `users`, `schools` tables
- **Auth**: `src/auth.php` (requireAuth())
- **Layout**: `partials/sidebar.php` (menu item)
- **CSS**: `assets/css/index.css`, `assets/css/sidebar.css` (inherited styles)
- **Icons**: Iconify icons (mdi:*)
