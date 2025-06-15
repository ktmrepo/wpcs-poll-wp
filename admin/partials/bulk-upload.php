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

<?php
// Admin notices array (if not already at the very top, move it here)
if (!isset($admin_notices)) {
    $admin_notices = array();
}

function wpcs_parse_csv($filepath) {
    $data = array();
    if (($handle = fopen($filepath, "r")) !== FALSE) {
        $header = fgetcsv($handle); // Assuming first row is header
        if ($header === false) { fclose($handle); return new WP_Error('csv_error', 'Cannot read CSV header.'); }

        // Expected headers (example, adjust as needed)
        // title,description,category,options (comma-separated),tags (comma-separated),is_active (1 or 0)

        while (($row = fgetcsv($handle)) !== FALSE) {
            // Simple association, assumes order and count match header. Robust parsing needed for production.
            if (count($header) == count($row)) {
                $data[] = array_combine($header, $row);
            }
        }
        fclose($handle);
    } else {
        return new WP_Error('csv_error', 'Cannot open CSV file.');
    }
    return $data;
}

function wpcs_parse_json($filepath) {
    $content = file_get_contents($filepath);
    if ($content === false) {
        return new WP_Error('json_error', 'Cannot read JSON file.');
    }
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return new WP_Error('json_error', 'Invalid JSON format: ' . json_last_error_msg());
    }
    return $data;
}

function wpcs_process_bulk_upload_file($filepath, $file_extension, $log_id, WPCS_Poll_Database $db) {
    global $admin_notices; // Use the global notices array

    $records = array();
    if ($file_extension === 'csv') {
        $records = wpcs_parse_csv($filepath);
    } elseif ($file_extension === 'json') {
        $records = wpcs_parse_json($filepath);
    } else {
        $db->update_bulk_upload_status($log_id, 'failed', 0, 0, 'Unsupported file type for processing.');
        $admin_notices[] = array('type' => 'error', 'message' => __('Unsupported file type for processing.', 'wpcs-poll'));
        return;
    }

    if (is_wp_error($records)) {
        $db->update_bulk_upload_status($log_id, 'failed', 0, 0, $records->get_error_message());
        $admin_notices[] = array('type' => 'error', 'message' => __('Error parsing file: ', 'wpcs-poll') . $records->get_error_message());
        return;
    }

    if (empty($records)) {
        $db->update_bulk_upload_status($log_id, 'completed', 0, 0, 'No records found in file.');
        $admin_notices[] = array('type' => 'warning', 'message' => __('No records found in the uploaded file.', 'wpcs-poll'));
        return;
    }

    $total_records = count($records);
    $successful_imports = 0;
    $failed_imports = 0;
    $error_details = array();

    $db->update_bulk_upload_status($log_id, 'processing', null, null, null); // Update status to processing

    foreach ($records as $index => $record) {
        // Basic data validation and mapping (highly simplified)
        $poll_data = array();
        $poll_data['title'] = !empty($record['title']) ? sanitize_text_field($record['title']) : '';

        if (empty($poll_data['title'])) {
            $failed_imports++;
            $error_details[] = "Record " . ($index+1) . ": Title is missing.";
            continue;
        }

        $poll_data['description'] = !empty($record['description']) ? sanitize_textarea_field($record['description']) : '';
        $poll_data['category'] = !empty($record['category']) ? sanitize_text_field($record['category']) : 'General';

        // Options processing (example for CSV: "Option 1,Option 2")
        // For JSON: expects an array of strings or array of {id: '...', text: '...'}
        $options_input = !empty($record['options']) ? $record['options'] : '';
        $parsed_options = array();
        if (is_string($options_input) && $file_extension === 'csv') {
            $opt_texts = str_getcsv($options_input); // Handles commas within quotes if any
            foreach ($opt_texts as $opt_text) {
                if(!empty(trim($opt_text))) $parsed_options[] = array('id' => 'opt_' . uniqid(), 'text' => trim($opt_text));
            }
        } elseif (is_array($options_input) && $file_extension === 'json') {
             foreach ($options_input as $opt) {
                if (is_string($opt) && !empty(trim($opt))) {
                    $parsed_options[] = array('id' => 'opt_' . uniqid(), 'text' => trim($opt));
                } elseif (is_array($opt) && !empty($opt['text'])) {
                     $parsed_options[] = array('id' => !empty($opt['id']) ? $opt['id'] : 'opt_' . uniqid(), 'text' => trim($opt['text']));
                }
            }
        }

        if (count($parsed_options) < 2) {
            $failed_imports++;
            $error_details[] = "Record " . ($index+1) . " ('" . esc_html($poll_data['title']) . "'): Must have at least two valid options.";
            continue;
        }
        $poll_data['options'] = wp_json_encode($parsed_options);

        $poll_data['tags'] = !empty($record['tags']) ? sanitize_text_field($record['tags']) : ''; // Assuming comma-separated string
        $poll_data['is_active'] = isset($record['is_active']) ? absint($record['is_active']) : 0;
        $poll_data['created_by'] = get_current_user_id();

        $create_result = $db->create_poll($poll_data);
        if (is_wp_error($create_result)) {
            $failed_imports++;
            $error_details[] = "Record " . ($index+1) . " ('" . esc_html($poll_data['title']) . "'): " . $create_result->get_error_message();
        } else {
            $successful_imports++;
        }
    }

    $final_status = ($failed_imports === 0 && $successful_imports > 0) ? 'completed' : (($failed_imports > 0 && $successful_imports > 0) ? 'partial' : 'failed');
    if ($successful_imports === 0 && $failed_imports === 0 && $total_records > 0) { // e.g. all records had validation issues before trying to import
         $final_status = 'failed';
    } else if ($successful_imports === 0 && $failed_imports > 0) {
        $final_status = 'failed';
    }


    $db->update_bulk_upload_status($log_id, $final_status, $successful_imports, $failed_imports, empty($error_details) ? null : wp_json_encode($error_details));

    $admin_notices[] = array(
        'type' => $final_status === 'failed' ? 'error' : ($final_status === 'partial' ? 'warning' : 'success'),
        'message' => sprintf(
            __('Bulk import processing finished for log ID %d. Status: %s. Successful: %d. Failed: %d. Total Records: %d.', 'wpcs-poll'),
            $log_id,
            strtoupper($final_status),
            $successful_imports,
            $failed_imports,
            $total_records
        ) . (empty($error_details) ? '' : '<br/><strong>Errors:</strong><pre>' . esc_html(implode("
", array_slice($error_details, 0, 5))) . (count($error_details) > 5 ? "
..." : "") . '</pre>')
    );
}

// Admin notices array
$admin_notices = array();

if (isset($_POST['wpcs_bulk_upload_nonce']) && wp_verify_nonce($_POST['wpcs_bulk_upload_nonce'], 'wpcs_bulk_upload_action')) {
    if (isset($_FILES['wpcs_bulk_upload_file']) && $_FILES['wpcs_bulk_upload_file']['error'] == UPLOAD_ERR_OK) {

        // Check file type (WordPress native way)
        $file_info = wp_check_filetype(basename($_FILES['wpcs_bulk_upload_file']['name']));
        $allowed_extensions = array('csv', 'json');

        if (empty($file_info['ext']) || !in_array(strtolower($file_info['ext']), $allowed_extensions)) {
            $admin_notices[] = array('type' => 'error', 'message' => __('Invalid file type. Only CSV and JSON files are allowed.', 'wpcs-poll'));
        } else {
            // Handle the upload using WordPress filesystem functions
            if (!function_exists('wp_handle_upload')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }

            // 'upload_dir' can be customized if needed, for now, use default WP uploads
            $uploaded_file = $_FILES['wpcs_bulk_upload_file'];
            $upload_overrides = array('test_form' => false, 'mimes' => array('csv' => 'text/csv', 'json' => 'application/json'));
            $movefile = wp_handle_upload($uploaded_file, $upload_overrides);

            if ($movefile && !isset($movefile['error'])) {
                $admin_notices[] = array(
                    'type' => 'updated',
                    'message' => sprintf(esc_html__('File "%s" uploaded successfully. Path: %s. Processing will begin shortly (not yet implemented).', 'wpcs-poll'), esc_html(basename($movefile['file'])), esc_html($movefile['file']))
                );

                // Log the upload attempt to the database
                if (!isset($wpcs_db) && class_exists('WPCS_Poll_Database')) {
                    $wpcs_db = new WPCS_Poll_Database();
                } elseif (!isset($wpcs_db)) {
                     $admin_notices[] = array('type' => 'error', 'message' => __('Database class not found, cannot log upload.', 'wpcs-poll'));
                     // Potentially delete the uploaded file if logging fails critically
                     // @unlink($movefile['file']);
                }

                if (isset($wpcs_db)) {
                    $log_data = array(
                        'user_id' => get_current_user_id(),
                        'filename' => basename($movefile['file']), // Store only the basename
                        // 'total_records' will be updated after parsing
                        'status' => 'uploaded' // New status: file is on server, pre-processing
                    );
                    $log_id = $wpcs_db->log_bulk_upload($log_data);

                    // if (is_wp_error($log_id)) { // This check is now inside the new block
                    //    $admin_notices[] = array('type' => 'error', 'message' => __('Failed to log upload task: ', 'wpcs-poll') . $log_id->get_error_message());
                    //    // @unlink($movefile['file']); // Clean up if logging fails
                    // } else {
                    //    $admin_notices[] = array('type' => 'updated', 'message' => sprintf(__('Upload task logged with ID: %d. File is ready for processing.', 'wpcs-poll'), $log_id));
                    //    // TODO: Here you would schedule a background task (WP Cron or Action Scheduler)
                    //    // to process $movefile['file'] using $log_id.
                    //    // For now, processing will be simulated or done synchronously (not recommended for large files).
                    // }
                    // The above is replaced by:
                        if (!is_wp_error($log_id) && $log_id > 0) {
                            // $admin_notices[] = array('type' => 'updated', 'message' => sprintf(__('Upload task logged with ID: %d. File is ready for processing.', 'wpcs-poll'), $log_id)); // Message moved to end of processing

                            // Call the processing function (synchronously for now)
                            wpcs_process_bulk_upload_file($movefile['file'], strtolower($file_info['ext']), $log_id, $wpcs_db);

                        } else if (is_wp_error($log_id)) {
                             $admin_notices[] = array('type' => 'error', 'message' => __('Failed to log upload task: ', 'wpcs-poll') . $log_id->get_error_message());
                             // @unlink($movefile['file']); // Clean up if logging fails
                        } else {
                             $admin_notices[] = array('type' => 'error', 'message' => __('Failed to log upload task (unknown error).', 'wpcs-poll'));
                        }
                }

            } else {
                $error_message = isset($movefile['error']) ? $movefile['error'] : __('Unknown error during file upload handling.', 'wpcs-poll');
                $admin_notices[] = array('type' => 'error', 'message' => esc_html__('Error moving uploaded file: ', 'wpcs-poll') . esc_html($error_message));
            }
        }
    } elseif (isset($_FILES['wpcs_bulk_upload_file']['error']) && $_FILES['wpcs_bulk_upload_file']['error'] != UPLOAD_ERR_NO_FILE) {
        $admin_notices[] = array('type' => 'error', 'message' => esc_html__('Error during file upload. Error code: ', 'wpcs-poll') . esc_html($_FILES['wpcs_bulk_upload_file']['error']));
    } elseif (!isset($_POST['action'])) { // Avoid showing "No file selected" on initial page load
        // $admin_notices[] = array('type' => 'error', 'message' => __('No file selected for upload.', 'wpcs-poll'));
    }
}

// Display admin notices
if (!empty($admin_notices)) {
    foreach ($admin_notices as $notice) {
        echo '<div id="message" class="' . esc_attr($notice['type']) . ' notice is-dismissible"><p>' . wp_kses_post($notice['message']) . '</p></div>';
    }
}
?>

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
