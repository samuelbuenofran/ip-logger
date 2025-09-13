/**
 * Mobile Navigation Functions
 * Handles mobile sidebar navigation functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing mobile navigation...');

    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (!sidebarToggle || !sidebar || !sidebarOverlay) {
        console.error('Mobile navigation elements not found');
        return;
    }

    console.log('Mobile navigation elements found');

    // Toggle sidebar
    sidebarToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Sidebar toggle clicked');
        sidebar.classList.toggle('show');
        sidebarOverlay.classList.toggle('show');
    });

    // Close sidebar when clicking overlay
    sidebarOverlay.addEventListener('click', function() {
        console.log('Overlay clicked, closing sidebar');
        sidebar.classList.remove('show');
        sidebarOverlay.classList.remove('show');
    });

    // Close sidebar when clicking on nav links (mobile only)
    const navLinks = document.querySelectorAll('.sidebar .pearlight-nav-link');
    navLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            if (window.innerWidth < 768) {
                console.log('Nav link clicked on mobile, closing sidebar');
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            }
        });
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        }
    });
});
