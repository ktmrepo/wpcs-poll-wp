<?php
/**
 * AJAX Handlers for WPCS Poll
 *
 * @package WPCS_Poll
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WPCS_Poll_AJAX {

    private $db;

    public function __construct() {
        // Enhanced AJAX hooks with better debugging
        add_action('wp_ajax_wpcs_submit_vote', array($this, 'handle_submit_vote'));
        add_action('wp_ajax_nopriv_wpcs_submit_vote', array($this, 'handle_submit_vote_nopriv'));
        
        // Bookmark functionality
        add_action('wp_ajax_wpcs_poll_bookmark', array($this, 'handle_bookmark'));
        
        // Poll submission
        add_action('wp_ajax_wpcs_poll_submit', array($this, 'handle_poll_submission'));
        
        // Admin AJAX actions
        add_action('wp_ajax_wpcs_poll_admin_approve', array($this, 'handle_admin_approve'));
        add_action('wp_ajax_wpcs_poll_admin_delete', array($this, 'handle_admin_delete'));
        add_action('wp_ajax_wpcs_poll_bulk_upload', array($this, 'handle_bulk_upload'));
        add_action('wp_ajax_wpcs_poll_quick_action', array($this, 'handle_quick_action'));
        add_action('wp_ajax_wpcs_get_user_activity', array($this, 'handle_get_user_activity'));
        
        // Debug action for testing
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('wp_ajax_wpcs_debug_test', array($this, 'handle_debug_test'));
            add_action('wp_ajax_nopriv_wpcs_debug_test', array($this, 'handle_debug_test'));
        }
    }

    /**
     * Get database handler, creating if not exists.
     */
    private function get_db() {
        if (null === $this->db) {
            if (class_exists('WPCS_Poll_Database')) {
                $this->db = new WPCS_Poll_Database();
            }
        }
        return $this->db;
    }

    /**
     * Debug test handler
     */
    public function handle_debug_test() {
        error_log('WPCS Poll Debug: Debug test called');
        error_log('WPCS Poll Debug: User ID: ' . get_current_user_id());
        error_log('WPCS Poll Debug: Is logged in: ' . (is_user_logged_in() ? 'Yes' : 'No'));
        error_log('WPCS Poll Debug: POST data: ' . print_r($_POST, true));
        
        wp_send_json_success(array(
            'message' => 'Debug test successful',
            'user_id' => get_current_user_id(),
            'is_logged_in' => is_user_logged_in(),
            'current_user' => wp_get_current_user()->user_login ?? 'None',
            'nonce_verified' => wp_verify_nonce($_POST['nonce'] ?? '', 'wpcs_poll_vote_nonce')
        ));
    }

    /**
     * Handle vote submission for non-logged users
     */
    public function handle_submit_vote_nopriv() {
        error_log('WPCS Poll Debug: Non-privileged vote submission');
        
        // Check if guest voting is allowed
        $plugin_options = get_option('wpcs_poll_options', array());
        $guest_voting_allowed = isset($plugin_options['guest_voting']) && $plugin_options['guest_voting'] == 1;

        if (!$guest_voting_allowed) {
            error_log('WPCS Poll Debug: Guest voting not allowed');
            wp_send_json_error(array('message' => __('Please log in to vote.', 'wpcs-poll')), 401);
            return;
        }

        // Call the main vote handler
        $this->handle_submit_vote();
    }

    /**
     * Handles the submission of a new vote.
     */
    public function handle_submit_vote() {
        // Enhanced logging for debugging
        error_log('WPCS Poll Debug: Vote submission started');
        error_log('WPCS Poll Debug: POST data: ' . print_r($_POST, true));
        error_log('WPCS Poll Debug: User ID: ' . get_current_user_id());
        error_log('WPCS Poll Debug: Is user logged in: ' . (is_user_logged_in() ? 'Yes' : 'No'));
        error_log('WPCS Poll Debug: Current action: ' . ($_POST['action'] ?? 'not set'));
        
        // Check if action is correct
        if (!isset($_POST['action']) || $_POST['action'] !== 'wpcs_submit_vote') {
            error_log('WPCS Poll Debug: Invalid action: ' . ($_POST['action'] ?? 'not set'));
            wp_send_json_error(array('message' => __('Invalid action.', 'wpcs-poll')), 400);
            return;
        }

        // Enhanced nonce verification with multiple fallbacks
        $nonce_verified = false;
        $nonce_value = '';
        
        // Try different nonce field names
        $nonce_fields = array('_ajax_nonce', 'nonce', 'wpcs_nonce');
        foreach ($nonce_fields as $field) {
            if (isset($_POST[$field])) {
                $nonce_value = sanitize_text_field($_POST[$field]);
                if (wp_verify_nonce($nonce_value, 'wpcs_poll_vote_nonce')) {
                    $nonce_verified = true;
                    error_log('WPCS Poll Debug: Nonce verified with field: ' . $field);
                    break;
                }
            }
        }

        if (!$nonce_verified) {
            error_log('WPCS Poll Debug: Nonce verification failed');
            error_log('WPCS Poll Debug: Available POST fields: ' . implode(', ', array_keys($_POST)));
            error_log('WPCS Poll Debug: Nonce value tried: ' . $nonce_value);
            
            // Create a fresh nonce for the response
            $fresh_nonce = wp_create_nonce('wpcs_poll_vote_nonce');
            error_log('WPCS Poll Debug: Fresh nonce created: ' . $fresh_nonce);
            
            wp_send_json_error(array(
                'message' => __('Security check failed. Please refresh the page and try again.', 'wpcs-poll'),
                'debug_info' => array(
                    'nonce_fields_tried' => $nonce_fields,
                    'fresh_nonce' => $fresh_nonce,
                    'user_logged_in' => is_user_logged_in()
                )
            ), 403);
            return;
        }

        $poll_id = isset($_POST['poll_id']) ? absint($_POST['poll_id']) : 0;
        $option_id = isset($_POST['option_id']) ? sanitize_text_field($_POST['option_id']) : '';
        $user_id = get_current_user_id();

        error_log('WPCS Poll Debug: Poll ID: ' . $poll_id . ', Option ID: ' . $option_id . ', User ID: ' . $user_id);

        $db = $this->get_db();
        if (!$db) {
            error_log('WPCS Poll Debug: Database service not available');
            wp_send_json_error(array('message' => __('Database service not available.', 'wpcs-poll')), 500);
            return;
        }

        if ($poll_id <= 0 || empty($option_id)) {
            error_log('WPCS Poll Debug: Invalid poll data - Poll ID: ' . $poll_id . ', Option ID: ' . $option_id);
            wp_send_json_error(array('message' => __('Invalid poll data provided.', 'wpcs-poll')), 400);
            return;
        }

        $poll = $db->get_poll($poll_id);
        if (!$poll || empty($poll->is_active)) { 
            error_log('WPCS Poll Debug: Poll not found or inactive - Poll ID: ' . $poll_id);
            wp_send_json_error(array('message' => __('This poll is not currently active or does not exist.', 'wpcs-poll')), 403);
            return;
        }
        
        // Validate option exists in poll
        $valid_option = false;
        if (is_array($poll->options)) {
            foreach ($poll->options as $opt) {
                if (isset($opt['id']) && $opt['id'] === $option_id) {
                    $valid_option = true;
                    break;
                }
            }
        }
        if (!$valid_option) {
            error_log('WPCS Poll Debug: Invalid option selected - Option ID: ' . $option_id);
            wp_send_json_error(array('message' => __('Invalid option selected for this poll.', 'wpcs-poll')), 400);
            return;
        }

        // Check user permissions and voting eligibility
        if ($user_id > 0) {
            if ($db->has_user_voted($user_id, $poll_id)) {
                error_log('WPCS Poll Debug: User already voted - User ID: ' . $user_id . ', Poll ID: ' . $poll_id);
                wp_send_json_error(array('message' => __('You have already voted on this poll.', 'wpcs-poll')), 403);
                return;
            }
        } else {
            $plugin_options = get_option('wpcs_poll_options', array());
            $guest_voting_allowed = isset($plugin_options['guest_voting']) && $plugin_options['guest_voting'] == 1;

            if (!$guest_voting_allowed) {
                error_log('WPCS Poll Debug: Guest voting not allowed');
                wp_send_json_error(array('message' => __('Please log in to vote.', 'wpcs-poll')), 401);
                return;
            }
        }
        
        $ip_address = '';
        if ($user_id === 0) {
            $ip_address = $this->get_client_ip(); 
        }

        error_log('WPCS Poll Debug: Attempting to add vote');
        $result = $db->add_vote($user_id, $poll_id, $option_id, $ip_address);

        if (is_wp_error($result)) {
            error_log('WPCS Poll Debug: Vote failed - ' . $result->get_error_message());
            $error_code = $result->get_error_code();
            $status_code = ($error_code === 'already_voted') ? 403 : 400;
            wp_send_json_error(array('message' => $result->get_error_message()), $status_code);
        } else {
            error_log('WPCS Poll Debug: Vote successful, getting updated counts');
            $new_counts = $db->get_vote_counts_for_poll($poll_id);
            if (is_wp_error($new_counts)) {
                error_log('WPCS Poll Debug: Failed to get updated vote counts - ' . $new_counts->get_error_message());
                wp_send_json_success(array(
                    'message' => __('Vote recorded successfully, but could not fetch updated results.', 'wpcs-poll'),
                    'vote_counts' => null
                ));
            } else {
                error_log('WPCS Poll Debug: Vote successful with updated counts: ' . print_r($new_counts, true));
                wp_send_json_success(array(
                    'message' => __('Vote recorded successfully!', 'wpcs-poll'),
                    'vote_counts' => $new_counts
                ));
            }
        }
    }

    /**
     * Handle bookmark functionality
     */
    public function handle_bookmark() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpcs_poll_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'wpcs-poll')));
            return;
        }

        $poll_id = intval($_POST['poll_id']);
        $user_id = get_current_user_id();

        if (!$user_id) {
            wp_send_json_error(array('message' => __('Please log in to bookmark polls.', 'wpcs-poll')));
            return;
        }

        global $wpdb;
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpcs_poll_bookmarks WHERE user_id = %d AND poll_id = %d",
            $user_id, $poll_id
        ));

        if ($existing) {
            $wpdb->delete(
                $wpdb->prefix . 'wpcs_poll_bookmarks',
                array('user_id' => $user_id, 'poll_id' => $poll_id)
            );
            wp_send_json_success(array('action' => 'removed', 'message' => __('Bookmark removed', 'wpcs-poll')));
        } else {
            $wpdb->insert(
                $wpdb->prefix . 'wpcs_poll_bookmarks',
                array('user_id' => $user_id, 'poll_id' => $poll_id)
            );
            wp_send_json_success(array('action' => 'added', 'message' => __('Poll bookmarked', 'wpcs-poll')));
        }
    }

    /**
     * Handle poll submission
     */
    public function handle_poll_submission() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpcs_poll_submit_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'wpcs-poll')));
            return;
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Please log in to submit polls.', 'wpcs-poll')));
            return;
        }

        $db = $this->get_db();
        if (!$db) {
            wp_send_json_error(array('message' => __('Database service not available.', 'wpcs-poll')));
            return;
        }

        $poll_data = array(
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'category' => sanitize_text_field($_POST['category']),
            'options' => array_map('sanitize_text_field', $_POST['options']),
            'tags' => sanitize_text_field($_POST['tags']),
            'created_by' => $user_id
        );

        $result = $db->create_poll($poll_data);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } else {
            wp_send_json_success(array(
                'message' => __('Poll submitted successfully!', 'wpcs-poll'),
                'poll_id' => $result
            ));
        }
    }

    /**
     * Handle bulk upload
     */
    public function handle_bulk_upload() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['wpcs_poll_bulk_nonce'], 'wpcs_poll_bulk_upload')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'wpcs-poll')));
            return;
        }

        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'wpcs-poll')));
            return;
        }

        // Check if file was uploaded
        if (!isset($_FILES['upload_file']) || $_FILES['upload_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => __('No file uploaded or upload error occurred.', 'wpcs-poll')));
            return;
        }

        $file = $_FILES['upload_file'];
        $file_type = sanitize_text_field($_POST['file_type']);
        $auto_approve = isset($_POST['auto_approve']) ? 1 : 0;

        // Validate file type
        if (!in_array($file_type, array('csv', 'json'))) {
            wp_send_json_error(array('message' => __('Invalid file type. Only CSV and JSON files are allowed.', 'wpcs-poll')));
            return;
        }

        // Validate file size (2MB limit)
        if ($file['size'] > 2 * 1024 * 1024) {
            wp_send_json_error(array('message' => __('File size exceeds 2MB limit.', 'wpcs-poll')));
            return;
        }

        // Validate file extension
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_extension !== $file_type) {
            wp_send_json_error(array('message' => __('File extension does not match selected file type.', 'wpcs-poll')));
            return;
        }

        // Process the file
        $result = $this->process_bulk_upload($file, $file_type, $auto_approve);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } else {
            wp_send_json_success($result);
        }
    }

    /**
     * Process bulk upload file
     */
    private function process_bulk_upload($file, $file_type, $auto_approve) {
        global $wpdb;

        $user_id = get_current_user_id();
        $filename = sanitize_file_name($file['name']);
        
        // Create upload record
        $upload_id = $wpdb->insert(
            $wpdb->prefix . 'wpcs_poll_bulk_uploads',
            array(
                'filename' => $filename,
                'file_type' => $file_type,
                'status' => 'processing',
                'uploaded_by' => $user_id
            ),
            array('%s', '%s', '%s', '%d')
        );

        if (!$upload_id) {
            return new WP_Error('upload_record_failed', __('Failed to create upload record.', 'wpcs-poll'));
        }

        $upload_id = $wpdb->insert_id;

        // Read and parse file
        $file_content = file_get_contents($file['tmp_name']);
        if ($file_content === false) {
            return new WP_Error('file_read_failed', __('Failed to read uploaded file.', 'wpcs-poll'));
        }

        $polls_data = array();
        $errors = array();

        try {
            if ($file_type === 'csv') {
                $polls_data = $this->parse_csv_content($file_content);
            } else {
                $polls_data = $this->parse_json_content($file_content);
            }
        } catch (Exception $e) {
            $wpdb->update(
                $wpdb->prefix . 'wpcs_poll_bulk_uploads',
                array('status' => 'failed', 'error_log' => $e->getMessage()),
                array('id' => $upload_id)
            );
            return new WP_Error('parse_failed', $e->getMessage());
        }

        // Process each poll
        $successful = 0;
        $failed = 0;
        $db = $this->get_db();

        foreach ($polls_data as $index => $poll_data) {
            try {
                // Validate poll data
                if (empty($poll_data['title']) || empty($poll_data['options']) || !is_array($poll_data['options']) || count($poll_data['options']) < 2) {
                    throw new Exception("Row " . ($index + 1) . ": Title and at least 2 options are required.");
                }

                // Prepare poll data
                $poll_data['is_active'] = $auto_approve ? 1 : 0;
                $poll_data['created_by'] = $user_id;

                // Create poll
                $result = $db->create_poll($poll_data);
                
                if (is_wp_error($result)) {
                    throw new Exception("Row " . ($index + 1) . ": " . $result->get_error_message());
                }

                $successful++;
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
                $failed++;
            }
        }

        // Update upload record
        $wpdb->update(
            $wpdb->prefix . 'wpcs_poll_bulk_uploads',
            array(
                'total_records' => count($polls_data),
                'successful_records' => $successful,
                'failed_records' => $failed,
                'status' => $failed > 0 ? 'completed_with_errors' : 'completed',
                'error_log' => !empty($errors) ? implode("\n", $errors) : null
            ),
            array('id' => $upload_id)
        );

        return array(
            'message' => sprintf(
                __('Upload completed. %d polls created successfully, %d failed.', 'wpcs-poll'),
                $successful,
                $failed
            ),
            'successful' => $successful,
            'failed' => $failed,
            'errors' => $errors
        );
    }

    /**
     * Parse CSV content
     */
    private function parse_csv_content($content) {
        $lines = str_getcsv($content, "\n");
        $polls = array();
        
        if (empty($lines)) {
            throw new Exception(__('CSV file is empty.', 'wpcs-poll'));
        }

        // Get headers
        $headers = str_getcsv(array_shift($lines));
        $required_headers = array('title', 'option1', 'option2');
        
        foreach ($required_headers as $required) {
            if (!in_array($required, $headers)) {
                throw new Exception(sprintf(__('Required column "%s" not found in CSV.', 'wpcs-poll'), $required));
            }
        }

        foreach ($lines as $line_num => $line) {
            if (empty(trim($line))) continue;
            
            $data = str_getcsv($line);
            if (count($data) !== count($headers)) {
                throw new Exception(sprintf(__('Row %d: Column count mismatch.', 'wpcs-poll'), $line_num + 2));
            }

            $row = array_combine($headers, $data);
            
            // Extract options
            $options = array();
            for ($i = 1; $i <= 10; $i++) {
                $option_key = 'option' . $i;
                if (isset($row[$option_key]) && !empty(trim($row[$option_key]))) {
                    $options[] = trim($row[$option_key]);
                }
            }

            $polls[] = array(
                'title' => trim($row['title']),
                'description' => isset($row['description']) ? trim($row['description']) : '',
                'category' => isset($row['category']) ? trim($row['category']) : 'General',
                'options' => $options,
                'tags' => isset($row['tags']) ? trim($row['tags']) : ''
            );
        }

        return $polls;
    }

    /**
     * Parse JSON content
     */
    private function parse_json_content($content) {
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(__('Invalid JSON format: ', 'wpcs-poll') . json_last_error_msg());
        }

        if (!is_array($data)) {
            throw new Exception(__('JSON must contain an array of polls.', 'wpcs-poll'));
        }

        $polls = array();
        foreach ($data as $index => $poll_data) {
            if (!is_array($poll_data)) {
                throw new Exception(sprintf(__('Poll %d: Invalid poll data format.', 'wpcs-poll'), $index + 1));
            }

            $polls[] = array(
                'title' => isset($poll_data['title']) ? trim($poll_data['title']) : '',
                'description' => isset($poll_data['description']) ? trim($poll_data['description']) : '',
                'category' => isset($poll_data['category']) ? trim($poll_data['category']) : 'General',
                'options' => isset($poll_data['options']) && is_array($poll_data['options']) ? $poll_data['options'] : array(),
                'tags' => isset($poll_data['tags']) ? (is_array($poll_data['tags']) ? implode(',', $poll_data['tags']) : trim($poll_data['tags'])) : ''
            );
        }

        return $polls;
    }

    /**
     * Handle admin approval
     */
    public function handle_admin_approve() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpcs_poll_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'wpcs-poll')));
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'wpcs-poll')));
            return;
        }

        $poll_id = intval($_POST['poll_id']);
        
        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'wpcs_polls',
            array('is_active' => 1),
            array('id' => $poll_id)
        );

        if ($result !== false) {
            wp_send_json_success(array('message' => __('Poll approved successfully.', 'wpcs-poll')));
        } else {
            wp_send_json_error(array('message' => __('Failed to approve poll.', 'wpcs-poll')));
        }
    }

    /**
     * Handle admin delete
     */
    public function handle_admin_delete() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpcs_poll_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'wpcs-poll')));
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'wpcs-poll')));
            return;
        }

        $poll_id = intval($_POST['poll_id']);
        
        $db = $this->get_db();
        if (!$db) {
            wp_send_json_error(array('message' => __('Database service not available.', 'wpcs-poll')));
            return;
        }

        $result = $db->delete_poll($poll_id);

        if ($result) {
            wp_send_json_success(array('message' => __('Poll deleted successfully.', 'wpcs-poll')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete poll.', 'wpcs-poll')));
        }
    }

    /**
     * Handle quick actions
     */
    public function handle_quick_action() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpcs_poll_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'wpcs-poll')));
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'wpcs-poll')));
            return;
        }

        $action = sanitize_text_field($_POST['poll_action']);
        $poll_id = intval($_POST['poll_id']);

        global $wpdb;

        switch ($action) {
            case 'toggle_active':
                $current_status = $wpdb->get_var($wpdb->prepare(
                    "SELECT is_active FROM {$wpdb->prefix}wpcs_polls WHERE id = %d",
                    $poll_id
                ));
                
                $new_status = $current_status ? 0 : 1;
                $result = $wpdb->update(
                    $wpdb->prefix . 'wpcs_polls',
                    array('is_active' => $new_status),
                    array('id' => $poll_id)
                );

                if ($result !== false) {
                    wp_send_json_success(array(
                        'message' => $new_status ? __('Poll activated.', 'wpcs-poll') : __('Poll deactivated.', 'wpcs-poll'),
                        'is_active' => (bool) $new_status
                    ));
                } else {
                    wp_send_json_error(array('message' => __('Failed to update poll status.', 'wpcs-poll')));
                }
                break;

            default:
                wp_send_json_error(array('message' => __('Invalid action.', 'wpcs-poll')));
        }
    }

    /**
     * Handle get user activity
     */
    public function handle_get_user_activity() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpcs_user_activity')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'wpcs-poll')));
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'wpcs-poll')));
            return;
        }

        $user_id = intval($_POST['user_id']);
        
        global $wpdb;

        // Get user info
        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error(array('message' => __('User not found.', 'wpcs-poll')));
            return;
        }

        // Get user's recent votes
        $recent_votes = $wpdb->get_results($wpdb->prepare("
            SELECT v.*, p.title as poll_title, p.category
            FROM {$wpdb->prefix}wpcs_poll_votes v
            JOIN {$wpdb->prefix}wpcs_polls p ON v.poll_id = p.id
            WHERE v.user_id = %d
            ORDER BY v.created_at DESC
            LIMIT 20
        ", $user_id));

        // Get user's created polls
        $created_polls = $wpdb->get_results($wpdb->prepare("
            SELECT p.*, 
                   (SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_poll_votes v WHERE v.poll_id = p.id) as vote_count
            FROM {$wpdb->prefix}wpcs_polls p
            WHERE p.created_by = %d
            ORDER BY p.created_at DESC
            LIMIT 10
        ", $user_id));

        ob_start();
        ?>
        <div class="user-activity-details">
            <h3><?php echo esc_html($user->display_name); ?> (<?php echo esc_html($user->user_email); ?>)</h3>
            
            <div class="activity-section">
                <h4><?php _e('Recent Votes', 'wpcs-poll'); ?></h4>
                <?php if ($recent_votes): ?>
                    <table class="wp-list-table widefat">
                        <thead>
                            <tr>
                                <th><?php _e('Poll', 'wpcs-poll'); ?></th>
                                <th><?php _e('Category', 'wpcs-poll'); ?></th>
                                <th><?php _e('Date', 'wpcs-poll'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_votes as $vote): ?>
                                <tr>
                                    <td><?php echo esc_html($vote->poll_title); ?></td>
                                    <td><?php echo esc_html($vote->category); ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($vote->created_at)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p><?php _e('No votes found.', 'wpcs-poll'); ?></p>
                <?php endif; ?>
            </div>

            <div class="activity-section">
                <h4><?php _e('Created Polls', 'wpcs-poll'); ?></h4>
                <?php if ($created_polls): ?>
                    <table class="wp-list-table widefat">
                        <thead>
                            <tr>
                                <th><?php _e('Title', 'wpcs-poll'); ?></th>
                                <th><?php _e('Category', 'wpcs-poll'); ?></th>
                                <th><?php _e('Votes', 'wpcs-poll'); ?></th>
                                <th><?php _e('Status', 'wpcs-poll'); ?></th>
                                <th><?php _e('Created', 'wpcs-poll'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($created_polls as $poll): ?>
                                <tr>
                                    <td><?php echo esc_html($poll->title); ?></td>
                                    <td><?php echo esc_html($poll->category); ?></td>
                                    <td><?php echo intval($poll->vote_count); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $poll->is_active ? 'active' : 'inactive'; ?>">
                                            <?php echo $poll->is_active ? __('Active', 'wpcs-poll') : __('Inactive', 'wpcs-poll'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($poll->created_at)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p><?php _e('No polls created.', 'wpcs-poll'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        $content = ob_get_clean();

        wp_send_json_success($content);
    }

    /**
     * Get client IP address.
     */
    private function get_client_ip() {
        $ip_address = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip_address = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
            $ip_address = $_SERVER['HTTP_FORWARDED'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip_address = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip_address = 'UNKNOWN';
        }
        return sanitize_text_field(wp_unslash($ip_address));
    }
}