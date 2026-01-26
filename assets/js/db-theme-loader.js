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
            '--primary': '#808080',
            '--primary-2': '#a0a0a0',
            '--primary-dark': '#1a1a1a',
            '--bg': '#262626',
            '--muted': '#3a3a3a',
            '--card': '#1f1f1f',
            '--surface': '#1f1f1f',
            '--muted-surface': '#2a2a2a',
            '--border': '#404040',
            '--text': '#f5f5f5',
            '--text-muted': '#a0a0a0',
            '--accent': '#808080',
            '--danger': '#e5e5e5',
            '--success': '#b0b0b0',
            '--section-header': '#2a2a2a',
            '--section-header-text': '#f5f5f5'
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
            '--primary': '#ff9f4a',
            '--primary-2': '#ffb366',
            '--primary-dark': '#2d1810',
            '--bg': '#2d1810',
            '--muted': '#4a3728',
            '--card': '#3d2416',
            '--surface': '#3d2416',
            '--muted-surface': '#4a3728',
            '--border': '#5c4033',
            '--text': '#ffd4a3',
            '--text-muted': '#b8906f',
            '--accent': '#ff9f4a',
            '--danger': '#ff6b5b',
            '--success': '#ffc044',
            '--section-header': '#4a3728',
            '--section-header-text': '#ffd4a3'
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
