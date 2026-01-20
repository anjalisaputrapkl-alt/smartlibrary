<?php
if (session_status() !== PHP_SESSION_ACTIVE)
    session_start();
$user = $_SESSION['user'] ?? null;
$current = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = '/perpustakaan-online/public';

function _is_active_sidebar($path, $current)
{
    $current = rtrim(str_replace('/perpustakaan-online/public', '', $current), '/') ?: '/';
    $path = rtrim(str_replace('/perpustakaan-online/public', '', $path), '/') ?: '/';
    return $current === $path ? ' active' : '';
}
?>
<!DOCTYPE html>
<html>

<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <style>
        :root {
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #0f1724;
            --muted: #6b7280;
            --accent: #0b3d61;
            --accent-light: #e0f2fe;
            --border: #e2e8f0;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }

        /* ===== ANIMATIONS ===== */
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-40px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Navigation Sidebar */
        .nav-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 240px;
            background: #062d4a;
            color: white;
            padding: 24px 0;
            z-index: 1002;
            overflow-y: auto;
            animation: slideInLeft 0.6s ease-out;
        }

        .nav-sidebar-header {
            padding: 0 24px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: white;
            cursor: pointer;
        }

        .nav-sidebar-header-icon {
            font-size: 32px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
        }

        .nav-sidebar-header-icon iconify-icon {
            width: 32px;
            height: 32px;
            color: white;
        }

        .nav-sidebar-header h2 {
            font-size: 14px;
            font-weight: 700;
            margin: 0;
        }

        .nav-sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-sidebar-menu li {
            margin: 0;
        }

        .nav-sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
            position: relative;
        }

        .nav-sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .nav-sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border-left-color: white;
            font-weight: 600;
        }

        .nav-sidebar-menu-icon {
            font-size: 18px;
            width: 24px;
            text-align: center;
            flex-shrink: 0;
        }

        iconify-icon {
            display: inline-block;
            vertical-align: middle;
        }

        .nav-sidebar-menu iconify-icon {
            font-size: 18px;
            width: 24px;
            height: 24px;
            color: rgba(255, 255, 255, 0.8);
        }

        .nav-sidebar-menu a:hover iconify-icon,
        .nav-sidebar-menu a.active iconify-icon {
            color: white;
        }

        .nav-sidebar-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
            margin: 16px 0;
        }

        /* Adjust main content to account for sidebar */
        .app {
            margin-left: 240px;
        }

        @media (max-width: 768px) {
            .nav-sidebar {
                width: 100%;
                height: auto;
                position: relative;
                flex-direction: row;
                padding: 12px 16px;
                display: flex;
                align-items: center;
            }

            .nav-sidebar-header {
                margin-bottom: 0;
                padding: 0;
            }

            .nav-sidebar-menu {
                display: flex;
                flex-direction: row;
                gap: 4px;
                flex: 1;
            }

            .nav-sidebar-menu a {
                padding: 8px 12px;
                font-size: 12px;
            }

            .nav-sidebar-menu span {
                display: none;
            }

            .app {
                margin-left: 0;
            }
        }
    </style>
</head>

<body></body>

</html>

<!-- Navigation Sidebar -->
<nav class="nav-sidebar" id="navSidebar">
    <a href="<?php echo $base; ?>/" class="nav-sidebar-header">
        <div class="nav-sidebar-header-icon">
            <iconify-icon icon="mdi:library"></iconify-icon>
        </div>
        <h2>Perpustakaan</h2>
    </a>

    <ul class="nav-sidebar-menu">
        <li>
            <a href="<?php echo $base; ?>/" class="nav-link<?php echo _is_active_sidebar($base . '/', $current); ?>">
                <span class="nav-sidebar-menu-icon">
                    <iconify-icon icon="mdi:view-dashboard"></iconify-icon>
                </span>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>/books.php"
                class="nav-link<?php echo _is_active_sidebar($base . '/books.php', $current); ?>">
                <span class="nav-sidebar-menu-icon">
                    <iconify-icon icon="mdi:book-multiple"></iconify-icon>
                </span>
                <span>Buku</span>
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>/book-maintenance.php"
                class="nav-link<?php echo _is_active_sidebar($base . '/book-maintenance.php', $current); ?>">
                <span class="nav-sidebar-menu-icon">
                    <iconify-icon icon="mdi:wrench"></iconify-icon>
                </span>
                <span>Pemeliharaan</span>
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>/members.php"
                class="nav-link<?php echo _is_active_sidebar($base . '/members.php', $current); ?>">
                <span class="nav-sidebar-menu-icon">
                    <iconify-icon icon="mdi:account-multiple"></iconify-icon>
                </span>
                <span>Anggota</span>
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>/borrows.php"
                class="nav-link<?php echo _is_active_sidebar($base . '/borrows.php', $current); ?>">
                <span class="nav-sidebar-menu-icon">
                    <iconify-icon icon="mdi:book-open-variant"></iconify-icon>
                </span>
                <span>Peminjaman</span>
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>/reports.php"
                class="nav-link<?php echo _is_active_sidebar($base . '/reports.php', $current); ?>">
                <span class="nav-sidebar-menu-icon">
                    <iconify-icon icon="mdi:chart-line"></iconify-icon>
                </span>
                <span>Laporan</span>
            </a>
        </li>
        <?php if ($user): ?>
            <li>
                <div class="nav-sidebar-divider"></div>
            </li>
            <li>
                <a href="<?php echo $base; ?>/settings.php"
                    class="nav-link<?php echo _is_active_sidebar($base . '/settings.php', $current); ?>">
                    <span class="nav-sidebar-menu-icon">
                        <iconify-icon icon="mdi:cog"></iconify-icon>
                    </span>
                    <span>Pengaturan</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $base; ?>/logout.php" class="nav-link">
                    <span class="nav-sidebar-menu-icon">
                        <iconify-icon icon="mdi:logout"></iconify-icon>
                    </span>
                    <span>Logout</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</nav>

<script>
    // Make sidebar links respond to active page
    document.querySelectorAll('.nav-link').forEach(link => {
        if (link.classList.contains('active')) {
            link.setAttribute('aria-current', 'page');
        }
    });
</script>