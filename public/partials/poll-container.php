<?php
/**
 * Poll Container Template
 *
 * @package WPCS_Poll
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$container_id = 'wpcs-poll-container-' . uniqid();
$style = isset($atts['style']) ? $atts['style'] : 'tiktok';
$category = isset($atts['category']) ? $atts['category'] : 'all';
$limit = isset($atts['limit']) ? intval($atts['limit']) : 10;
$autoplay = isset($atts['autoplay']) ? $atts['autoplay'] === 'true' : false;
$show_navigation = isset($atts['show_navigation']) ? $atts['show_navigation'] === 'true' : true;
?>

<div id="<?php echo esc_attr($container_id); ?>" 
     class="wpcs-poll-container wpcs-poll-style-<?php echo esc_attr($style); ?>"
     data-category="<?php echo esc_attr($category); ?>"
     data-limit="<?php echo esc_attr($limit); ?>"
     data-autoplay="<?php echo $autoplay ? 'true' : 'false'; ?>"
     data-show-navigation="<?php echo $show_navigation ? 'true' : 'false'; ?>">
    
    <div class="wpcs-polls-loading">
        <div class="loading-spinner"></div>
        <p><?php _e('Loading polls...', 'wpcs-poll'); ?></p>
    </div>
    
    <div class="wpcs-polls-error" style="display: none;">
        <p><?php _e('Failed to load polls. Please try again.', 'wpcs-poll'); ?></p>
        <button class="retry-btn"><?php _e('Retry', 'wpcs-poll'); ?></button>
    </div>
</div>

<style>
.wpcs-polls-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 300px;
    color: #666;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #0073aa;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 15px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.wpcs-polls-error {
    text-align: center;
    padding: 40px;
    color: #d63638;
}

.retry-btn {
    background: #0073aa;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    margin-top: 10px;
}

.retry-btn:hover {
    background: #005a87;
}
</style>