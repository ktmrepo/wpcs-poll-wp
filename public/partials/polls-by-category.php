<?php
/**
 * Polls by Category Template
 *
 * @package WPCS_Poll
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$db = new WPCS_Poll_Database();
$categories = array_map('trim', explode(',', $atts['cat']));
$limit = intval($atts['limit']);
$style = sanitize_text_field($atts['style']);
$show_pagination = $atts['show_pagination'] === 'true';
$per_page = intval($atts['per_page']);

// Get current page for pagination
$current_page = get_query_var('paged') ? get_query_var('paged') : 1;
$offset = ($current_page - 1) * $per_page;

// Build query arguments
$query_args = array(
    'limit' => $show_pagination ? $per_page : $limit,
    'offset' => $show_pagination ? $offset : 0,
    'is_active' => 1,
    'order_by' => 'created_at',
    'order' => 'DESC'
);

// Handle category filtering
$polls = array();
$total_polls = 0;

foreach ($categories as $category) {
    // Check if it's a category ID or name
    if (is_numeric($category)) {
        // Handle category by ID (if you implement category management)
        $query_args['category'] = $category;
    } else {
        // Handle category by name
        $query_args['category'] = $category;
    }
    
    $category_polls = $db->get_polls($query_args);
    $polls = array_merge($polls, $category_polls);
}

// Remove duplicates and limit results
$polls = array_unique($polls, SORT_REGULAR);
if (!$show_pagination && count($polls) > $limit) {
    $polls = array_slice($polls, 0, $limit);
}

$user_id = get_current_user_id();
?>

<div class="wpcs-polls-by-category wpcs-polls-style-<?php echo esc_attr($style); ?>">
    
    <?php if (!empty($polls)): ?>
        
        <div class="polls-header">
            <h3 class="polls-title">
                <?php 
                if (count($categories) === 1) {
                    printf(__('Polls in %s', 'wpcs-poll'), esc_html($categories[0]));
                } else {
                    printf(__('Polls in %s', 'wpcs-poll'), esc_html(implode(', ', $categories)));
                }
                ?>
            </h3>
            <span class="polls-count"><?php echo count($polls); ?> <?php _e('polls found', 'wpcs-poll'); ?></span>
        </div>

        <div class="polls-grid">
            <?php foreach ($polls as $poll): 
                $options = is_array($poll->options) ? $poll->options : json_decode($poll->options, true);
                $total_votes = 0;
                foreach ($options as $option) {
                    $total_votes += isset($option['votes']) ? intval($option['votes']) : 0;
                }
                
                // Check if user has voted
                $user_vote = null;
                if ($user_id) {
                    global $wpdb;
                    $user_vote = $wpdb->get_var($wpdb->prepare(
                        "SELECT option_id FROM {$wpdb->prefix}wpcs_poll_votes WHERE user_id = %d AND poll_id = %d",
                        $user_id, $poll->id
                    ));
                }
            ?>
                <div class="poll-card" data-poll-id="<?php echo esc_attr($poll->id); ?>">
                    <div class="poll-header">
                        <h4 class="poll-title"><?php echo esc_html($poll->title); ?></h4>
                        <?php if ($poll->description): ?>
                            <p class="poll-description"><?php echo esc_html($poll->description); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="poll-options">
                        <?php foreach ($options as $option): 
                            $vote_count = isset($option['votes']) ? intval($option['votes']) : 0;
                            $percentage = $total_votes > 0 ? ($vote_count / $total_votes) * 100 : 0;
                            $is_selected = $user_vote === $option['id'];
                            $show_results = $user_vote !== null;
                        ?>
                            <div class="poll-option <?php echo $is_selected ? 'selected' : ''; ?> <?php echo $show_results ? 'show-results' : ''; ?>"
                                 data-option-id="<?php echo esc_attr($option['id']); ?>"
                                 <?php if (!$user_vote && $user_id): ?>
                                     onclick="wpcsVoteOnPoll(<?php echo $poll->id; ?>, '<?php echo esc_js($option['id']); ?>')"
                                 <?php endif; ?>>
                                
                                <div class="option-content">
                                    <span class="option-text"><?php echo esc_html($option['text']); ?></span>
                                    <?php if ($show_results): ?>
                                        <span class="option-percentage"><?php echo number_format($percentage, 1); ?>%</span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($show_results): ?>
                                    <div class="option-progress">
                                        <div class="progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                    <div class="option-votes"><?php echo $vote_count; ?> votes</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="poll-meta">
                        <div class="poll-stats">
                            <span class="poll-category"><?php echo esc_html($poll->category); ?></span>
                            <span class="poll-votes"><?php echo $total_votes; ?> votes</span>
                        </div>
                        
                        <div class="poll-actions">
                            <?php if ($user_id): ?>
                                <button class="bookmark-btn" onclick="wpcsBookmarkPoll(<?php echo $poll->id; ?>)">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
                                    </svg>
                                </button>
                            <?php endif; ?>
                            
                            <button class="share-btn" onclick="wpcsSharePoll(<?php echo $poll->id; ?>)">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="18" cy="5" r="3"></circle>
                                    <circle cx="6" cy="12" r="3"></circle>
                                    <circle cx="18" cy="19" r="3"></circle>
                                    <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                                    <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="poll-footer">
                        <small><?php echo date('M j, Y', strtotime($poll->created_at)); ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($show_pagination && $total_polls > $per_page): ?>
            <div class="polls-pagination">
                <?php
                $total_pages = ceil($total_polls / $per_page);
                echo paginate_links(array(
                    'total' => $total_pages,
                    'current' => $current_page,
                    'prev_text' => __('&laquo; Previous', 'wpcs-poll'),
                    'next_text' => __('Next &raquo;', 'wpcs-poll')
                ));
                ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="no-polls-found">
            <h3><?php _e('No Polls Found', 'wpcs-poll'); ?></h3>
            <p><?php _e('No polls were found in the specified categories.', 'wpcs-poll'); ?></p>
        </div>
    <?php endif; ?>

    <?php if (!$user_id): ?>
        <div class="login-prompt">
            <p><?php printf(__('Please <a href="%s">log in</a> to vote on polls.', 'wpcs-poll'), wp_login_url(get_permalink())); ?></p>
        </div>
    <?php endif; ?>
</div>

<style>
.wpcs-polls-by-category {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.polls-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid #0073aa;
}

.polls-title {
    margin: 0;
    color: #0073aa;
    font-size: 24px;
}

.polls-count {
    color: #666;
    font-size: 14px;
}

/* Grid Style */
.wpcs-polls-style-grid .polls-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 25px;
}

/* List Style */
.wpcs-polls-style-list .polls-grid {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* Card Style */
.wpcs-polls-style-card .polls-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.poll-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.poll-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
}

.poll-header {
    margin-bottom: 20px;
}

.poll-title {
    margin: 0 0 10px 0;
    font-size: 18px;
    font-weight: bold;
    color: #333;
    line-height: 1.3;
}

.poll-description {
    margin: 0;
    color: #666;
    font-size: 14px;
    line-height: 1.4;
}

.poll-options {
    margin-bottom: 20px;
}

.poll-option {
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 12px 15px;
    margin-bottom: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.poll-option:hover {
    background: #e9ecef;
    border-color: #0073aa;
}

.poll-option.selected {
    background: #e3f2fd;
    border-color: #0073aa;
}

.option-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    z-index: 2;
}

.option-text {
    font-weight: 500;
    color: #333;
}

.option-percentage {
    font-weight: bold;
    color: #0073aa;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.poll-option.show-results .option-percentage {
    opacity: 1;
}

.option-progress {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.poll-option.show-results .option-progress {
    opacity: 1;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, rgba(0, 115, 170, 0.2), rgba(0, 115, 170, 0.1));
    border-radius: 6px;
    transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
}

.option-votes {
    position: absolute;
    bottom: -20px;
    right: 15px;
    font-size: 11px;
    color: #666;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.poll-option.show-results .option-votes {
    opacity: 1;
}

.poll-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.poll-stats {
    display: flex;
    gap: 15px;
    font-size: 12px;
}

.poll-category {
    background: #0073aa;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: 500;
}

.poll-votes {
    color: #666;
    font-weight: 500;
}

.poll-actions {
    display: flex;
    gap: 10px;
}

.bookmark-btn, .share-btn {
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    padding: 5px;
    border-radius: 4px;
    transition: color 0.2s ease;
}

.bookmark-btn:hover, .share-btn:hover {
    color: #0073aa;
}

.poll-footer {
    margin-top: 10px;
    text-align: center;
}

.poll-footer small {
    color: #999;
    font-size: 11px;
}

.no-polls-found {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.no-polls-found h3 {
    margin-bottom: 10px;
    color: #333;
}

.login-prompt {
    text-align: center;
    margin-top: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.polls-pagination {
    margin-top: 40px;
    text-align: center;
}

.polls-pagination .page-numbers {
    display: inline-block;
    padding: 8px 12px;
    margin: 0 2px;
    background: #f8f9fa;
    color: #0073aa;
    text-decoration: none;
    border-radius: 4px;
    transition: background-color 0.2s ease;
}

.polls-pagination .page-numbers:hover,
.polls-pagination .page-numbers.current {
    background: #0073aa;
    color: white;
}

/* Responsive Design */
@media (max-width: 768px) {
    .wpcs-polls-style-grid .polls-grid,
    .wpcs-polls-style-card .polls-grid {
        grid-template-columns: 1fr;
    }
    
    .polls-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .poll-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .poll-actions {
        align-self: flex-end;
    }
}
</style>