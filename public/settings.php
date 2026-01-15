<?php
require __DIR__ . '/../src/auth.php';
requireAuth();
$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if ($name === '' || $slug === '') {
        $error = 'Nama dan slug wajib diisi.';
    } else {
        // ensure slug unique
        $stmt = $pdo->prepare('SELECT id FROM schools WHERE slug = :slug AND id != :id');
        $stmt->execute(['slug' => $slug, 'id' => $sid]);
        $exists = $stmt->fetchColumn();
        if ($exists) {
            $error = 'Slug sudah digunakan oleh sekolah lain.';
        } else {
            $stmt = $pdo->prepare('UPDATE schools SET name = :name, slug = :slug WHERE id = :id');
            $stmt->execute(['name' => $name, 'slug' => $slug, 'id' => $sid]);
            $success = 'Pengaturan tersimpan.';
        }
    }
}

$stmt = $pdo->prepare('SELECT * FROM schools WHERE id = :id');
$stmt->execute(['id' => $sid]);
$school = $stmt->fetch();
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengaturan Sekolah - Perpustakaan Online</title>
    <script src="../assets/js/theme.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg: #f1f4f8;
            --surface: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --border: #e5e7eb;
            --accent: #2563eb;
            --danger: #dc2626;
            --success: #16a34a;
        }

        * {
            box-sizing: border-box
        }

        html,
        body {
            margin: 0;
        }

        body {
            font-family: Inter, system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        a {
            text-decoration: none;
            color: inherit
        }

        /* Layout */
        .app {
            min-height: 100vh;
            display: grid;
            grid-template-rows: 64px 1fr;
            margin-left: 260px;
        }

        /* Topbar */
        .topbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 22px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: fixed;
            top: 0;
            left: 260px;
            right: 0;
            z-index: 999;
        }

        .topbar strong {
            font-size: 15px;
        }

        /* Content */
        .content {
            padding: 32px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 32px;
            margin-top: 64px;
        }

        /* Main */
        .main {
            display: flex;
            flex-direction: column;
            gap: 32px;
        }

        /* Card */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
        }

        .card h2 {
            font-size: 16px;
            margin: 0 0 20px;
            font-weight: 600;
        }

        .card h3 {
            font-size: 14px;
            margin: 20px 0 12px;
            font-weight: 600;
        }

        .card hr {
            border: none;
            border-top: 1px solid var(--border);
            margin: 20px 0;
        }

        /* Form */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 16px;
        }

        label {
            font-size: 12px;
            color: var(--muted);
            font-weight: 500;
        }

        input,
        select {
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 13px;
            font-family: inherit;
        }

        small {
            font-size: 12px;
            color: var(--muted);
        }

        /* Button */
        .btn {
            padding: 8px 14px;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: white;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn:hover {
            background: #f9fafb;
        }

        .btn.primary {
            background: var(--accent);
            color: white;
            border: none;
        }

        .btn.primary:hover {
            opacity: 0.9;
        }

        .btn.secondary {
            background: #f3f4f6;
            color: var(--text);
        }

        /* Alert */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
            font-size: 13px;
        }

        .alert.danger {
            background: #fee2e2;
            color: var(--danger);
            border: 1px solid #fecaca;
        }

        .alert.success {
            background: #dcfce7;
            color: var(--success);
            border: 1px solid #bbf7d0;
        }

        .alert span {
            font-weight: 600;
        }

        /* Settings Grid */
        .settings-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
        }

        .settings-controls {
            display: flex;
            flex-direction: column;
            gap: 32px;
        }

        .color-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .color-input {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .color-input input {
            width: 100%;
            height: 40px;
            cursor: pointer;
        }

        .flex-row {
            display: flex;
            gap: 12px;
            align-items: flex-end;
        }

        .flex-row>div {
            flex: 1;
        }

        /* Preview */
        .preview-card {
            position: sticky;
            top: 100px;
        }

        .preview-content {
            font-size: 13px;
            line-height: 1.6;
        }

        @media (max-width: 1024px) {
            .settings-section {
                grid-template-columns: 1fr;
            }

            .preview-card {
                position: static;
            }
        }
    </style>
</head>

<body>
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <div class="app">

        <div class="topbar">
            <strong>⚙️ Pengaturan Sekolah</strong>
        </div>

        <div class="content">
            <div class="main">

                <div class="settings-section">
                    <div class="settings-controls">

                        <!-- Theme Settings -->
                        <div class="card">
                            <h2>🎨 Pengaturan Tema</h2>

                            <h3>Pilih Tema</h3>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                                <button class="btn"
                                    style="padding: 12px; background: #f0f9ff; border: 2px solid var(--accent);">Default</button>
                                <button class="btn"
                                    style="padding: 12px; background: #1f2937; color: white;">Dark</button>
                                <button class="btn"
                                    style="padding: 12px; background: #f6f9ff; border: 2px solid #3A7FF2;">Blue</button>
                            </div>

                            <h3>Penyesuaian Warna</h3>
                            <div class="color-grid">
                                <div class="color-input">
                                    <label>Warna Utama</label>
                                    <input id="primary-color" type="color" value="#2563eb">
                                </div>
                                <div class="color-input">
                                    <label>Warna Sekunder</label>
                                    <input id="secondary-color" type="color" value="#60a5fa">
                                </div>
                                <div class="color-input">
                                    <label>Background</label>
                                    <input id="bg-color" type="color" value="#f1f4f8">
                                </div>
                                <div class="color-input">
                                    <label>Aksen</label>
                                    <input id="accent-color" type="color" value="#1f2937">
                                </div>
                            </div>

                            <button class="btn secondary" style="margin-top: 12px; width: 100%;">↺ Reset Warna</button>
                        </div>

                        <!-- Typography -->
                        <div class="card">
                            <h2>✍️ Tipografi</h2>

                            <div class="form-group">
                                <label>Font</label>
                                <select>
                                    <option selected>Inter (Modern)</option>
                                    <option>System (Sans)</option>
                                    <option>Serif (Classic)</option>
                                </select>
                            </div>

                            <div class="flex-row">
                                <div>
                                    <label>Ukuran</label>
                                    <select>
                                        <option>Small</option>
                                        <option selected>Default</option>
                                        <option>Large</option>
                                    </select>
                                </div>
                                <div>
                                    <label>Bobot</label>
                                    <select>
                                        <option>Normal</option>
                                        <option selected>Semi-bold</option>
                                        <option>Bold</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Layout Settings -->
                        <div class="card">
                            <h2>📐 Tata Letak</h2>

                            <div class="flex-row">
                                <div>
                                    <label>Sudut Rounded</label>
                                    <select>
                                        <option>Small</option>
                                        <option selected>Medium</option>
                                        <option>Large</option>
                                    </select>
                                </div>
                                <div>
                                    <label>Bayangan</label>
                                    <select>
                                        <option>None</option>
                                        <option selected>Soft</option>
                                        <option>Medium</option>
                                        <option>Deep</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- School Info -->
                        <div class="card">
                            <h2>🏫 Informasi Sekolah</h2>

                            <?php if (!empty($error)): ?>
                                <div class="alert danger">
                                    <span>⚠️</span>
                                    <div><?php echo $error; ?></div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($success)): ?>
                                <div class="alert success">
                                    <span>✓</span>
                                    <div><?php echo $success; ?></div>
                                </div>
                            <?php endif; ?>

                            <form method="post">
                                <div class="form-group">
                                    <label for="name">Nama Sekolah</label>
                                    <input id="name" name="name" required
                                        value="<?php echo htmlspecialchars($school['name']); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="slug">Slug (untuk URL)</label>
                                    <input id="slug" name="slug" required
                                        value="<?php echo htmlspecialchars($school['slug']); ?>">
                                    <small>Gunakan huruf kecil, angka, dan tanda hubung (-)</small>
                                </div>

                                <button type="submit" class="btn primary" style="width: 100%;">💾 Simpan
                                    Perubahan</button>
                            </form>
                        </div>

                    </div>

                    <!-- Preview Panel -->
                    <div class="card preview-card">
                        <h2>👁️ Pratinjau</h2>
                        <div class="preview-content">
                            <div style="padding: 16px; background: #f9fafb; border-radius: 8px; margin-bottom: 12px;">
                                <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 12px;">
                                    <div
                                        style="width: 32px; height: 32px; background: var(--accent); border-radius: 6px;">
                                    </div>
                                    <strong>Perpustakaan</strong>
                                </div>
                                <div style="display: flex; gap: 8px; flex-direction: column;">
                                    <div style="height: 8px; background: #e5e7eb; border-radius: 4px; width: 100%;">
                                    </div>
                                    <div style="height: 8px; background: #e5e7eb; border-radius: 4px; width: 80%;">
                                    </div>
                                    <div style="height: 8px; background: #e5e7eb; border-radius: 4px; width: 60%;">
                                    </div>
                                </div>
                            </div>

                            <div style="padding: 12px; background: #f9fafb; border-radius: 8px; margin-bottom: 12px;">
                                <strong style="font-size: 12px;">Statistik</strong>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 8px;">
                                    <div
                                        style="padding: 8px; background: white; border-radius: 6px; border: 1px solid var(--border); text-align: center;">
                                        <div style="font-size: 18px; font-weight: 600;">24</div>
                                        <div style="font-size: 11px; color: var(--muted);">Buku</div>
                                    </div>
                                    <div
                                        style="padding: 8px; background: white; border-radius: 6px; border: 1px solid var(--border); text-align: center;">
                                        <div style="font-size: 18px; font-weight: 600;">18</div>
                                        <div style="font-size: 11px; color: var(--muted);">Anggota</div>
                                    </div>
                                </div>
                            </div>

                            <div
                                style="padding: 12px; background: white; border: 1px solid var(--border); border-radius: 8px; font-size: 12px;">
                                <strong style="display: block; margin-bottom: 8px;">Tabel Contoh</strong>
                                <table style="width: 100%; font-size: 11px;">
                                    <tr style="border-bottom: 1px solid var(--border);">
                                        <td style="padding: 4px;">Item 1</td>
                                        <td style="padding: 4px; text-align: right;">4</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 4px;">Item 2</td>
                                        <td style="padding: 4px; text-align: right;">2</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <?php include __DIR__ . '/partials/footer.php'; ?>

    <script>
        // Simple theme preview
        document.querySelectorAll('.settings-controls button').forEach(btn => {
            if (btn.textContent.includes('Default') || btn.textContent.includes('Dark') || btn.textContent.includes('Blue')) {
                btn.addEventListener('click', () => {
                    btn.parentElement.querySelectorAll('button').forEach(b => b.style.borderWidth = '1px');
                    btn.style.borderWidth = '2px';
                });
            }
        });

        // Color preview
        ['primary-color', 'secondary-color', 'bg-color', 'accent-color'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('change', (e) => {
                    document.documentElement.style.setProperty('--' + id.replace('-color', ''), e.target.value);
                });
            }
        });
    </script>

</body>

</html>

<script>
    // Simple theme preview
    document.querySelectorAll('.settings-controls button').forEach(btn => {
        if (btn.textContent.includes('Default') || btn.textContent.includes('Dark') || btn.textContent.includes('Blue')) {
            btn.addEventListener('click', () => {
                btn.parentElement.querySelectorAll('button').forEach(b => b.style.borderWidth = '1px');
                btn.style.borderWidth = '2px';
            });
        }
    });

    // Color preview
    ['primary-color', 'secondary-color', 'bg-color', 'accent-color'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', (e) => {
                document.documentElement.style.setProperty('--' + id.replace('-color', ''), e.target.value);
            });
        }
    });
</script>

</body>

</html>

<script>
    (function () {
        const defaultSettings = {
            theme: 'blue',
            primary: '#3A7FF2',
            secondary: '#7AB8F5',
            bg: '#F6F9FF',
            accent: '#0A1A4F',
            fontFamily: "Inter, system-ui, -apple-system, 'Segoe UI', Roboto",
            fontSize: '16',
            fontWeight: '600',
            cornerRadius: '10',
            shadowStrength: 'soft',
            dashboardCardBg: '#FFFFFF',
            catalogMode: 'grid',
            reportsTableStyle: 'bordered'
        };

        let undoStack = [], redoStack = [];

        function loadSettings() {
            try {
                return JSON.parse(localStorage.getItem('smartlib_theme')) || defaultSettings;
            } catch (e) { return defaultSettings; }
        }

        function saveToStorage(s) {
            localStorage.setItem('smartlib_theme', JSON.stringify(s));
        }

        function applySettings(s) {
            document.documentElement.style.setProperty('--primary', s.primary);
            document.documentElement.style.setProperty('--primary-2', s.secondary);
            document.documentElement.style.setProperty('--bg', s.bg);
            document.documentElement.style.setProperty('--primary-dark', s.accent);
            document.documentElement.style.setProperty('--radius-md', s.cornerRadius + 'px');
            document.body.style.fontFamily = s.fontFamily;
            document.body.style.fontSize = s.fontSize + 'px';
            // shadow classes
            document.body.classList.remove('shadow-none', 'shadow-soft', 'shadow-medium', 'shadow-deep');
            document.body.classList.add('shadow-' + s.shadowStrength);

            // preview tweaks
            var preview = document.getElementById('live-preview');
            if (preview) {
                preview.querySelectorAll('.card').forEach(c => {
                    c.style.background = s.dashboardCardBg;
                    c.style.borderRadius = s.cornerRadius + 'px';
                });
                if (s.reportsTableStyle === 'borderless') {
                    preview.querySelectorAll('table').forEach(t => t.style.border = 'none');
                } else {
                    preview.querySelectorAll('table').forEach(t => t.style.border = '1px solid var(--border)');
                }
            }
        }

        function pushUndo(s) { undoStack.push(JSON.stringify(s)); if (undoStack.length > 50) undoStack.shift(); redoStack = []; }
        function undo() { if (!undoStack.length) return; var cur = loadSettings(); redoStack.push(JSON.stringify(cur)); var prev = JSON.parse(undoStack.pop()); saveToStorage(prev); updateControls(prev); applySettings(prev); try { window.dispatchEvent(new CustomEvent('smartlib_theme:changed', { detail: prev })); } catch (e) { } }
        function redo() { if (!redoStack.length) return; var cur = loadSettings(); undoStack.push(JSON.stringify(cur)); var nxt = JSON.parse(redoStack.pop()); saveToStorage(nxt); updateControls(nxt); applySettings(nxt); try { window.dispatchEvent(new CustomEvent('smartlib_theme:changed', { detail: nxt })); } catch (e) { } }

        function updateControls(s) {
            document.getElementById('theme-select').value = s.theme;
            document.getElementById('primary-color').value = s.primary;
            document.getElementById('secondary-color').value = s.secondary;
            document.getElementById('bg-color').value = s.bg;
            document.getElementById('accent-color').value = s.accent;
            document.getElementById('font-family').value = s.fontFamily;
            document.getElementById('font-size').value = s.fontSize;
            document.getElementById('font-weight').value = s.fontWeight;
            document.getElementById('corner-radius').value = s.cornerRadius;
            document.getElementById('shadow-strength').value = s.shadowStrength;
            document.getElementById('dashboard-card-bg').value = s.dashboardCardBg;
            document.getElementById('catalog-mode').value = s.catalogMode;
            document.getElementById('reports-table-style').value = s.reportsTableStyle;
        }

        document.addEventListener('DOMContentLoaded', function () {
            var settings = loadSettings();
            updateControls(settings);
            applySettings(settings);

            // wire controls
            ['theme-select', 'primary-color', 'secondary-color', 'bg-color', 'accent-color', 'font-family', 'font-size', 'font-weight', 'corner-radius', 'shadow-strength', 'dashboard-card-bg', 'catalog-mode', 'reports-table-style'].forEach(id => {
                var el = document.getElementById(id);
                if (!el) return;
                el.addEventListener('change', function (e) {
                    var cur = loadSettings();
                    pushUndo(cur);
                    var key = id.replace(/-/g, '');
                    // map ids to settings
                    switch (id) {
                        case 'theme-select': cur.theme = e.target.value; break;
                        case 'primary-color': cur.primary = e.target.value; break;
                        case 'secondary-color': cur.secondary = e.target.value; break;
                        case 'bg-color': cur.bg = e.target.value; break;
                        case 'accent-color': cur.accent = e.target.value; break;
                        case 'font-family': cur.fontFamily = e.target.value; break;
                        case 'font-size': cur.fontSize = e.target.value; break;
                        case 'font-weight': cur.fontWeight = e.target.value; break;
                        case 'corner-radius': cur.cornerRadius = e.target.value; break;
                        case 'shadow-strength': cur.shadowStrength = e.target.value; break;
                        case 'dashboard-card-bg': cur.dashboardCardBg = e.target.value; break;
                        case 'catalog-mode': cur.catalogMode = e.target.value; break;
                        case 'reports-table-style': cur.reportsTableStyle = e.target.value; break;
                        default: break;
                    }
                    saveToStorage(cur);
                    applySettings(cur);
                    try { window.dispatchEvent(new CustomEvent('smartlib_theme:changed', { detail: cur })); } catch (e) { }
                });
            });

            document.getElementById('btn-reset-colors').addEventListener('click', function () {
                pushUndo(loadSettings());
                var s = loadSettings(); s.primary = defaultSettings.primary; s.secondary = defaultSettings.secondary; s.bg = defaultSettings.bg; s.accent = defaultSettings.accent; saveToStorage(s); updateControls(s); applySettings(s);
                try { window.dispatchEvent(new CustomEvent('smartlib_theme:changed', { detail: s })); } catch (e) { }
                showToast('Color values reset.');
            });

            document.getElementById('save-settings').addEventListener('click', function () {
                var s = loadSettings(); saveToStorage(s); try { window.dispatchEvent(new CustomEvent('smartlib_theme:changed', { detail: s })); } catch (e) { }; showToast('Settings saved to localStorage. (Server-side save not enabled.)');
            });

            document.getElementById('reset-settings').addEventListener('click', function () {
                pushUndo(loadSettings()); saveToStorage(defaultSettings); updateControls(defaultSettings); applySettings(defaultSettings); try { window.dispatchEvent(new CustomEvent('smartlib_theme:changed', { detail: defaultSettings })); } catch (e) { }; showToast('Settings reset to default.');
            });

            document.getElementById('undo-settings').addEventListener('click', function () { undo(); });
            document.getElementById('redo-settings').addEventListener('click', function () { redo(); });

            // theme-mini preview quick click
            document.querySelectorAll('.theme-mini').forEach(el => {
                el.addEventListener('click', function () {
                    var t = el.getAttribute('data-theme');
                    var cur = loadSettings();
                    pushUndo(cur);
                    if (t === 'dark') {
                        cur.primary = '#0A1A4F'; cur.secondary = '#3A7FF2'; cur.bg = '#071226'; cur.accent = '#7AB8F5';
                    } else if (t === 'blue') {
                        cur.primary = '#3A7FF2'; cur.secondary = '#7AB8F5'; cur.bg = '#F6F9FF'; cur.accent = '#0A1A4F';
                    } else {
                        cur = Object.assign({}, defaultSettings);
                    }
                    saveToStorage(cur); updateControls(cur); applySettings(cur);
                    try { window.dispatchEvent(new CustomEvent('smartlib_theme:changed', { detail: cur })); } catch (e) { }
                });
            });

            // toast helper
            function showToast(msg) {
                var t = document.createElement('div');
                t.className = 'toast';
                t.style.cssText = 'position:fixed;right:18px;bottom:18px;background:#0A1A4F;color:#fff;padding:10px 14px;border-radius:8px;box-shadow:0 8px 20px rgba(10,26,79,0.12);z-index:9999';
                t.innerText = msg; document.body.appendChild(t);
                setTimeout(() => { t.style.opacity = 0; setTimeout(() => t.remove(), 300); }, 2200);
            }

        });
    })();
</script>