jQuery(document).ready(function($) {
    'use strict';

    // Function to get the next available index for poll options
    function getNextPollOptionIndex() {
        let maxIndex = -1;
        $('#poll-options-container .poll-option-item').each(function() {
            const nameAttr = $(this).find('input[type="text"]').attr('name');
            if (nameAttr) {
                const match = nameAttr.match(/poll_options\[(\d+)\]/);
                if (match && match[1]) {
                    const currentIndex = parseInt(match[1], 10);
                    if (currentIndex > maxIndex) {
                        maxIndex = currentIndex;
                    }
                }
            }
        });
        return maxIndex + 1;
    }

    // Handle adding a new poll option
    $('#wpcs-add-option').on('click', function() {
        const nextIndex = getNextPollOptionIndex();
        const newOptionId = 'opt_new_' + (Date.now() + nextIndex); // Unique ID for new option

        const newOptionHTML = `
            <div class="poll-option-item" style="margin-bottom: 10px;">
                <input type="text" name="poll_options[${nextIndex}][text]" value="" placeholder="Option Text" required>
                <input type="hidden" name="poll_options[${nextIndex}][id]" value="${newOptionId}">
                <button type="button" class="button button-small wpcs-remove-option">${wpcs_poll_admin_i18n.removeOptionText || 'Remove'}</button>
            </div>
        `;
        // Insert before the "Add Option" button
        $(newOptionHTML).insertBefore($(this));
    });

    // Handle removing a poll option (delegated event)
    $('#poll-options-container').on('click', '.wpcs-remove-option', function() {
        // Ensure at least two options remain (optional, backend handles final validation)
        // if ($('#poll-options-container .poll-option-item').length > 2) {
        $(this).closest('.poll-option-item').remove();
        // } else {
        // alert('At least two options are required.');
        // }
    });

    // For internationalization of "Remove" text, if needed later.
    // Ensure wpcs_poll_admin_i18n.removeOptionText is localized via wp_localize_script if you want to use it.
    // For now, it defaults to 'Remove'.
    // Example of how you might add it to wp_localize_script in PHP:
    // 'i18n' => array('removeOptionText' => __('Remove', 'wpcs-poll'))
    // Then access via wpcs_poll_admin.i18n.removeOptionText
    // We'll make a simplified version for now.
    if (typeof wpcs_poll_admin_i18n === 'undefined') {
        window.wpcs_poll_admin_i18n = { removeOptionText: 'Remove' };
    }

    // Handle WPCS Poll User Role change
    // Use event delegation in case the table is reloaded via AJAX in the future
    $('#wpcs-user-management-form').on('change', '.wpcs-poll-user-role-select', function() {
        const $select = $(this);
        const userId = $select.data('user-id');
        const newRole = $select.val();
        const nonce = $select.data('nonce');
        const $spinner = $select.siblings('.spinner');

        // Show spinner
        $spinner.addClass('is-active');
        $select.prop('disabled', true); // Disable select during processing

        $.ajax({
            url: ajaxurl, // WordPress AJAX URL
            type: 'POST',
            data: {
                action: 'wpcs_update_user_poll_role', // Our AJAX action hook
                user_id: userId,
                new_role: newRole,
                _ajax_nonce: nonce // Nonce for verification
            },
            success: function(response) {
                // Assuming response is JSON: { success: true/false, message: '...' }
                if (response.success) {
                    // Optionally, provide visual feedback like a temporary success message
                    // For now, just re-enable the select
                } else {
                    // Alert the error message and revert the select to its previous value (if possible)
                    // This part might be tricky if the previous value isn't stored.
                    // For simplicity, we'll just alert and re-enable.
                    alert('Error: ' + (response.data && response.data.message ? response.data.message : 'Could not update role.'));
                    // Revert logic would go here if needed.
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                alert('AJAX Error: ' + textStatus + ' - ' + errorThrown);
                // Revert logic might also be needed here.
            },
            complete: function() {
                // Hide spinner and re-enable select
                $spinner.removeClass('is-active');
                $select.prop('disabled', false);
            }
        });
    });
});
