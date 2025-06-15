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
    // Ensure the WPCS_Poll_User_List_Table class is available (it should be loaded by WPCS_Poll_Admin)
    if (class_exists('WPCS_Poll_User_List_Table')) {
        // Create an instance of our package class...
        $user_list_table = new WPCS_Poll_User_List_Table();
        // Fetch, prepare, sort, and filter our data...
        $user_list_table->prepare_items();
        ?>
        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <!-- For role updates, we might use AJAX or a form that posts to admin-post.php -->
        <form method="post" id="wpcs-user-management-form">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
            <?php
            // Potentially add nonce fields here if we were to submit this form for bulk role changes.
            // For individual role changes via AJAX or separate actions, this form might not be strictly needed for submission.

            // Now we can render the completed list table
            $user_list_table->display();
            ?>
        </form>
    <?php
    } else {
        echo '<div class="error"><p>' . __('User List Table class not found. Please ensure it is loaded correctly.', 'wpcs-poll') . '</p></div>';
    }
    ?>

    <h2><?php esc_html_e('Notes on WPCS Poll Roles:', 'wpcs-poll'); ?></h2>
    <ul>
        <li><strong><?php esc_html_e('User:', 'wpcs-poll'); ?></strong> <?php esc_html_e('Standard user of the polling system. Can vote, view polls, etc., based on plugin settings.', 'wpcs-poll'); ?></li>
        <li><strong><?php esc_html_e('Admin (WPCS Poll):', 'wpcs-poll'); ?></strong> <?php esc_html_e('User with administrative privileges within the WPCS Poll system. This is separate from WordPress site administrator roles but could grant access to manage polls or view all results, depending on how features are built out. By default, WordPress administrators are assigned this role on plugin activation.', 'wpcs-poll'); ?></li>
    </ul>
</div>
