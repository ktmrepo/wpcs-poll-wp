<?php
/**
 * Admin Dashboard
 *
 * @package WPCS_Poll
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get statistics
$total_polls = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_polls");
$active_polls = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_polls WHERE is_active = 1");
$pending_polls = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_polls WHERE is_active = 0");
$total_votes = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_poll_votes");
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="wpcs-poll-dashboard">
        <!-- Statistics Cards -->
        <div class="wpcs-stats-grid">
            <div class="wpcs-stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-content">
                    <h3><?php echo number_format($total_polls); ?></h3>
                    <p><?php _e('Total Polls', 'wpcs-poll'); ?></p>
                </div>
            </div>
            
            <div class="wpcs-stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-content">
                    <h3><?php echo number_format($active_polls); ?></h3>
                    <p><?php _e('Active Polls', 'wpcs-poll'); ?></p>
                </div>
            </div>
            
            <div class="wpcs-stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-content">
                    <h3><?php echo number_format($pending_polls); ?></h3>
                    <p><?php _e('Pending Approval', 'wpcs-poll'); ?></p>
                </div>
            </div>
            
            <div class="wpcs-stat-card">
                <div class="stat-icon">🗳️</div>
                <div class="stat-content">
                    <h3><?php echo number_format($total_votes); ?></h3>
                    <p><?php _e('Total Votes', 'wpcs-poll'); ?></p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="wpcs-quick-actions">
            <h2><?php _e('Quick Actions', 'wpcs-poll'); ?></h2>
            <div class="action-buttons">
                <a href="<?php echo admin_url('admin.php?page=wpcs-poll-manage'); ?>" class="button button-primary">
                    <?php _e('Manage Polls', 'wpcs-poll'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=wpcs-poll-pending'); ?>" class="button button-secondary">
                    <?php _e('Review Pending', 'wpcs-poll'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=wpcs-poll-bulk'); ?>" class="button button-secondary">
                    <?php _e('Bulk Upload', 'wpcs-poll'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=wpcs-poll-analytics'); ?>" class="button button-secondary">
                    <?php _e('View Analytics', 'wpcs-poll'); ?>
                </a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="wpcs-recent-activity">
            <h2><?php _e('Recent Activity', 'wpcs-poll'); ?></h2>
            <?php
            $recent_polls = $wpdb->get_results("
                SELECT p.*, u.display_name as creator_name
                FROM {$wpdb->prefix}wpcs_polls p
                LEFT JOIN {$wpdb->prefix}users u ON p.created_by = u.ID
                ORDER BY p.created_at DESC
                LIMIT 5
            ");
            
            if ($recent_polls): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Poll Title', 'wpcs-poll'); ?></th>
                            <th><?php _e('Creator', 'wpcs-poll'); ?></th>
                            <th><?php _e('Category', 'wpcs-poll'); ?></th>
                            <th><?php _e('Status', 'wpcs-poll'); ?></th>
                            <th><?php _e('Created', 'wpcs-poll'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_polls as $poll): ?>
                            <tr>
                                <td><strong><?php echo esc_html($poll->title); ?></strong></td>
                                <td><?php echo esc_html($poll->creator_name ?: 'Unknown'); ?></td>
                                <td><?php echo esc_html($poll->category); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $poll->is_active ? 'active' : 'pending'; ?>">
                                        <?php echo $poll->is_active ? __('Active', 'wpcs-poll') : __('Pending', 'wpcs-poll'); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($poll->created_at)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php _e('No polls found.', 'wpcs-poll'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.wpcs-poll-dashboard {
    max-width: 1200px;
}

.wpcs-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.wpcs-stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-icon {
    font-size: 2em;
}

.stat-content h3 {
    margin: 0;
    font-size: 2em;
    color: #0073aa;
}

.stat-content p {
    margin: 5px 0 0 0;
    color: #666;
}

.wpcs-quick-actions {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
}

.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.wpcs-recent-activity {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.status-badge.active {
    background: #d4edda;
    color: #155724;
}

.status-badge.pending {
    background: #fff3cd;
    color: #856404;
}
</style>