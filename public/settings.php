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
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengaturan Sekolah - Perpustakaan Online</title>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <script src="../assets/js/theme.js"></script>
</head>

<body>
    <?php include __DIR__ . '/partials/header.php'; ?>

    <div class="container settings-page">
        <div class="header-row">
            <div>
                <h1>Pengaturan Tampilan & Tema</h1>
                <p class="header-info">Personalisasi tampilan SmartLibrary tanpa mengubah data atau logika sistem.</p>
            </div>
            <div class="btn-group">
                <a class="btn" href="/perpustakaan-online/public/index.php">← Dashboard</a>
            </div>
        </div>

        <div class="settings-grid">
            <!-- Left: Controls -->
            <div>
                <div class="card">
                    <h2>Theme Selector</h2>
                    <div class="section" style="margin-top:8px;">
                        <label>Pilih Tema</label>
                        <select id="theme-select">
                            <option value="default">Default</option>
                            <option value="dark">Dark Mode</option>
                            <option value="light">Light Mode</option>
                            <option value="blue">Blue Elegant</option>
                            <option value="custom">Custom Theme</option>
                        </select>

                        <div class="theme-previews">
                            <div class="card theme-mini" data-theme="default"><small>Default</small></div>
                            <div class="card theme-mini" data-theme="dark"><small>Dark</small></div>
                            <div class="card theme-mini" data-theme="blue"><small>Blue</small></div>
                        </div>
                    </div>

                    <hr />

                    <h3>Color Customizer</h3>
                    <div class="section" style="display:flex; gap:12px; flex-wrap:wrap;">
                        <div style="min-width:160px;">
                            <label>Primary color</label>
                            <input id="primary-color" type="color" value="#3A7FF2">
                        </div>
                        <div style="min-width:160px;">
                            <label>Secondary color</label>
                            <input id="secondary-color" type="color" value="#7AB8F5">
                        </div>
                        <div style="min-width:160px;">
                            <label>Background</label>
                            <input id="bg-color" type="color" value="#F6F9FF">
                        </div>
                        <div style="min-width:160px;">
                            <label>Accent</label>
                            <input id="accent-color" type="color" value="#0A1A4F">
                        </div>
                    </div>
                    <div style="margin-top:10px;">
                        <button id="btn-reset-colors" class="btn btn-secondary">Reset to Default</button>
                    </div>

                    <hr />

                    <h3>Typography</h3>
                    <div class="section" style="margin-top:8px; display:flex; gap:12px; align-items:center;">
                        <label style="min-width:120px;">Font</label>
                        <select id="font-family" style="width:220px;">
                            <option value="Inter, system-ui, -apple-system, 'Segoe UI', Roboto">Inter (Modern)</option>
                            <option value="'Helvetica Neue', Helvetica, Arial, sans-serif">Sans (System)</option>
                            <option value="Georgia, 'Times New Roman', Times, serif">Serif (Classic)</option>
                        </select>
                    </div>
                    <div class="section" style="margin-top:8px; display:flex; gap:12px; align-items:center;">
                        <label style="min-width:120px;">Size</label>
                        <select id="font-size">
                            <option value="14">Small</option>
                            <option value="16" selected>Default</option>
                            <option value="18">Large</option>
                        </select>
                        <label style="min-width:120px;">Weight</label>
                        <select id="font-weight">
                            <option value="400">Normal</option>
                            <option value="600" selected>Semi-bold</option>
                            <option value="800">Bold</option>
                        </select>
                    </div>

                    <hr />

                    <h3>Layout Settings</h3>
                    <div class="section" style="display:flex; gap:12px; align-items:center; margin-top:8px;">
                        <label style="min-width:120px;">Corners</label>
                        <select id="corner-radius">
                            <option value="6">Small</option>
                            <option value="10" selected>Medium</option>
                            <option value="16">Rounded XL</option>
                        </select>
                        <label style="min-width:120px;">Shadow</label>
                        <select id="shadow-strength">
                            <option value="none">None</option>
                            <option value="soft" selected>Soft</option>
                            <option value="medium">Medium</option>
                            <option value="deep">Deep</option>
                        </select>
                    </div>

                    <hr />

                    <h3>Per-page Settings</h3>
                    <div class="section" style="margin-top:8px;">
                        <label>Dashboard - Card Background</label>
                        <input id="dashboard-card-bg" type="color" value="#FFFFFF">
                        <label style="margin-top:6px;">Katalog - Mode</label>
                        <select id="catalog-mode">
                            <option value="grid">Grid</option>
                            <option value="list">List</option>
                        </select>
                        <label style="margin-top:6px;">Reports - Table Style</label>
                        <select id="reports-table-style">
                            <option value="bordered">Bordered</option>
                            <option value="borderless">Borderless</option>
                        </select>
                    </div>

                    <div style="display:flex; gap:10px; margin-top:14px;">
                        <button id="save-settings" class="btn btn-primary">Save Changes</button>
                        <button id="reset-settings" class="btn btn-secondary">Reset to Default</button>
                        <button id="undo-settings" class="btn btn-small">Undo</button>
                        <button id="redo-settings" class="btn btn-small">Redo</button>
                    </div>
                </div>

                <!-- Keep School Info as a separate card -->
                <div class="card mt-16">
                    <div class="header-row">
                        <div>
                            <h3>Informasi Sekolah</h3>
                        </div>
                    </div>

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

                    <form method="post" style="max-width: 500px;">
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
                        <button type="submit" class="btn btn-block">💾 Simpan Pengaturan</button>
                    </form>
                </div>
            </div>

            <!-- Right: Live preview -->
            <aside class="card theme-preview min-h-480" id="live-preview">
                <div class="preview-header">
                    <strong>Live Preview</strong>
                    <small class="text-muted">Responsive sample</small>
                </div>

                <div class="preview-navbar">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <div class="brand-logo" style="width:34px;height:34px;"></div>
                        <div style="font-weight:700;">Perpustakaan</div>
                    </div>
                </div>

                <div class="preview-content">
                    <div style="display:grid; grid-template-columns:1fr 120px; gap:12px; align-items:start;">
                        <div class="card">
                            <h4 style="margin:0 0 6px 0;">Contoh Kartu</h4>
                            <p class="text-muted">Ringkasan singkat dan metrik.</p>
                        </div>
                        <div class="card center">
                            <div class="badge">7</div>
                        </div>
                    </div>

                    <div style="margin-top:12px;">
                        <h4 style="margin-bottom:8px;">Tabel Contoh</h4>
                        <table style="width:100%; border-collapse:collapse; font-size:13px;">
                            <thead>
                                <tr style="background:rgba(10,26,79,0.04);">
                                    <th style="padding:8px; text-align:left;">Judul</th>
                                    <th style="padding:8px; text-align:left;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="padding:8px;">Buku A</td>
                                    <td style="padding:8px;">Tersedia</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px;">Buku B</td>
                                    <td style="padding:8px;">Dipinjam</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </aside>
        </div>

    </div>

    <?php include __DIR__ . '/partials/footer.php'; ?>

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


</body>

</html>