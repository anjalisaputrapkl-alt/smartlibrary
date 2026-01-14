// GLOBAL THEME APPLIER
(function(){
    function loadSettings(){
        try {
            return JSON.parse(localStorage.getItem('smartlib_theme')) || {};
        } catch(e){
            return {};
        }
    }

    function applyTheme(){
        const s = loadSettings();

        // CSS VARIABLES
        if(s.primary) document.documentElement.style.setProperty('--primary', s.primary);
        if(s.secondary) document.documentElement.style.setProperty('--primary-2', s.secondary);
        if(s.bg) document.documentElement.style.setProperty('--bg', s.bg);
        if(s.accent) document.documentElement.style.setProperty('--primary-dark', s.accent);

        if(s.cornerRadius)
            document.documentElement.style.setProperty('--radius-md', s.cornerRadius+"px");

        if(s.fontFamily) document.body.style.fontFamily = s.fontFamily;
        if(s.fontSize) document.body.style.fontSize = s.fontSize+"px";

        // SHADOW
        document.body.classList.remove('shadow-none','shadow-soft','shadow-medium','shadow-deep');
        if(s.shadowStrength)
            document.body.classList.add('shadow-'+s.shadowStrength);

        // PER PAGE EXAMPLES (OPTIONAL)
        if(s.catalogMode){
            document.body.setAttribute('data-catalog-mode', s.catalogMode);
        }
    }

    document.addEventListener("DOMContentLoaded", applyTheme);
    window.addEventListener("smartlib_theme:changed", applyTheme);
})();
