<?php
/**
 * Admin User Management Page
 *
 * @package WPCS_Poll
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="wrap wpcs-poll-admin-page">
    <h1><?php esc_html_e('User Management (WPCS Poll)', 'wpcs-poll'); ?></h1>

    <p>
        <?php esc_html_e('This page allows you to manage user roles specific to the WPCS Poll plugin and view poll-related user activity.', 'wpcs-poll'); ?>
    </p>
    <p>
        <?php esc_html_e('Currently, it focuses on the custom "WPCS Poll Role" (user/admin) defined by this plugin.', 'wpcs-poll'); ?>
    </p>

    <?php
    // TODO: Implement WP_List_Table for users, showing:
    // - Username
    // - Email
    // - WordPress Role(s)
    // - WPCS Poll Role (editable)
    // - Number of polls created (if applicable)
    // - Number of votes cast

    // Example:
    // if (!class_exists('WPCS_Poll_User_List_Table')) {
    //     require_once WPCS_POLL_PLUGIN_PATH . 'admin/class-wpcs-poll-user-list-table.php'; // To be created
    // }
    // $user_list_table = new WPCS_Poll_User_List_Table();
    // $user_list_table->prepare_items();
    // $user_list_table->display();
    ?>
    <p><em><?php esc_html_e('User list table (WP_List_Table for users) placeholder.', 'wpcs-poll'); ?></em></p>
    <p><em><?php esc_html_e('This table would display users along with their WPCS Poll specific role (e.g., "user" or "admin" for plugin features) and allow modification of this role.', 'wpcs-poll'); ?></em></p>
    <p><em><?php esc_html_e('The "WPCS Poll Role" is stored in user meta (wpcs_poll_role) and was added during plugin activation.', 'wpcs-poll'); ?></em></p>

    <h2><?php esc_html_e('Notes on WPCS Poll Roles:', 'wpcs-poll'); ?></h2>
    <ul>
        <li><strong><?php esc_html_e('User:', 'wpcs-poll'); ?></strong> <?php esc_html_e('Standard user of the polling system. Can vote, view polls, etc., based on plugin settings.', 'wpcs-poll'); ?></li>
        <li><strong><?php esc_html_e('Admin (WPCS Poll):', 'wpcs-poll'); ?></strong> <?php esc_html_e('User with administrative privileges within the WPCS Poll system. This is separate from WordPress site administrator roles but could grant access to manage polls or view all results, depending on how features are built out. By default, WordPress administrators are assigned this role on plugin activation.', 'wpcs-poll'); ?></li>
    </ul>
</div>
