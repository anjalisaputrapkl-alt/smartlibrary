// Global Theme System
// Automatically loads the saved theme on all pages from per-school database storage

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
            '--accent': '#d946ef',
            '--danger': '#dc2626',
            '--success': '#a855f7'
        },
        orange: {
            '--bg': '#fffbeb',
            '--surface': '#ffffff',
            '--text': '#92400e',
            '--muted': '#6b7280',
            '--border': '#fed7aa',
            '--accent': '#f97316',
            '--danger': '#dc2626',
            '--success': '#ea580c'
        },
        rose: {
            '--bg': '#fff7ed',
            '--surface': '#ffffff',
            '--text': '#831843',
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
        },
        monochrome: {
            '--bg': '#262626',
            '--surface': '#1f1f1f',
            '--text': '#f5f5f5',
            '--muted': '#a0a0a0',
            '--border': '#404040',
            '--accent': '#808080',
            '--danger': '#e5e5e5',
            '--success': '#b0b0b0'
        },
        sepia: {
            '--bg': '#f5ede0',
            '--surface': '#faf4ed',
            '--text': '#5d4e37',
            '--muted': '#8b7355',
            '--border': '#e8d7c3',
            '--accent': '#a0826d',
            '--danger': '#c85a54',
            '--success': '#9a7c6a'
        },
        slate: {
            '--bg': '#1c1f26',
            '--surface': '#262d3a',
            '--text': '#e8ebf0',
            '--muted': '#8b92a9',
            '--border': '#3a4251',
            '--accent': '#7c88a3',
            '--danger': '#ef5350',
            '--success': '#66bb6a'
        },
        vintage: {
            '--bg': '#f5e6d3',
            '--surface': '#faf4ed',
            '--text': '#4a3728',
            '--muted': '#8b7355',
            '--border': '#e8d4b8',
            '--accent': '#c9a961',
            '--danger': '#b85450',
            '--success': '#9a7c4a'
        },
        academia: {
            '--bg': '#f8fafb',
            '--surface': '#ffffff',
            '--text': '#1a3a52',
            '--muted': '#5a6b7f',
            '--border': '#dce3eb',
            '--accent': '#2d5a8c',
            '--danger': '#c73e3e',
            '--success': '#2d7a3d'
        },
        bookish: {
            '--bg': '#2b2520',
            '--surface': '#3d3a34',
            '--text': '#f4f1e8',
            '--muted': '#a39f97',
            '--border': '#504b43',
            '--accent': '#d4a574',
            '--danger': '#d4545f',
            '--success': '#8b9d6e'
        },
        ocean: {
            '--bg': '#0a1628',
            '--surface': '#1a2a42',
            '--text': '#c7e5f5',
            '--muted': '#7a9ac9',
            '--border': '#2d4a6f',
            '--accent': '#1fa3f4',
            '--danger': '#ff6b6b',
            '--success': '#51cf66'
        },
        sunset: {
            '--bg': '#2d1810',
            '--surface': '#3d2416',
            '--text': '#ffd4a3',
            '--muted': '#b8906f',
            '--border': '#5c4033',
            '--accent': '#ff9f4a',
            '--danger': '#ff6b5b',
            '--success': '#ffc044'
        },
        teal: {
            '--bg': '#0d4f4f',
            '--surface': '#1a6b6b',
            '--text': '#e0f2f1',
            '--muted': '#4a9b9b',
            '--border': '#2d7a7a',
            '--accent': '#26a69a',
            '--danger': '#ef5350',
            '--success': '#4db6ac'
        }
    };

    // Load theme from API (per-school from database)
    async function loadThemeFromAPI() {
        try {
            const response = await fetch('/perpustakaan-online/public/api/theme.php');
            if (!response.ok) throw new Error('Failed to load theme');
            const data = await response.json();
            
            if (data.success) {
                const theme = themes[data.theme_name] || themes.light;
                
                // Cache theme name in sessionStorage for instant loading on next page
                sessionStorage.setItem('theme_cache', data.theme_name);
                
                // Apply base theme colors
                Object.entries(theme).forEach(([key, value]) => {
                    document.documentElement.style.setProperty(key, value);
                });
                
                // Apply custom colors if any (override theme)
                if (data.custom_colors && Object.keys(data.custom_colors).length > 0) {
                    sessionStorage.setItem('custom_colors_cache', JSON.stringify(data.custom_colors));
                    Object.entries(data.custom_colors).forEach(([colorId, value]) => {
                        const cssVar = colorId.replace('color-', '--');
                        document.documentElement.style.setProperty(cssVar, value);
                    });
                }
                
                // Apply typography if any
                if (data.typography && Object.keys(data.typography).length > 0) {
                    if (data.typography['font-family']) {
                        document.documentElement.style.fontFamily = data.typography['font-family'];
                        document.body.style.fontFamily = data.typography['font-family'];
                    }
                    if (data.typography['font-weight']) {
                        document.documentElement.style.fontWeight = data.typography['font-weight'];
                        document.body.style.fontWeight = data.typography['font-weight'];
                    }
                }
                
                return;
            }
        } catch (error) {
            console.warn('Could not load theme from API:', error);
        }
        
        // Fallback: use default light theme
        applyDefaultTheme();
    }

    // Apply default light theme as fallback
    function applyDefaultTheme() {
        const defaultTheme = themes.light;
        Object.entries(defaultTheme).forEach(([key, value]) => {
            document.documentElement.style.setProperty(key, value);
        });
    }

    // Load and apply theme when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadThemeFromAPI);
    } else {
        loadThemeFromAPI();
    }
})();

