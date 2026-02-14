jQuery(document).ready(function($) {
    // Handle delete child action
    $(document).on('click', '.delete-child', function(e) {
        e.preventDefault();
        
        var $link = $(this);
        var childId = $link.data('child-id');
        
        if (!childId) {
            console.error('No child ID found');
            return;
        }
        
        // Show confirmation dialog
        if (confirm(wcChildSubscriptionManager.i18n.confirm_delete)) {
            // Perform AJAX delete
            $.ajax({
                url: wcChildSubscriptionManager.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'wc_child_subscription_manager_delete_child',
                    nonce: wcChildSubscriptionManager.nonce,
                    child_id: childId
                },
                beforeSend: function() {
                    // You could show a loading indicator here
                    $link.prop('disabled', true).text('Deleting...');
                },
                success: function(response) {
                    if (response.success) {
                        // Remove the row from the table
                        $link.closest('tr').fadeOut(300, function() {
                            $(this).remove();
                            
                            // Show success message
                            alert(wcChildSubscriptionManager.i18n.success);
                        });
                    } else {
                        // Show error message
                        alert(response.data.message || wcChildSubscriptionManager.i18n.error);
                    }
                },
                error: function() {
                    // Show generic error message
                    alert(wcChildSubscriptionManager.i18n.error);
                },
                complete: function() {
                    $link.prop('disabled', false).text('Delete');
                }
            });
        }
    });
    
    // Handle edit child action
    $(document).on('click', '.edit-child', function(e) {
        e.preventDefault();
        
        var $link = $(this);
        var childId = $link.data('child-id');
        
        if (!childId) {
            console.error('No child ID found');
            return;
        }
        
        // For edit functionality, we would typically redirect to the edit page
        // This is already handled by the direct link in the HTML, but we could add 
        // confirmation or loading indicators if needed
        console.log('Editing child ID: ' + childId);
    });
});
