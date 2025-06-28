/**
 * WPCS Poll Admin JavaScript
 */

jQuery(document).ready(function($) {
    
    // File upload handling
    $('#wpcs-bulk-upload-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'wpcs_poll_bulk_upload');
        
        // Show progress
        $('.upload-progress').show();
        $('#submit').prop('disabled', true).val('Uploading...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = evt.loaded / evt.total * 100;
                        $('.progress-fill').css('width', percentComplete + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                if (response.success) {
                    alert('Upload completed successfully!');
                    location.reload();
                } else {
                    alert('Upload failed: ' + (response.data ? response.data.message : 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred during upload: ' + error);
            },
            complete: function() {
                $('.upload-progress').hide();
                $('#submit').prop('disabled', false).val('Upload Polls');
                $('.progress-fill').css('width', '0%');
            }
        });
    });
    
    // Auto-detect file type
    $('#upload_file').on('change', function() {
        var fileName = $(this).val();
        var fileExtension = fileName.split('.').pop().toLowerCase();
        
        if (fileExtension === 'csv' || fileExtension === 'json') {
            $('#file_type').val(fileExtension);
        }
    });
    
    // Confirm delete actions
    $('.button-link-delete').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
    
    // Select all functionality
    $('#cb-select-all-1, #cb-select-all-2').on('change', function() {
        var isChecked = $(this).prop('checked');
        $('input[name="poll_ids[]"]').prop('checked', isChecked);
    });
    
    // Individual checkbox handling
    $('input[name="poll_ids[]"]').on('change', function() {
        var totalCheckboxes = $('input[name="poll_ids[]"]').length;
        var checkedCheckboxes = $('input[name="poll_ids[]"]:checked').length;
        
        $('#cb-select-all-1, #cb-select-all-2').prop('checked', totalCheckboxes === checkedCheckboxes);
    });
    
    // Bulk action confirmation
    $('#doaction, #doaction2').on('click', function(e) {
        var action = $(this).siblings('select').val();
        var selectedItems = $('input[name="poll_ids[]"]:checked').length;
        
        if (action === '-1') {
            alert('Please select an action.');
            e.preventDefault();
            return;
        }
        
        if (selectedItems === 0) {
            alert('Please select at least one item.');
            e.preventDefault();
            return;
        }
        
        var actionText = action.charAt(0).toUpperCase() + action.slice(1);
        if (!confirm('Are you sure you want to ' + actionText.toLowerCase() + ' ' + selectedItems + ' item(s)?')) {
            e.preventDefault();
        }
    });
    
    // AJAX for quick actions
    $('.quick-action').on('click', function(e) {
        e.preventDefault();
        
        var $this = $(this);
        var action = $this.data('action');
        var pollId = $this.data('poll-id');
        var originalText = $this.text();
        
        $this.text('Processing...').prop('disabled', true);
        
        $.post(ajaxurl, {
            action: 'wpcs_poll_quick_action',
            poll_action: action,
            poll_id: pollId,
            nonce: wpcs_poll_admin.nonce
        }, function(response) {
            if (response.success) {
                // Update UI based on action
                if (action === 'toggle_active') {
                    var newStatus = response.data.is_active ? 'Active' : 'Inactive';
                    $this.closest('tr').find('.status-badge')
                        .removeClass('active inactive')
                        .addClass(response.data.is_active ? 'active' : 'inactive')
                        .text(newStatus);
                }
                
                // Show success message
                showNotice('success', response.data.message);
            } else {
                showNotice('error', response.data ? response.data.message : 'Action failed');
            }
        }).fail(function() {
            showNotice('error', 'Network error occurred');
        }).always(function() {
            $this.text(originalText).prop('disabled', false);
        });
    });
    
    // Show admin notices
    function showNotice(type, message) {
        var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        var notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap h1').after(notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            notice.fadeOut();
        }, 5000);
    }
    
    // Enhanced search functionality
    var searchTimeout;
    $('#poll-search-input').on('input', function() {
        clearTimeout(searchTimeout);
        var searchTerm = $(this).val();
        
        searchTimeout = setTimeout(function() {
            if (searchTerm.length >= 3 || searchTerm.length === 0) {
                // Trigger search
                $('#search-submit').click();
            }
        }, 500);
    });
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + S to save
        if ((e.ctrlKey || e.metaKey) && e.which === 83) {
            e.preventDefault();
            $('input[type="submit"][name="submit"]').click();
        }
        
        // Escape to close modals
        if (e.which === 27) {
            $('.wpcs-modal').hide();
        }
    });
    
    // Initialize tooltips if available
    if ($.fn.tooltip) {
        $('[data-tooltip]').tooltip();
    }
    
    // Auto-save draft functionality for forms
    var autoSaveTimeout;
    $('form input, form textarea, form select').on('change input', function() {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(function() {
            // Auto-save logic here if needed
        }, 2000);
    });
});

// Global functions for inline actions
function editPoll(pollId) {
    // Redirect to edit page or open modal
    window.location.href = 'admin.php?page=wpcs-poll-edit&poll_id=' + pollId;
}

function deletePoll(pollId) {
    if (confirm('Are you sure you want to delete this poll? This action cannot be undone.')) {
        jQuery.post(ajaxurl, {
            action: 'wpcs_poll_delete',
            poll_id: pollId,
            nonce: wpcs_poll_admin.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Failed to delete poll: ' + (response.data ? response.data.message : 'Unknown error'));
            }
        });
    }
}

function approvePoll(pollId) {
    jQuery.post(ajaxurl, {
        action: 'wpcs_poll_approve',
        poll_id: pollId,
        nonce: wpcs_poll_admin.nonce
    }, function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert('Failed to approve poll: ' + (response.data ? response.data.message : 'Unknown error'));
        }
    });
}

function rejectPoll(pollId) {
    if (confirm('Are you sure you want to reject and delete this poll?')) {
        jQuery.post(ajaxurl, {
            action: 'wpcs_poll_reject',
            poll_id: pollId,
            nonce: wpcs_poll_admin.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Failed to reject poll: ' + (response.data ? response.data.message : 'Unknown error'));
            }
        });
    }
}