<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Kamera Saja</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial;
            background: #000;
            color: #fff;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
        }

        h1 {
            margin-bottom: 20px;
        }

        video {
            width: 100%;
            max-width: 400px;
            height: auto;
            background: #111;
            border-radius: 8px;
            margin: 20px 0;
        }

        button {
            padding: 15px 30px;
            margin: 10px;
            font-size: 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .status {
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 18px;
        }

        .success {
            background: #27ae60;
            color: white;
        }

        .error {
            background: #e74c3c;
            color: white;
        }

        .info {
            background: #3498db;
            color: white;
        }

        p {
            margin: 10px 0;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>📷 Test Kamera (SIMPLE)</h1>

        <p>Halaman ini hanya untuk test apakah kamera berfungsi.</p>
        <p>Tidak ada library, tidak ada barcode - hanya KAMERA SAJA.</p>

        <video id="video" playsinline autoplay></video>

        <div>
            <button class="btn-primary" onclick="startCamera()">🎥 Buka Kamera</button>
            <button class="btn-danger" onclick="stopCamera()">⏹️ Tutup Kamera</button>
        </div>

        <div id="status"></div>
    </div>

    <script>
        let stream = null;
        const video = document.getElementById('video');
        const status = document.getElementById('status');

        function log(msg, type = 'info') {
            console.log(msg);
            const div = document.createElement('div');
            div.className = `status ${type}`;
            div.textContent = msg;
            status.innerHTML = '';
            status.appendChild(div);
        }

        async function startCamera() {
            log('⏳ Requesting camera access...', 'info');

            // Check browser support
            if (!navigator.mediaDevices) {
                log('❌ Browser not supported', 'error');
                return;
            }

            try {
                console.log('Requesting camera...');
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment' }
                });

                console.log('✓ Camera granted');

                // Use srcObject (modern way)
                try {
                    video.srcObject = stream;
                } catch (e) {
                    console.log('Fallback to createObjectURL');
                    const url = URL.createObjectURL(stream);
                    video.src = url;
                }

                // Wait for metadata
                await new Promise((resolve) => {
                    video.onloadedmetadata = () => {
                        resolve();
                    };
                    setTimeout(resolve, 5000);
                });

                video.play();
                log('✅ KAMERA BERFUNGSI! Kalau video di atas menampilkan preview kamera = OK', 'success');

            } catch (error) {
                console.error('Camera error:', error);

                if (error.name === 'NotAllowedError') {
                    log('❌ IZIN DITOLAK - Tap ALLOW ketika browser tanya izin', 'error');
                } else if (error.name === 'NotFoundError') {
                    log('❌ KAMERA TIDAK DITEMUKAN - Device tidak punya kamera', 'error');
                } else if (error.name === 'NotReadableError') {
                    log('❌ KAMERA TIDAK BISA DIBACA - App lain sedang pakai kamera', 'error');
                } else if (error.name === 'SecurityError') {
                    log('❌ SECURITY ERROR - Pakai HTTPS atau localhost', 'error');
                } else {
                    log(`❌ ERROR: ${error.name} - ${error.message}`, 'error');
                }
            }
        }

        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
                video.srcObject = null;
                log('Camera ditutup', 'info');
            }
        }

        // Auto-start on load
        window.addEventListener('load', () => {
            log('📱 Tekan BUKA KAMERA untuk test', 'info');
        });
    </script>
</body>

</html>