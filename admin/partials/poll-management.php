<?php
/**
 * Admin Poll Management Page
 *
 * @package WPCS_Poll
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Instantiate the database class (similar to dashboard)
if (!isset($wpcs_db)) {
    if (class_exists('WPCS_Poll_Database')) {
        $wpcs_db = new WPCS_Poll_Database();
    } else {
        echo '<div class="error"><p>Error: WPCS_Poll_Database class not found.</p></div>';
        return;
    }
}

// Check for actions (add, edit, delete)
$action = isset($_GET['action']) ? sanitize_key($_GET['action']) : '';
$poll_id = isset($_GET['poll_id']) ? absint($_GET['poll_id']) : 0;

// Simple message display
$message = '';
if (isset($_GET['message'])) {
    if ($_GET['message'] === 'poll_added') {
        $message = __('Poll added successfully.', 'wpcs-poll');
    } elseif ($_GET['message'] === 'poll_updated') {
        $message = __('Poll updated successfully.', 'wpcs-poll');
    } elseif ($_GET['message'] === 'poll_deleted') {
        $message = __('Poll deleted successfully.', 'wpcs-poll');
    }
    if ($message) {
        echo '<div class="updated"><p>' . esc_html($message) . '</p></div>';
    }
}


// If 'add_new' or 'edit' action, show the form. Otherwise, show the table.
if ('add_new' === $action || ('edit' === $action && $poll_id)) {
    // Include the form partial for adding/editing polls
    include_once WPCS_POLL_PLUGIN_PATH . 'admin/partials/poll-form.php';

    if ('edit' === $action && $poll_id) {
        // $poll = $wpcs_db->get_poll($poll_id); // Fetch poll data for editing
        // if (!$poll) {
        //     echo '<div class="error"><p>' . __('Poll not found for editing.', 'wpcs-poll') . '</p></div>';
        //     return;
        // }
        // The actual data loading is handled within poll-form.php for now
    }

} else {
    // Display the table of polls
?>
    <div class="wrap wpcs-poll-admin-page">
        <h1>
            <?php esc_html_e('Manage Polls', 'wpcs-poll'); ?>
            <a href="<?php echo admin_url('admin.php?page=wpcs-poll-manage&action=add_new'); ?>" class="page-title-action">
                <?php esc_html_e('Add New Poll', 'wpcs-poll'); ?>
            </a>
        </h1>

        <?php
        // Ensure the $wpcs_db variable is available from the earlier check in this file.
        if (isset($wpcs_db) && $wpcs_db instanceof WPCS_Poll_Database) {
            // Create an instance of our package class...
            $poll_list_table = new WPCS_Poll_List_Table($wpcs_db);
            // Fetch, prepare, sort, and filter our data...
            $poll_list_table->prepare_items();
            ?>
            <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
            <form method="get">
                <!-- For plugins, we also need to ensure that the form posts back to our current page -->
                <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
                <?php
                // Now we can render the completed list table
                $poll_list_table->display();
                ?>
            </form>
        <?php
        } else {
            echo '<div class="error"><p>' . __('Database connection not available. Cannot display polls.', 'wpcs-poll') . '</p></div>';
        }
        ?>

    </div>
<?php
} // End if/else for action
?>
