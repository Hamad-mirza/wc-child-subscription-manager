jQuery(document).ready(function($) {
    // Initialize the children dropdown functionality
    console.log('WC Child Subscription Manager checkout scripts loaded');
    
    // Function to check if subscription products are in cart
    function hasSubscriptionProducts() {
        // This is a simplified check - in a real implementation, 
        // you might want to use AJAX to check the cart contents
        return true; // For now, we'll assume subscription products are present
    }
    
    // Handle dropdown change event
    $(document).on('change', 'select[name="billing_child_id"]', function() {
        var selectedValue = $(this).val();
        console.log('Selected child ID:', selectedValue);
        
        // You can add additional logic here based on the selection
        // For example, show/hide additional fields or update pricing
    });
    
    // Ensure dropdown is properly initialized when checkout updates
    $(document).ajaxComplete(function(event, xhr, settings) {
        // Check if this is a checkout update
        if (settings.url.indexOf('update-order-review') > -1) {
            // Only re-initialize if subscription products are present
            if (hasSubscriptionProducts()) {
                // Re-initialize any needed functionality after checkout updates
                console.log('Checkout updated, re-initializing dropdown functionality');
                
                // Ensure the dropdown maintains its value after update
                var $dropdown = $('select[name="billing_child_id"]');
                if ($dropdown.length > 0) {
                    // Trigger a change event to ensure any dependent logic runs
                    $dropdown.trigger('change');
                }
            }
        }
    });
    
    // Also handle browser back/forward navigation
    $(window).on('pageshow', function(event) {
        if (hasSubscriptionProducts()) {
            var $dropdown = $('select[name="billing_child_id"]');
            if ($dropdown.length > 0 && event.originalEvent.persisted) {
                // Restore dropdown state if needed
                $dropdown.trigger('change');
            }
        }
    });
    
    // Additional handling for WooCommerce checkout updates
    $(document).on('updated_checkout', function() {
        if (hasSubscriptionProducts()) {
            console.log('Checkout updated via WooCommerce event');
            var $dropdown = $('select[name="billing_child_id"]');
            if ($dropdown.length > 0) {
                $dropdown.trigger('change');
            }
        }
    });
    
    // Add debug logging for dropdown visibility
    if (hasSubscriptionProducts()) {
        console.log('Initial dropdown elements found:', $('select[name="billing_child_id"]').length);
    } else {
        console.log('No subscription products in cart - child dropdown will not be shown');
    }
});
