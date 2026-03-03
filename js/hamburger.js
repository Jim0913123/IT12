// Toggle sidebar function
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const hamburger = document.querySelector('.hamburger-menu');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebar && hamburger && mainContent) {
        // If sidebar is visible, hide it. If hidden, show it.
        if (sidebar.classList.contains('active') || !sidebar.classList.contains('hidden')) {
            // Hide sidebar
            sidebar.classList.remove('active');
            sidebar.classList.add('hidden');
            hamburger.classList.remove('active');
            mainContent.classList.add('full-width');
        } else {
            // Show sidebar
            sidebar.classList.remove('hidden');
            sidebar.classList.add('active');
            hamburger.classList.add('active');
            mainContent.classList.remove('full-width');
        }
    }
}

// Close sidebar when clicking outside
document.addEventListener('click', function(event) {
    const sidebar = document.querySelector('.sidebar');
    const hamburger = document.querySelector('.hamburger-menu');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebar && hamburger && mainContent && 
        !sidebar.contains(event.target) && 
        !hamburger.contains(event.target) && 
        (sidebar.classList.contains('active') || !sidebar.classList.contains('hidden'))) {
        // Hide sidebar when clicking outside
        sidebar.classList.remove('active');
        sidebar.classList.add('hidden');
        hamburger.classList.remove('active');
        mainContent.classList.add('full-width');
    }
});

// Initialize sidebar state
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar');
    const hamburger = document.querySelector('.hamburger-menu');
    const mainContent = document.querySelector('.main-content');
    
    // Start with sidebar open on desktop, closed on mobile
    if (window.innerWidth <= 768) {
        sidebar.classList.add('hidden');
        mainContent.classList.add('full-width');
    } else {
        sidebar.classList.remove('hidden');
        sidebar.classList.add('active');
        hamburger.classList.add('active');
        mainContent.classList.remove('full-width');
    }
});
