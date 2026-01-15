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
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="<?php echo $base; ?>/" class="sidebar-brand">
            <span>Perpustakaan Online</span>
        </a>
    </div>

    <nav class="sidebar-nav">
        <a href="<?php echo $base; ?>/" class="sidebar-link<?php echo _is_active_sidebar($base . '/', $current); ?>">
            <span class="sidebar-icon">📊</span>
            <span class="sidebar-label">Dashboard</span>
        </a>
        <a href="<?php echo $base; ?>/books.php"
            class="sidebar-link<?php echo _is_active_sidebar($base . '/books.php', $current); ?>">
            <span class="sidebar-icon">📚</span>
            <span class="sidebar-label">Buku</span>
        </a>
        <a href="<?php echo $base; ?>/book-maintenance.php"
            class="sidebar-link<?php echo _is_active_sidebar($base . '/book-maintenance.php', $current); ?>">
            <span class="sidebar-icon">🔧</span>
            <span class="sidebar-label">Pemeliharaan Buku</span>
        </a>
        <a href="<?php echo $base; ?>/members.php"
            class="sidebar-link<?php echo _is_active_sidebar($base . '/members.php', $current); ?>">
            <span class="sidebar-icon">👥</span>
            <span class="sidebar-label">Anggota</span>
        </a>
        <a href="<?php echo $base; ?>/borrows.php"
            class="sidebar-link<?php echo _is_active_sidebar($base . '/borrows.php', $current); ?>">
            <span class="sidebar-icon">📖</span>
            <span class="sidebar-label">Peminjaman</span>
        </a>
        <a href="<?php echo $base; ?>/reports.php"
            class="sidebar-link<?php echo _is_active_sidebar($base . '/reports.php', $current); ?>">
            <span class="sidebar-icon">📈</span>
            <span class="sidebar-label">Laporan</span>
        </a>
        <?php if ($user): ?>
            <a href="<?php echo $base; ?>/settings.php"
                class="sidebar-link<?php echo _is_active_sidebar($base . '/settings.php', $current); ?>">
                <span class="sidebar-icon">⚙️</span>
                <span class="sidebar-label">Pengaturan</span>
            </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="<?php echo $base; ?>/logout.php" class="sidebar-logout">
            <span class="sidebar-icon">🚪</span>
            <span class="sidebar-label">Logout</span>
        </a>
    </div>
</aside>

<style>
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        height: 100vh;
        width: 260px;
        background: var(--surface);
        border-right: 1px solid var(--border);
        display: flex;
        flex-direction: column;
        padding: 20px;
        overflow-y: auto;
        z-index: 1000;
    }

    .sidebar-header {
        margin-bottom: 32px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--border);
    }

    .sidebar-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 600;
        font-size: 16px;
        color: var(--accent);
        transition: opacity 0.2s;
        text-decoration: none;
    }

    .sidebar-brand:hover {
        opacity: 0.8;
    }

    .sidebar-nav {
        display: flex;
        flex-direction: column;
        gap: 8px;
        flex: 1;
    }

    .sidebar-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        border-radius: 8px;
        color: var(--text);
        transition: all 0.2s;
        font-size: 14px;
        text-decoration: none;
    }

    .sidebar-link:hover {
        background: #f3f4f6;
        color: var(--accent);
    }

    .sidebar-link.active {
        background: rgba(37, 99, 235, 0.1);
        color: var(--accent);
        font-weight: 600;
    }

    .sidebar-icon {
        font-size: 18px;
        width: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .sidebar-label {
        flex: 1;
    }

    .sidebar-footer {
        padding-top: 20px;
        border-top: 1px solid var(--border);
    }

    .sidebar-logout {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        border-radius: 8px;
        color: var(--danger);
        font-size: 14px;
        transition: all 0.2s;
        text-decoration: none;
    }

    .sidebar-logout:hover {
        background: rgba(220, 38, 38, 0.1);
    }

    /* Adjust main content to account for sidebar */
    .app {
        margin-left: 260px;
    }

    @media (max-width: 768px) {
        .sidebar {
            width: 100%;
            height: auto;
            position: relative;
            border-right: none;
            border-bottom: 1px solid var(--border);
            flex-direction: row;
            padding: 12px 16px;
        }

        .sidebar-header {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .sidebar-nav {
            flex-direction: row;
            gap: 4px;
            flex: 1;
        }

        .sidebar-label {
            display: none;
        }

        .app {
            margin-left: 0;
        }
    }
</style>