<?php
/**
 * Plugin Uninstaller
 *
 * @package WPCS_Poll
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WPCS_Poll_Uninstaller {

    /**
     * Plugin uninstall handler
     */
    public static function uninstall() {
        // Check if user wants to delete data
        $options = get_option('wpcs_poll_options', array());
        
        if (isset($options['delete_data_on_uninstall']) && $options['delete_data_on_uninstall']) {
            self::delete_tables();
            self::delete_options();
        }
    }

    /**
     * Delete all plugin tables
     */
    private static function delete_tables() {
        global $wpdb;

        $tables = array(
            $wpdb->prefix . 'wpcs_polls',
            $wpdb->prefix . 'wpcs_poll_votes',
            $wpdb->prefix . 'wpcs_poll_bookmarks',
            $wpdb->prefix . 'wpcs_poll_bulk_uploads'
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }

    /**
     * Delete all plugin options
     */
    private static function delete_options() {
        delete_option('wpcs_poll_options');
        delete_option('wpcs_poll_version');
        delete_option('wpcs_poll_categories');
        
        // Delete any transients
        delete_transient('wpcs_poll_stats');
        delete_transient('wpcs_poll_popular_categories');
    }
}