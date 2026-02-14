jQuery(document).ready(function($) {
    // Handle delete child action
    $(document).on('click', '.delete-child', function(e) {
        e.preventDefault();
        
        var $link = $(this);
        var childId = $link.data('child-id');
        
        if (confirm(wcChildSubscriptionManager.i18n.confirm_delete)) {
            $.ajax({
                url: wcChildSubscriptionManager.ajaxurl,
                type: 'POST',
                data: {
                    action: 'wc_child_subscription_manager_delete_child',
                    child_id: childId,
                    nonce: wcChildSubscriptionManager.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Remove the row from the table
                        $link.closest('tr').remove();
                        alert(wcChildSubscriptionManager.i18n.success);
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert(wcChildSubscriptionManager.i18n.error);
                }
            });
        }
    });
});
