<?php
class WPCS_Poll {
    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->plugin_name = 'wpcs-poll';
        $this->version = WPCS_POLL_VERSION;
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once WPCS_POLL_PLUGIN_PATH . 'includes/class-wpcs-poll-activator.php';
        require_once WPCS_POLL_PLUGIN_PATH . 'includes/class-wpcs-poll-deactivator.php';
        require_once WPCS_POLL_PLUGIN_PATH . 'includes/class-wpcs-poll-database.php';
        require_once WPCS_POLL_PLUGIN_PATH . 'includes/class-wpcs-poll-ajax.php';
        require_once WPCS_POLL_PLUGIN_PATH . 'includes/class-wpcs-poll-shortcodes.php';
        require_once WPCS_POLL_PLUGIN_PATH . 'includes/class-wpcs-poll-rest-api.php';
        require_once WPCS_POLL_PLUGIN_PATH . 'admin/class-wpcs-poll-admin.php';
    }

    private function define_admin_hooks() {
        $plugin_admin = new WPCS_Poll_Admin($this->plugin_name, $this->version);
        add_action('admin_enqueue_scripts', array($plugin_admin, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($plugin_admin, 'enqueue_scripts'));
        add_action('admin_menu', array($plugin_admin, 'add_admin_menu'));
    }

    private function define_public_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        add_action('init', array($this, 'init_components'));
    }

    public function enqueue_public_assets() {
        wp_enqueue_style($this->plugin_name, WPCS_POLL_PLUGIN_URL . 'public/css/wpcs-poll-public.css', array(), $this->version);
        wp_enqueue_script($this->plugin_name, WPCS_POLL_PLUGIN_URL . 'public/js/wpcs-poll-public.js', array('jquery'), $this->version, true);
        
        // Localize script for AJAX
        wp_localize_script($this->plugin_name, 'wpcs_poll_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpcs_poll_nonce'),
            'rest_url' => rest_url('wpcs-poll/v1/')
        ));
    }

    public function init_components() {
        new WPCS_Poll_AJAX();
        new WPCS_Poll_Shortcodes();
        new WPCS_Poll_REST_API();
    }

    public function run() {
        // Plugin initialization complete
    }
}