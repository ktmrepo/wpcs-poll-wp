<?php
/**
 * Admin Settings Page
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
    <h1><?php esc_html_e('WPCS Poll Settings', 'wpcs-poll'); ?></h1>

    <?php
    // Display WordPress settings errors/messages (e.g., "Settings saved.")
    settings_errors();
    ?>

    <form method="post" action="options.php">
        <?php
        // Output nonce, action, and option_page fields for our settings page.
        // The 'wpcs_poll_settings_group' is the option group we registered.
        settings_fields('wpcs_poll_settings_group');

        // Output settings sections and their fields.
        // The 'wpcs-poll-settings' is the page slug we used in add_settings_section and add_settings_field.
        do_settings_sections('wpcs-poll-settings');

        // Output save settings button
        submit_button(__('Save Poll Settings', 'wpcs-poll'));
        ?>
    </form>
</div>
