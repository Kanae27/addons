/**
 * Checkbox Handler for Extension Proposal
 * This file manages the checkbox display and interaction for the extension proposal form
 */

document.addEventListener('DOMContentLoaded', function() {
    // Function to handle request type checkbox click (client/department)
    function handleRequestTypeChange() {
        const clickedType = $(this).data('request-type');
        const proposalId = $('#proposal_id').val();
        
        if (!proposalId) {
            console.error('No proposal ID found');
            return;
        }
        
        // Update UI immediately for better UX
        $('[data-request-type]').each(function() {
            const $checkbox = $(this);
            const $parent = $checkbox.closest('td');
            
            if ($checkbox.data('request-type') === clickedType) {
                $checkbox.html('<span style="position: absolute; top: -4px; left: 2px; font-size: 18px;">×</span>');
            } else {
                $checkbox.html('');
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
            }
        });
    }
    
    // Function to handle activity type checkbox click (program/project/activity)
    function handleActivityTypeChange() {
        const clickedType = $(this).data('activity-type');
        const proposalId = $('#proposal_id').val();
        
        if (!proposalId) {
            console.error('No proposal ID found');
            return;
        }
        
        // Update UI immediately for better UX
        $('[data-activity-type]').each(function() {
            const $checkbox = $(this);
            const $parent = $checkbox.closest('td');
            
            if ($checkbox.data('activity-type') === clickedType) {
                $checkbox.html('<span style="position: absolute; top: -4px; left: 2px; font-size: 18px;">×</span>');
            } else {
                $checkbox.html('');
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
    // For request type checkboxes
    const requestType = $('#reportPreview').data('request-type') || 'client';
    $('[data-request-type="' + requestType + '"]').html('<span style="position: absolute; top: -4px; left: 2px; font-size: 18px;">×</span>');
    
    // For activity type checkboxes
    const activityType = $('#reportPreview').data('activity-type') || 'activity';
    $('[data-activity-type="' + activityType + '"]').html('<span style="position: absolute; top: -4px; left: 2px; font-size: 18px;">×</span>');
} 