document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.querySelector('.admin-sidebar');
    const mainContent = document.querySelector('.admin-container');
    const header = document.querySelector('.admin-header');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mobileToggle = document.getElementById('mobileSidebarToggle');
    const overlay = document.getElementById('sidebarOverlay');

    // Function to apply collapsed state
    function setCollapsedState(isCollapsed) {
        if (isCollapsed) {
            if (sidebar) sidebar.classList.add('collapsed');
            if (mainContent) mainContent.classList.add('collapsed');
            if (header) header.classList.add('collapsed');
        } else {
            if (sidebar) sidebar.classList.remove('collapsed');
            if (mainContent) mainContent.classList.remove('collapsed');
            if (header) header.classList.remove('collapsed');
        }
        localStorage.setItem('sidebarCollapsed', isCollapsed);
    }

    // Check localStorage for collapsed state on load
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (window.innerWidth > 768) {
        setCollapsedState(isCollapsed);
    }

    // Desktop Toggle
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function (e) {
            e.preventDefault();
            const willBeCollapsed = !sidebar.classList.contains('collapsed');
            setCollapsedState(willBeCollapsed);
        });
    }

    // Mobile Toggle
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function (e) {
            e.preventDefault();
            if (sidebar) sidebar.classList.add('mobile-open');
            if (overlay) overlay.classList.add('show');
        });
    }

    // Overlay Click (Close Mobile Sidebar)
    if (overlay) {
        overlay.addEventListener('click', function () {
            if (sidebar) sidebar.classList.remove('mobile-open');
            if (overlay) overlay.classList.remove('show');
        });
    }

    // Handle Resize
    window.addEventListener('resize', function () {
        if (window.innerWidth <= 768) {
            // Mobile: Reset collapsed state impacts (sidebar should be hidden/shown by mobile-open)
            if (sidebar) sidebar.classList.remove('collapsed');
            if (mainContent) mainContent.classList.remove('collapsed');
            if (header) header.classList.remove('collapsed');
        } else {
            // Desktop: Restore user preference
            if (sidebar) sidebar.classList.remove('mobile-open');
            if (overlay) overlay.classList.remove('show');
            setCollapsedState(localStorage.getItem('sidebarCollapsed') === 'true');
        }
    });



    // --- Theme Switcher Logic ---
    const themeButtons = document.querySelectorAll('[data-theme-value]');

    function getStoredTheme() {
        return localStorage.getItem('theme') || 'auto';
    }

    function setStoredTheme(theme) {
        localStorage.setItem('theme', theme);
    }

    function getPreferredTheme() {
        const storedTheme = getStoredTheme();
        if (storedTheme === 'auto') {
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        return storedTheme;
    }

    function setTheme(theme) {
        let effectiveTheme = theme;
        if (theme === 'auto') {
            effectiveTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }

        document.documentElement.setAttribute('data-bs-theme', effectiveTheme);
        document.documentElement.setAttribute('data-theme', effectiveTheme);

        if (effectiveTheme === 'dark') {
            document.body.classList.add('dark-mode');
        } else {
            document.body.classList.remove('dark-mode');
        }
    }

    function updateActiveThemeIcon(theme) {
        themeButtons.forEach(btn => {
            const btnTheme = btn.getAttribute('data-theme-value');
            const check = btn.querySelector('.theme-check');

            if (btnTheme === theme) {
                btn.classList.add('active');
                if (check) check.classList.remove('d-none');
            } else {
                btn.classList.remove('active');
                if (check) check.classList.add('d-none');
            }
        });
    }

    // Initialize
    const savedTheme = getStoredTheme();
    setTheme(savedTheme);
    updateActiveThemeIcon(savedTheme);

    // Event Listeners
    themeButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            // Prevent dropdown from closing immediately (optional, or let it close)
            e.stopPropagation(); // Keep menu open to see selection change? Maybe better UX to let it logic handle it.
            // Actually, bootstrap dropdowns usually close on item click. 
            // If we want it to act like a setting toggle, we might want to keep it open, but standard behavior is fine.

            const theme = btn.getAttribute('data-theme-value');
            setStoredTheme(theme);
            setTheme(theme);
            updateActiveThemeIcon(theme);
        });
    });

    // Listen for system changes if auto
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        const storedTheme = getStoredTheme();
        if (storedTheme === 'auto') {
            setTheme('auto');
        }
    });

});
