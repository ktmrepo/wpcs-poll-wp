<?php
/**
 * Plugin Name: WPCS Poll
 * Plugin URI: https://yoursite.com/wpcs-poll
 * Description: TikTok-style interactive polling system with comprehensive admin management
 * Version: 1.2.1
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: wpcs-poll
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WPCS_POLL_VERSION', '1.2.1');
define('WPCS_POLL_BUILD_DATE', '2024-12-19 16:45:00');
define('WPCS_POLL_BUILD_NUMBER', '20241219164500');
define('WPCS_POLL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPCS_POLL_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Include required files
require_once WPCS_POLL_PLUGIN_PATH . 'includes/class-wpcs-poll-activator.php';
require_once WPCS_POLL_PLUGIN_PATH . 'includes/class-wpcs-poll-deactivator.php';
require_once WPCS_POLL_PLUGIN_PATH . 'includes/class-wpcs-poll-uninstaller.php';
require_once WPCS_POLL_PLUGIN_PATH . 'includes/class-wpcs-poll.php';

// Activation and deactivation hooks
register_activation_hook(__FILE__, array('WPCS_Poll_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('WPCS_Poll_Deactivator', 'deactivate'));
register_uninstall_hook(__FILE__, array('WPCS_Poll_Uninstaller', 'uninstall'));

// Initialize the plugin
function run_wpcs_poll() {
    $plugin = new WPCS_Poll();
    $plugin->run();
}

// Start the plugin
add_action('plugins_loaded', 'run_wpcs_poll');

// Add version check and update notification
add_action('admin_notices', 'wpcs_poll_version_notice');

function wpcs_poll_version_notice() {
    $current_version = get_option('wpcs_poll_version', '0.0.0');
    
    if (version_compare($current_version, WPCS_POLL_VERSION, '<')) {
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>WPCS Poll Updated!</strong> Version ' . WPCS_POLL_VERSION . ' is now active. ';
        echo '<a href="' . admin_url('admin.php?page=wpcs-poll-settings') . '">View version details</a></p>';
        echo '</div>';
        
        // Update stored version
        update_option('wpcs_poll_version', WPCS_POLL_VERSION);
        update_option('wpcs_poll_build_date', WPCS_POLL_BUILD_DATE);
        update_option('wpcs_poll_build_number', WPCS_POLL_BUILD_NUMBER);
    }
}