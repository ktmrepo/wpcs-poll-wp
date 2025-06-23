<?php
/**
 * AJAX Handlers for WPCS Poll
 *
 * @package WPCS_Poll
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WPCS_Poll_AJAX {

    private $db;

    public function __construct() {
        // Hooks for new vote submission using WPCS_Poll_Database
        add_action('wp_ajax_wpcs_submit_vote', array($this, 'handle_submit_vote'));
        
        $plugin_options = get_option('wpcs_poll_options', array());
        if (isset($plugin_options['guest_voting']) && $plugin_options['guest_voting'] == 1) {
            add_action('wp_ajax_nopriv_wpcs_submit_vote', array($this, 'handle_submit_vote'));
        }

        // TODO: Review and refactor other existing AJAX handlers below to use WPCS_Poll_Database
        // and consistent JSON responses if they are still needed.

        // Original hooks for other functionalities (bookmark, poll submission, admin actions)
        // These may need to be refactored or removed if their functionality is handled elsewhere
        // or if they don't follow the new architecture (e.g., direct DB access).
        add_action('wp_ajax_wpcs_poll_bookmark', array($this, 'handle_bookmark')); // Needs refactor
        add_action('wp_ajax_wpcs_poll_submit', array($this, 'handle_poll_submission')); // Needs refactor/review
        
        // Admin AJAX actions - these should ideally be in WPCS_Poll_Admin if admin-specific
        add_action('wp_ajax_wpcs_poll_admin_approve', array($this, 'handle_admin_approve')); // Needs refactor/review
        add_action('wp_ajax_wpcs_poll_admin_delete', array($this, 'handle_admin_delete'));   // Needs refactor/review
        add_action('wp_ajax_wpcs_poll_bulk_upload', array($this, 'handle_bulk_upload'));   // Needs refactor/review
    }

    /**
     * Get database handler, creating if not exists.
     */
    private function get_db() {
        if (null === $this->db) {
            if (class_exists('WPCS_Poll_Database')) {
                $this->db = new WPCS_Poll_Database();
            } else {
                // This situation should ideally not happen if plugin loads correctly.
                // Trigger an error or ensure WPCS_Poll_Database is always available.
            }
        }
        return $this->db;
    }

    /**
     * Handles the submission of a new vote.
     */
    public function handle_submit_vote() {
        // Verify nonce - new nonce for this handler
        check_ajax_referer('wpcs_poll_vote_nonce', '_ajax_nonce');

        $poll_id = isset($_POST['poll_id']) ? absint($_POST['poll_id']) : 0;
        $option_id = isset($_POST['option_id']) ? sanitize_text_field($_POST['option_id']) : '';
        $user_id = get_current_user_id(); // 0 if not logged in

        $db = $this->get_db();
        if (!$db) {
            wp_send_json_error(array('message' => __('Database service not available.', 'wpcs-poll')), 500);
            return;
        }

        if ($poll_id <= 0 || empty($option_id)) {
            wp_send_json_error(array('message' => __('Invalid poll data provided.', 'wpcs-poll')), 400);
            return;
        }

        $poll = $db->get_poll($poll_id);
        if (!$poll || empty($poll->is_active)) { 
            wp_send_json_error(array('message' => __('This poll is not currently active or does not exist.', 'wpcs-poll')), 403);
            return;
        }
        
        $valid_option = false;
        if (is_array($poll->options)) {
            foreach ($poll->options as $opt) {
                if (isset($opt['id']) && $opt['id'] === $option_id) {
                    $valid_option = true;
                    break;
                }
            }
        }
        if (!$valid_option) {
            wp_send_json_error(array('message' => __('Invalid option selected for this poll.', 'wpcs-poll')), 400);
            return;
        }

        if ($user_id > 0) {
            if ($db->has_user_voted($user_id, $poll_id)) {
                wp_send_json_error(array('message' => __('You have already voted on this poll.', 'wpcs-poll')), 403);
                return;
            }
        } else {
            $plugin_options = get_option('wpcs_poll_options', array());
            $guest_voting_allowed = isset($plugin_options['guest_voting']) && $plugin_options['guest_voting'] == 1;

            if (!$guest_voting_allowed) {
                wp_send_json_error(array('message' => __('Please log in to vote.', 'wpcs-poll')), 401);
                return;
            }
            // IP-based duplicate checking for guests would be an enhancement in add_vote or here.
        }
        
        $ip_address = '';
        if ($user_id === 0) {
            $ip_address = $this->get_client_ip(); 
        }

        $result = $db->add_vote($user_id, $poll_id, $option_id, $ip_address);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()), ($result->get_error_code() === 'already_voted' ? 403 : 400) );
        } else {
            $new_counts = $db->get_vote_counts_for_poll($poll_id);
            if (is_wp_error($new_counts)) {
                 wp_send_json_success(array(
                    'message' => __('Vote recorded successfully, but could not fetch updated results.', 'wpcs-poll'),
                    'vote_counts' => null
                ));
            } else {
                wp_send_json_success(array(
                    'message' => __('Vote recorded successfully!', 'wpcs-poll'),
                    'vote_counts' => $new_counts
                ));
            }
        }
    }

    /**
     * Get client IP address.
     * Handles various server variables.
     */
    private function get_client_ip() {
        $ip_address = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip_address = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
            $ip_address = $_SERVER['HTTP_FORWARDED'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip_address = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip_address = 'UNKNOWN';
        }
        return sanitize_text_field(wp_unslash($ip_address));
    }

    // --- Methods below are from the original file and need review/refactoring --- 

    public function handle_bookmark() {
        // TODO: Refactor to use WPCS_Poll_Database and JSON responses, check nonce 'wpcs_poll_vote_nonce' or a new one.
        if (!wp_verify_nonce($_POST['nonce'], 'wpcs_poll_nonce')) { // Original nonce
            wp_die('Security check failed');
        }

        $poll_id = intval($_POST['poll_id']);
        $user_id = get_current_user_id();

        if (!$user_id) {
            wp_send_json_error(array('message' => 'Please log in to bookmark'));
            return;
        }

        global $wpdb; // Direct DB access - needs refactor
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpcs_poll_bookmarks WHERE user_id = %d AND poll_id = %d",
            $user_id, $poll_id
        ));

        if ($existing) {
            $wpdb->delete(
                $wpdb->prefix . 'wpcs_poll_bookmarks',
                array('user_id' => $user_id, 'poll_id' => $poll_id)
            );
            wp_send_json_success(array('action' => 'removed', 'message' => 'Bookmark removed'));
        } else {
            $wpdb->insert(
                $wpdb->prefix . 'wpcs_poll_bookmarks',
                array('user_id' => $user_id, 'poll_id' => $poll_id)
            );
            wp_send_json_success(array('action' => 'added', 'message' => 'Poll bookmarked'));
        }
    }

    public function handle_poll_submission() {
        // TODO: Implement this or remove if poll submission is admin-only.
        // Needs security checks, validation, and use of WPCS_Poll_Database->create_poll().
        wp_send_json_error(array('message' => 'Poll submission not yet implemented via AJAX.'));
    }

    // Admin AJAX actions - these should ideally be moved to WPCS_Poll_Admin if they are purely admin operations.
    public function handle_admin_approve() {
        // TODO: Implement or move to admin class.
        wp_send_json_error(array('message' => 'Admin approval not yet implemented.'));
    }

    public function handle_admin_delete() {
        // TODO: Implement or move to admin class.
        wp_send_json_error(array('message' => 'Admin delete not yet implemented.'));
    }

    public function handle_bulk_upload() {
        // TODO: This seems like it would be part of the admin page form submission, not a separate AJAX action usually.
        // If it's for chunked uploads or progress, it needs full implementation.
        wp_send_json_error(array('message' => 'Bulk upload AJAX handler not yet implemented.'));
    }
}
