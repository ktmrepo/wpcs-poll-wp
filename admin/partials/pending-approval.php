<?php
/**
 * Pending Approval Admin Page
 *
 * @package WPCS_Poll
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Handle approval actions
if (isset($_POST['action'])) {
    $poll_id = intval($_POST['poll_id']);
    $action = sanitize_text_field($_POST['action']);
    
    if ($poll_id && in_array($action, ['approve', 'reject'])) {
        if ($action === 'approve') {
            $wpdb->update(
                $wpdb->prefix . 'wpcs_polls',
                ['is_active' => 1],
                ['id' => $poll_id]
            );
            echo '<div class="notice notice-success"><p>' . __('Poll approved successfully.', 'wpcs-poll') . '</p></div>';
        } else {
            $wpdb->delete(
                $wpdb->prefix . 'wpcs_polls',
                ['id' => $poll_id]
            );
            echo '<div class="notice notice-success"><p>' . __('Poll rejected and deleted.', 'wpcs-poll') . '</p></div>';
        }
    }
}

// Get pending polls
$pending_polls = $wpdb->get_results("
    SELECT p.*, u.display_name as creator_name, u.user_email as creator_email
    FROM {$wpdb->prefix}wpcs_polls p
    LEFT JOIN {$wpdb->prefix}users u ON p.created_by = u.ID
    WHERE p.is_active = 0
    ORDER BY p.created_at ASC
");
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php if ($pending_polls): ?>
        <div class="wpcs-pending-polls">
            <?php foreach ($pending_polls as $poll): 
                $options = json_decode($poll->options, true);
            ?>
                <div class="pending-poll-card">
                    <div class="poll-header">
                        <h3><?php echo esc_html($poll->title); ?></h3>
                        <div class="poll-meta">
                            <span class="creator"><?php _e('By:', 'wpcs-poll'); ?> <?php echo esc_html($poll->creator_name ?: 'Unknown'); ?></span>
                            <span class="date"><?php echo date('M j, Y g:i A', strtotime($poll->created_at)); ?></span>
                            <span class="category"><?php echo esc_html($poll->category); ?></span>
                        </div>
                    </div>
                    
                    <?php if ($poll->description): ?>
                        <div class="poll-description">
                            <p><?php echo esc_html($poll->description); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="poll-options">
                        <h4><?php _e('Poll Options:', 'wpcs-poll'); ?></h4>
                        <ul>
                            <?php foreach ($options as $option): ?>
                                <li><?php echo esc_html($option['text']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <?php if ($poll->tags): ?>
                        <div class="poll-tags">
                            <strong><?php _e('Tags:', 'wpcs-poll'); ?></strong>
                            <?php 
                            $tags = explode(',', $poll->tags);
                            foreach ($tags as $tag): 
                            ?>
                                <span class="tag"><?php echo esc_html(trim($tag)); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="poll-actions">
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="poll_id" value="<?php echo $poll->id; ?>">
                            <input type="hidden" name="action" value="approve">
                            <input type="submit" class="button button-primary" value="<?php _e('Approve', 'wpcs-poll'); ?>" onclick="return confirm('<?php _e('Are you sure you want to approve this poll?', 'wpcs-poll'); ?>')">
                        </form>
                        
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="poll_id" value="<?php echo $poll->id; ?>">
                            <input type="hidden" name="action" value="reject">
                            <input type="submit" class="button button-secondary" value="<?php _e('Reject', 'wpcs-poll'); ?>" onclick="return confirm('<?php _e('Are you sure you want to reject and delete this poll?', 'wpcs-poll'); ?>')">
                        </form>
                        
                        <button type="button" class="button button-link" onclick="togglePollDetails(<?php echo $poll->id; ?>)">
                            <?php _e('View Details', 'wpcs-poll'); ?>
                        </button>
                    </div>
                    
                    <div id="poll-details-<?php echo $poll->id; ?>" class="poll-details" style="display: none;">
                        <h4><?php _e('Additional Information:', 'wpcs-poll'); ?></h4>
                        <table class="form-table">
                            <tr>
                                <th><?php _e('Creator Email:', 'wpcs-poll'); ?></th>
                                <td><?php echo esc_html($poll->creator_email ?: 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Submission Date:', 'wpcs-poll'); ?></th>
                                <td><?php echo date('F j, Y g:i A', strtotime($poll->created_at)); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Poll ID:', 'wpcs-poll'); ?></th>
                                <td><?php echo $poll->id; ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Number of Options:', 'wpcs-poll'); ?></th>
                                <td><?php echo count($options); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-pending-polls">
            <h2><?php _e('No Pending Polls', 'wpcs-poll'); ?></h2>
            <p><?php _e('All polls have been reviewed. Great job!', 'wpcs-poll'); ?></p>
        </div>
    <?php endif; ?>
</div>

<script>
function togglePollDetails(pollId) {
    var details = document.getElementById('poll-details-' + pollId);
    if (details.style.display === 'none') {
        details.style.display = 'block';
    } else {
        details.style.display = 'none';
    }
}
</script>

<style>
.wpcs-pending-polls {
    max-width: 800px;
}

.pending-poll-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.poll-header h3 {
    margin: 0 0 10px 0;
    color: #0073aa;
}

.poll-meta {
    display: flex;
    gap: 15px;
    font-size: 14px;
    color: #666;
    margin-bottom: 15px;
}

.poll-meta span {
    background: #f1f1f1;
    padding: 4px 8px;
    border-radius: 4px;
}

.poll-description {
    margin-bottom: 15px;
    padding: 10px;
    background: #f9f9f9;
    border-left: 4px solid #0073aa;
}

.poll-options ul {
    list-style: none;
    padding: 0;
}

.poll-options li {
    background: #f1f1f1;
    padding: 8px 12px;
    margin: 5px 0;
    border-radius: 4px;
}

.poll-tags {
    margin: 15px 0;
}

.tag {
    background: #0073aa;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
    margin-right: 5px;
}

.poll-actions {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.poll-details {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.no-pending-polls {
    text-align: center;
    padding: 40px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
}
</style>