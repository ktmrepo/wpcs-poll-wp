<?php
/**
 * Admin Bulk Upload Page
 *
 * @package WPCS_Poll
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission for bulk upload (basic outline)
if (isset($_POST['wpcs_bulk_upload_nonce']) && wp_verify_nonce($_POST['wpcs_bulk_upload_nonce'], 'wpcs_bulk_upload_action')) {
    if (isset($_FILES['wpcs_bulk_upload_file']) && $_FILES['wpcs_bulk_upload_file']['error'] == UPLOAD_ERR_OK) {
        // TODO: Process the uploaded file
        // 1. Validate file type (CSV, JSON) and size.
        // 2. Move uploaded file to a temporary location.
        // 3. Parse the file.
        // 4. For each record:
        //    a. Validate data.
        //    b. Insert into database (e.g., create_poll).
        // 5. Log the bulk upload attempt in `wp_wpcs_poll_bulk_uploads` table.
        //    - user_id, filename, total_records, successful_imports, failed_imports, status, error_log

        // For now, just show a message.
        echo '<div id="message" class="updated fade"><p>' .
            sprintf(esc_html__('File "%s" uploaded. Processing would happen here (not yet implemented).', 'wpcs-poll'), esc_html(basename($_FILES['wpcs_bulk_upload_file']['name']))) .
            '</p></div>';
    } elseif (isset($_FILES['wpcs_bulk_upload_file']['error']) && $_FILES['wpcs_bulk_upload_file']['error'] != UPLOAD_ERR_NO_FILE) {
        echo '<div id="message" class="error fade"><p>' . esc_html__('Error uploading file. Please try again.', 'wpcs-poll') . ' Error code: ' . esc_html($_FILES['wpcs_bulk_upload_file']['error']) . '</p></div>';
    } else {
         echo '<div id="message" class="error fade"><p>' . esc_html__('No file selected for upload.', 'wpcs-poll') . '</p></div>';
    }
}

?>
<div class="wrap wpcs-poll-admin-page">
    <h1><?php esc_html_e('Bulk Upload Polls', 'wpcs-poll'); ?></h1>

    <p>
        <?php esc_html_e('Upload a CSV or JSON file to bulk import polls into the system.', 'wpcs-poll'); ?>
    </p>
    <p>
        <?php esc_html_e('Note: The file processing logic is not yet fully implemented.', 'wpcs-poll'); ?>
    </p>

    <h2><?php esc_html_e('Upload File', 'wpcs-poll'); ?></h2>
    <form method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('wpcs_bulk_upload_action', 'wpcs_bulk_upload_nonce'); ?>

        <table class="form-table">
            <tr valign="top">
                <th scope="row">
                    <label for="wpcs_bulk_upload_file"><?php esc_html_e('Choose File', 'wpcs-poll'); ?></label>
                </th>
                <td>
                    <input type="file" id="wpcs_bulk_upload_file" name="wpcs_bulk_upload_file" accept=".csv, application/json, text/csv">
                    <p class="description">
                        <?php esc_html_e('Supported formats: CSV, JSON.', 'wpcs-poll'); ?><br>
                        <?php esc_html_e('CSV format: title,description,category,options (comma-separated option texts),tags (comma-separated),is_active (1 or 0)', 'wpcs-poll'); ?><br>
                        <?php esc_html_e('JSON format: Array of objects, each object with keys: title, description, category, options (array of strings or objects with id/text), tags (array of strings), is_active (boolean/integer).', 'wpcs-poll'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Upload and Import', 'wpcs-poll')); ?>
    </form>

    <hr>

    <h2><?php esc_html_e('Upload History', 'wpcs-poll'); ?></h2>
    <p>
        <?php esc_html_e('A list of past bulk uploads and their statuses will be displayed here.', 'wpcs-poll'); ?>
    </p>
    <?php
    // TODO: Display bulk upload logs from `wp_wpcs_poll_bulk_uploads` table.
    // This would likely use another WP_List_Table instance.
    // Example:
    // $bulk_upload_logs_table = new WPCS_Poll_Bulk_Upload_List_Table($wpcs_db);
    // $bulk_upload_logs_table->prepare_items();
    // $bulk_upload_logs_table->display();
    ?>
    <p><em><?php esc_html_e('Bulk upload history table placeholder.', 'wpcs-poll'); ?></em></p>

</div>
