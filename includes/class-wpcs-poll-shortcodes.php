<?php
class WPCS_Poll_Shortcodes {
    public function __construct() {
        add_shortcode('wpcs_poll_container', array($this, 'poll_container_shortcode'));
        add_shortcode('wpcs_poll_single', array($this, 'single_poll_shortcode'));
        add_shortcode('wpcs_poll_user_dashboard', array($this, 'user_dashboard_shortcode'));
        add_shortcode('wpcs_poll_submit_form', array($this, 'submit_form_shortcode'));
    }

    public function poll_container_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => 'all',
            'limit' => 10,
            'style' => 'tiktok', // tiktok|grid|list
            'autoplay' => 'false',
            'show_navigation' => 'true'
        ), $atts);

        ob_start();
        include WPCS_POLL_PLUGIN_PATH . 'public/partials/poll-container.php';
        return ob_get_clean();
    }

    public function single_poll_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'show_results' => 'after_vote'
        ), $atts);

        if (!$atts['id']) {
            return '<p>Poll ID required</p>';
        }

        ob_start();
        include WPCS_POLL_PLUGIN_PATH . 'public/partials/single-poll.php';
        return ob_get_clean();
    }
}