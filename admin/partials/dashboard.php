<?php
/**
 * Admin Dashboard Page
 *
 * @package WPCS_Poll
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Instantiate the database class if not already available
// This might be better handled by passing it from the main admin class or using a global
if (!isset($wpcs_db)) {
    // Assuming class-wpcs-poll-database.php is loaded and WPCS_Poll_Database class exists
    // This is a simplified approach for now.
    if (class_exists('WPCS_Poll_Database')) {
        $wpcs_db = new WPCS_Poll_Database();
    } else {
        // Handle error: Database class not found
        echo '<div class="error"><p>Error: WPCS_Poll_Database class not found.</p></div>';
        return;
    }
}

// Placeholder data - to be replaced with actual data from $wpcs_db methods
$total_polls = 0; // Example: $wpcs_db->get_polls_count();
$total_votes = 0; // Example: $wpcs_db->get_votes_count();
$active_polls = 0; // Example: $wpcs_db->get_polls_count(array('is_active' => 1));

?>

<div class="wrap wpcs-poll-admin-page">
    <h1><?php esc_html_e('WPCS Poll Dashboard', 'wpcs-poll'); ?></h1>

    <div id="dashboard-widgets-wrap">
        <div id="dashboard-widgets" class="metabox-holder">
            <div id="postbox-container-1" class="postbox-container">
                <div class="meta-box-sortables">

                    <div class="postbox">
                        <h2 class="hndle"><span><?php esc_html_e('Overview', 'wpcs-poll'); ?></span></h2>
                        <div class="inside">
                            <ul>
                                <li>
                                    <?php printf(
                                        /* translators: %s: Number of polls */
                                        esc_html__('Total Polls: %s', 'wpcs-poll'),
                                        '<strong>' . esc_html($total_polls) . '</strong>'
                                    ); ?>
                                </li>
                                <li>
                                    <?php printf(
                                        /* translators: %s: Number of votes */
                                        esc_html__('Total Votes Cast: %s', 'wpcs-poll'),
                                        '<strong>' . esc_html($total_votes) . '</strong>'
                                    ); ?>
                                </li>
                                <li>
                                    <?php printf(
                                        /* translators: %s: Number of active polls */
                                        esc_html__('Active Polls: %s', 'wpcs-poll'),
                                        '<strong>' . esc_html($active_polls) . '</strong>'
                                    ); ?>
                                </li>
                            </ul>
                            <p>
                                <?php esc_html_e('More statistics and insights will be available here soon.', 'wpcs-poll'); ?>
                            </p>
                        </div>
                    </div>

                    <div class="postbox">
                        <h2 class="hndle"><span><?php esc_html_e('Quick Actions', 'wpcs-poll'); ?></span></h2>
                        <div class="inside">
                            <p>
                                <a href="<?php echo admin_url('admin.php?page=wpcs-poll-manage&action=add_new'); ?>" class="button button-primary">
                                    <?php esc_html_e('Create New Poll', 'wpcs-poll'); ?>
                                </a
                            </p>
                            <p>
                                <a href="<?php echo admin_url('admin.php?page=wpcs-poll-manage'); ?>" class="button">
                                    <?php esc_html_e('Manage All Polls', 'wpcs-poll'); ?>
                                </a
                            </p>
                        </div>
                    </div>

                </div>
            </div>

            <div id="postbox-container-2" class="postbox-container">
                <div class="meta-box-sortables">
                    <!-- Additional widgets can go here -->
                     <div class="postbox">
                        <h2 class="hndle"><span><?php esc_html_e('Recent Activity (Placeholder)', 'wpcs-poll'); ?></span></h2>
                        <div class="inside">
                            <p><?php esc_html_e('Recent votes and poll submissions will be shown here.', 'wpcs-poll'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
