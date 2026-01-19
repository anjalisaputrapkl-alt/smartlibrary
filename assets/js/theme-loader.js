// Inline Theme Loader - Prevent Flash on Page Refresh
// This script runs BEFORE CSS loads to apply theme immediately
// Prevents white flash or default color flicker

(function() {
    const themes = {
        light: {
            '--bg': '#f1f4f8',
            '--surface': '#ffffff',
            '--text': '#1f2937',
            '--muted': '#6b7280',
            '--border': '#e5e7eb',
            '--accent': '#2563eb',
            '--danger': '#dc2626',
            '--success': '#16a34a'
        },
        dark: {
            '--bg': '#1f2937',
            '--surface': '#111827',
            '--text': '#f3f4f6',
            '--muted': '#9ca3af',
            '--border': '#374151',
            '--accent': '#3b82f6',
            '--danger': '#ef4444',
            '--success': '#22c55e'
        },
        blue: {
            '--bg': '#0f172a',
            '--surface': '#1e293b',
            '--text': '#e2e8f0',
            '--muted': '#94a3b8',
            '--border': '#334155',
            '--accent': '#3b82f6',
            '--danger': '#f87171',
            '--success': '#4ade80'
        },
        green: {
            '--bg': '#f0fdf4',
            '--surface': '#ffffff',
            '--text': '#166534',
            '--muted': '#6b7280',
            '--border': '#dcfce7',
            '--accent': '#10b981',
            '--danger': '#dc2626',
            '--success': '#059669'
        },
        purple: {
            '--bg': '#faf5ff',
            '--surface': '#ffffff',
            '--text': '#6b21a8',
            '--muted': '#6b7280',
            '--border': '#e9d5ff',
            '--accent': '#9333ea',
            '--danger': '#dc2626',
            '--success': '#7e22ce'
        },
        rose: {
            '--bg': '#fff7ed',
            '--surface': '#ffffff',
            '--text': '#881391',
            '--muted': '#6b7280',
            '--border': '#ffe4e6',
            '--accent': '#f43f5e',
            '--danger': '#dc2626',
            '--success': '#be185d'
        },
        indigo: {
            '--bg': '#f0f4ff',
            '--surface': '#ffffff',
            '--text': '#312e81',
            '--muted': '#6b7280',
            '--border': '#e0e7ff',
            '--accent': '#6366f1',
            '--danger': '#dc2626',
            '--success': '#4f46e5'
        },
        cyan: {
            '--bg': '#ecf9ff',
            '--surface': '#ffffff',
            '--text': '#164e63',
            '--muted': '#6b7280',
            '--border': '#cffafe',
            '--accent': '#06b6d4',
            '--danger': '#dc2626',
            '--success': '#0891b2'
        },
        pink: {
            '--bg': '#fdf2f8',
            '--surface': '#ffffff',
            '--text': '#831854',
            '--muted': '#6b7280',
            '--border': '#fbcfe8',
            '--accent': '#ec4899',
            '--danger': '#dc2626',
            '--success': '#db2777'
        },
        amber: {
            '--bg': '#fffbeb',
            '--surface': '#ffffff',
            '--text': '#78350f',
            '--muted': '#6b7280',
            '--border': '#fef3c7',
            '--accent': '#f59e0b',
            '--danger': '#dc2626',
            '--success': '#d97706'
        },
        red: {
            '--bg': '#fef2f2',
            '--surface': '#ffffff',
            '--text': '#7f1d1d',
            '--muted': '#6b7280',
            '--border': '#fee2e2',
            '--accent': '#ef4444',
            '--danger': '#dc2626',
            '--success': '#dc2626'
        },
        slate: {
            '--bg': '#f8fafc',
            '--surface': '#ffffff',
            '--text': '#1e293b',
            '--muted': '#64748b',
            '--border': '#e2e8f0',
            '--accent': '#64748b',
            '--danger': '#dc2626',
            '--success': '#475569'
        },
        teal: {
            '--bg': '#f0fdfa',
            '--surface': '#ffffff',
            '--text': '#134e4a',
            '--muted': '#6b7280',
            '--border': '#ccfbf1',
            '--accent': '#14b8a6',
            '--danger': '#dc2626',
            '--success': '#0d9488'
        },
        lime: {
            '--bg': '#f7fee7',
            '--surface': '#ffffff',
            '--text': '#365314',
            '--muted': '#6b7280',
            '--border': '#dcfce7',
            '--accent': '#84cc16',
            '--danger': '#dc2626',
            '--success': '#65a30d'
        }
    };

    // Apply theme directly from API with localStorage fallback
    function applyThemeImmediately() {
        // Try to get theme from sessionStorage first (faster)
        const cachedTheme = sessionStorage.getItem('theme_cache');
        const cachedCustom = sessionStorage.getItem('custom_colors_cache');
        
        if (cachedTheme) {
            const theme = themes[cachedTheme] || themes.light;
            applyThemeColors(theme);
            
            if (cachedCustom) {
                try {
                    const customColors = JSON.parse(cachedCustom);
                    Object.entries(customColors).forEach(([colorId, value]) => {
                        const cssVar = colorId.replace('color-', '--');
                        document.documentElement.style.setProperty(cssVar, value);
                    });
                } catch (e) {
                    console.warn('Could not parse custom colors');
                }
            }
            return;
        }

        // Fallback to default light theme
        applyThemeColors(themes.light);
    }

    function applyThemeColors(theme) {
        Object.entries(theme).forEach(([key, value]) => {
            document.documentElement.style.setProperty(key, value);
        });
    }

    // Run immediately, no waiting for DOM
    applyThemeImmediately();
})();
