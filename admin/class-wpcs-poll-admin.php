<?php
class WPCS_Poll_Admin {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        // Ensure critical admin files are loaded
        require_once WPCS_POLL_PLUGIN_PATH . 'admin/class-wpcs-poll-list-table.php';
        require_once WPCS_POLL_PLUGIN_PATH . 'admin/class-wpcs-poll-user-list-table.php';

        add_action('admin_init', array($this, 'register_settings')); // New line for settings
        add_action('admin_post_wpcs_save_poll', array($this, 'handle_save_poll'));
        add_action('admin_action_wpcs_delete_poll_action', array($this, 'handle_delete_poll'));
        add_action('wp_ajax_wpcs_update_user_poll_role', array($this, 'handle_update_user_poll_role_ajax'));
        // Other hooks for admin menu, styles, scripts if not handled by the main plugin class
    }

    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, WPCS_POLL_PLUGIN_URL . 'admin/css/wpcs-poll-admin.css', array(), $this->version, 'all');
    }

    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, WPCS_POLL_PLUGIN_URL . 'admin/js/wpcs-poll-admin.js', array('jquery'), $this->version, false);
        
        wp_localize_script($this->plugin_name, 'wpcs_poll_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpcs_poll_admin_nonce'),
            'rest_url' => rest_url('wpcs-poll/v1/')
        ));
    }

    public function add_admin_menu() {
        add_menu_page(
            'WPCS Polls',
            'WPCS Polls',
            'manage_options',
            'wpcs-poll-dashboard',
            array($this, 'display_dashboard'),
            'dashicons-chart-bar',
            30
        );

        add_submenu_page(
            'wpcs-poll-dashboard',
            'All Polls',
            'All Polls',
            'manage_options',
            'wpcs-poll-manage',
            array($this, 'display_poll_management')
        );

        add_submenu_page(
            'wpcs-poll-dashboard',
            'Pending Approval',
            'Pending Approval',
            'manage_options',
            'wpcs-poll-pending',
            array($this, 'display_pending_approval')
        );

        add_submenu_page(
            'wpcs-poll-dashboard',
            'Bulk Upload',
            'Bulk Upload',
            'manage_options',
            'wpcs-poll-bulk',
            array($this, 'display_bulk_upload')
        );

        add_submenu_page(
            'wpcs-poll-dashboard',
            'User Management',
            'User Management',
            'manage_options',
            'wpcs-poll-users',
            array($this, 'display_user_management')
        );

        add_submenu_page(
            'wpcs-poll-dashboard',
            'Analytics',
            'Analytics',
            'manage_options',
            'wpcs-poll-analytics',
            array($this, 'display_analytics')
        );

        add_submenu_page(
            'wpcs-poll-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'wpcs-poll-settings',
            array($this, 'display_settings')
        );
    }

    public function display_dashboard() {
        include_once WPCS_POLL_PLUGIN_PATH . 'admin/partials/dashboard.php';
    }

    public function display_poll_management() {
        include_once WPCS_POLL_PLUGIN_PATH . 'admin/partials/poll-management.php';
    }

    public function display_pending_approval() {
        include_once WPCS_POLL_PLUGIN_PATH . 'admin/partials/pending-approval.php';
    }

    public function display_bulk_upload() {
        include_once WPCS_POLL_PLUGIN_PATH . 'admin/partials/bulk-upload.php';
    }

    public function display_user_management() {
        include_once WPCS_POLL_PLUGIN_PATH . 'admin/partials/user-management.php';
    }

    public function display_analytics() {
        include_once WPCS_POLL_PLUGIN_PATH . 'admin/partials/analytics.php';
    }

    public function display_settings() {
        include_once WPCS_POLL_PLUGIN_PATH . 'admin/partials/settings.php';
    }

    public function handle_save_poll() {
        // Verify nonce
        if (!isset($_POST['_wpcs_nonce']) || !wp_verify_nonce($_POST['_wpcs_nonce'], 'wpcs_save_poll_nonce')) {
            wp_die(__('Invalid nonce specified', 'wpcs-poll'), __('Error', 'wpcs-poll'), array('response' => 403));
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) { // Or a more specific capability
            wp_die(__('You do not have sufficient permissions to access this page.', 'wpcs-poll'), __('Error', 'wpcs-poll'), array('response' => 403));
        }

        // Sanitize and validate input
        $poll_id = isset($_POST['poll_id']) ? absint($_POST['poll_id']) : 0;
        $title = isset($_POST['poll_title']) ? sanitize_text_field($_POST['poll_title']) : '';
        $description = isset($_POST['poll_description']) ? sanitize_textarea_field($_POST['poll_description']) : '';
        $category = isset($_POST['poll_category']) ? sanitize_text_field($_POST['poll_category']) : 'General';
        $tags = isset($_POST['poll_tags']) ? sanitize_text_field($_POST['poll_tags']) : '';
        $is_active = isset($_POST['poll_is_active']) ? absint($_POST['poll_is_active']) : 0;

        $options = array();
        if (isset($_POST['poll_options']) && is_array($_POST['poll_options'])) {
            foreach ($_POST['poll_options'] as $option_item) {
                if (isset($option_item['text']) && is_string($option_item['text'])) {
                    $text = sanitize_text_field(trim($option_item['text']));
                    if (!empty($text)) {
                        $id = isset($option_item['id']) ? sanitize_text_field($option_item['id']) : 'new_' . uniqid();
                        $options[] = array('id' => $id, 'text' => $text);
                    }
                }
            }
        }

        // Basic validation
        if (empty($title)) {
            // Handle error: Title is required
            wp_redirect(admin_url('admin.php?page=wpcs-poll-manage&action=' . ($poll_id ? 'edit&poll_id=' . $poll_id : 'add_new') . '&message=error_title_required'));
            exit;
        }
        if (count($options) < 2) {
            // Handle error: At least two options are required
            wp_redirect(admin_url('admin.php?page=wpcs-poll-manage&action=' . ($poll_id ? 'edit&poll_id=' . $poll_id : 'add_new') . '&message=error_options_required'));
            exit;
        }

        $poll_data = array(
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'options' => wp_json_encode($options), // Store options as JSON
            'tags' => $tags,
            'is_active' => $is_active,
            // 'created_by' will be set in the database class method
        );

        // Instantiate database class
        // This assumes WPCS_Poll_Database is loaded.
        // A better approach might be to pass it via constructor or use a service locator.
        if (!class_exists('WPCS_Poll_Database')) {
            // This should ideally not happen if dependencies are loaded correctly
            wp_die(__('Critical Error: Database class not found.', 'wpcs-poll'));
            return;
        }
        $db = new WPCS_Poll_Database();

        $result = false;
        if ($poll_id > 0) {
            // Update existing poll
            // Note: 'created_by' and 'created_at' are not updated.
            // 'updated_at' is handled by the update_poll method itself.

            // Unset fields that are not part of the $poll_data structure for update_poll
            // or are set by the database method itself.
            unset($poll_data['created_by']);
            // $poll_data already contains: title, description, category, options (JSON), tags, is_active

            $result = $db->update_poll($poll_id, $poll_data);

            if (is_wp_error($result)) {
                // Handle database error from update_poll
                // Log the error: error_log('WPCS Poll Update DB Error: ' . $result->get_error_message());
                $error_message = $result->get_error_message();
                wp_redirect(admin_url('admin.php?page=wpcs-poll-manage&action=edit&poll_id=' . $poll_id . '&message=error_saving_poll&details=' . urlencode($error_message)));
                exit;
            }
            $message_type = 'poll_updated';
        } else {
            // Create new poll (this part should already be updated)
            $poll_data['created_by'] = get_current_user_id();
            $result = $db->create_poll($poll_data);

            if (is_wp_error($result)) {
                // Handle database error from create_poll
                $error_message = $result->get_error_message();
                wp_redirect(admin_url('admin.php?page=wpcs-poll-manage&action=add_new&message=error_saving_poll&details=' . urlencode($error_message)));
                exit;
            }
            $message_type = 'poll_added';
        }

        if ($result) {
            wp_redirect(admin_url('admin.php?page=wpcs-poll-manage&message=' . $message_type));
        } else {
            // Handle database error
            wp_redirect(admin_url('admin.php?page=wpcs-poll-manage&action=' . ($poll_id ? 'edit&poll_id=' . $poll_id : 'add_new') . '&message=error_saving_poll'));
        }
        exit;
    }

    public function handle_delete_poll() {
        // Get Poll ID and verify nonce
        $poll_id = isset($_GET['poll_id']) ? absint($_GET['poll_id']) : 0;
        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field($_GET['_wpnonce']) : '';

        if (!$poll_id || !wp_verify_nonce($nonce, 'wpcs_delete_poll_' . $poll_id)) {
            wp_die(__('Invalid action or security token expired.', 'wpcs-poll'), __('Error', 'wpcs-poll'), array('response' => 403));
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) { // Or a more specific capability for deleting polls
            wp_die(__('You do not have sufficient permissions to delete this poll.', 'wpcs-poll'), __('Error', 'wpcs-poll'), array('response' => 403));
        }

        // Instantiate database class
        if (!class_exists('WPCS_Poll_Database')) {
            wp_die(__('Critical Error: Database class not found.', 'wpcs-poll'));
            return;
        }
        $db = new WPCS_Poll_Database();

        // Call delete_poll method
        $result = $db->delete_poll($poll_id);

        $redirect_url = admin_url('admin.php?page=wpcs-poll-manage');

        if ($result && !is_wp_error($result)) {
            $redirect_url = add_query_arg('message', 'poll_deleted', $redirect_url);
        } else {
            $error_details = is_wp_error($result) ? $result->get_error_message() : __('Unknown error during deletion.', 'wpcs-poll');
            $redirect_url = add_query_arg(array(
                'message' => 'error_deleting_poll',
                'details' => urlencode($error_details)
            ), $redirect_url);
        }

        wp_redirect($redirect_url);
        exit;
    }

    public function handle_update_user_poll_role_ajax() {
        // Check nonce (WordPress's check_ajax_referer uses '_ajax_nonce' by default from POST)
        $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        check_ajax_referer('wpcs_update_user_poll_role_' . $user_id, '_ajax_nonce');

        // Check user capabilities
        if (!current_user_can('manage_options')) { // Or a more specific capability like 'edit_users'
            wp_send_json_error(array('message' => __('You do not have permission to change user roles.', 'wpcs-poll')), 403);
        }

        if (!$user_id) {
            wp_send_json_error(array('message' => __('Invalid user ID.', 'wpcs-poll')), 400);
        }

        $new_role = isset($_POST['new_role']) ? sanitize_text_field($_POST['new_role']) : '';
        if (!in_array($new_role, array('user', 'admin'))) {
            wp_send_json_error(array('message' => __('Invalid role specified.', 'wpcs-poll')), 400);
        }

        // Update user meta
        $result = update_user_meta($user_id, 'wpcs_poll_role', $new_role);

        if ($result === false) {
            // update_user_meta returns false if the update failed.
            wp_send_json_error(array('message' => __('Failed to update user poll role in the database.', 'wpcs-poll')), 500);
        } elseif ($result === true || is_int($result)) {
            // update_user_meta returns true if meta value is the same (no change) or meta_id on successful update.
            // Both are considered success here.
            wp_send_json_success(array('message' => __('User poll role updated successfully.', 'wpcs-poll')));
        }

        // Fallback, though should be covered by above.
        wp_send_json_error(array('message' => __('An unknown error occurred.', 'wpcs-poll')), 500);
    }

    public function register_settings() {
        // Register a setting group
        register_setting(
            'wpcs_poll_settings_group', // Option group
            'wpcs_poll_options',        // Option name (stores all options as an array)
            array($this, 'sanitize_poll_options') // Sanitization callback
        );

        // Add settings section for General Settings
        add_settings_section(
            'wpcs_poll_general_section', // ID
            __('General Settings', 'wpcs-poll'), // Title
            array($this, 'general_section_callback'), // Callback
            'wpcs-poll-settings' // Page slug where this section will be shown
        );

        // Add fields to General Settings section
        add_settings_field(
            'default_poll_status', // ID
            __('Default Poll Status', 'wpcs-poll'), // Title
            array($this, 'default_poll_status_callback'), // Callback
            'wpcs-poll-settings', // Page slug
            'wpcs_poll_general_section' // Section ID
        );

        add_settings_field(
            'polls_per_page_frontend',
            __('Polls Per Page (Frontend)', 'wpcs-poll'),
            array($this, 'polls_per_page_frontend_callback'),
            'wpcs-poll-settings',
            'wpcs_poll_general_section'
        );

        // Add settings section for Display Settings
        add_settings_section(
            'wpcs_poll_display_section',
            __('Display Settings', 'wpcs-poll'),
            array($this, 'display_section_callback'),
            'wpcs-poll-settings'
        );

        add_settings_field(
            'show_results_link',
            __('Show "View Results" Link', 'wpcs-poll'),
            array($this, 'show_results_link_callback'),
            'wpcs-poll-settings',
            'wpcs_poll_display_section'
        );

        add_settings_field(
            'guest_voting',
            __('Allow Guest Voting', 'wpcs-poll'),
            array($this, 'guest_voting_callback'),
            'wpcs-poll-settings',
            'wpcs_poll_display_section'
        );
    }

    public function sanitize_poll_options($input) {
        $sanitized_input = array();
        if (isset($input['default_poll_status'])) {
            $sanitized_input['default_poll_status'] = in_array($input['default_poll_status'], array('active', 'inactive')) ? $input['default_poll_status'] : 'active';
        }
        if (isset($input['polls_per_page_frontend'])) {
            $sanitized_input['polls_per_page_frontend'] = absint($input['polls_per_page_frontend']);
            if ($sanitized_input['polls_per_page_frontend'] <= 0) $sanitized_input['polls_per_page_frontend'] = 10;
        }
        $sanitized_input['show_results_link'] = isset($input['show_results_link']) ? 1 : 0;
        $sanitized_input['guest_voting'] = isset($input['guest_voting']) ? 1 : 0;

        return $sanitized_input;
    }

    public function general_section_callback() {
        echo '<p>' . esc_html__('Configure general settings for the WPCS Poll plugin.', 'wpcs-poll') . '</p>';
    }

    public function display_section_callback() {
        echo '<p>' . esc_html__('Configure how polls and related elements are displayed on the frontend.', 'wpcs-poll') . '</p>';
    }

    public function default_poll_status_callback() {
        $options = get_option('wpcs_poll_options', array('default_poll_status' => 'active'));
        $status = isset($options['default_poll_status']) ? $options['default_poll_status'] : 'active';
        echo '<select id="default_poll_status" name="wpcs_poll_options[default_poll_status]">';
        echo '<option value="active" ' . selected($status, 'active', false) . '>' . esc_html__('Active', 'wpcs-poll') . '</option>';
        echo '<option value="inactive" ' . selected($status, 'inactive', false) . '>' . esc_html__('Inactive', 'wpcs-poll') . '</option>';
        echo '</select>';
        echo '<p class="description">' . esc_html__('Default status for newly created polls.', 'wpcs-poll') . '</p>';
    }

    public function polls_per_page_frontend_callback() {
        $options = get_option('wpcs_poll_options', array('polls_per_page_frontend' => 10));
        $per_page = isset($options['polls_per_page_frontend']) ? absint($options['polls_per_page_frontend']) : 10;
        echo '<input type="number" id="polls_per_page_frontend" name="wpcs_poll_options[polls_per_page_frontend]" value="' . esc_attr($per_page) . '" min="1" step="1" />';
        echo '<p class="description">' . esc_html__('Number of polls to display per page on frontend listings.', 'wpcs-poll') . '</p>';
    }

    public function show_results_link_callback() {
        $options = get_option('wpcs_poll_options', array('show_results_link' => 1));
        $checked = isset($options['show_results_link']) ? $options['show_results_link'] : 1;
        echo '<input type="checkbox" id="show_results_link" name="wpcs_poll_options[show_results_link]" value="1" ' . checked(1, $checked, false) . ' />';
        echo '<label for="show_results_link"> ' . esc_html__('Display a "View Results" link before voting.', 'wpcs-poll') . '</label>';
    }

    public function guest_voting_callback() {
        $options = get_option('wpcs_poll_options', array('guest_voting' => 0));
        $checked = isset($options['guest_voting']) ? $options['guest_voting'] : 0;
        echo '<input type="checkbox" id="guest_voting" name="wpcs_poll_options[guest_voting]" value="1" ' . checked(1, $checked, false) . ' />';
        echo '<label for="guest_voting"> ' . esc_html__('Allow guests (non-logged-in users) to vote (IP-based tracking).', 'wpcs-poll') . '</label>';
    }
}