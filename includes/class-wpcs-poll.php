<?php
/**
 * Main Plugin Class
 *
 * @package WPCS_Poll
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WPCS_Poll {
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
        require_once WPCS_POLL_PLUGIN_PATH . 'includes/class-wpcs-poll-database.php';
        require_once WPCS_POLL_PLUGIN_PATH . 'includes/class-wpcs-poll-ajax.php';
        require_once WPCS_POLL_PLUGIN_PATH . 'includes/class-wpcs-poll-shortcodes.php';
        require_once WPCS_POLL_PLUGIN_PATH . 'includes/class-wpcs-poll-rest-api.php';
        require_once WPCS_POLL_PLUGIN_PATH . 'admin/class-wpcs-poll-admin.php';
    }

    private function define_admin_hooks() {
        if (is_admin()) {
            $plugin_admin = new WPCS_Poll_Admin($this->plugin_name, $this->version);
            add_action('admin_enqueue_scripts', array($plugin_admin, 'enqueue_styles'));
            add_action('admin_enqueue_scripts', array($plugin_admin, 'enqueue_scripts'));
            add_action('admin_menu', array($plugin_admin, 'add_admin_menu'));
        }
    }

    private function define_public_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        add_action('init', array($this, 'init_components'));
        add_action('wp_head', array($this, 'add_debug_info'));
    }

    public function enqueue_public_assets() {
        wp_enqueue_style($this->plugin_name, WPCS_POLL_PLUGIN_URL . 'public/css/wpcs-poll-public.css', array(), $this->version);
        wp_enqueue_script($this->plugin_name, WPCS_POLL_PLUGIN_URL . 'public/js/wpcs-poll-public.js', array('jquery'), $this->version, true);
        
        // Enhanced localization with better debugging
        $localize_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpcs_poll_vote_nonce'),
            'rest_url' => rest_url('wpcs-poll/v1/'),
            'user_id' => get_current_user_id(),
            'is_logged_in' => is_user_logged_in(),
            'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
            'plugin_version' => $this->version
        );
        
        // Add debug information if debug mode is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $localize_data['debug_info'] = array(
                'current_user' => wp_get_current_user()->user_login ?? 'not logged in',
                'user_roles' => wp_get_current_user()->roles ?? array(),
                'site_url' => site_url(),
                'admin_url' => admin_url(),
                'nonce_life' => apply_filters('nonce_life', DAY_IN_SECONDS)
            );
        }
        
        wp_localize_script($this->plugin_name, 'wpcs_poll_ajax', $localize_data);
        
        // Add inline script for immediate debugging
        $inline_script = "
        console.log('WPCS Poll Debug: Script loaded');
        console.log('WPCS Poll Debug: wpcs_poll_ajax object will be:', " . json_encode($localize_data) . ");
        ";
        wp_add_inline_script($this->plugin_name, $inline_script, 'before');
    }

    public function add_debug_info() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            echo "<!-- WPCS Poll Debug Info -->\n";
            echo "<!-- User ID: " . get_current_user_id() . " -->\n";
            echo "<!-- Is Logged In: " . (is_user_logged_in() ? 'Yes' : 'No') . " -->\n";
            echo "<!-- Current User: " . (wp_get_current_user()->user_login ?? 'None') . " -->\n";
            echo "<!-- Nonce: " . wp_create_nonce('wpcs_poll_vote_nonce') . " -->\n";
            echo "<!-- AJAX URL: " . admin_url('admin-ajax.php') . " -->\n";
            echo "<!-- Plugin Version: " . $this->version . " -->\n";
            echo "<!-- End WPCS Poll Debug Info -->\n";
        }
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