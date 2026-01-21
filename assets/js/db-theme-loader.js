/**
 * Database Theme Loader (Student Version)
 * Fetch tema dari database, simpan localStorage, dan terapkan CSS variables
 * Jalankan PALING PERTAMA di halaman
 */

(function() {
    'use strict';

    // Tema color definitions (sama seperti theme.js)
    const themes = {
        light: {
            '--primary': '#3A7FF2',
            '--primary-2': '#7AB8F5',
            '--primary-dark': '#0A1A4F',
            '--bg': '#F6F9FF',
            '--muted': '#F3F7FB',
            '--card': '#FFFFFF',
            '--surface': '#FFFFFF',
            '--muted-surface': '#F7FAFF',
            '--border': '#E6EEF8',
            '--text': '#0F172A',
            '--text-muted': '#475569',
            '--accent': '#3A7FF2',
            '--danger': '#EF4444',
            '--success': '#10B981',
            '--section-header': '#f0f9ff',
            '--section-header-text': '#0A1A4F'
        },
        dark: {
            '--primary': '#60A5FA',
            '--primary-2': '#93C5FD',
            '--primary-dark': '#1E40AF',
            '--bg': '#0F172A',
            '--muted': '#1F2937',
            '--card': '#111827',
            '--surface': '#1F2937',
            '--muted-surface': '#111827',
            '--border': '#374151',
            '--text': '#F0F4F8',
            '--text-muted': '#B0BAC8',
            '--accent': '#60A5FA',
            '--danger': '#EF4444',
            '--success': '#22C55E',
            '--section-header': '#1e3a5f',
            '--section-header-text': '#93c5fd'
        },
        blue: {
            '--primary': '#0EA5E9',
            '--primary-2': '#38BDF8',
            '--primary-dark': '#0C4A6E',
            '--bg': '#0F172A',
            '--muted': '#0C2540',
            '--card': '#1E293B',
            '--surface': '#1E293B',
            '--muted-surface': '#0C2540',
            '--border': '#334155',
            '--text': '#E0F2FE',
            '--text-muted': '#A5D6F0',
            '--accent': '#0EA5E9',
            '--danger': '#F87171',
            '--success': '#4ADE80',
            '--section-header': '#1e3a8a',
            '--section-header-text': '#dbeafe'
        },
        monochrome: {
            '--primary': '#404040',
            '--primary-2': '#737373',
            '--primary-dark': '#000000',
            '--bg': '#F5F5F5',
            '--muted': '#EFEFEF',
            '--card': '#FFFFFF',
            '--surface': '#FFFFFF',
            '--muted-surface': '#F9F9F9',
            '--border': '#D3D3D3',
            '--text': '#1A1A1A',
            '--text-muted': '#4D4D4D',
            '--accent': '#404040',
            '--danger': '#808080',
            '--success': '#595959',
            '--section-header': '#f0f0f0',
            '--section-header-text': '#000000'
        },
        sepia: {
            '--primary': '#A1887F',
            '--primary-2': '#D7CCC8',
            '--primary-dark': '#3E2723',
            '--bg': '#F4EEE4',
            '--muted': '#EFEBE9',
            '--card': '#FEF5E7',
            '--surface': '#FEFBF7',
            '--muted-surface': '#F1EBE3',
            '--border': '#D7CCC8',
            '--text': '#2C1810',
            '--text-muted': '#6D4C41',
            '--accent': '#A1887F',
            '--danger': '#BF360C',
            '--success': '#558B2F',
            '--section-header': '#efebe9',
            '--section-header-text': '#3e2723'
        },
        slate: {
            '--primary': '#475569',
            '--primary-2': '#64748B',
            '--primary-dark': '#1E293B',
            '--bg': '#F8FAFC',
            '--muted': '#F1F5F9',
            '--card': '#FFFFFF',
            '--surface': '#F8FAFC',
            '--muted-surface': '#F1F5F9',
            '--border': '#CBD5E1',
            '--text': '#0F172A',
            '--text-muted': '#475569',
            '--accent': '#475569',
            '--danger': '#DC2626',
            '--success': '#059669',
            '--section-header': '#e2e8f0',
            '--section-header-text': '#1e293b'
        },
        ocean: {
            '--primary': '#0EA5E9',
            '--primary-2': '#06B6D4',
            '--primary-dark': '#0C4A6E',
            '--bg': '#0C4A6E',
            '--muted': '#0B5563',
            '--card': '#164E63',
            '--surface': '#164E63',
            '--muted-surface': '#0D7C8F',
            '--border': '#06B6D4',
            '--text': '#E0F2FE',
            '--text-muted': '#7EE8F0',
            '--accent': '#0EA5E9',
            '--danger': '#F87171',
            '--success': '#6EE7B7',
            '--section-header': '#0e7490',
            '--section-header-text': '#a5f3fc'
        },
        sunset: {
            '--primary': '#F97316',
            '--primary-2': '#FB923C',
            '--primary-dark': '#7C2D12',
            '--bg': '#7C2D12',
            '--muted': '#92400E',
            '--card': '#92400E',
            '--surface': '#92400E',
            '--muted-surface': '#B45309',
            '--border': '#FB923C',
            '--text': '#FEF3C7',
            '--text-muted': '#FED7AA',
            '--accent': '#F97316',
            '--danger': '#DC2626',
            '--success': '#84CC16',
            '--section-header': '#c2410c',
            '--section-header-text': '#fed7aa'
        },
        teal: {
            '--primary': '#14B8A6',
            '--primary-2': '#2DD4BF',
            '--primary-dark': '#0D5C54',
            '--bg': '#134E4A',
            '--muted': '#0F766E',
            '--card': '#0D9488',
            '--surface': '#0D9488',
            '--muted-surface': '#115E59',
            '--border': '#14B8A6',
            '--text': '#D1FAE5',
            '--text-muted': '#6EE7D8',
            '--accent': '#06B6D4',
            '--danger': '#EF4444',
            '--success': '#10B981',
            '--section-header': '#115e59',
            '--section-header-text': '#a7f3d0'
        }
    };

    /**
     * Terapkan tema ke CSS variables
     */
    function applyTheme(themeName) {
        if (!themeName || !themes[themeName]) {
            themeName = 'light';
        }

        const colors = themes[themeName];
        const root = document.documentElement;

        Object.keys(colors).forEach(key => {
            root.style.setProperty(key, colors[key]);
        });

        console.log('✓ Theme applied:', themeName);
    }

    /**
     * Load tema dari API dan terapkan
     */
    async function loadSchoolTheme() {
        try {
            // Cek localStorage dulu (fallback cepat)
            const cachedTheme = localStorage.getItem('theme');
            if (cachedTheme && themes[cachedTheme]) {
                applyTheme(cachedTheme);
            } else {
                applyTheme('light');
            }

            // Fetch tema terbaru dari API
            const response = await fetch('./api/student-theme.php');
            
            if (!response.ok) {
                console.warn('⚠️ Failed to fetch school theme from API');
                return;
            }

            const data = await response.json();

            if (data.success && data.theme_name) {
                // Simpan tema baru
                localStorage.setItem('theme', data.theme_name);
                
                // Terapkan ke halaman
                applyTheme(data.theme_name);
                
                console.log('✓ School theme loaded & applied:', data.theme_name);
            }
        } catch (error) {
            console.warn('⚠️ Error loading school theme:', error);
            applyTheme('light');
        }
    }

    // Load tema SEGERA, jangan tunggu DOMContentLoaded
    // agar tema sudah teraupdate sebelum halaman render
    loadSchoolTheme();

    // Juga load ulang saat DOM ready (backup)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(loadSchoolTheme, 100);
        });
    }
})();
