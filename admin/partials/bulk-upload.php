<?php
/**
 * Bulk Upload Admin Page
 *
 * @package WPCS_Poll
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="wpcs-poll-admin-content">
        <div class="postbox">
            <h2 class="hndle"><span><?php _e('Bulk Upload Polls', 'wpcs-poll'); ?></span></h2>
            <div class="inside">
                <form id="wpcs-bulk-upload-form" method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('wpcs_poll_bulk_upload', 'wpcs_poll_bulk_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="upload_file"><?php _e('Upload File', 'wpcs-poll'); ?></label>
                            </th>
                            <td>
                                <input type="file" id="upload_file" name="upload_file" accept=".csv,.json" required>
                                <p class="description">
                                    <?php _e('Upload a CSV or JSON file containing poll data. Maximum file size: 2MB', 'wpcs-poll'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="file_type"><?php _e('File Type', 'wpcs-poll'); ?></label>
                            </th>
                            <td>
                                <select id="file_type" name="file_type" required>
                                    <option value=""><?php _e('Select file type', 'wpcs-poll'); ?></option>
                                    <option value="csv"><?php _e('CSV', 'wpcs-poll'); ?></option>
                                    <option value="json"><?php _e('JSON', 'wpcs-poll'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="auto_approve"><?php _e('Auto Approve', 'wpcs-poll'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="auto_approve" name="auto_approve" value="1">
                                <label for="auto_approve"><?php _e('Automatically approve uploaded polls', 'wpcs-poll'); ?></label>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Upload Polls', 'wpcs-poll'); ?>">
                    </p>
                </form>
            </div>
        </div>

        <div class="postbox">
            <h2 class="hndle"><span><?php _e('File Format Examples', 'wpcs-poll'); ?></span></h2>
            <div class="inside">
                <h3><?php _e('CSV Format', 'wpcs-poll'); ?></h3>
                <pre><code>title,description,category,option1,option2,option3,option4,tags
"What's your favorite color?","Choose your preferred color","General","Red","Blue","Green","Yellow","color,preference"
"Best programming language?","For web development","Technology","JavaScript","Python","PHP","Ruby","programming,web"</code></pre>
                
                <h3><?php _e('JSON Format', 'wpcs-poll'); ?></h3>
                <pre><code>[
  {
    "title": "What's your favorite color?",
    "description": "Choose your preferred color",
    "category": "General",
    "options": ["Red", "Blue", "Green", "Yellow"],
    "tags": ["color", "preference"]
  },
  {
    "title": "Best programming language?",
    "description": "For web development",
    "category": "Technology",
    "options": ["JavaScript", "Python", "PHP", "Ruby"],
    "tags": ["programming", "web"]
  }
]</code></pre>
            </div>
        </div>

        <div class="postbox">
            <h2 class="hndle"><span><?php _e('Upload History', 'wpcs-poll'); ?></span></h2>
            <div class="inside">
                <div id="upload-history">
                    <p><?php _e('No upload history available.', 'wpcs-poll'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#wpcs-bulk-upload-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'wpcs_poll_bulk_upload');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#submit').prop('disabled', true).val('<?php _e('Uploading...', 'wpcs-poll'); ?>');
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php _e('Upload completed successfully!', 'wpcs-poll'); ?>');
                    location.reload();
                } else {
                    alert('<?php _e('Upload failed: ', 'wpcs-poll'); ?>' + response.data.message);
                }
            },
            error: function() {
                alert('<?php _e('An error occurred during upload.', 'wpcs-poll'); ?>');
            },
            complete: function() {
                $('#submit').prop('disabled', false).val('<?php _e('Upload Polls', 'wpcs-poll'); ?>');
            }
        });
    });
});
</script>