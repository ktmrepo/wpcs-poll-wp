<?php
/**
 * Plugin Activator
 *
 * @package WPCS_Poll
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WPCS_Poll_Activator {

    /**
     * Plugin activation handler
     */
    public static function activate() {
        self::create_tables();
        self::set_default_options();
        self::create_default_categories();
        self::record_installation_info();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Polls table
        $polls_table = $wpdb->prefix . 'wpcs_polls';
        $polls_sql = "CREATE TABLE $polls_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            category varchar(100) DEFAULT 'General',
            options longtext NOT NULL,
            tags text,
            is_active tinyint(1) DEFAULT 0,
            created_by bigint(20) unsigned,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY created_by (created_by),
            KEY is_active (is_active),
            KEY category (category),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Votes table
        $votes_table = $wpdb->prefix . 'wpcs_poll_votes';
        $votes_sql = "CREATE TABLE $votes_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned DEFAULT 0,
            poll_id bigint(20) unsigned NOT NULL,
            option_id varchar(50) NOT NULL,
            ip_address varchar(45),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_user_poll (user_id, poll_id),
            KEY poll_id (poll_id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Bookmarks table
        $bookmarks_table = $wpdb->prefix . 'wpcs_poll_bookmarks';
        $bookmarks_sql = "CREATE TABLE $bookmarks_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            poll_id bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_user_poll_bookmark (user_id, poll_id),
            KEY user_id (user_id),
            KEY poll_id (poll_id)
        ) $charset_collate;";

        // Bulk uploads table
        $bulk_uploads_table = $wpdb->prefix . 'wpcs_poll_bulk_uploads';
        $bulk_uploads_sql = "CREATE TABLE $bulk_uploads_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            filename varchar(255) NOT NULL,
            file_type varchar(10) NOT NULL,
            total_records int(11) DEFAULT 0,
            successful_records int(11) DEFAULT 0,
            failed_records int(11) DEFAULT 0,
            status varchar(20) DEFAULT 'processing',
            error_log longtext,
            uploaded_by bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY uploaded_by (uploaded_by),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($polls_sql);
        dbDelta($votes_sql);
        dbDelta($bookmarks_sql);
        dbDelta($bulk_uploads_sql);
    }

    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        $default_options = array(
            'guest_voting' => 0,
            'auto_approve_polls' => 0,
            'require_login_to_create' => 1,
            'max_options_per_poll' => 10,
            'default_category' => 'General',
            'enable_poll_comments' => 0,
            'enable_poll_sharing' => 1,
            'polls_per_page' => 10,
            'enable_analytics' => 1,
            'delete_data_on_uninstall' => 0
        );

        add_option('wpcs_poll_options', $default_options);
        add_option('wpcs_poll_version', WPCS_POLL_VERSION);
    }

    /**
     * Create default categories
     */
    private static function create_default_categories() {
        $default_categories = array(
            'General',
            'Technology',
            'Entertainment',
            'Sports',
            'Politics',
            'Science',
            'Health',
            'Education',
            'Business',
            'Lifestyle'
        );

        // Store categories as an option for easy management
        add_option('wpcs_poll_categories', $default_categories);
    }

    /**
     * Record installation information
     */
    private static function record_installation_info() {
        // Record installation date if not already set
        if (!get_option('wpcs_poll_install_date')) {
            add_option('wpcs_poll_install_date', current_time('mysql'));
        }
        
        // Update version information
        update_option('wpcs_poll_version', WPCS_POLL_VERSION);
        update_option('wpcs_poll_build_date', WPCS_POLL_BUILD_DATE);
        update_option('wpcs_poll_build_number', WPCS_POLL_BUILD_NUMBER);
        
        // Record activation timestamp
        update_option('wpcs_poll_last_activation', current_time('timestamp'));
    }
}