<?php
/**
 * Database operations for WPCS Poll
 *
 * @package WPCS_Poll
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WPCS_Poll_Database {

    /**
     * Get a poll by ID
     */
    public function get_poll($poll_id) {
        global $wpdb;
        
        $poll = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpcs_polls WHERE id = %d",
            $poll_id
        ));
        
        if ($poll && $poll->options) {
            $poll->options = json_decode($poll->options, true);
        }
        
        return $poll;
    }

    /**
     * Check if user has voted on a poll
     */
    public function has_user_voted($user_id, $poll_id) {
        global $wpdb;
        
        $vote = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}wpcs_poll_votes WHERE user_id = %d AND poll_id = %d",
            $user_id, $poll_id
        ));
        
        return !empty($vote);
    }

    /**
     * Add a vote
     */
    public function add_vote($user_id, $poll_id, $option_id, $ip_address = '') {
        global $wpdb;
        
        // Check if user already voted
        if ($user_id > 0 && $this->has_user_voted($user_id, $poll_id)) {
            return new WP_Error('already_voted', __('You have already voted on this poll.', 'wpcs-poll'));
        }
        
        // Insert vote
        $result = $wpdb->insert(
            $wpdb->prefix . 'wpcs_poll_votes',
            array(
                'user_id' => $user_id,
                'poll_id' => $poll_id,
                'option_id' => $option_id,
                'ip_address' => $ip_address,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('vote_failed', __('Failed to record vote.', 'wpcs-poll'));
        }
        
        // Update poll option vote counts
        $this->update_poll_vote_counts($poll_id);
        
        return $wpdb->insert_id;
    }

    /**
     * Get vote counts for a poll
     */
    public function get_vote_counts_for_poll($poll_id) {
        global $wpdb;
        
        $poll = $this->get_poll($poll_id);
        if (!$poll || !is_array($poll->options)) {
            return new WP_Error('invalid_poll', __('Poll not found or invalid.', 'wpcs-poll'));
        }
        
        $vote_counts = array();
        foreach ($poll->options as $option) {
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_poll_votes WHERE poll_id = %d AND option_id = %s",
                $poll_id, $option['id']
            ));
            $vote_counts[$option['id']] = intval($count);
        }
        
        return $vote_counts;
    }

    /**
     * Update poll option vote counts
     */
    private function update_poll_vote_counts($poll_id) {
        global $wpdb;
        
        $poll = $this->get_poll($poll_id);
        if (!$poll || !is_array($poll->options)) {
            return false;
        }
        
        // Update vote counts for each option
        foreach ($poll->options as &$option) {
            $vote_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_poll_votes WHERE poll_id = %d AND option_id = %s",
                $poll_id, $option['id']
            ));
            $option['votes'] = intval($vote_count);
        }
        
        // Update poll with new vote counts
        return $wpdb->update(
            $wpdb->prefix . 'wpcs_polls',
            array('options' => json_encode($poll->options)),
            array('id' => $poll_id),
            array('%s'),
            array('%d')
        );
    }

    /**
     * Create a new poll
     */
    public function create_poll($data) {
        global $wpdb;
        
        $defaults = array(
            'title' => '',
            'description' => '',
            'category' => 'General',
            'options' => array(),
            'tags' => '',
            'is_active' => 0,
            'created_by' => get_current_user_id()
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Validate required fields
        if (empty($data['title']) || empty($data['options']) || !is_array($data['options']) || count($data['options']) < 2) {
            return new WP_Error('invalid_data', __('Title and at least 2 options are required.', 'wpcs-poll'));
        }
        
        // Process options
        $processed_options = array();
        foreach ($data['options'] as $index => $option) {
            $processed_options[] = array(
                'id' => 'option_' . ($index + 1),
                'text' => sanitize_text_field($option),
                'votes' => 0
            );
        }
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'wpcs_polls',
            array(
                'title' => sanitize_text_field($data['title']),
                'description' => sanitize_textarea_field($data['description']),
                'category' => sanitize_text_field($data['category']),
                'options' => json_encode($processed_options),
                'tags' => sanitize_text_field($data['tags']),
                'is_active' => intval($data['is_active']),
                'created_by' => intval($data['created_by']),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('creation_failed', __('Failed to create poll.', 'wpcs-poll'));
        }
        
        return $wpdb->insert_id;
    }

    /**
     * Get polls with filters
     */
    public function get_polls($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'limit' => 10,
            'offset' => 0,
            'category' => '',
            'search' => '',
            'user_id' => 0,
            'is_active' => null,
            'order_by' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_conditions = array();
        $where_values = array();
        
        if ($args['category'] && $args['category'] !== 'all') {
            $where_conditions[] = "category = %s";
            $where_values[] = $args['category'];
        }
        
        if ($args['search']) {
            $where_conditions[] = "(title LIKE %s OR description LIKE %s)";
            $where_values[] = '%' . $args['search'] . '%';
            $where_values[] = '%' . $args['search'] . '%';
        }
        
        if ($args['user_id']) {
            $where_conditions[] = "created_by = %d";
            $where_values[] = $args['user_id'];
        }
        
        if ($args['is_active'] !== null) {
            $where_conditions[] = "is_active = %d";
            $where_values[] = $args['is_active'];
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $order_clause = sprintf(
            'ORDER BY %s %s',
            sanitize_sql_orderby($args['order_by']),
            $args['order'] === 'ASC' ? 'ASC' : 'DESC'
        );
        
        $limit_clause = sprintf('LIMIT %d OFFSET %d', intval($args['limit']), intval($args['offset']));
        
        $query = "SELECT * FROM {$wpdb->prefix}wpcs_polls {$where_clause} {$order_clause} {$limit_clause}";
        
        if (!empty($where_values)) {
            $polls = $wpdb->get_results($wpdb->prepare($query, $where_values));
        } else {
            $polls = $wpdb->get_results($query);
        }
        
        // Process options for each poll
        foreach ($polls as &$poll) {
            if ($poll->options) {
                $poll->options = json_decode($poll->options, true);
            }
            if ($poll->tags) {
                $poll->tags = explode(',', $poll->tags);
            }
        }
        
        return $polls;
    }

    /**
     * Delete a poll
     */
    public function delete_poll($poll_id) {
        global $wpdb;
        
        // Delete votes first
        $wpdb->delete(
            $wpdb->prefix . 'wpcs_poll_votes',
            array('poll_id' => $poll_id),
            array('%d')
        );
        
        // Delete bookmarks
        $wpdb->delete(
            $wpdb->prefix . 'wpcs_poll_bookmarks',
            array('poll_id' => $poll_id),
            array('%d')
        );
        
        // Delete poll
        $result = $wpdb->delete(
            $wpdb->prefix . 'wpcs_polls',
            array('id' => $poll_id),
            array('%d')
        );
        
        return $result !== false;
    }
}