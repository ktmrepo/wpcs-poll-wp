<?php
/**
 * Analytics Admin Page
 *
 * @package WPCS_Poll
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get analytics data
$total_polls = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_polls");
$active_polls = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_polls WHERE is_active = 1");
$total_votes = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_poll_votes");
$total_users = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}users");

// Popular categories
$popular_categories = $wpdb->get_results("
    SELECT category, COUNT(*) as poll_count, 
           (SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_poll_votes v 
            JOIN {$wpdb->prefix}wpcs_polls p2 ON v.poll_id = p2.id 
            WHERE p2.category = p.category) as vote_count
    FROM {$wpdb->prefix}wpcs_polls p
    WHERE is_active = 1
    GROUP BY category
    ORDER BY poll_count DESC
    LIMIT 10
");

// Most voted polls
$most_voted_polls = $wpdb->get_results("
    SELECT p.title, p.category, COUNT(v.id) as vote_count
    FROM {$wpdb->prefix}wpcs_polls p
    LEFT JOIN {$wpdb->prefix}wpcs_poll_votes v ON p.id = v.poll_id
    WHERE p.is_active = 1
    GROUP BY p.id
    ORDER BY vote_count DESC
    LIMIT 10
");

// Recent activity (last 30 days)
$recent_activity = $wpdb->get_results("
    SELECT DATE(created_at) as date, COUNT(*) as votes
    FROM {$wpdb->prefix}wpcs_poll_votes
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC
");

// User engagement
$user_engagement = $wpdb->get_row("
    SELECT 
        COUNT(DISTINCT user_id) as active_users,
        AVG(votes_per_user) as avg_votes_per_user
    FROM (
        SELECT user_id, COUNT(*) as votes_per_user
        FROM {$wpdb->prefix}wpcs_poll_votes
        GROUP BY user_id
    ) as user_votes
");
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- Overview Statistics -->
    <div class="wpcs-analytics-overview">
        <h2><?php _e('Overview', 'wpcs-poll'); ?></h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-content">
                    <h3><?php echo number_format($total_polls); ?></h3>
                    <p><?php _e('Total Polls', 'wpcs-poll'); ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-content">
                    <h3><?php echo number_format($active_polls); ?></h3>
                    <p><?php _e('Active Polls', 'wpcs-poll'); ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🗳️</div>
                <div class="stat-content">
                    <h3><?php echo number_format($total_votes); ?></h3>
                    <p><?php _e('Total Votes', 'wpcs-poll'); ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-content">
                    <h3><?php echo number_format($user_engagement->active_users ?? 0); ?></h3>
                    <p><?php _e('Active Users', 'wpcs-poll'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Analytics -->
    <div class="wpcs-analytics-charts">
        <div class="chart-row">
            <!-- Popular Categories -->
            <div class="chart-container">
                <h3><?php _e('Popular Categories', 'wpcs-poll'); ?></h3>
                <div class="category-chart">
                    <?php if ($popular_categories): ?>
                        <?php foreach ($popular_categories as $category): ?>
                            <div class="category-bar">
                                <div class="category-info">
                                    <span class="category-name"><?php echo esc_html($category->category); ?></span>
                                    <span class="category-stats">
                                        <?php echo number_format($category->poll_count); ?> polls, 
                                        <?php echo number_format($category->vote_count); ?> votes
                                    </span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo ($category->poll_count / $popular_categories[0]->poll_count) * 100; ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p><?php _e('No category data available.', 'wpcs-poll'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Most Voted Polls -->
            <div class="chart-container">
                <h3><?php _e('Most Voted Polls', 'wpcs-poll'); ?></h3>
                <div class="polls-chart">
                    <?php if ($most_voted_polls): ?>
                        <?php foreach ($most_voted_polls as $poll): ?>
                            <div class="poll-item">
                                <div class="poll-title"><?php echo esc_html(wp_trim_words($poll->title, 8)); ?></div>
                                <div class="poll-meta">
                                    <span class="poll-category"><?php echo esc_html($poll->category); ?></span>
                                    <span class="poll-votes"><?php echo number_format($poll->vote_count); ?> votes</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p><?php _e('No poll data available.', 'wpcs-poll'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Activity Timeline -->
        <div class="chart-container full-width">
            <h3><?php _e('Recent Activity (Last 30 Days)', 'wpcs-poll'); ?></h3>
            <div class="activity-chart">
                <?php if ($recent_activity): ?>
                    <div class="activity-timeline">
                        <?php 
                        $max_votes = max(array_column($recent_activity, 'votes'));
                        foreach ($recent_activity as $activity): 
                        ?>
                            <div class="activity-day">
                                <div class="activity-bar" style="height: <?php echo ($activity->votes / $max_votes) * 100; ?>%"></div>
                                <div class="activity-date"><?php echo date('M j', strtotime($activity->date)); ?></div>
                                <div class="activity-votes"><?php echo $activity->votes; ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p><?php _e('No recent activity data available.', 'wpcs-poll'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Engagement Metrics -->
    <div class="wpcs-engagement-metrics">
        <h2><?php _e('Engagement Metrics', 'wpcs-poll'); ?></h2>
        <div class="metrics-grid">
            <div class="metric-card">
                <h4><?php _e('Average Votes per User', 'wpcs-poll'); ?></h4>
                <div class="metric-value"><?php echo number_format($user_engagement->avg_votes_per_user ?? 0, 1); ?></div>
            </div>
            
            <div class="metric-card">
                <h4><?php _e('User Participation Rate', 'wpcs-poll'); ?></h4>
                <div class="metric-value">
                    <?php 
                    $participation_rate = $total_users > 0 ? (($user_engagement->active_users ?? 0) / $total_users) * 100 : 0;
                    echo number_format($participation_rate, 1) . '%';
                    ?>
                </div>
            </div>
            
            <div class="metric-card">
                <h4><?php _e('Average Votes per Poll', 'wpcs-poll'); ?></h4>
                <div class="metric-value">
                    <?php 
                    $avg_votes_per_poll = $active_polls > 0 ? $total_votes / $active_polls : 0;
                    echo number_format($avg_votes_per_poll, 1);
                    ?>
                </div>
            </div>
            
            <div class="metric-card">
                <h4><?php _e('Poll Approval Rate', 'wpcs-poll'); ?></h4>
                <div class="metric-value">
                    <?php 
                    $approval_rate = $total_polls > 0 ? ($active_polls / $total_polls) * 100 : 0;
                    echo number_format($approval_rate, 1) . '%';
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.wpcs-analytics-overview {
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

.wpcs-analytics-charts {
    margin-bottom: 30px;
}

.chart-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.chart-container {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.chart-container.full-width {
    grid-column: 1 / -1;
}

.chart-container h3 {
    margin-top: 0;
    color: #0073aa;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 10px;
}

.category-bar {
    margin-bottom: 15px;
}

.category-info {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
}

.category-name {
    font-weight: bold;
}

.category-stats {
    font-size: 12px;
    color: #666;
}

.progress-bar {
    height: 8px;
    background: #f1f1f1;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #0073aa, #005a87);
    transition: width 0.3s ease;
}

.poll-item {
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.poll-item:last-child {
    border-bottom: none;
}

.poll-title {
    font-weight: bold;
    margin-bottom: 5px;
}

.poll-meta {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #666;
}

.poll-category {
    background: #f1f1f1;
    padding: 2px 6px;
    border-radius: 3px;
}

.poll-votes {
    color: #0073aa;
    font-weight: bold;
}

.activity-timeline {
    display: flex;
    align-items: end;
    gap: 5px;
    height: 200px;
    padding: 20px 0;
}

.activity-day {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    height: 100%;
}

.activity-bar {
    background: linear-gradient(to top, #0073aa, #005a87);
    width: 100%;
    min-height: 2px;
    border-radius: 2px 2px 0 0;
    margin-bottom: auto;
}

.activity-date {
    font-size: 10px;
    color: #666;
    margin-top: 5px;
}

.activity-votes {
    font-size: 10px;
    font-weight: bold;
    color: #0073aa;
}

.wpcs-engagement-metrics {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.metric-card {
    text-align: center;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
}

.metric-card h4 {
    margin: 0 0 10px 0;
    color: #666;
    font-size: 14px;
}

.metric-value {
    font-size: 2em;
    font-weight: bold;
    color: #0073aa;
}

@media (max-width: 768px) {
    .chart-row {
        grid-template-columns: 1fr;
    }
    
    .activity-timeline {
        height: 150px;
    }
    
    .activity-date {
        transform: rotate(-45deg);
        transform-origin: center;
    }
}
</style>