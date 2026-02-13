<?php
/**
 * Theme Loader Engine - V6 (Vibrant Predominance Mode)
 * Introduces layout layer separation for higher contrast and vibrancy.
 */

if (!isset($pdo)) {
    $pdo = require __DIR__ . '/src/db.php';
}

require_once __DIR__ . '/src/ThemeModel.php';
$themeModel = new ThemeModel($pdo);

// 1. Dapatkan theme_key dari database
$school_id = $_SESSION['user']['school_id'] ?? 1;
$activeKey = $themeModel->checkSpecialTheme($school_id);

if ($activeKey) {
    $configPath = __DIR__ . '/theme-config.json';
    if (file_exists($configPath)) {
        $config = json_decode(file_get_contents($configPath), true);
        
        if (isset($config[$activeKey])) {
            $theme = $config[$activeKey];
            
            // Define Fallbacks & Variables
            $primary = $theme['primary_color'];
            $bg = $theme['background_color'];
            $text = $theme['text_color'];
            $pattern = $theme['pattern'];
            $card = $theme['card_color'] ?? '#ffffff';
            $border = $theme['border_color'] ?? '#e2e8f0';
            
            // Helper for RGB conversion
            if (!function_exists('hexToRgb_local')) {
                function hexToRgb_local($hex) {
                    $hex = str_replace("#", "", $hex);
                    if(strlen($hex) == 3) {
                        $r = hexdec(substr($hex,0,1).substr($hex,0,1));
                        $g = hexdec(substr($hex,1,1).substr($hex,1,1));
                        $b = hexdec(substr($hex,2,1).substr($hex,2,1));
                    } else {
                        $r = hexdec(substr($hex,0,2));
                        $g = hexdec(substr($hex,2,2));
                        $b = hexdec(substr($hex,4,2));
                    }
                    return "$r, $g, $b";
                }
            }
            
            $rgb_primary = hexToRgb_local($primary);

            echo "<!-- National Holiday Theme Active: $activeKey (V6 - Vibrant) -->\n";
            echo "<script>window.isSpecialThemeActive = true;</script>\n";
            echo "<style>
                :root {
                    /* System Variable Overrides */
                    --primary: {$primary} !important;
                    --accent: {$primary} !important;
                    --bg: {$bg} !important;
                    --text: {$text} !important;
                    --surface: {$card} !important;
                    --card: {$card} !important;
                    --border: {$border} !important;
                    
                    /* NEW V6 Layer Variables */
                    --theme-primary: {$primary} !important;
                    --theme-bg: {$bg} !important;
                    --theme-text: {$text} !important;
                    --theme-pattern: url('{$pattern}') !important;
                    
                    --sidebar-bg: {$primary} !important;
                    --header-bg: {$primary} !important;
                    --body-bg: {$bg} !important;
                }
                
                /* Body & Global Background (Patterned) */
                html body, 
                html body .app, 
                html body .content {
                    background-image: var(--theme-pattern) !important;
                    background-color: var(--body-bg) !important;
                    background-repeat: repeat !important;
                    background-attachment: fixed !important;
                    background-size: 150px !important;
                }

                /* V6 Vibrant Sidebar (Dark/Primary color) */
                html body .nav-sidebar, 
                html body .sidebar,
                html body #navSidebar {
                    background-color: var(--sidebar-bg) !important;
                    background-image: linear-gradient(rgba(0,0,0,0.2), rgba(0,0,0,0.2)), var(--theme-pattern) !important;
                    background-blend-mode: overlay;
                    box-shadow: 4px 0 15px rgba(0,0,0,0.1) !important;
                }

                /* V6 Vibrant Header/Topbar */
                html body .topbar,
                html body .header {
                    background-color: var(--header-bg) !important;
                    background-image: linear-gradient(90deg, rgba(0,0,0,0.1), transparent) !important;
                    color: #ffffff !important;
                    border-bottom: 2px solid rgba(255,255,255,0.2) !important;
                }

                html body .topbar strong,
                html body .topbar span,
                html body .topbar iconify-icon,
                html body .header h1,
                html body .header p,
                html body .header .subtitle {
                    color: #ffffff !important;
                    background: none !important;
                    border: none !important;
                    box-shadow: none !important;
                }

                /* Text Visibility on Dark Sidebar */
                html body .nav-sidebar-menu a,
                html body .nav-sidebar-header h2,
                html body .school-name {
                    color: #ffffff !important;
                    text-shadow: 0 1px 2px rgba(0,0,0,0.2) !important;
                }

                html body .nav-sidebar-menu a:hover,
                html body .nav-sidebar-menu a.active {
                    background: rgba(255, 255, 255, 0.15) !important;
                    color: #ffffff !important;
                }

                /* Card Translucency (Refined) */
                html body .card, 
                html body .stat, 
                html body .settings-tabs {
                    background-color: rgba(255, 255, 255, 0.95) !important;
                    backdrop-filter: blur(4px);
                    border: 1px solid rgba({$rgb_primary}, 0.2) !important;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.05) !important;
                }

                /* Remove distracting ornaments */
                body::before, body::after, .app::before, .app::after {
                    display: none !important;
                    content: none !important;
                }
            </style>\n";
            
            // Load specific theme CSS file if it exists
            $paths = ["/perpustakaan-online/public/themes/special/{$activeKey}.css"];
            foreach($paths as $p) {
                $fsPath = __DIR__ . str_replace('/perpustakaan-online', '', $p);
                if (file_exists($fsPath)) {
                    echo "<link rel='stylesheet' href='$p'>\n";
                }
            }
        }
    }
}
?>
