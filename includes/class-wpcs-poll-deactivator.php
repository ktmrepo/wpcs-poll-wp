<?php
/**
 * Plugin Deactivator
 *
 * @package WPCS_Poll
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WPCS_Poll_Deactivator {

    /**
     * Plugin deactivation handler
     */
    public static function deactivate() {
        // Clear any scheduled events
        wp_clear_scheduled_hook('wpcs_poll_cleanup');
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Clear any cached data
        self::clear_cache();
    }

    /**
     * Clear plugin cache
     */
    private static function clear_cache() {
        // Clear any transients
        delete_transient('wpcs_poll_stats');
        delete_transient('wpcs_poll_popular_categories');
        
        // Clear any object cache if needed
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }
}