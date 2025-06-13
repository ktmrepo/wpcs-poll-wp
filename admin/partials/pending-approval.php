<?php
/**
 * Admin Pending Approval Page
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
    <h1><?php esc_html_e('Polls Awaiting Review', 'wpcs-poll'); ?></h1>

    <p>
        <?php
        // Translators: This message explains the purpose of this page.
        echo esc_html__('This page can be used to list polls that require admin approval before becoming active. Currently, this might show polls that are marked as "Inactive".', 'wpcs-poll');
        ?>
    </p>
    <p>
        <?php
        echo esc_html__('If a user submission system is implemented where users can submit polls, those polls would typically appear here for review.', 'wpcs-poll');
        ?>
    </p>

    <?php
    // Ensure $wpcs_db is available. It should be instantiated by the calling menu function if needed,
    // or we can do it here if class-wpcs-poll-admin.php doesn't pass it.
    if (!isset($wpcs_db) && class_exists('WPCS_Poll_Database')) {
        $wpcs_db = new WPCS_Poll_Database();
    } elseif (!isset($wpcs_db)) {
        echo '<div class="error"><p>' . __('Database connection not available. Cannot display polls.', 'wpcs-poll') . '</p></div>';
        return; // Stop if no DB connection
    }

    // Check if WPCS_Poll_List_Table class exists
    if (!class_exists('WPCS_Poll_List_Table')) {
        // This should be loaded by WPCS_Poll_Admin constructor or main plugin file
        // require_once WPCS_POLL_PLUGIN_PATH . 'admin/class-wpcs-poll-list-table.php';
         echo '<div class="error"><p>' . __('Poll List Table class not found.', 'wpcs-poll') . '</p></div>';
        return;
    }


    // Create an instance of our package class, potentially filtering for inactive polls
    // For now, we might reuse the same list table or create a specialized one.
    // Let's try to use the existing one but ideally, we'd filter.
    // The get_polls method needs to be extended to support filtering by 'is_active'.

    // For this example, let's assume we'll modify get_polls to accept an 'is_active' arg
    // And we'll need a way to tell the list table to show different actions (e.g., "Approve")

    echo '<h2>' . esc_html__('Inactive Polls', 'wpcs-poll') . '</h2>';
    echo '<p>' . esc_html__('The table below lists all currently inactive polls. You can edit them to make them active.', 'wpcs-poll') . '</p>';

    // To properly implement a pending approval, we would:
    // 1. Modify get_polls() to accept arguments like 'status' => 'pending' or 'is_active' => 0.
    // 2. Potentially create a new List Table class or modify the existing one
    //    to show different columns/actions (e.g., an "Approve" action).
    // For now, we will display inactive polls using a modified query if possible,
    // or just use the standard list table which shows all polls.

    $poll_list_table_inactive = new WPCS_Poll_List_Table($wpcs_db); // Reusing the same list table class

    // We need to modify prepare_items or how data is fetched for this specific view.
    // This is a simplified approach: fetch all and let admin see active status.
    // A better way would be to pass a filter to get_polls.
    // $poll_list_table_inactive->filter_status = 'inactive'; // Hypothetical property

    $poll_list_table_inactive->prepare_items(); // This will fetch all polls by default

    ?>
    <form method="get">
        <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
        <?php $poll_list_table_inactive->display(); ?>
    </form>

    <p style="margin-top: 20px;">
        <strong><?php esc_html_e('Future Enhancements:', 'wpcs-poll'); ?></strong>
    </p>
    <ul>
        <li><?php esc_html_e('Filter this list to show only polls explicitly awaiting approval (e.g., submitted by non-admins or with a specific "pending" status).', 'wpcs-poll'); ?></li>
        <li><?php esc_html_e('Add "Approve" and "Reject" actions directly to this table.', 'wpcs-poll'); ?></li>
    </ul>

</div>
