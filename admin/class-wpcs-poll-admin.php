<?php
class WPCS_Poll_Admin {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
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
}