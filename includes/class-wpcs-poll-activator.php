<?php

class WPCS_Poll_Activator {

    public static function activate() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        // Table: wp_wpcs_polls
        $table_name_polls = $wpdb->prefix . 'wpcs_polls';
        $sql_polls = "CREATE TABLE $table_name_polls (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text DEFAULT NULL,
            category varchar(100) DEFAULT 'General',
            options longtext NOT NULL, -- JSON encoded options
            tags text DEFAULT NULL, -- Comma-separated tags
            is_active tinyint(1) DEFAULT 0,
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY created_by (created_by),
            KEY category (category),
            KEY is_active (is_active)
        ) $charset_collate;";
        dbDelta($sql_polls);

        // Table: wp_wpcs_poll_votes
        $table_name_votes = $wpdb->prefix . 'wpcs_poll_votes';
        $sql_votes = "CREATE TABLE $table_name_votes (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            poll_id bigint(20) NOT NULL,
            option_id varchar(50) NOT NULL,
            ip_address varchar(45) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_vote (user_id, poll_id),
            KEY poll_id (poll_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta($sql_votes);

        // Table: wp_wpcs_poll_bookmarks
        $table_name_bookmarks = $wpdb->prefix . 'wpcs_poll_bookmarks';
        $sql_bookmarks = "CREATE TABLE $table_name_bookmarks (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            poll_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_bookmark (user_id, poll_id)
        ) $charset_collate;";
        dbDelta($sql_bookmarks);

        // Table: wp_wpcs_poll_bulk_uploads
        $table_name_bulk_uploads = $wpdb->prefix . 'wpcs_poll_bulk_uploads';
        $sql_bulk_uploads = "CREATE TABLE $table_name_bulk_uploads (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            filename varchar(255) NOT NULL,
            total_records int(11) DEFAULT 0,
            successful_imports int(11) DEFAULT 0,
            failed_imports int(11) DEFAULT 0,
            status varchar(20) DEFAULT 'pending', -- pending|processing|completed|failed
            error_log longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql_bulk_uploads);

        // Add user meta fields
        $users = get_users();
        foreach ($users as $user) {
            if (!get_user_meta($user->ID, 'wpcs_poll_bio', true)) {
                add_user_meta($user->ID, 'wpcs_poll_bio', '');
            }
            if (!get_user_meta($user->ID, 'wpcs_poll_location', true)) {
                add_user_meta($user->ID, 'wpcs_poll_location', '');
            }
            if (!get_user_meta($user->ID, 'wpcs_poll_website', true)) {
                add_user_meta($user->ID, 'wpcs_poll_website', '');
            }
            if (!get_user_meta($user->ID, 'wpcs_poll_role', true)) {
                // Check if user is administrator
                if (in_array('administrator', (array) $user->roles)) {
                    add_user_meta($user->ID, 'wpcs_poll_role', 'admin');
                } else {
                    add_user_meta($user->ID, 'wpcs_poll_role', 'user');
                }
            }
        }
    }
}
