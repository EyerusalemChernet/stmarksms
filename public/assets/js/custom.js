document.addEventListener('DOMContentLoaded', function() {

    $('.date-pick').datepicker();

    // ── Sidebar hide/show toggle ─────────────────────────────────────────────
    // Restore state from localStorage on every page load
    if (localStorage.getItem('sidebar-state') === 'hidden') {
        $('body').addClass('sidebar-hidden');
        updateToggleIcon(true);
    }

    // Intercept the sidebar-main-toggle click BEFORE app.js handles it
    // app.js toggles sidebar-xs (icon-only); we want full hide instead
    $(document).on('click', '.sidebar-main-toggle', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation(); // prevent app.js from also firing

        var isHidden = $('body').toggleClass('sidebar-hidden').hasClass('sidebar-hidden');
        localStorage.setItem('sidebar-state', isHidden ? 'hidden' : 'visible');
        updateToggleIcon(isHidden);
    });

    function updateToggleIcon(hidden) {
        var $icon = $('.sidebar-main-toggle i');
        if (hidden) {
            $icon.removeClass('bi-layout-sidebar').addClass('bi-layout-sidebar-inset');
        } else {
            $icon.removeClass('bi-layout-sidebar-inset').addClass('bi-layout-sidebar');
        }
    }

});
