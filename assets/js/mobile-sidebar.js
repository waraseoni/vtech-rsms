/* Mobile Sidebar Toggle JavaScript */
function toggleMobileSidebar() {
    var sidebar = document.querySelector('.main-sidebar');
    var body = document.body;
    if(!sidebar) return;
    
    if (body.classList.contains('sidebar-open')) {
        body.classList.remove('sidebar-open');
        sidebar.classList.remove('show');
        sidebar.style.transform = 'translateX(-100%)';
    } else {
        body.classList.add('sidebar-open');
        sidebar.classList.add('show');
        sidebar.style.transform = 'translateX(0)';
    }
}

function closeMobileSidebar() {
    var sidebar = document.querySelector('.main-sidebar');
    var body = document.body;
    
    body.classList.remove('sidebar-open');
    if(sidebar) {
        sidebar.classList.remove('show');
        sidebar.style.transform = 'translateX(-100%)';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Double tap detection for opening sidebar
    let lastTap = 0;
    const doubleTapDelay = 300;
    
    // Double tap on left edge area to open sidebar
    document.addEventListener('touchend', function(e) {
        var currentTime = new Date().getTime();
        var tapLength = currentTime - lastTap;
        
        var touch = e.changedTouches[0];
        var startX = touch.clientX;
        
        // Only trigger if tap is on left edge (first 40px of screen)
        if (tapLength < doubleTapDelay && tapLength > 0 && startX < 40) {
            toggleMobileSidebar();
        }
        
        lastTap = currentTime;
    });
    
    // Also support double-click with mouse for desktop testing
    document.addEventListener('dblclick', function(e) {
        var startX = e.clientX;
        
        // Left edge double click
        if (startX < 40) {
            toggleMobileSidebar();
        }
    });
    
    // Close sidebar when clicking outside
    document.addEventListener('click', function(e) {
        var body = document.body;
        var sidebar = document.querySelector('.main-sidebar');
        
        if (!body.classList.contains('sidebar-open')) return;
        if (e.target.closest('.main-sidebar')) return;
        if (e.target.closest('.fab-profile') || e.target.closest('.profile-dropdown')) return;
        
        closeMobileSidebar();
    });
    
    // Close sidebar on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeMobileSidebar();
        }
    });
});
