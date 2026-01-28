// ============================================================================
// Barcode Scanner untuk Smartphone - HTML5 QRCode
// ============================================================================

// Get the base path for API calls (works with all deployment scenarios)
function getApiBasePath() {
    // Get the current page path
    const path = window.location.pathname;
    console.log('[DEBUG] Current pathname:', path);
    
    // Remove barcode-scan.php from path to get base directory
    if (path.includes('/public/')) {
        const basePath = path.substring(0, path.indexOf('/public/')) + '/public/api/';
        console.log('[DEBUG] Detected API path:', basePath);
        return basePath;
    }
    
    // Fallback for ngrok or other scenarios
    console.log('[DEBUG] Using fallback API path: /public/api/');
    return '/public/api/';
}

const API_BASE_PATH = getApiBasePath();
console.log('[INIT] API_BASE_PATH =', API_BASE_PATH);
console.log('[INIT] Current URL =', window.location.href);

let qrcodeScanner = null;
let currentSessionId = null;
let currentSessionToken = null;
let sessionData = {
    member: null,
    books: []
};
let currentScanType = 'member';
let scanningActive = false;

// ============================================================================
// Session Storage (localStorage persistence)
// ============================================================================

function saveSessionToStorage() {
    const sessionState = {
        sessionId: currentSessionId,
        sessionToken: currentSessionToken,
        member: sessionData.member,
        books: sessionData.books,
        timestamp: Date.now()
    };
    localStorage.setItem('barcodeSession', JSON.stringify(sessionState));
    console.log('[STORAGE] Session saved to localStorage');
}

function restoreSessionFromStorage() {
    try {
        const stored = localStorage.getItem('barcodeSession');
        if (!stored) return false;

        const session = JSON.parse(stored);
        
        // Check if session is still valid (not older than 30 minutes)
        const ageMinutes = (Date.now() - session.timestamp) / 1000 / 60;
        if (ageMinutes > 30) {
            console.log('[STORAGE] Session expired (older than 30 min)');
            localStorage.removeItem('barcodeSession');
            return false;
        }

        currentSessionId = session.sessionId;
        currentSessionToken = session.sessionToken;
        sessionData.member = session.member;
        sessionData.books = session.books;

        console.log('[STORAGE] Session restored from localStorage');
        console.log('[STORAGE] Session age:', Math.round(ageMinutes), 'minutes');
        return true;
    } catch (error) {
        console.error('[STORAGE] Error restoring session:', error);
        return false;
    }
}

function clearSessionStorage() {
    localStorage.removeItem('barcodeSession');
    console.log('[STORAGE] Session cleared from localStorage');
}

// ============================================================================
// DOM Elements
// ============================================================================

const stepSession = document.getElementById('step-session');
const stepScanner = document.getElementById('step-scanner');
const stepCompletion = document.getElementById('step-completion');

const sessionToken = document.getElementById('sessionToken');
const btnVerifySession = document.getElementById('btnVerifySession');
const sessionError = document.getElementById('sessionError');

const qrReader = document.getElementById('qr-reader');
const scanInstruction = document.getElementById('scanInstruction');
const scanError = document.getElementById('scanError');
const memberDisplay = document.getElementById('memberDisplay');
const memberName = document.getElementById('memberName');
const scannedItems = document.getElementById('scannedItems');

const btnScanMember = document.getElementById('btnScanMember');
const btnScanBook = document.getElementById('btnScanBook');
const btnCloseScanner = document.getElementById('btnCloseScanner');
const btnClearScans = document.getElementById('btnClearScans');
const btnFinishScanning = document.getElementById('btnFinishScanning');

const summaryMember = document.getElementById('summaryMember');
const summaryBooks = document.getElementById('summaryBooks');
const btnNewSession = document.getElementById('btnNewSession');

const loadingOverlay = document.getElementById('loadingOverlay');

// ============================================================================
// Step 1: Session Verification
// ============================================================================

btnVerifySession.addEventListener('click', async () => {
    const token = sessionToken.value.trim();

    if (!token) {
        showError(sessionError, 'Masukkan kode sesi');
        return;
    }

    if (token.length !== 32) {
        showError(sessionError, 'Kode sesi harus 32 karakter');
        return;
    }

    showLoading(true);
    sessionError.textContent = '';

    try {
        console.log('[VERIFY] Starting session verification...');
        console.log('[VERIFY] Token:', token);
        console.log('[VERIFY] API URL:', API_BASE_PATH + 'verify-barcode-session.php');
        
        const response = await fetch(API_BASE_PATH + 'verify-barcode-session.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ token: token })
        });

        console.log('[VERIFY] Response status:', response.status);
        
        const data = await response.json();
        console.log('[VERIFY] Response data:', data);

        if (!response.ok || !data.success) {
            const errMsg = data.message || 'Verifikasi gagal';
            console.error('[VERIFY] Verification failed:', errMsg);
            showError(sessionError, errMsg);
            showLoading(false);
            return;
        }

        // Session verified
        currentSessionId = data.data.session_id;
        currentSessionToken = token;

        // Save to localStorage
        saveSessionToStorage();

        console.log('[VERIFY] Session verified! ID:', currentSessionId);

        // Proceed to scanner
        goToScanner();
        showLoading(false);

    } catch (error) {
        console.error('[VERIFY] Error:', error);
        const errMsg = 'Koneksi gagal: ' + (error.message || 'Unknown error');
        showError(sessionError, errMsg);
        showLoading(false);
    }
});

// ============================================================================
// Step 2: Barcode Scanner
// ============================================================================

async function goToScanner() {
    stepSession.classList.remove('active');
    stepScanner.classList.add('active');

    // Initialize QR Code Scanner
    initializeScanner();

    currentScanType = 'member';
    updateScanInstruction();
}

function initializeScanner() {
    console.log('[SCANNER] Initializing scanner...');
    
    if (qrcodeScanner) {
        console.log('[SCANNER] Stopping existing scanner');
        qrcodeScanner.stop();
    }

    // Check if browser supports camera access
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        console.error('[SCANNER] mediaDevices API tidak tersedia');
        showError(scanError, 'Browser tidak mendukung akses kamera. Update browser Anda.');
        return;
    }

    console.log('[SCANNER] mediaDevices API available');

    // Check if Html5Qrcode is available
    if (typeof Html5Qrcode === 'undefined') {
        console.error('[SCANNER] Html5Qrcode library not loaded');
        showError(scanError, '❌ Perpustakaan barcode belum dimuat. Refresh halaman atau periksa koneksi internet.');
        return;
    }

    console.log('[SCANNER] Html5Qrcode library available');

    qrcodeScanner = new Html5Qrcode('qr-reader');
    console.log('[SCANNER] Html5Qrcode instance created');

    const qrConfig = {
        fps: 15,
        qrbox: { width: 250, height: 250},
        aspectRatio: 1,
        disableFlip: false
    };

    console.log('[SCANNER] Starting camera with config:', qrConfig);

    qrcodeScanner.start(
        { facingMode: 'environment' }, // Use back camera
        qrConfig,
        onScanSuccess,
        onScanFailure
    ).then(() => {
        console.log('[SCANNER] ✓ Camera started successfully');
        scanningActive = true;
    }).catch(err => {
        console.error('[SCANNER] Camera start error:', err);
        console.error('[SCANNER] Error name:', err.name);
        console.error('[SCANNER] Error message:', err.message);
        
        // Provide specific error messages based on error type
        let errorMsg = 'Tidak dapat mengakses kamera.';
        
        if (err.name === 'NotAllowedError') {
            errorMsg = '❌ Akses kamera ditolak. Berikan izin akses kamera di pengaturan browser Anda. Refresh halaman setelah memberikan izin.';
        } else if (err.name === 'NotFoundError') {
            errorMsg = '❌ Kamera tidak ditemukan. Periksa apakah perangkat memiliki kamera.';
        } else if (err.name === 'NotReadableError') {
            errorMsg = '❌ Kamera sedang digunakan aplikasi lain. Tutup aplikasi tersebut terlebih dahulu.';
        } else if (err.name === 'SecurityError') {
            errorMsg = '❌ Akses kamera diblokir. Pastikan menggunakan HTTPS atau localhost.';
        } else if (err.name === 'PermissionDeniedError') {
            errorMsg = '❌ Izin kamera ditolak oleh sistem. Ubah di pengaturan perangkat.';
        }
        
        showError(scanError, errorMsg);
        scanningActive = false;
    });
}

function onScanSuccess(decodedText, decodedResult) {
    if (!scanningActive) return;

    // Pause scanner to process
    scanningActive = false;

    const barcode = decodedText.trim();

    if (currentScanType === 'member') {
        processMemberScan(barcode);
    } else {
        processBookScan(barcode);
    }

    // Resume scanning after 1 second
    setTimeout(() => {
        if (qrcodeScanner && qrcodeScanner.isScanning) {
            scanningActive = true;
        }
    }, 1000);
}

function onScanFailure(error) {
    // This is called very frequently, ignore errors
    // Only log if it's something serious
}

// ============================================================================
// Scan Type Selection
// ============================================================================

document.querySelectorAll('.scan-type-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
        // Update active button
        document.querySelectorAll('.scan-type-btn').forEach(b => {
            b.classList.remove('active');
        });
        e.target.classList.add('active');

        // Update scan type
        currentScanType = e.target.dataset.type;
        updateScanInstruction();
        scanError.textContent = '';
    });
});

function updateScanInstruction() {
    const instructions = {
        'member': '👤 Arahkan kamera ke barcode anggota (NISN)',
        'book': '📚 Arahkan kamera ke barcode buku (ISBN)'
    };
    scanInstruction.textContent = instructions[currentScanType] || '';
}

// ============================================================================
// Member Scan Processing
// ============================================================================

async function processMemberScan(barcode) {
    showLoading(true);

    try {
        const response = await fetch(API_BASE_PATH + 'process-barcode-scan.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                session_id: currentSessionId,
                barcode: barcode,
                type: 'member'
            })
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            showError(scanError, data.message || 'Pemindaian gagal');
            showLoading(false);
            scanningActive = true;
            return;
        }

        // Member found
        sessionData.member = data.data;

        // Save to localStorage
        saveSessionToStorage();

        // Update UI
        memberName.textContent = `${data.data.name} (${data.data.nisn})`;
        memberDisplay.style.display = 'inline-flex';

        // Add to scanned items
        addScannedItem('member', data.data.name, 'Anggota');

        // Auto switch to book scanning
        document.getElementById('btnScanBook').click();

        scanError.textContent = '';
        showLoading(false);
        scanningActive = true;

    } catch (error) {
        console.error('Error:', error);
        showError(scanError, 'Koneksi gagal');
        showLoading(false);
        scanningActive = true;
    }
}

// ============================================================================
// Book Scan Processing
// ============================================================================

async function processBookScan(barcode) {
    showLoading(true);

    try {
        const response = await fetch(API_BASE_PATH + 'process-barcode-scan.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                session_id: currentSessionId,
                barcode: barcode,
                type: 'book'
            })
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            showError(scanError, data.message || 'Pemindaian buku gagal');
            showLoading(false);
            scanningActive = true;
            return;
        }

        // Book found and added
        const book = data.data;
        sessionData.books.push(book);

        // Save to localStorage
        saveSessionToStorage();

        // Add to scanned items
        addScannedItem('book', book.title, `ISBN: ${book.isbn}`);

        scanError.textContent = '';
        showLoading(false);
        scanningActive = true;

    } catch (error) {
        console.error('Error:', error);
        showError(scanError, 'Koneksi gagal');
        showLoading(false);
        scanningActive = true;
    }
}

// ============================================================================
// UI Updates
// ============================================================================

function addScannedItem(type, name, subtitle) {
    // Remove empty message if exists
    const emptyMsg = scannedItems.querySelector('.empty-message');
    if (emptyMsg) {
        emptyMsg.remove();
    }

    const item = document.createElement('div');
    item.className = 'scanned-item';
    item.innerHTML = `
        <span class="scanned-item-type">${type === 'member' ? '👤 ANGGOTA' : '📚 BUKU'}</span>
        <div class="scanned-item-name">
            <div style="font-weight: 600;">${escapeHtml(name)}</div>
            <div style="font-size: 11px; color: #999; margin-top: 2px;">${escapeHtml(subtitle)}</div>
        </div>
    `;

    scannedItems.appendChild(item);
}

function clearScannedItems() {
    scannedItems.innerHTML = '<p class="empty-message">Belum ada hasil pemindaian</p>';
    sessionData.books = [];
    memberDisplay.style.display = 'none';
}

// ============================================================================
// Button Actions
// ============================================================================

btnCloseScanner.addEventListener('click', () => {
    if (confirm('Batalkan pemindaian?')) {
        stopScanning();
        goBackToSession();
    }
});

btnClearScans.addEventListener('click', () => {
    if (confirm('Hapus semua hasil pemindaian?')) {
        clearScannedItems();
    }
});

btnFinishScanning.addEventListener('click', () => {
    if (!sessionData.member) {
        showError(scanError, 'Scan anggota terlebih dahulu');
        return;
    }

    if (sessionData.books.length === 0) {
        showError(scanError, 'Scan minimal satu buku');
        return;
    }

    // Go to completion screen
    goToCompletion();
});

btnNewSession.addEventListener('click', () => {
    location.reload();
});

// ============================================================================
// Step 3: Completion
// ============================================================================

function goToCompletion() {
    stopScanning();

    stepScanner.classList.remove('active');
    stepCompletion.classList.add('active');

    // Update summary
    summaryMember.textContent = sessionData.member.name;
    summaryBooks.textContent = sessionData.books.length;
}

// ============================================================================
// Utility Functions
// ============================================================================

function goBackToSession() {
    sessionData = { member: null, books: [] };
    currentSessionId = null;
    currentSessionToken = null;
    sessionToken.value = '';

    // Clear storage
    clearSessionStorage();

    stepScanner.classList.remove('active');
    stepSession.classList.add('active');
}

function stopScanning() {
    if (qrcodeScanner) {
        qrcodeScanner.stop().catch(err => {
            console.error('Error stopping scanner:', err);
        });
        scanningActive = false;
    }
}

function showError(element, message) {
    element.textContent = message;
    element.classList.add('show');

    setTimeout(() => {
        element.classList.remove('show');
    }, 5000);
}

function showLoading(show) {
    if (show) {
        loadingOverlay.classList.add('show');
    } else {
        loadingOverlay.classList.remove('show');
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================================================
// Initialize on Load
// ============================================================================

document.addEventListener('DOMContentLoaded', () => {
    console.log('%c=== BARCODE SCANNER INITIALIZATION ===', 'color: blue; font-weight: bold; font-size: 14px;');
    console.log('%cPage loaded at:', 'color: blue; font-weight: bold;', new Date().toLocaleTimeString());
    
    // Try to restore session from localStorage
    const sessionRestored = restoreSessionFromStorage();
    if (sessionRestored) {
        console.log('%c✓ Session restored from localStorage', 'color: green; font-weight: bold;');
        console.log('Session ID:', currentSessionId);
        console.log('Member:', sessionData.member?.name || 'None');
        console.log('Scanned books:', sessionData.books.length);
        
        // Fill in the session token field (for display/reference)
        sessionToken.value = currentSessionToken;
        
        // Update UI with restored data
        if (sessionData.member) {
            memberName.textContent = `${sessionData.member.name} (${sessionData.member.nisn})`;
            memberDisplay.style.display = 'inline-flex';
            
            // Add member to scanned items
            const emptyMsg = scannedItems.querySelector('.empty-message');
            if (emptyMsg) {
                emptyMsg.remove();
            }
            const memberItem = document.createElement('div');
            memberItem.className = 'scanned-item';
            memberItem.innerHTML = `
                <span class="scanned-item-type">👤 ANGGOTA</span>
                <div class="scanned-item-name">
                    <div style="font-weight: 600;">${escapeHtml(sessionData.member.name)}</div>
                    <div style="font-size: 11px; color: #999; margin-top: 2px;">${escapeHtml(sessionData.member.nisn)}</div>
                </div>
            `;
            scannedItems.appendChild(memberItem);
        }
        
        // Update scanned books
        if (sessionData.books && sessionData.books.length > 0) {
            sessionData.books.forEach(book => {
                const item = document.createElement('div');
                item.className = 'scanned-item';
                item.innerHTML = `
                    <span class="scanned-item-type">📚 BUKU</span>
                    <div class="scanned-item-name">
                        <div style="font-weight: 600;">${escapeHtml(book.title)}</div>
                        <div style="font-size: 11px; color: #999; margin-top: 2px;">ISBN: ${escapeHtml(book.isbn || '-')}</div>
                    </div>
                `;
                scannedItems.appendChild(item);
            });
        }
        
        // Skip to scanner step
        stepSession.classList.remove('active');
        stepScanner.classList.add('active');
        
        // Initialize scanner - but wait for library to be loaded first
        currentScanType = 'book'; // Set to book scan since member already scanned
        updateScanInstruction();
        
        // Wait for library to be loaded before initializing scanner
        const initScannerWhenReady = () => {
            if (typeof Html5Qrcode !== 'undefined') {
                console.log('[RESTORE] Html5Qrcode ready, initializing scanner');
                initializeScanner();
            } else {
                console.log('[RESTORE] Waiting for Html5Qrcode library...');
                setTimeout(initScannerWhenReady, 500);
            }
        };
        initScannerWhenReady();
        
    } else {
        // Focus on input field only if no session restored
        sessionToken.focus();
    }
    
    // ===== DIAGNOSTICS =====
    console.log('%c--- ENVIRONMENT INFO ---', 'color: green;');
    console.log('Current URL:', window.location.href);
    console.log('Pathname:', window.location.pathname);
    console.log('API Base Path:', API_BASE_PATH);
    
    console.log('%c--- BROWSER CAPABILITIES ---', 'color: green;');
    console.log('mediaDevices:', navigator.mediaDevices ? '✓ Available' : '✗ Not available');
    console.log('getUserMedia:', navigator.mediaDevices?.getUserMedia ? '✓ Available' : '✗ Not available');
    console.log('Browser:', navigator.userAgent.split(' ').pop());
    
    console.log('%c--- LIBRARY STATUS ---', 'color: green;');
    const html5QrcodeLoaded = typeof Html5Qrcode !== 'undefined';
    console.log('Html5Qrcode (initial):', html5QrcodeLoaded ? '✓ LOADED' : '✗ NOT YET (waiting for CDN)');
    
    if (!html5QrcodeLoaded) {
        console.log('Waiting for library to load from CDN...');
        sessionError.textContent = '⏳ Loading barcode library dari CDN... Mohon tunggu.';
        
        // Wait for library to load
        const waitForLibrary = setInterval(() => {
            if (typeof Html5Qrcode !== 'undefined') {
                console.log('✓ Html5Qrcode library loaded!');
                sessionError.textContent = '';
                clearInterval(waitForLibrary);
            }
        }, 500);
        
        // Timeout after 30 seconds
        setTimeout(() => {
            if (typeof Html5Qrcode === 'undefined') {
                clearInterval(waitForLibrary);
                console.error('%c❌ CRITICAL: Html5Qrcode library failed to load after 30 seconds!', 'color: red; font-weight: bold;');
                sessionError.textContent = '❌ Gagal memuat library barcode. Coba refresh halaman atau periksa koneksi internet.';
            }
        }, 30000);
    }
    
    console.log('%c--- DOM ELEMENTS ---', 'color: green;');
    console.log('step-session:', document.getElementById('step-session') ? '✓' : '✗');
    console.log('step-scanner:', document.getElementById('step-scanner') ? '✓' : '✗');
    console.log('qr-reader:', document.getElementById('qr-reader') ? '✓' : '✗');
    console.log('sessionToken:', document.getElementById('sessionToken') ? '✓' : '✗');
    console.log('btnVerifySession:', document.getElementById('btnVerifySession') ? '✓' : '✗');
    
    console.log('%c=== READY FOR INPUT ===', 'color: blue; font-weight: bold; font-size: 14px;');
    console.log('✓ Waiting for session token input...');

    // Listen for library load event (if using custom loader)
    document.addEventListener('libraryReady', () => {
        console.log('✓ Library ready event received');
        if (sessionError.textContent.includes('Loading')) {
            sessionError.textContent = '';
        }
    });
});

// ============================================================================
// Cleanup on Page Unload
// ============================================================================

window.addEventListener('beforeunload', () => {
    stopScanning();
});
