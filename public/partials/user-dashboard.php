<?php
/**
 * User Dashboard Template
 *
 * @package WPCS_Poll
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    return;
}

$user_id = get_current_user_id();
$user = wp_get_current_user();
$db = new WPCS_Poll_Database();

global $wpdb;

// Get user statistics
$user_stats = $wpdb->get_row($wpdb->prepare("
    SELECT 
        COUNT(DISTINCT v.poll_id) as polls_voted_on,
        COUNT(v.id) as total_votes,
        (SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_polls p WHERE p.created_by = %d) as polls_created,
        (SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_poll_bookmarks b WHERE b.user_id = %d) as bookmarks_count
    FROM {$wpdb->prefix}wpcs_poll_votes v
    WHERE v.user_id = %d
", $user_id, $user_id, $user_id));

// Get recent votes
$recent_votes = array();
if ($atts['show_recent_votes'] === 'true') {
    $recent_votes = $wpdb->get_results($wpdb->prepare("
        SELECT v.*, p.title as poll_title, p.category
        FROM {$wpdb->prefix}wpcs_poll_votes v
        JOIN {$wpdb->prefix}wpcs_polls p ON v.poll_id = p.id
        WHERE v.user_id = %d
        ORDER BY v.created_at DESC
        LIMIT 10
    ", $user_id));
}

// Get created polls
$created_polls = array();
if ($atts['show_created_polls'] === 'true') {
    $created_polls = $wpdb->get_results($wpdb->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_poll_votes v WHERE v.poll_id = p.id) as vote_count
        FROM {$wpdb->prefix}wpcs_polls p
        WHERE p.created_by = %d
        ORDER BY p.created_at DESC
        LIMIT 10
    ", $user_id));
}

// Get bookmarked polls
$bookmarked_polls = array();
if ($atts['show_bookmarks'] === 'true') {
    $bookmarked_polls = $wpdb->get_results($wpdb->prepare("
        SELECT p.*, b.created_at as bookmarked_at,
               (SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_poll_votes v WHERE v.poll_id = p.id) as vote_count
        FROM {$wpdb->prefix}wpcs_poll_bookmarks b
        JOIN {$wpdb->prefix}wpcs_polls p ON b.poll_id = p.id
        WHERE b.user_id = %d AND p.is_active = 1
        ORDER BY b.created_at DESC
        LIMIT 10
    ", $user_id));
}
?>

<div class="wpcs-user-dashboard">
    
    <div class="dashboard-header">
        <h2><?php printf(__('Welcome back, %s!', 'wpcs-poll'), esc_html($user->display_name)); ?></h2>
        <p class="dashboard-subtitle"><?php _e('Here\'s your polling activity overview', 'wpcs-poll'); ?></p>
    </div>

    <?php if ($atts['show_stats'] === 'true'): ?>
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon">🗳️</div>
                <div class="stat-content">
                    <h3><?php echo intval($user_stats->total_votes); ?></h3>
                    <p><?php _e('Total Votes', 'wpcs-poll'); ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-content">
                    <h3><?php echo intval($user_stats->polls_voted_on); ?></h3>
                    <p><?php _e('Polls Voted On', 'wpcs-poll'); ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">✏️</div>
                <div class="stat-content">
                    <h3><?php echo intval($user_stats->polls_created); ?></h3>
                    <p><?php _e('Polls Created', 'wpcs-poll'); ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🔖</div>
                <div class="stat-content">
                    <h3><?php echo intval($user_stats->bookmarks_count); ?></h3>
                    <p><?php _e('Bookmarks', 'wpcs-poll'); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="dashboard-content">
        
        <?php if ($atts['show_recent_votes'] === 'true' && !empty($recent_votes)): ?>
            <div class="dashboard-section">
                <h3><?php _e('Recent Votes', 'wpcs-poll'); ?></h3>
                <div class="recent-votes">
                    <?php foreach ($recent_votes as $vote): ?>
                        <div class="vote-item">
                            <div class="vote-info">
                                <h4><?php echo esc_html($vote->poll_title); ?></h4>
                                <span class="vote-category"><?php echo esc_html($vote->category); ?></span>
                            </div>
                            <div class="vote-date">
                                <?php echo human_time_diff(strtotime($vote->created_at), current_time('timestamp')); ?> <?php _e('ago', 'wpcs-poll'); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($atts['show_created_polls'] === 'true' && !empty($created_polls)): ?>
            <div class="dashboard-section">
                <h3><?php _e('Your Polls', 'wpcs-poll'); ?></h3>
                <div class="created-polls">
                    <?php foreach ($created_polls as $poll): ?>
                        <div class="poll-item">
                            <div class="poll-info">
                                <h4><?php echo esc_html($poll->title); ?></h4>
                                <div class="poll-meta">
                                    <span class="poll-category"><?php echo esc_html($poll->category); ?></span>
                                    <span class="poll-votes"><?php echo intval($poll->vote_count); ?> votes</span>
                                    <span class="poll-status <?php echo $poll->is_active ? 'active' : 'inactive'; ?>">
                                        <?php echo $poll->is_active ? __('Active', 'wpcs-poll') : __('Pending', 'wpcs-poll'); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="poll-date">
                                <?php echo date('M j, Y', strtotime($poll->created_at)); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($atts['show_bookmarks'] === 'true' && !empty($bookmarked_polls)): ?>
            <div class="dashboard-section">
                <h3><?php _e('Bookmarked Polls', 'wpcs-poll'); ?></h3>
                <div class="bookmarked-polls">
                    <?php foreach ($bookmarked_polls as $poll): ?>
                        <div class="poll-item">
                            <div class="poll-info">
                                <h4><?php echo esc_html($poll->title); ?></h4>
                                <div class="poll-meta">
                                    <span class="poll-category"><?php echo esc_html($poll->category); ?></span>
                                    <span class="poll-votes"><?php echo intval($poll->vote_count); ?> votes</span>
                                </div>
                            </div>
                            <div class="poll-actions">
                                <button class="view-poll-btn" onclick="window.location.href='#poll-<?php echo $poll->id; ?>'">
                                    <?php _e('View', 'wpcs-poll'); ?>
                                </button>
                                <button class="remove-bookmark-btn" onclick="wpcsBookmarkPoll(<?php echo $poll->id; ?>)">
                                    <?php _e('Remove', 'wpcs-poll'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <div class="dashboard-actions">
        <a href="#" class="btn btn-primary" onclick="wpcsShowSubmitForm()">
            <?php _e('Create New Poll', 'wpcs-poll'); ?>
        </a>
        <a href="<?php echo wp_logout_url(get_permalink()); ?>" class="btn btn-secondary">
            <?php _e('Logout', 'wpcs-poll'); ?>
        </a>
    </div>

</div>

<style>
.wpcs-user-dashboard {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
}

.dashboard-header {
    text-align: center;
    margin-bottom: 30px;
}

.dashboard-header h2 {
    margin: 0 0 10px 0;
    color: #333;
    font-size: 28px;
}

.dashboard-subtitle {
    color: #666;
    font-size: 16px;
    margin: 0;
}

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 12px;
    padding: 25px;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-icon {
    font-size: 2.5em;
    margin-bottom: 15px;
}

.stat-content h3 {
    margin: 0 0 5px 0;
    font-size: 2.2em;
    color: #0073aa;
    font-weight: bold;
}

.stat-content p {
    margin: 0;
    color: #666;
    font-size: 14px;
    font-weight: 500;
}

.dashboard-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin-bottom: 40px;
}

.dashboard-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.dashboard-section h3 {
    margin: 0 0 20px 0;
    color: #0073aa;
    font-size: 20px;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 10px;
}

.vote-item, .poll-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #eee;
}

.vote-item:last-child, .poll-item:last-child {
    border-bottom: none;
}

.vote-info h4, .poll-info h4 {
    margin: 0 0 5px 0;
    font-size: 16px;
    color: #333;
}

.vote-category, .poll-category {
    background: #f1f1f1;
    color: #666;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    margin-right: 10px;
}

.poll-meta {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.poll-votes {
    color: #0073aa;
    font-weight: 500;
    font-size: 12px;
}

.poll-status {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
}

.poll-status.active {
    background: #d4edda;
    color: #155724;
}

.poll-status.inactive {
    background: #fff3cd;
    color: #856404;
}

.vote-date, .poll-date {
    color: #999;
    font-size: 12px;
    white-space: nowrap;
}

.poll-actions {
    display: flex;
    gap: 10px;
}

.view-poll-btn, .remove-bookmark-btn {
    background: none;
    border: 1px solid #ddd;
    color: #666;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.2s ease;
}

.view-poll-btn:hover {
    background: #0073aa;
    color: white;
    border-color: #0073aa;
}

.remove-bookmark-btn:hover {
    background: #d63638;
    color: white;
    border-color: #d63638;
}

.dashboard-actions {
    text-align: center;
    padding-top: 30px;
    border-top: 1px solid #eee;
}

.btn {
    display: inline-block;
    padding: 12px 24px;
    margin: 0 10px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-primary {
    background: #0073aa;
    color: white;
}

.btn-primary:hover {
    background: #005a87;
    color: white;
}

.btn-secondary {
    background: #f1f1f1;
    color: #666;
}

.btn-secondary:hover {
    background: #ddd;
    color: #333;
}

/* Responsive Design */
@media (max-width: 768px) {
    .dashboard-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .dashboard-content {
        grid-template-columns: 1fr;
    }
    
    .vote-item, .poll-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .poll-actions {
        align-self: flex-end;
    }
    
    .btn {
        display: block;
        margin: 10px 0;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .dashboard-stats {
        grid-template-columns: 1fr;
    }
}
</style>