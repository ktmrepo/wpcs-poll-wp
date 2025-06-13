<?php
/**
 * Admin Poll Form (for Add New and Edit)
 *
 * @package WPCS_Poll
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Values for the form fields
$poll_title = '';
$poll_description = '';
$poll_category = 'General';
$poll_options = array(array('id' => 'opt1', 'text' => ''), array('id' => 'opt2', 'text' => '')); // Default two empty options
$poll_tags = '';
$poll_is_active = 0;
$current_poll_id = 0;

$is_editing = false;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['poll_id'])) {
    $is_editing = true;
    $current_poll_id = absint($_GET['poll_id']);
    // TODO: Load existing poll data using $wpcs_db->get_poll($current_poll_id)
    // For now, we'll just simulate it for structure
    // Example:
    // $poll = $wpcs_db->get_poll($current_poll_id);
    // if ($poll) {
    //     $poll_title = $poll->title;
    //     $poll_description = $poll->description;
    //     $poll_category = $poll->category;
    //     $poll_options = json_decode($poll->options, true); // Assuming options are stored as JSON
    //     $poll_tags = $poll->tags;
    //     $poll_is_active = $poll->is_active;
    // } else {
    //     echo '<div class="error"><p>' . __('Poll not found.', 'wpcs-poll') . '</p></div>';
    //     return; // Stop if poll not found
    // }
    echo '<p><em>Simulating edit mode for Poll ID: ' . esc_html($current_poll_id) . '. Data would be pre-filled here.</em></p>';
}

?>
<div class="wrap wpcs-poll-admin-page">
    <h1>
        <?php echo $is_editing ? esc_html__('Edit Poll', 'wpcs-poll') : esc_html__('Add New Poll', 'wpcs-poll'); ?>
    </h1>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="wpcs_save_poll">
        <input type="hidden" name="poll_id" value="<?php echo esc_attr($current_poll_id); ?>">
        <?php wp_nonce_field('wpcs_save_poll_nonce', '_wpcs_nonce'); ?>

        <table class="form-table">
            <tbody>
                <!-- Title -->
                <tr>
                    <th scope="row">
                        <label for="poll_title"><?php esc_html_e('Title', 'wpcs-poll'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="poll_title" name="poll_title" class="regular-text" value="<?php echo esc_attr($poll_title); ?>" required>
                        <p class="description"><?php esc_html_e('The main title for your poll.', 'wpcs-poll'); ?></p>
                    </td>
                </tr>

                <!-- Description -->
                <tr>
                    <th scope="row">
                        <label for="poll_description"><?php esc_html_e('Description', 'wpcs-poll'); ?></label>
                    </th>
                    <td>
                        <textarea id="poll_description" name="poll_description" class="large-text" rows="5"><?php echo esc_textarea($poll_description); ?></textarea>
                        <p class="description"><?php esc_html_e('A brief description of the poll (optional).', 'wpcs-poll'); ?></p>
                    </td>
                </tr>

                <!-- Category -->
                <tr>
                    <th scope="row">
                        <label for="poll_category"><?php esc_html_e('Category', 'wpcs-poll'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="poll_category" name="poll_category" value="<?php echo esc_attr($poll_category); ?>">
                        <p class="description"><?php esc_html_e('Assign a category to the poll (e.g., Technology, Sports). Default: General.', 'wpcs-poll'); ?></p>
                    </td>
                </tr>

                <!-- Options -->
                <tr id="poll-options-row">
                    <th scope="row">
                        <?php esc_html_e('Poll Options', 'wpcs-poll'); ?>
                    </th>
                    <td id="poll-options-container">
                        <?php foreach ($poll_options as $index => $option_item) : ?>
                            <div class="poll-option-item" style="margin-bottom: 10px;">
                                <input type="text" name="poll_options[<?php echo $index; ?>][text]" value="<?php echo esc_attr($option_item['text']); ?>" placeholder="<?php esc_attr_e('Option Text', 'wpcs-poll'); ?>" required>
                                <input type="hidden" name="poll_options[<?php echo $index; ?>][id]" value="<?php echo esc_attr($option_item['id']); // Important for existing options ?>">
                                <?php if ($index > 1) : // Allow removing options beyond the first two ?>
                                    <button type="button" class="button button-small wpcs-remove-option"><?php esc_html_e('Remove', 'wpcs-poll'); ?></button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <button type="button" id="wpcs-add-option" class="button"><?php esc_html_e('Add Option', 'wpcs-poll'); ?></button>
                        <p class="description"><?php esc_html_e('Define the choices for your poll. At least two options are required.', 'wpcs-poll'); ?></p>
                        <p class="description"><?php esc_html_e('Note: Dynamic adding/removing options will require JavaScript (to be implemented).', 'wpcs-poll'); ?></p>
                    </td>
                </tr>

                <!-- Tags -->
                <tr>
                    <th scope="row">
                        <label for="poll_tags"><?php esc_html_e('Tags', 'wpcs-poll'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="poll_tags" name="poll_tags" class="regular-text" value="<?php echo esc_attr($poll_tags); ?>">
                        <p class="description"><?php esc_html_e('Comma-separated tags for easier filtering and searching (optional).', 'wpcs-poll'); ?></p>
                    </td>
                </tr>

                <!-- Is Active -->
                <tr>
                    <th scope="row">
                        <label for="poll_is_active"><?php esc_html_e('Status', 'wpcs-poll'); ?></label>
                    </th>
                    <td>
                        <select id="poll_is_active" name="poll_is_active">
                            <option value="1" <?php selected($poll_is_active, 1); ?>><?php esc_html_e('Active', 'wpcs-poll'); ?></option>
                            <option value="0" <?php selected($poll_is_active, 0); ?>><?php esc_html_e('Inactive', 'wpcs-poll'); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e('Set whether the poll is currently active and open for voting.', 'wpcs-poll'); ?></p>
                    </td>
                </tr>

            </tbody>
        </table>

        <?php submit_button($is_editing ? __('Update Poll', 'wpcs-poll') : __('Create Poll', 'wpcs-poll')); ?>
    </form>
</div>
