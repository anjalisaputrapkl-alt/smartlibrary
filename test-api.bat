@echo off
REM Test API response directly
REM Pastikan sudah login dulu

echo Testing search API...
curl -v "http://localhost/perpustakaan-online/public/api/barcode-api.php?action=search&q=matematika" ^
    -H "Content-Type: application/json" ^
    --cookie "PHPSESSID=your_session_id_here"

echo.
echo Testing with simple request...
curl "http://localhost/perpustakaan-online/public/api/barcode-api.php?action=search&q=test"
