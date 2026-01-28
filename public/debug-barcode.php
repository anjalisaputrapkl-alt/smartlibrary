<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Barcode Scanner</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 500px;
            margin: 0 auto;
        }

        .section {
            background: white;
            padding: 20px;
            margin: 10px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #333;
            margin-bottom: 15px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }

        input,
        button {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        button {
            background: #667eea;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background: #5568d3;
        }

        .status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            font-size: 14px;
        }

        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .console {
            background: #222;
            color: #0f0;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
        }

        .console-line {
            margin: 3px 0;
        }

        .console-error {
            color: #f00;
        }

        .console-warn {
            color: #ff0;
        }

        .console-info {
            color: #0f0;
        }

        #camera-preview {
            width: 100%;
            max-width: 300px;
            height: 300px;
            background: #000;
            border-radius: 4px;
            margin: 10px 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🔧 Debug Barcode Scanner</h1>

        <!-- Step 1: Check Environment -->
        <div class="section">
            <h2>1️⃣ Check Environment</h2>
            <div id="envStatus"></div>
        </div>

        <!-- Step 2: Test Library -->
        <div class="section">
            <h2>2️⃣ Test Library Load</h2>
            <button onclick="testLibrary()">Load Html5Qrcode Library</button>
            <div id="libraryStatus"></div>
        </div>

        <!-- Step 3: Test API Connection -->
        <div class="section">
            <h2>3️⃣ Test API Connection</h2>
            <input type="text" id="testToken" placeholder="Paste session token (32 chars)" maxlength="32">
            <button onclick="testAPI()">Test API</button>
            <div id="apiStatus"></div>
        </div>

        <!-- Step 4: Test Camera -->
        <div class="section">
            <h2>4️⃣ Test Camera Access</h2>
            <button onclick="testCamera()">Request Camera Access</button>
            <div id="camera-preview"></div>
            <div id="cameraStatus"></div>
        </div>

        <!-- Console Output -->
        <div class="section">
            <h2>📋 Console Output</h2>
            <div class="console" id="console"></div>
            <button onclick="clearConsole()">Clear Console</button>
        </div>
    </div>

    <script>
        // ===== CONSOLE LOG OVERRIDE =====
        const consoleLogs = [];
        const originalLog = console.log;
        const originalError = console.error;
        const originalWarn = console.warn;

        console.log = function (...args) {
            originalLog.apply(console, args);
            addConsoleLog(args.join(' '), 'info');
        };

        console.error = function (...args) {
            originalError.apply(console, args);
            addConsoleLog(args.join(' '), 'error');
        };

        console.warn = function (...args) {
            originalWarn.apply(console, args);
            addConsoleLog(args.join(' '), 'warn');
        };

        function addConsoleLog(msg, type) {
            consoleLogs.push({ msg, type, time: new Date().toLocaleTimeString() });
            updateConsoleDisplay();
        }

        function updateConsoleDisplay() {
            const consoleDiv = document.getElementById('console');
            consoleDiv.innerHTML = consoleLogs.map(log => {
                const className = `console-line console-${log.type}`;
                return `<div class="${className}">[${log.time}] ${escapeHtml(log.msg)}</div>`;
            }).join('');
            consoleDiv.scrollTop = consoleDiv.scrollHeight;
        }

        function clearConsole() {
            consoleLogs.length = 0;
            updateConsoleDisplay();
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function setStatus(elementId, message, type) {
            const element = document.getElementById(elementId);
            element.innerHTML = `<div class="status ${type}">${message}</div>`;
        }

        // ===== STEP 1: CHECK ENVIRONMENT =====
        window.addEventListener('load', () => {
            console.log('=== BARCODE DEBUG TOOL ===');
            console.log('URL:', window.location.href);
            console.log('Pathname:', window.location.pathname);

            let envHtml = '';

            // Check mediaDevices
            const hasMediaDevices = !!navigator.mediaDevices;
            console.log('mediaDevices:', hasMediaDevices ? '✓' : '✗');
            envHtml += `<div class="status ${hasMediaDevices ? 'success' : 'error'}">
                mediaDevices: ${hasMediaDevices ? '✓ Available' : '✗ NOT available'}
            </div>`;

            // Check getUserMedia
            const hasGetUserMedia = !!navigator.mediaDevices?.getUserMedia;
            console.log('getUserMedia:', hasGetUserMedia ? '✓' : '✗');
            envHtml += `<div class="status ${hasGetUserMedia ? 'success' : 'error'}">
                getUserMedia: ${hasGetUserMedia ? '✓ Available' : '✗ NOT available'}
            </div>`;

            // Check browser
            const browserInfo = navigator.userAgent;
            console.log('Browser:', browserInfo);
            envHtml += `<div class="status info">Browser: ${browserInfo.substring(0, 100)}...</div>`;

            document.getElementById('envStatus').innerHTML = envHtml;
        });

        // ===== STEP 2: TEST LIBRARY =====
        function testLibrary() {
            console.log('Testing library...');

            if (typeof Html5Qrcode !== 'undefined') {
                console.log('✓ Html5Qrcode already loaded');
                setStatus('libraryStatus', '✓ Html5Qrcode is loaded', 'success');
                return;
            }

            console.log('Loading Html5Qrcode from CDN...');
            const script = document.createElement('script');
            script.src = 'https://unpkg.com/html5-qrcode@2.2.0/minified/html5-qrcode.min.js';
            script.onload = () => {
                console.log('✓ Html5Qrcode library loaded successfully');
                setStatus('libraryStatus', '✓ Library loaded successfully', 'success');
            };
            script.onerror = () => {
                console.error('✗ Failed to load Html5Qrcode from unpkg.com');
                console.log('Trying alternative CDN...');

                const script2 = document.createElement('script');
                script2.src = 'https://cdn.jsdelivr.net/npm/html5-qrcode@2.2.0/minified/html5-qrcode.min.js';
                script2.onload = () => {
                    console.log('✓ Html5Qrcode loaded from alternative CDN');
                    setStatus('libraryStatus', '✓ Library loaded (from alternative CDN)', 'success');
                };
                script2.onerror = () => {
                    console.error('✗ Both CDNs failed');
                    setStatus('libraryStatus', '✗ Failed to load library from both CDNs', 'error');
                };
                document.head.appendChild(script2);
            };
            document.head.appendChild(script);
        }

        // ===== STEP 3: TEST API =====
        async function testAPI() {
            const token = document.getElementById('testToken').value.trim();

            if (!token) {
                setStatus('apiStatus', '❌ Please enter a token', 'error');
                console.error('No token provided');
                return;
            }

            if (token.length !== 32) {
                setStatus('apiStatus', '❌ Token must be 32 characters', 'error');
                console.error('Token length:', token.length, '(expected 32)');
                return;
            }

            console.log('Testing API with token:', token.substring(0, 8) + '...');

            // Try to detect API path
            const pathname = window.location.pathname;
            let apiPath = '/public/api/';
            if (pathname.includes('/public/')) {
                const basePath = pathname.substring(0, pathname.indexOf('/public/'));
                apiPath = basePath + '/public/api/';
            }

            console.log('API Path:', apiPath);
            const apiUrl = apiPath + 'verify-barcode-session.php';
            console.log('Full API URL:', window.location.origin + apiUrl);

            try {
                setStatus('apiStatus', '⏳ Testing API...', 'info');

                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ token: token })
                });

                console.log('Response status:', response.status);
                const data = await response.json();
                console.log('Response data:', data);

                if (response.ok && data.success) {
                    console.log('✓ API test successful');
                    console.log('Session ID:', data.data.session_id);
                    setStatus('apiStatus', `✓ API works! Session ID: ${data.data.session_id}`, 'success');
                } else {
                    console.error('API error:', data.message);
                    setStatus('apiStatus', `✗ API error: ${data.message}`, 'error');
                }
            } catch (error) {
                console.error('Fetch error:', error.message);
                setStatus('apiStatus', `✗ Connection error: ${error.message}`, 'error');
            }
        }

        // ===== STEP 4: TEST CAMERA =====
        async function testCamera() {
            console.log('Requesting camera access...');

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                console.error('mediaDevices not available');
                setStatus('cameraStatus', '✗ Browser does not support camera access', 'error');
                return;
            }

            try {
                console.log('Asking for camera permission...');
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment' }
                });

                console.log('✓ Camera access granted');

                const video = document.getElementById('camera-preview');
                console.log('Video element:', video);

                // Modern approach - use srcObject
                try {
                    video.srcObject = stream;
                    console.log('✓ Using srcObject');
                } catch (e) {
                    console.log('srcObject not supported, trying createObjectURL...');
                    try {
                        const url = URL.createObjectURL(stream);
                        video.src = url;
                        console.log('✓ Using createObjectURL');
                    } catch (e2) {
                        console.error('createObjectURL failed:', e2.message);
                        throw e2;
                    }
                }

                // Wait for video to be ready
                await new Promise((resolve) => {
                    video.onloadedmetadata = () => {
                        console.log('✓ Video metadata loaded');
                        resolve();
                    };
                    // Timeout after 5 seconds
                    setTimeout(resolve, 5000);
                });

                video.play().then(() => {
                    console.log('✓ Camera preview playing');
                    setStatus('cameraStatus', '✓ Camera is working!', 'success');
                }).catch(err => {
                    console.error('Play error:', err);
                    setStatus('cameraStatus', `⚠️ Camera stream acquired but play failed: ${err.message}`, 'error');
                });

            } catch (error) {
                console.error('Camera error:', error.name, '-', error.message);

                let errorMsg = '✗ Camera error: ' + error.message;
                if (error.name === 'NotAllowedError') {
                    errorMsg = '✗ Camera access denied by user';
                } else if (error.name === 'NotFoundError') {
                    errorMsg = '✗ No camera found on device';
                } else if (error.name === 'NotReadableError') {
                    errorMsg = '✗ Camera is in use by another app';
                } else if (error.name === 'SecurityError') {
                    errorMsg = '✗ Camera access blocked (HTTPS required)';
                }

                setStatus('cameraStatus', errorMsg, 'error');
            }
        }
    </script>
</body>

</html>