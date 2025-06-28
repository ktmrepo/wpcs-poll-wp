<?php
/**
 * User Management Admin Page
 *
 * @package WPCS_Poll
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get user statistics
$users_with_votes = $wpdb->get_results("
    SELECT u.ID, u.display_name, u.user_email, u.user_registered,
           COUNT(v.id) as vote_count,
           COUNT(DISTINCT v.poll_id) as polls_voted_on,
           (SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_polls p WHERE p.created_by = u.ID) as polls_created
    FROM {$wpdb->prefix}users u
    LEFT JOIN {$wpdb->prefix}wpcs_poll_votes v ON u.ID = v.user_id
    GROUP BY u.ID
    ORDER BY vote_count DESC
    LIMIT 50
");

$total_users = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}users");
$active_voters = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}wpcs_poll_votes");
$poll_creators = $wpdb->get_var("SELECT COUNT(DISTINCT created_by) FROM {$wpdb->prefix}wpcs_polls WHERE created_by IS NOT NULL");
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- User Statistics -->
    <div class="wpcs-user-stats">
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo number_format($total_users); ?></h3>
                <p><?php _e('Total Users', 'wpcs-poll'); ?></p>
            </div>
            <div class="stat-card">
                <h3><?php echo number_format($active_voters); ?></h3>
                <p><?php _e('Active Voters', 'wpcs-poll'); ?></p>
            </div>
            <div class="stat-card">
                <h3><?php echo number_format($poll_creators); ?></h3>
                <p><?php _e('Poll Creators', 'wpcs-poll'); ?></p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_users > 0 ? number_format(($active_voters / $total_users) * 100, 1) : 0; ?>%</h3>
                <p><?php _e('Engagement Rate', 'wpcs-poll'); ?></p>
            </div>
        </div>
    </div>

    <!-- User Activity Table -->
    <div class="wpcs-user-activity">
        <h2><?php _e('User Activity Overview', 'wpcs-poll'); ?></h2>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th class="manage-column"><?php _e('User', 'wpcs-poll'); ?></th>
                    <th class="manage-column"><?php _e('Email', 'wpcs-poll'); ?></th>
                    <th class="manage-column"><?php _e('Votes Cast', 'wpcs-poll'); ?></th>
                    <th class="manage-column"><?php _e('Polls Voted On', 'wpcs-poll'); ?></th>
                    <th class="manage-column"><?php _e('Polls Created', 'wpcs-poll'); ?></th>
                    <th class="manage-column"><?php _e('Registered', 'wpcs-poll'); ?></th>
                    <th class="manage-column"><?php _e('Actions', 'wpcs-poll'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users_with_votes): ?>
                    <?php foreach ($users_with_votes as $user): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($user->display_name); ?></strong>
                                <div class="user-id">ID: <?php echo $user->ID; ?></div>
                            </td>
                            <td><?php echo esc_html($user->user_email); ?></td>
                            <td>
                                <span class="vote-count"><?php echo number_format($user->vote_count); ?></span>
                            </td>
                            <td>
                                <span class="polls-voted"><?php echo number_format($user->polls_voted_on); ?></span>
                            </td>
                            <td>
                                <span class="polls-created"><?php echo number_format($user->polls_created); ?></span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($user->user_registered)); ?></td>
                            <td>
                                <a href="<?php echo admin_url('user-edit.php?user_id=' . $user->ID); ?>" class="button button-small">
                                    <?php _e('Edit User', 'wpcs-poll'); ?>
                                </a>
                                <button type="button" class="button button-small" onclick="viewUserActivity(<?php echo $user->ID; ?>)">
                                    <?php _e('View Activity', 'wpcs-poll'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7"><?php _e('No user activity found.', 'wpcs-poll'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Top Contributors -->
    <div class="wpcs-top-contributors">
        <h2><?php _e('Top Contributors', 'wpcs-poll'); ?></h2>
        
        <div class="contributors-grid">
            <div class="contributor-section">
                <h3><?php _e('Most Active Voters', 'wpcs-poll'); ?></h3>
                <?php
                $top_voters = array_slice($users_with_votes, 0, 5);
                foreach ($top_voters as $voter):
                    if ($voter->vote_count > 0):
                ?>
                    <div class="contributor-item">
                        <span class="contributor-name"><?php echo esc_html($voter->display_name); ?></span>
                        <span class="contributor-stat"><?php echo number_format($voter->vote_count); ?> votes</span>
                    </div>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
            
            <div class="contributor-section">
                <h3><?php _e('Most Prolific Creators', 'wpcs-poll'); ?></h3>
                <?php
                $top_creators = $wpdb->get_results("
                    SELECT u.display_name, COUNT(p.id) as poll_count
                    FROM {$wpdb->prefix}users u
                    JOIN {$wpdb->prefix}wpcs_polls p ON u.ID = p.created_by
                    GROUP BY u.ID
                    ORDER BY poll_count DESC
                    LIMIT 5
                ");
                
                foreach ($top_creators as $creator):
                ?>
                    <div class="contributor-item">
                        <span class="contributor-name"><?php echo esc_html($creator->display_name); ?></span>
                        <span class="contributor-stat"><?php echo number_format($creator->poll_count); ?> polls</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- User Activity Modal -->
<div id="user-activity-modal" class="wpcs-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?php _e('User Activity Details', 'wpcs-poll'); ?></h2>
            <span class="close" onclick="closeUserActivityModal()">&times;</span>
        </div>
        <div class="modal-body" id="user-activity-content">
            <!-- Content will be loaded via AJAX -->
        </div>
    </div>
</div>

<script>
function viewUserActivity(userId) {
    // Show modal
    document.getElementById('user-activity-modal').style.display = 'block';
    
    // Load user activity via AJAX
    jQuery.post(ajaxurl, {
        action: 'wpcs_get_user_activity',
        user_id: userId,
        nonce: '<?php echo wp_create_nonce('wpcs_user_activity'); ?>'
    }, function(response) {
        if (response.success) {
            document.getElementById('user-activity-content').innerHTML = response.data;
        } else {
            document.getElementById('user-activity-content').innerHTML = '<p><?php _e('Error loading user activity.', 'wpcs-poll'); ?></p>';
        }
    });
}

function closeUserActivityModal() {
    document.getElementById('user-activity-modal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    var modal = document.getElementById('user-activity-modal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

<style>
.wpcs-user-stats {
    margin-bottom: 30px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-card h3 {
    margin: 0;
    font-size: 2em;
    color: #0073aa;
}

.stat-card p {
    margin: 5px 0 0 0;
    color: #666;
}

.wpcs-user-activity {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
}

.user-id {
    font-size: 12px;
    color: #666;
}

.vote-count, .polls-voted, .polls-created {
    font-weight: bold;
    color: #0073aa;
}

.wpcs-top-contributors {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
}

.contributors-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
}

.contributor-section h3 {
    margin-top: 0;
    color: #0073aa;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 10px;
}

.contributor-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background: #f9f9f9;
    border-radius: 4px;
    margin-bottom: 10px;
}

.contributor-name {
    font-weight: bold;
}

.contributor-stat {
    color: #0073aa;
    font-weight: bold;
}

/* Modal Styles */
.wpcs-modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 0;
    border-radius: 8px;
    width: 80%;
    max-width: 800px;
    max-height: 80vh;
    overflow-y: auto;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
}

.close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #aaa;
}

.close:hover {
    color: #000;
}

.modal-body {
    padding: 20px;
}
</style>