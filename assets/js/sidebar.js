/**
 * Sidebar Toggle Handler
 * Handles mobile navigation sidebar toggle, close on click outside, and responsive behavior
 * 
 * Requirements:
 * - .nav-toggle element with id="navToggle"
 * - .nav-sidebar element with id="navSidebar" or querySelector('.nav-sidebar')
 */

document.addEventListener('DOMContentLoaded', function () {
    const navToggle = document.getElementById('navToggle');
    const navSidebar = document.getElementById('navSidebar') || document.querySelector('.nav-sidebar');

    if (!navToggle || !navSidebar) {
        return; // Exit if required elements don't exist
    }

    /**
     * Toggle sidebar visibility
     */
    navToggle.addEventListener('click', function (e) {
        e.stopPropagation();
        navSidebar.classList.toggle('active');
    });

    // Also add mousedown for touch devices
    navToggle.addEventListener('mousedown', function (e) {
        e.stopPropagation();
    });

    /**
     * Close sidebar when clicking on a navigation link
     */
    const navLinks = navSidebar.querySelectorAll('.nav-sidebar-menu a, .nav-sidebar-header, .nav-sidebar-divider ~ .nav-sidebar-menu a');
    navLinks.forEach(link => {
        link.addEventListener('click', function () {
            // Only close on non-dropdown links or when it's a real page change
            navSidebar.classList.remove('active');
        });
    });

    /**
     * Close sidebar when clicking outside of it
     */
    document.addEventListener('click', function (event) {
        // Check if click is outside sidebar and toggle button
        const isClickInsideSidebar = navSidebar.contains(event.target);
        const isClickOnToggle = navToggle.contains(event.target);

        if (!isClickInsideSidebar && !isClickOnToggle) {
            navSidebar.classList.remove('active');
        }
    });

    /**
     * Close sidebar on escape key press
     */
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            navSidebar.classList.remove('active');
        }
    });

    /**
     * Handle window resize: close sidebar on desktop view
     */
    window.addEventListener('resize', function () {
        if (window.innerWidth > 768) {
            navSidebar.classList.remove('active');
        }
    });

    /**
     * Handle orientation change for mobile devices
     */
    window.addEventListener('orientationchange', function () {
        navSidebar.classList.remove('active');
    });

    /**
     * Prevent body scroll when sidebar is open (mobile only)
     */
    const originalToggleClick = navToggle.click.bind(navToggle);
    navToggle.addEventListener('click', function () {
        if (window.innerWidth <= 768) {
            if (navSidebar.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }
    });

    // Ensure body scroll is enabled on sidebar close
    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.attributeName === 'class') {
                if (!navSidebar.classList.contains('active')) {
                    document.body.style.overflow = '';
                }
            }
        });
    });

    observer.observe(navSidebar, { attributes: true });
});
