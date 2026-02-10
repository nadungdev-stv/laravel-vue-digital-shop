/**
 * Admin Mobile Responsive JavaScript
 * Simple approach using CSS classes
 */

// Toggle mobile sidebar
window.toggleMobileSidebar = function() {
    console.log('🔵 toggleMobileSidebar called!');
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');

    console.log('Sidebar element:', sidebar);
    console.log('Overlay element:', overlay);

    if (sidebar && overlay) {
        const isOpen = sidebar.classList.contains('show');

        if (isOpen) {
            // Close sidebar
            sidebar.classList.remove('show');
            overlay.classList.remove('active');
            sidebar.setAttribute('style', 'left: -280px !important;');
            document.body.style.overflow = '';
            console.log('❌ Sidebar closed');
        } else {
            // Open sidebar
            sidebar.classList.add('show');
            overlay.classList.add('active');
            sidebar.setAttribute('style', 'left: 0px !important;');
            document.body.style.overflow = 'hidden';
            console.log('✅ Sidebar opened');
        }

        console.log('Has show class:', sidebar.classList.contains('show'));
        console.log('Sidebar left:', window.getComputedStyle(sidebar).left);
    } else {
        console.error('❌ Sidebar or overlay not found!');
    }
};

// Close mobile sidebar
window.closeMobileSidebar = function() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (sidebar && overlay) {
        sidebar.classList.remove('show');
        overlay.classList.remove('active');
        sidebar.setAttribute('style', 'left: -280px !important;');
        document.body.style.overflow = '';
        console.log('❌ Sidebar closed via closeMobileSidebar');
    }
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Admin Mobile JS Loaded');

    const sidebar = document.getElementById('adminSidebar');
    const menuToggle = document.getElementById('mobileMenuToggle');
    const overlay = document.getElementById('sidebarOverlay');

    console.log('Elements found:');
    console.log('- Sidebar:', !!sidebar, sidebar);
    console.log('- Menu toggle:', !!menuToggle, menuToggle);
    console.log('- Overlay:', !!overlay, overlay);
    console.log('- Window width:', window.innerWidth);

    // Make sure sidebar starts hidden on mobile
    if (sidebar && window.innerWidth <= 768) {
        sidebar.classList.remove('show');
        sidebar.setAttribute('style', 'left: -280px !important;');
        console.log('Initial sidebar left:', window.getComputedStyle(sidebar).left);
    }

    // Add click event to menu toggle button
    if (menuToggle) {
        menuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleMobileSidebar();
        });
    }

    // Add click event to overlay
    if (overlay) {
        overlay.addEventListener('click', function() {
            closeMobileSidebar();
        });
    }

    // Close sidebar when clicking nav links (on mobile)
    if (sidebar) {
        const navLinks = sidebar.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    closeMobileSidebar();
                }
            });
        });
    }

    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            // Close sidebar if resizing to desktop
            if (window.innerWidth > 768) {
                closeMobileSidebar();
            }
        }, 250);
    });
});

// Handle escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeMobileSidebar();
    }
});
