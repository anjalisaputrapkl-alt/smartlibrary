// GLOBAL THEME APPLIER
(function(){
    function loadSettings(){
        try {
            const saved = localStorage.getItem('perpustakaan_theme');
            return saved ? JSON.parse(saved) : {};
        } catch(e){
            return {};
        }
    }

    function applyTheme(){
        const s = loadSettings();
        
        // Apply CSS variables to root
        if(s.primary) document.documentElement.style.setProperty('--accent', s.primary);
        if(s.secondary) document.documentElement.style.setProperty('--secondary', s.secondary);
        if(s.bg) document.documentElement.style.setProperty('--bg', s.bg);
        if(s.accent) document.documentElement.style.setProperty('--text', s.accent);
        
        // Apply surface (card background)
        if(s.dashboardCardBg) {
            document.documentElement.style.setProperty('--surface', s.dashboardCardBg);
        }

        if(s.cornerRadius) {
            document.documentElement.style.setProperty('--radius-md', s.cornerRadius + "px");
        }

        // Apply typography
        if(s.fontFamily) {
            document.body.style.fontFamily = s.fontFamily;
            document.documentElement.style.fontFamily = s.fontFamily;
        }
        if(s.fontSize) {
            document.body.style.fontSize = s.fontSize + "px";
        }
        if(s.fontWeight) {
            document.body.style.fontWeight = s.fontWeight;
        }

        // Apply card backgrounds with dynamic style
        const cardStyleId = 'perpustakaan-card-style';
        let cardStyle = document.getElementById(cardStyleId);
        if(s.dashboardCardBg) {
            if(!cardStyle) {
                cardStyle = document.createElement('style');
                cardStyle.id = cardStyleId;
                document.head.appendChild(cardStyle);
            }
            cardStyle.textContent = `
                .card, .panel, .activity-section, .chart-box, .actions, .stat {
                    background: ${s.dashboardCardBg} !important;
                }
            `;
        }

        // Apply sidebar & topbar colors with dynamic style
        const navStyleId = 'perpustakaan-nav-style';
        let navStyle = document.getElementById(navStyleId);
        const hasSidebarCustom = s.sidebarBg || s.sidebarText || s.sidebarBorder;
        const hasTopbarCustom = s.topbarBg || s.topbarText || s.topbarBorder;
        
        if(hasSidebarCustom || hasTopbarCustom) {
            if(!navStyle) {
                navStyle = document.createElement('style');
                navStyle.id = navStyleId;
                document.head.appendChild(navStyle);
            }
            
            navStyle.textContent = `
                .sidebar {
                    background: ${s.sidebarBg || '#ffffff'} !important;
                    color: ${s.sidebarText || '#1f2937'} !important;
                    border-right: 1px solid ${s.sidebarBorder || '#e5e7eb'} !important;
                }
                .sidebar-brand {
                    color: ${s.sidebarText || '#1f2937'} !important;
                }
                .sidebar-link {
                    color: ${s.sidebarText || '#1f2937'} !important;
                }
                .sidebar-logout {
                    color: ${s.sidebarText || '#1f2937'} !important;
                }
                .sidebar-link:hover {
                    background: rgba(0,0,0,0.05) !important;
                }
                .topbar {
                    background: ${s.topbarBg || '#ffffff'} !important;
                    color: ${s.topbarText || '#1f2937'} !important;
                    border-bottom: 1px solid ${s.topbarBorder || '#e5e7eb'} !important;
                }
                .topbar strong {
                    color: ${s.topbarText || '#1f2937'} !important;
                }
            `;
        }

        // Apply table styles
        if(s.reportsTableStyle === 'borderless') {
            const tableStyleId = 'perpustakaan-table-style';
            let tableStyle = document.getElementById(tableStyleId);
            if(!tableStyle) {
                tableStyle = document.createElement('style');
                tableStyle.id = tableStyleId;
                document.head.appendChild(tableStyle);
            }
            tableStyle.textContent = `
                table {
                    border: none !important;
                }
                table td, table th {
                    border: none !important;
                    border-bottom: 1px solid var(--border) !important;
                }
            `;
        }

        // Apply catalog mode
        if(s.catalogMode) {
            document.body.setAttribute('data-catalog-mode', s.catalogMode);
        }
    }

    // Apply theme immediately when script loads
    applyTheme();
    
    // Also apply on DOM ready
    if(document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyTheme);
    }
    
    // Listen for theme changes from settings page
    window.addEventListener('perpustakaan_theme:changed', applyTheme);
    
    // Re-apply theme on visibility change (when tab becomes visible)
    document.addEventListener('visibilitychange', function() {
        if(!document.hidden) {
            applyTheme();
        }
    });
})();
