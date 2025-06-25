<?php
class WPCS_Poll_AJAX {
    public function __construct() {
        // Public AJAX actions
        add_action('wp_ajax_wpcs_poll_vote', array($this, 'handle_vote'));
        add_action('wp_ajax_nopriv_wpcs_poll_vote', array($this, 'handle_vote'));
        
        add_action('wp_ajax_wpcs_poll_bookmark', array($this, 'handle_bookmark'));
        add_action('wp_ajax_wpcs_poll_submit', array($this, 'handle_poll_submission'));
        
        // Admin AJAX actions
        add_action('wp_ajax_wpcs_poll_admin_approve', array($this, 'handle_admin_approve'));
        add_action('wp_ajax_wpcs_poll_admin_delete', array($this, 'handle_admin_delete'));
        add_action('wp_ajax_wpcs_poll_bulk_upload', array($this, 'handle_bulk_upload'));
    }

    public function handle_vote() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wpcs_poll_nonce')) {
            wp_die('Security check failed');
        }

        $poll_id = intval($_POST['poll_id']);
        $option_id = sanitize_text_field($_POST['option_id']);
        $user_id = get_current_user_id();

        if (!$user_id) {
            wp_send_json_error(array('message' => 'Please log in to vote'));
            return;
        }

        global $wpdb;
        
        // Check if user already voted
        $existing_vote = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpcs_poll_votes WHERE user_id = %d AND poll_id = %d",
            $user_id, $poll_id
        ));

        if ($existing_vote) {
            wp_send_json_error(array('message' => 'You have already voted on this poll'));
            return;
        }

        // Record the vote
        $result = $wpdb->insert(
            $wpdb->prefix . 'wpcs_poll_votes',
            array(
                'user_id' => $user_id,
                'poll_id' => $poll_id,
                'option_id' => $option_id,
                'ip_address' => $this->get_user_ip()
            ),
            array('%d', '%d', '%s', '%s')
        );

        if ($result) {
            // Update poll option vote count
            $this->update_poll_vote_counts($poll_id);
            
            wp_send_json_success(array(
                'message' => 'Vote recorded successfully',
                'poll_id' => $poll_id,
                'option_id' => $option_id
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to record vote'));
        }
    }

    public function handle_bookmark() {
        if (!wp_verify_nonce($_POST['nonce'], 'wpcs_poll_nonce')) {
            wp_die('Security check failed');
        }

        $poll_id = intval($_POST['poll_id']);
        $user_id = get_current_user_id();

        if (!$user_id) {
            wp_send_json_error(array('message' => 'Please log in to bookmark'));
            return;
        }

        global $wpdb;
        
        // Check if already bookmarked
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpcs_poll_bookmarks WHERE user_id = %d AND poll_id = %d",
            $user_id, $poll_id
        ));

        if ($existing) {
            // Remove bookmark
            $wpdb->delete(
                $wpdb->prefix . 'wpcs_poll_bookmarks',
                array('user_id' => $user_id, 'poll_id' => $poll_id)
            );
            wp_send_json_success(array('action' => 'removed', 'message' => 'Bookmark removed'));
        } else {
            // Add bookmark
            $wpdb->insert(
                $wpdb->prefix . 'wpcs_poll_bookmarks',
                array('user_id' => $user_id, 'poll_id' => $poll_id)
            );
            wp_send_json_success(array('action' => 'added', 'message' => 'Poll bookmarked'));
        }
    }

    private function update_poll_vote_counts($poll_id) {
        global $wpdb;
        
        // Get poll options
        $poll = $wpdb->get_row($wpdb->prepare(
            "SELECT options FROM {$wpdb->prefix}wpcs_polls WHERE id = %d",
            $poll_id
        ));

        if (!$poll) return;

        $options = json_decode($poll->options, true);
        
        // Count votes for each option
        foreach ($options as &$option) {
            $vote_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_poll_votes WHERE poll_id = %d AND option_id = %s",
                $poll_id, $option['id']
            ));
            $option['votes'] = intval($vote_count);
        }

        // Update poll with new vote counts
        $wpdb->update(
            $wpdb->prefix . 'wpcs_polls',
            array('options' => json_encode($options)),
            array('id' => $poll_id)
        );
    }

    private function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
}
