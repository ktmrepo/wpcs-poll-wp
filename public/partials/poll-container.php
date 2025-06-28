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

// Debug information
if (defined('WP_DEBUG') && WP_DEBUG) {
    echo "<!-- WPCS Poll Debug: REST URL = " . rest_url('wpcs-poll/v1/') . " -->";
    echo "<!-- WPCS Poll Debug: AJAX URL = " . admin_url('admin-ajax.php') . " -->";
}
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
        <h3><?php _e('Failed to Load Polls', 'wpcs-poll'); ?></h3>
        <p><?php _e('We couldn\'t load the polls right now. Please try again.', 'wpcs-poll'); ?></p>
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
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
}

.wpcs-polls-loading p {
    color: white;
    margin-top: 15px;
    font-size: 16px;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid rgba(255, 255, 255, 0.3);
    border-top: 4px solid #ffffff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.wpcs-polls-error {
    text-align: center;
    padding: 40px;
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
    border-radius: 20px;
}

.wpcs-polls-error h3 {
    margin: 0 0 15px 0;
    color: #721c24;
}

.wpcs-polls-error p {
    margin: 0 0 20px 0;
}

.retry-btn {
    background: #0073aa;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.2s ease;
}

.retry-btn:hover {
    background: #005a87;
}

.no-polls-message {
    text-align: center;
    padding: 60px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 20px;
}

.no-polls-message h3 {
    margin: 0 0 15px 0;
    font-size: 24px;
}

.no-polls-message p {
    margin: 0;
    font-size: 16px;
    opacity: 0.9;
}

.no-options {
    text-align: center;
    color: rgba(255, 255, 255, 0.7);
    font-style: italic;
    padding: 20px;
}
</style>