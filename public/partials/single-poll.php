<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$poll_id = intval($atts['id']);
$show_results = isset($atts['show_results']) ? $atts['show_results'] : 'after_vote';
$user_id = get_current_user_id();

// Get poll data
$poll = $wpdb->get_row($wpdb->prepare("
    SELECT p.*, u.display_name as creator_name,
           (SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_poll_votes v WHERE v.poll_id = p.id) as total_votes,
           " . ($user_id ? "(SELECT option_id FROM {$wpdb->prefix}wpcs_poll_votes v WHERE v.poll_id = p.id AND v.user_id = %d) as user_vote" : "NULL as user_vote") . "
    FROM {$wpdb->prefix}wpcs_polls p
    LEFT JOIN {$wpdb->prefix}users u ON p.created_by = u.ID
    WHERE p.id = %d AND p.is_active = 1
", $user_id ? array($user_id, $poll_id) : array($poll_id)));

if (!$poll) {
    echo '<p>Poll not found or inactive.</p>';
    return;
}

$options = json_decode($poll->options, true);
$has_voted = !empty($poll->user_vote);
$show_results_now = $show_results === 'always' || ($show_results === 'after_vote' && $has_voted);
?>

<div class="wpcs-single-poll" data-poll-id="<?php echo esc_attr($poll->id); ?>">
    <div class="poll-header">
        <h3 class="poll-title"><?php echo esc_html($poll->title); ?></h3>
        <?php if ($poll->description): ?>
            <p class="poll-description"><?php echo esc_html($poll->description); ?></p>
        <?php endif; ?>
    </div>

    <div class="poll-options">
        <?php foreach ($options as $option): 
            $vote_count = intval($option['votes']);
            $percentage = $poll->total_votes > 0 ? ($vote_count / $poll->total_votes) * 100 : 0;
            $is_selected = $has_voted && $poll->user_vote === $option['id'];
        ?>
            <div class="poll-option <?php echo $is_selected ? 'selected' : ''; ?> <?php echo $show_results_now ? 'show-results' : ''; ?>"
                 data-option-id="<?php echo esc_attr($option['id']); ?>"
                 <?php if (!$has_voted && $user_id): ?>
                     onclick="wpcsVoteOnPoll(<?php echo $poll->id; ?>, '<?php echo esc_js($option['id']); ?>')"
                 <?php endif; ?>>
                
                <div class="option-content">
                    <span class="option-text"><?php echo esc_html($option['text']); ?></span>
                    <?php if ($show_results_now): ?>
                        <span class="option-percentage"><?php echo number_format($percentage, 1); ?>%</span>
                    <?php endif; ?>
                </div>

                <?php if ($show_results_now): ?>
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
            <span class="poll-votes"><?php echo intval($poll->total_votes); ?> total votes</span>
        </div>
        
        <div class="poll-actions">
            <?php if ($user_id): ?>
                <button class="bookmark-btn" onclick="wpcsBookmarkPoll(<?php echo $poll->id; ?>)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
                    </svg>
                    Bookmark
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
                Share
            </button>
        </div>
    </div>

    <div class="poll-footer">
        <small>Created by <?php echo esc_html($poll->creator_name); ?> on <?php echo date('M j, Y', strtotime($poll->created_at)); ?></small>
    </div>
</div>

<?php if (!$has_voted && !$user_id): ?>
    <div class="login-prompt">
        <p>Please <a href="<?php echo wp_login_url(get_permalink()); ?>">log in</a> to vote on this poll.</p>
    </div>
<?php endif; ?>
