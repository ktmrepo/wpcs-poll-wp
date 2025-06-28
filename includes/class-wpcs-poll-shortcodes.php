<?php
/**
 * Shortcodes for WPCS Poll
 *
 * @package WPCS_Poll
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WPCS_Poll_Shortcodes {
    
    private $db;

    public function __construct() {
        // Register shortcodes
        add_shortcode('wpcs_poll', array($this, 'display_polls_shortcode'));
        add_shortcode('wpcs_poll_container', array($this, 'poll_container_shortcode'));
        add_shortcode('wpcs_poll_single', array($this, 'single_poll_shortcode'));
        add_shortcode('wpcs_polls', array($this, 'polls_by_category_shortcode'));
        add_shortcode('wpcs_poll_user_dashboard', array($this, 'user_dashboard_shortcode'));
        add_shortcode('wpcs_poll_submit_form', array($this, 'submit_form_shortcode'));
    }

    /**
     * Get database handler
     */
    private function get_db() {
        if (null === $this->db) {
            if (class_exists('WPCS_Poll_Database')) {
                $this->db = new WPCS_Poll_Database();
            }
        }
        return $this->db;
    }

    /**
     * Main poll shortcode - displays random polls or single poll
     * Usage: [wpcs_poll] or [wpcs_poll id="123"]
     */
    public function display_polls_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'style' => 'tiktok',
            'limit' => 10,
            'category' => 'all',
            'autoplay' => 'false',
            'show_navigation' => 'true'
        ), $atts);

        // If ID is specified, show single poll
        if ($atts['id']) {
            return $this->single_poll_shortcode($atts);
        }

        // Otherwise show poll container with random polls
        return $this->poll_container_shortcode($atts);
    }

    /**
     * Poll container shortcode for TikTok-style interface
     */
    public function poll_container_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => 'all',
            'limit' => 10,
            'style' => 'tiktok',
            'autoplay' => 'false',
            'show_navigation' => 'true'
        ), $atts);

        ob_start();
        include WPCS_POLL_PLUGIN_PATH . 'public/partials/poll-container.php';
        return ob_get_clean();
    }

    /**
     * Single poll shortcode
     * Usage: [wpcs_poll_single id="123"]
     */
    public function single_poll_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'show_results' => 'after_vote',
            'style' => 'card'
        ), $atts);

        if (!$atts['id']) {
            return '<p class="wpcs-poll-error">' . __('Poll ID is required.', 'wpcs-poll') . '</p>';
        }

        ob_start();
        include WPCS_POLL_PLUGIN_PATH . 'public/partials/single-poll.php';
        return ob_get_clean();
    }

    /**
     * Polls by category shortcode
     * Usage: [wpcs_polls cat="1,5,7"] or [wpcs_polls cat="Technology,Sports"]
     */
    public function polls_by_category_shortcode($atts) {
        $atts = shortcode_atts(array(
            'cat' => '',
            'limit' => 10,
            'style' => 'grid',
            'show_pagination' => 'true',
            'per_page' => 6
        ), $atts);

        if (empty($atts['cat'])) {
            return '<p class="wpcs-poll-error">' . __('Category parameter is required.', 'wpcs-poll') . '</p>';
        }

        ob_start();
        include WPCS_POLL_PLUGIN_PATH . 'public/partials/polls-by-category.php';
        return ob_get_clean();
    }

    /**
     * User dashboard shortcode
     */
    public function user_dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p class="wpcs-poll-login-required">' . 
                   sprintf(__('Please <a href="%s">log in</a> to view your dashboard.', 'wpcs-poll'), 
                   wp_login_url(get_permalink())) . '</p>';
        }

        $atts = shortcode_atts(array(
            'show_stats' => 'true',
            'show_recent_votes' => 'true',
            'show_created_polls' => 'true',
            'show_bookmarks' => 'true'
        ), $atts);

        ob_start();
        include WPCS_POLL_PLUGIN_PATH . 'public/partials/user-dashboard.php';
        return ob_get_clean();
    }

    /**
     * Poll submission form shortcode
     */
    public function submit_form_shortcode($atts) {
        $plugin_options = get_option('wpcs_poll_options', array());
        $require_login = isset($plugin_options['require_login_to_create']) && $plugin_options['require_login_to_create'];

        if ($require_login && !is_user_logged_in()) {
            return '<p class="wpcs-poll-login-required">' . 
                   sprintf(__('Please <a href="%s">log in</a> to submit a poll.', 'wpcs-poll'), 
                   wp_login_url(get_permalink())) . '</p>';
        }

        $atts = shortcode_atts(array(
            'max_options' => 10,
            'show_description' => 'true',
            'show_tags' => 'true',
            'show_category' => 'true'
        ), $atts);

        ob_start();
        include WPCS_POLL_PLUGIN_PATH . 'public/partials/submit-form.php';
        return ob_get_clean();
    }
}