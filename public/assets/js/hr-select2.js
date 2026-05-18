/**
 * HR Module Select2 Initialization
 * Adds search/filter functionality to all dropdowns in HR module
 */

document.addEventListener('DOMContentLoaded', function () {
    initializeHRSelects();
});

function initializeHRSelects() {
    // Initialize all select elements with Select2 for search functionality
    // Exclude specific selects that should remain simple
    
    const excludeSelects = [
        'input[type="hidden"]', // Skip hidden inputs
    ];

    // Initialize all form-control selects with Select2
    $('select.form-control').each(function() {
        const $select = $(this);
        
        // Skip if already initialized
        if ($select.data('select2')) {
            return;
        }

        // Get the number of options to determine if search is needed
        const optionCount = $select.find('option').length;
        
        // Initialize Select2 with search for dropdowns with more than 3 options
        if (optionCount > 3) {
            $select.select2({
                allowClear: true,
                placeholder: $select.attr('data-placeholder') || 'Search and select...',
                width: '100%',
                language: {
                    noResults: function() {
                        return 'No results found';
                    },
                    searching: function() {
                        return 'Searching...';
                    }
                }
            });
        }
    });

    // Also initialize selects with class 'select' (legacy)
    $('select.select').each(function() {
        const $select = $(this);
        
        if ($select.data('select2')) {
            return;
        }

        const optionCount = $select.find('option').length;
        
        if (optionCount > 3) {
            $select.select2({
                allowClear: true,
                placeholder: $select.attr('data-placeholder') || 'Search and select...',
                width: '100%',
                language: {
                    noResults: function() {
                        return 'No results found';
                    },
                    searching: function() {
                        return 'Searching...';
                    }
                }
            });
        }
    });

    // Re-initialize Select2 on AJAX form submissions (for dynamic forms)
    $(document).on('ajaxComplete', function() {
        $('select.form-control, select.select').each(function() {
            if (!$(this).data('select2')) {
                const optionCount = $(this).find('option').length;
                if (optionCount > 3) {
                    $(this).select2({
                        allowClear: true,
                        placeholder: $(this).attr('data-placeholder') || 'Search and select...',
                        width: '100%'
                    });
                }
            }
        });
    });
}

// Re-initialize when new forms are added dynamically
function reinitializeSelect2() {
    $('select.form-control, select.select').each(function() {
        if ($(this).data('select2')) {
            $(this).select2('destroy');
        }
    });
    initializeHRSelects();
}
