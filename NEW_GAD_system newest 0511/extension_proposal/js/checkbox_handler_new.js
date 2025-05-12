/**
 * Checkbox Handler for Extension Proposal
 * This file manages the checkbox display and interaction for the extension proposal form
 */

document.addEventListener('DOMContentLoaded', function() {
    // Function to handle request type checkbox click (client/department)
    function handleRequestTypeChange() {
        // Check if the checkbox is read-only
        if ($(this).attr('data-readonly') === 'true') {
            console.log('Checkbox is read-only, ignoring click');
            return;
        }
        
        const clickedType = $(this).data('request-type');
        const proposalId = $('#proposal_id').val();
        
        if (!proposalId) {
            console.error('No proposal ID found');
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Proposal ID not found. Please reload the page and try again.'
            });
            return;
        }
        
        // Update UI immediately for better UX
        $('[data-request-type]').each(function() {
            const $checkbox = $(this);
            
            // Ensure consistent styling
            $checkbox.css({
                'width': '12px',
                'height': '12px',
                'border': '1px solid black',
                'background-color': 'white',
                'display': 'inline-block'
            });
            
            if ($checkbox.data('request-type') === clickedType) {
                $checkbox.html('<span style="position: absolute; top: -4px; left: 1px; font-size: 16px;">×</span>');
            } else {
                $checkbox.html('□');
            }
        });
        
        // Save to database
        $.ajax({
            url: 'api/update_proposal_request_type.php',
            method: 'POST',
            data: {
                proposal_id: proposalId,
                request_type: clickedType
            },
            dataType: 'json',
            success: function(response) {
                console.log('Request type updated:', response);
            },
            error: function(xhr, status, error) {
                console.error('Error updating request type:', error);
                console.error('API Error:', xhr.responseJSON || xhr.responseText);
                
                // Display proper error message
                Swal.fire({
                    icon: 'error',
                    title: 'Update Failed',
                    text: 'Could not update request type. Please try again.'
                });
            }
        });
    }
    
    // Function to handle activity type checkbox click (program/project/activity)
    function handleActivityTypeChange() {
        // Check if the checkbox is read-only
        if ($(this).attr('data-readonly') === 'true') {
            console.log('Checkbox is read-only, ignoring click');
            return;
        }
        
        const clickedType = $(this).data('activity-type');
        const proposalId = $('#proposal_id').val();
        
        if (!proposalId) {
            console.error('No proposal ID found');
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Proposal ID not found. Please reload the page and try again.'
            });
            return;
        }
        
        // Update UI immediately for better UX
        $('[data-activity-type]').each(function() {
            const $checkbox = $(this);
            
            // Ensure consistent styling
            $checkbox.css({
                'width': '12px',
                'height': '12px',
                'border': '1px solid black',
                'background-color': 'white',
                'display': 'inline-block'
            });
            
            if ($checkbox.data('activity-type') === clickedType) {
                $checkbox.html('<span style="position: absolute; top: -4px; left: 1px; font-size: 16px;">×</span>');
            } else {
                $checkbox.html('□');
            }
        });
        
        // Save to database
        $.ajax({
            url: 'api/update_proposal_type.php',
            method: 'POST',
            data: {
                proposal_id: proposalId,
                type: clickedType
            },
            dataType: 'json',
            success: function(response) {
                console.log('Activity type updated:', response);
            },
            error: function(xhr, status, error) {
                console.error('Error updating activity type:', error);
                console.error('API Error:', xhr.responseJSON || xhr.responseText);
                
                // Display proper error message
                Swal.fire({
                    icon: 'error',
                    title: 'Update Failed',
                    text: 'Could not update activity type. Please try again.'
                });
            }
        });
    }
    
    // Add event delegation to handle dynamic checkbox elements
    $(document).on('click', '[data-request-type]', handleRequestTypeChange);
    $(document).on('click', '[data-activity-type]', handleActivityTypeChange);
    
    // Add hover effects for better UX
    $(document).on('mouseenter', '[data-request-type], [data-activity-type]', function() {
        $(this).css('background-color', 'rgba(0, 123, 255, 0.1)');
        $(this).css('cursor', 'pointer');
    });
    
    $(document).on('mouseleave', '[data-request-type], [data-activity-type]', function() {
        $(this).css('background-color', '');
    });
});

// Function to ensure checkboxes display correctly on proposal load
function fixCheckboxDisplay() {
    // No need to apply styling to checkboxes - they're now Unicode characters
    // Just ensure the correct data is shown in the form
    
    // For request type (this is now read-only in the display)
    const requestType = $('#reportPreview').data('request-type') || 'client';
    
    // For activity type (this is now read-only in the display)
    const activityType = $('#reportPreview').data('activity-type') || 'activity';
    
    // Log for debugging
    console.log('Fixed checkbox display - Request type:', requestType, 'Activity type:', activityType);
}
