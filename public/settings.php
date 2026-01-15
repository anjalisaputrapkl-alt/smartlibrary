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
                                <button class="btn theme-btn" data-theme="light"
                                    style="padding: 12px; background: #f0f9ff; border: 2px solid var(--accent); font-weight: 600;">☀️
                                    Light</button>
                                <button class="btn theme-btn" data-theme="dark"
                                    style="padding: 12px; background: #1f2937; color: white; font-weight: 600;">🌙
                                    Dark</button>
                                <button class="btn theme-btn" data-theme="blue"
                                    style="padding: 12px; background: #0f172a; color: #60a5fa; border: 2px solid #60a5fa; font-weight: 600;">🔵
                                    Blue</button>
                            </div>
                            <small style="display: block; margin-top: 12px; color: var(--muted);">Tema yang dipilih akan
                                disimpan secara otomatis</small>

                            <h3>Tema Tambahan</h3>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                                <button class="btn theme-btn" data-theme="green"
                                    style="padding: 12px; background: #065f46; color: #d1fae5; border: 2px solid #10b981; font-weight: 600;">🟢
                                    Green</button>
                                <button class="btn theme-btn" data-theme="purple"
                                    style="padding: 12px; background: #581c87; color: #e9d5ff; border: 2px solid #d946ef; font-weight: 600;">🟣
                                    Purple</button>
                                <button class="btn theme-btn" data-theme="orange"
                                    style="padding: 12px; background: #7c2d12; color: #fed7aa; border: 2px solid #f97316; font-weight: 600;">🟠
                                    Orange</button>
                                <button class="btn theme-btn" data-theme="rose"
                                    style="padding: 12px; background: #831843; color: #ffe4e6; border: 2px solid #f43f5e; font-weight: 600;">🌹
                                    Rose</button>
                                <button class="btn theme-btn" data-theme="indigo"
                                    style="padding: 12px; background: #312e81; color: #e0e7ff; border: 2px solid #6366f1; font-weight: 600;">💜
                                    Indigo</button>
                                <button class="btn theme-btn" data-theme="cyan"
                                    style="padding: 12px; background: #164e63; color: #cffafe; border: 2px solid #06b6d4; font-weight: 600;">🔷
                                    Cyan</button>
                                <button class="btn theme-btn" data-theme="pink"
                                    style="padding: 12px; background: #831854; color: #fbcfe8; border: 2px solid #ec4899; font-weight: 600;">💖
                                    Pink</button>
                                <button class="btn theme-btn" data-theme="amber"
                                    style="padding: 12px; background: #78350f; color: #fef3c7; border: 2px solid #f59e0b; font-weight: 600;">🟡
                                    Amber</button>
                                <button class="btn theme-btn" data-theme="red"
                                    style="padding: 12px; background: #7f1d1d; color: #fee2e2; border: 2px solid #ef4444; font-weight: 600;">🔴
                                    Red</button>
                                <button class="btn theme-btn" data-theme="slate"
                                    style="padding: 12px; background: #1e293b; color: #e2e8f0; border: 2px solid #64748b; font-weight: 600;">⚫
                                    Slate</button>
                                <button class="btn theme-btn" data-theme="teal"
                                    style="padding: 12px; background: #134e4a; color: #ccfbf1; border: 2px solid #14b8a6; font-weight: 600;">🧊
                                    Teal</button>
                                <button class="btn theme-btn" data-theme="lime"
                                    style="padding: 12px; background: #365314; color: #cdfe56; border: 2px solid #84cc16; font-weight: 600;">🟢
                                    Lime</button>
                            </div>
                        </div>

                        <!-- Color Customization -->
                        <!-- REMOVED -->

                        <!-- Typography -->
                        <!-- REMOVED -->

                        <!-- Layout Settings -->
                        <!-- REMOVED -->

                    </div>

                    <!-- School Info Panel -->
                    <div class="card preview-card">
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

            </div>
        </div>
    </div>

    <?php include __DIR__ . '/partials/footer.php'; ?>

    <script>
        // Theme definitions
        const themes = {
            light: {
                name: 'Light',
                colors: {
                    '--bg': '#f1f4f8',
                    '--surface': '#ffffff',
                    '--text': '#1f2937',
                    '--muted': '#6b7280',
                    '--border': '#e5e7eb',
                    '--accent': '#2563eb',
                    '--danger': '#dc2626',
                    '--success': '#16a34a'
                }
            },
            dark: {
                name: 'Dark',
                colors: {
                    '--bg': '#1f2937',
                    '--surface': '#111827',
                    '--text': '#f3f4f6',
                    '--muted': '#9ca3af',
                    '--border': '#374151',
                    '--accent': '#3b82f6',
                    '--danger': '#ef4444',
                    '--success': '#22c55e'
                }
            },
            blue: {
                name: 'Blue',
                colors: {
                    '--bg': '#0f172a',
                    '--surface': '#1e293b',
                    '--text': '#e2e8f0',
                    '--muted': '#94a3b8',
                    '--border': '#334155',
                    '--accent': '#3b82f6',
                    '--danger': '#f87171',
                    '--success': '#4ade80'
                }
            },
            green: {
                name: 'Green',
                colors: {
                    '--bg': '#f0fdf4',
                    '--surface': '#ffffff',
                    '--text': '#166534',
                    '--muted': '#6b7280',
                    '--border': '#dcfce7',
                    '--accent': '#10b981',
                    '--danger': '#dc2626',
                    '--success': '#059669'
                }
            },
            purple: {
                name: 'Purple',
                colors: {
                    '--bg': '#faf5ff',
                    '--surface': '#ffffff',
                    '--text': '#6b21a8',
                    '--muted': '#6b7280',
                    '--border': '#e9d5ff',
                    '--accent': '#d946ef',
                    '--danger': '#dc2626',
                    '--success': '#a855f7'
                }
            },
            orange: {
                name: 'Orange',
                colors: {
                    '--bg': '#fffbeb',
                    '--surface': '#ffffff',
                    '--text': '#92400e',
                    '--muted': '#6b7280',
                    '--border': '#fed7aa',
                    '--accent': '#f97316',
                    '--danger': '#dc2626',
                    '--success': '#ea580c'
                }
            },
            rose: {
                name: 'Rose',
                colors: {
                    '--bg': '#fff7ed',
                    '--surface': '#ffffff',
                    '--text': '#831843',
                    '--muted': '#6b7280',
                    '--border': '#ffe4e6',
                    '--accent': '#f43f5e',
                    '--danger': '#dc2626',
                    '--success': '#be185d'
                }
            },
            indigo: {
                name: 'Indigo',
                colors: {
                    '--bg': '#f0f4ff',
                    '--surface': '#ffffff',
                    '--text': '#312e81',
                    '--muted': '#6b7280',
                    '--border': '#e0e7ff',
                    '--accent': '#6366f1',
                    '--danger': '#dc2626',
                    '--success': '#4f46e5'
                }
            },
            cyan: {
                name: 'Cyan',
                colors: {
                    '--bg': '#ecf9ff',
                    '--surface': '#ffffff',
                    '--text': '#164e63',
                    '--muted': '#6b7280',
                    '--border': '#cffafe',
                    '--accent': '#06b6d4',
                    '--danger': '#dc2626',
                    '--success': '#0891b2'
                }
            },
            pink: {
                name: 'Pink',
                colors: {
                    '--bg': '#fdf2f8',
                    '--surface': '#ffffff',
                    '--text': '#831854',
                    '--muted': '#6b7280',
                    '--border': '#fbcfe8',
                    '--accent': '#ec4899',
                    '--danger': '#dc2626',
                    '--success': '#db2777'
                }
            },
            amber: {
                name: 'Amber',
                colors: {
                    '--bg': '#fffbeb',
                    '--surface': '#ffffff',
                    '--text': '#78350f',
                    '--muted': '#6b7280',
                    '--border': '#fef3c7',
                    '--accent': '#f59e0b',
                    '--danger': '#dc2626',
                    '--success': '#d97706'
                }
            },
            red: {
                name: 'Red',
                colors: {
                    '--bg': '#fef2f2',
                    '--surface': '#ffffff',
                    '--text': '#7f1d1d',
                    '--muted': '#6b7280',
                    '--border': '#fee2e2',
                    '--accent': '#ef4444',
                    '--danger': '#dc2626',
                    '--success': '#dc2626'
                }
            },
            slate: {
                name: 'Slate',
                colors: {
                    '--bg': '#f8fafc',
                    '--surface': '#ffffff',
                    '--text': '#1e293b',
                    '--muted': '#64748b',
                    '--border': '#e2e8f0',
                    '--accent': '#64748b',
                    '--danger': '#dc2626',
                    '--success': '#475569'
                }
            },
            teal: {
                name: 'Teal',
                colors: {
                    '--bg': '#f0fdfa',
                    '--surface': '#ffffff',
                    '--text': '#134e4a',
                    '--muted': '#6b7280',
                    '--border': '#ccfbf1',
                    '--accent': '#14b8a6',
                    '--danger': '#dc2626',
                    '--success': '#0d9488'
                }
            },
            lime: {
                name: 'Lime',
                colors: {
                    '--bg': '#f7fee7',
                    '--surface': '#ffffff',
                    '--text': '#365314',
                    '--muted': '#6b7280',
                    '--border': '#dcfce7',
                    '--accent': '#84cc16',
                    '--danger': '#dc2626',
                    '--success': '#65a30d'
                }
            }
        };

        // Global state to track current theme
        let currentTheme = 'light';

        // Update button states
        function updateThemeButtons(active) {
            document.querySelectorAll('.theme-btn').forEach(btn => {
                if (btn.getAttribute('data-theme') === active) {
                    btn.style.boxShadow = '0 0 0 3px rgba(37, 99, 235, 0.2)';
                    btn.style.fontWeight = '600';
                } else {
                    btn.style.boxShadow = 'none';
                    btn.style.fontWeight = '400';
                }
            });
        }

        // Apply theme and save to API
        async function applyTheme(themeName) {
            const theme = themes[themeName];
            if (!theme) return;

            // Apply colors to DOM
            Object.entries(theme.colors).forEach(([key, value]) => {
                document.documentElement.style.setProperty(key, value);
            });

            currentTheme = themeName;
            updateThemeButtons(themeName);

            // Save to database via API
            try {
                const response = await fetch('/perpustakaan-online/public/api/theme.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        theme_name: themeName
                    })
                });
                if (!response.ok) console.error('Failed to save theme');
            } catch (error) {
                console.error('Error saving theme:', error);
            }
        }

        // Theme button listeners
        document.querySelectorAll('.theme-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const themeName = btn.getAttribute('data-theme');
                applyTheme(themeName);
            });
        });

        // Load settings from API on page load
        async function loadSettingsFromAPI() {
            try {
                const response = await fetch('/perpustakaan-online/public/api/theme.php');
                if (!response.ok) throw new Error('Failed to load settings');
                const data = await response.json();
                if (data.success) {
                    currentTheme = data.theme_name;
                    applyTheme(data.theme_name);
                }
            } catch (error) {
                console.warn('Could not load settings from API, using defaults:', error);
            }
        }

        // Load and apply saved theme on page load
        document.addEventListener('DOMContentLoaded', async () => {
            // Load from API first
            await loadSettingsFromAPI();
        });
    </script>

</body>

</html>