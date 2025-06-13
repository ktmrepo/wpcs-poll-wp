<?php
class WPCS_Poll_Admin {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        add_action('admin_post_wpcs_save_poll', array($this, 'handle_save_poll'));
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
        $db = new WPCS_Poll_Database();

        $result = false;
        if ($poll_id > 0) {
            // Update existing poll
            // $result = $db->update_poll($poll_id, $poll_data);
            // For now, simulate success
            $result = true;
            $message_type = 'poll_updated';
        } else {
            // Create new poll
            // $poll_data['created_by'] = get_current_user_id();
            // $result = $db->create_poll($poll_data);
            // For now, simulate success and getting a new ID
            $result = rand(1, 100);
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
}