document.addEventListener('DOMContentLoaded', function () {

    $('.date-pick').datepicker();

    // ── Mobile sidebar toggle ────────────────────────────────────────────────
    $('#mobile-sidebar-toggle').on('click', function () {
        $('body').toggleClass('sidebar-mobile-open');
    });

    // Close sidebar when clicking the overlay (the ::after pseudo-element)
    $(document).on('click', function (e) {
        if ($('body').hasClass('sidebar-mobile-open')) {
            var $sidebar = $('.sidebar.sidebar-dark');
            var $toggle  = $('#mobile-sidebar-toggle');
            if (!$sidebar.is(e.target) && $sidebar.has(e.target).length === 0
                && !$toggle.is(e.target) && $toggle.has(e.target).length === 0) {
                $('body').removeClass('sidebar-mobile-open');
            }
        }
    });

    // ── Sidebar hide/show — restore state on EVERY page load ────────────────
    restoreSidebarState();

    // Intercept the sidebar-main-toggle click BEFORE app.js handles it
    $(document).on('click', '.sidebar-main-toggle', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        var isHidden = $('body').toggleClass('sidebar-hidden').hasClass('sidebar-hidden');
        localStorage.setItem('sidebar-state', isHidden ? 'hidden' : 'visible');
        updateToggleIcon(isHidden);
    });

    function restoreSidebarState() {
        var state   = localStorage.getItem('sidebar-state');
        var isHidden = (state === 'hidden');

        $('body').toggleClass('sidebar-hidden', isHidden);
        updateToggleIcon(isHidden);
    }

    function updateToggleIcon(hidden) {
        var $icon = $('.sidebar-main-toggle i');
        if (hidden) {
            $icon.removeClass('bi-layout-sidebar').addClass('bi-layout-sidebar-inset');
        } else {
            $icon.removeClass('bi-layout-sidebar-inset').addClass('bi-layout-sidebar');
        }
    }

});
