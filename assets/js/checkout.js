jQuery(document).ready(function($) {
    // Initialize the children dropdown functionality
    console.log('WC Child Subscription Manager checkout scripts loaded');
    
    // Handle dropdown change event
    $(document).on('change', '.wc-child-subscription-dropdown', function() {
        var selectedValue = $(this).val();
        console.log('Selected child ID:', selectedValue);
        
        // You can add additional logic here based on the selection
        // For example, show/hide additional fields or update pricing
    });
    
    // Ensure dropdown is properly initialized when checkout updates
    $(document).ajaxComplete(function(event, xhr, settings) {
        // Check if this is a checkout update
        if (settings.url.indexOf('update-order-review') > -1) {
            // Re-initialize any needed functionality after checkout updates
            console.log('Checkout updated, re-initializing dropdown functionality');
            
            // Ensure the dropdown maintains its value after update
            var $dropdown = $('.wc-child-subscription-dropdown');
            if ($dropdown.length > 0) {
                // Trigger a change event to ensure any dependent logic runs
                $dropdown.trigger('change');
            }
        }
    });
    
    // Also handle browser back/forward navigation
    $(window).on('pageshow', function(event) {
        var $dropdown = $('.wc-child-subscription-dropdown');
        if ($dropdown.length > 0 && event.originalEvent.persisted) {
            // Restore dropdown state if needed
            $dropdown.trigger('change');
        }
    });
    
    // Additional handling for WooCommerce checkout updates
    $(document).on('updated_checkout', function() {
        console.log('Checkout updated via WooCommerce event');
        var $dropdown = $('.wc-child-subscription-dropdown');
        if ($dropdown.length > 0) {
            $dropdown.trigger('change');
        }
    });
    
    // Add debug logging for dropdown visibility
    console.log('Initial dropdown elements found:', $('.wc-child-subscription-dropdown').length);
});
