<?php
/**
 * Plugin Name: WPCS Poll
 * Plugin URI: https://yoursite.com/wpcs-poll
 * Description: TikTok-style interactive polling system with comprehensive admin management
 * Version: 1.0.0
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
define('WPCS_POLL_VERSION', '1.0.0');
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