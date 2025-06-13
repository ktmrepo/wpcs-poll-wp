<?php
class WPCS_Poll_REST_API {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        register_rest_route('wpcs-poll/v1', '/polls', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_polls'),
                'permission_callback' => '__return_true'
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'create_poll'),
                'permission_callback' => array($this, 'check_create_permission')
            )
        ));

        register_rest_route('wpcs-poll/v1', '/polls/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_poll'),
                'permission_callback' => '__return_true'
            ),
            array(
                'methods' => 'PUT',
                'callback' => array($this, 'update_poll'),
                'permission_callback' => array($this, 'check_edit_permission')
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'delete_poll'),
                'permission_callback' => array($this, 'check_delete_permission')
            )
        ));

        register_rest_route('wpcs-poll/v1', '/polls/(?P<id>\d+)/vote', array(
            'methods' => 'POST',
            'callback' => array($this, 'vote_on_poll'),
            'permission_callback' => array($this, 'check_vote_permission')
        ));

        register_rest_route('wpcs-poll/v1', '/user/dashboard', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user_dashboard'),
            'permission_callback' => array($this, 'check_user_permission')
        ));

        register_rest_route('wpcs-poll/v1', '/admin/stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_admin_stats'),
            'permission_callback' => array($this, 'check_admin_permission')
        ));
    }

    public function get_polls($request) {
        global $wpdb;
        
        $category = $request->get_param('category');
        $limit = $request->get_param('limit') ?: 10;
        $offset = $request->get_param('offset') ?: 0;
        $search = $request->get_param('search');
        $user_id = get_current_user_id();

        $where_conditions = array("p.is_active = 1");
        $where_values = array();

        if ($category && $category !== 'all') {
            $where_conditions[] = "p.category = %s";
            $where_values[] = $category;
        }

        if ($search) {
            $where_conditions[] = "(p.title LIKE %s OR p.description LIKE %s)";
            $where_values[] = '%' . $search . '%';
            $where_values[] = '%' . $search . '%';
        }

        $where_clause = implode(' AND ', $where_conditions);

        $query = "
            SELECT p.*, u.display_name as creator_name,
                   (SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_poll_votes v WHERE v.poll_id = p.id) as total_votes,
                   " . ($user_id ? "(SELECT option_id FROM {$wpdb->prefix}wpcs_poll_votes v WHERE v.poll_id = p.id AND v.user_id = %d) as user_vote" : "NULL as user_vote") . ",
                   " . ($user_id ? "(SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_poll_bookmarks b WHERE b.poll_id = p.id AND b.user_id = %d) as is_bookmarked" : "0 as is_bookmarked") . "
            FROM {$wpdb->prefix}wpcs_polls p
            LEFT JOIN {$wpdb->prefix}users u ON p.created_by = u.ID
            WHERE {$where_clause}
            ORDER BY p.created_at DESC
            LIMIT %d OFFSET %d
        ";

        if ($user_id) {
            $where_values[] = $user_id;
            $where_values[] = $user_id;
        }
        $where_values[] = $limit;
        $where_values[] = $offset;

        $polls = $wpdb->get_results($wpdb->prepare($query, $where_values));

        // Process polls data
        foreach ($polls as &$poll) {
            $poll->options = json_decode($poll->options, true);
            $poll->tags = $poll->tags ? explode(',', $poll->tags) : array();
            $poll->is_bookmarked = (bool) $poll->is_bookmarked;
        }

        return rest_ensure_response($polls);
    }

    public function get_poll($request) {
        global $wpdb;
        
        $poll_id = $request['id'];
        $user_id = get_current_user_id();

        $query = "
            SELECT p.*, u.display_name as creator_name,
                   (SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_poll_votes v WHERE v.poll_id = p.id) as total_votes,
                   " . ($user_id ? "(SELECT option_id FROM {$wpdb->prefix}wpcs_poll_votes v WHERE v.poll_id = p.id AND v.user_id = %d) as user_vote" : "NULL as user_vote") . "
            FROM {$wpdb->prefix}wpcs_polls p
            LEFT JOIN {$wpdb->prefix}users u ON p.created_by = u.ID
            WHERE p.id = %d
        ";

        $values = $user_id ? array($user_id, $poll_id) : array($poll_id);
        $poll = $wpdb->get_row($wpdb->prepare($query, $values));

        if (!$poll) {
            return new WP_Error('poll_not_found', 'Poll not found', array('status' => 404));
        }

        $poll->options = json_decode($poll->options, true);
        $poll->tags = $poll->tags ? explode(',', $poll->tags) : array();

        return rest_ensure_response($poll);
    }

    public function create_poll($request) {
        global $wpdb;

        $user_id = get_current_user_id();
        $title = sanitize_text_field($request->get_param('title'));
        $description = sanitize_textarea_field($request->get_param('description'));
        $category = sanitize_text_field($request->get_param('category')) ?: 'General';
        $options = $request->get_param('options');
        $tags = $request->get_param('tags');

        // Validate required fields
        if (empty($title) || empty($options) || !is_array($options) || count($options) < 2) {
            return new WP_Error('invalid_data', 'Title and at least 2 options are required', array('status' => 400));
        }

        // Process options
        $processed_options = array();
        foreach ($options as $index => $option) {
            $processed_options[] = array(
                'id' => 'option_' . ($index + 1),
                'text' => sanitize_text_field($option),
                'votes' => 0
            );
        }

        // Process tags
        $processed_tags = '';
        if ($tags && is_array($tags)) {
            $processed_tags = implode(',', array_map('sanitize_text_field', $tags));
        }

        // Insert poll
        $result = $wpdb->insert(
            $wpdb->prefix . 'wpcs_polls',
            array(
                'title' => $title,
                'description' => $description,
                'category' => $category,
                'options' => json_encode($processed_options),
                'tags' => $processed_tags,
                'is_active' => current_user_can('manage_options') ? 1 : 0, // Auto-approve for admins
                'created_by' => $user_id
            ),
            array('%s', '%s', '%s', '%s', '%s', '%d', '%d')
        );

        if ($result) {
            $poll_id = $wpdb->insert_id;
            return rest_ensure_response(array(
                'id' => $poll_id,
                'message' => current_user_can('manage_options') ? 'Poll created and published' : 'Poll created and pending approval'
            ));
        } else {
            return new WP_Error('creation_failed', 'Failed to create poll', array('status' => 500));
        }
    }

    public function vote_on_poll($request) {
        global $wpdb;

        $poll_id = $request['id'];
        $option_id = sanitize_text_field($request->get_param('option_id'));
        $user_id = get_current_user_id();

        if (!$user_id) {
            return new WP_Error('login_required', 'Please log in to vote', array('status' => 401));
        }

        // Check if user already voted
        $existing_vote = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpcs_poll_votes WHERE user_id = %d AND poll_id = %d",
            $user_id, $poll_id
        ));

        if ($existing_vote) {
            return new WP_Error('already_voted', 'You have already voted on this poll', array('status' => 400));
        }

        // Verify poll exists and is active
        $poll = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpcs_polls WHERE id = %d AND is_active = 1",
            $poll_id
        ));

        if (!$poll) {
            return new WP_Error('poll_not_found', 'Poll not found or inactive', array('status' => 404));
        }

        // Record vote
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
            // Update vote counts
            $this->update_poll_vote_counts($poll_id);
            
            return rest_ensure_response(array(
                'message' => 'Vote recorded successfully',
                'poll_id' => $poll_id,
                'option_id' => $option_id
            ));
        } else {
            return new WP_Error('vote_failed', 'Failed to record vote', array('status' => 500));
        }
    }

    public function get_user_dashboard($request) {
        global $wpdb;

        $user_id = get_current_user_id();

        // Get user's voting stats
        $voting_stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_votes,
                COUNT(DISTINCT poll_id) as polls_voted_on
            FROM {$wpdb->prefix}wpcs_poll_votes 
            WHERE user_id = %d
        ", $user_id));

        // Get user's created polls
        $created_polls = $wpdb->get_results($wpdb->prepare("
            SELECT p.*, 
                   (SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_poll_votes v WHERE v.poll_id = p.id) as total_votes
            FROM {$wpdb->prefix}wpcs_polls p
            WHERE p.created_by = %d
            ORDER BY p.created_at DESC
            LIMIT 10
        ", $user_id));

        // Get user's bookmarked polls
        $bookmarked_polls = $wpdb->get_results($wpdb->prepare("
            SELECT p.*, b.created_at as bookmarked_at,
                   (SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_poll_votes v WHERE v.poll_id = p.id) as total_votes
            FROM {$wpdb->prefix}wpcs_poll_bookmarks b
            JOIN {$wpdb->prefix}wpcs_polls p ON b.poll_id = p.id
            WHERE b.user_id = %d AND p.is_active = 1
            ORDER BY b.created_at DESC
            LIMIT 10
        ", $user_id));

        // Get recent voting activity
        $recent_votes = $wpdb->get_results($wpdb->prepare("
            SELECT v.*, p.title as poll_title, p.category
            FROM {$wpdb->prefix}wpcs_poll_votes v
            JOIN {$wpdb->prefix}wpcs_polls p ON v.poll_id = p.id
            WHERE v.user_id = %d
            ORDER BY v.created_at DESC
            LIMIT 10
        ", $user_id));

        return rest_ensure_response(array(
            'voting_stats' => $voting_stats,
            'created_polls' => $created_polls,
            'bookmarked_polls' => $bookmarked_polls,
            'recent_votes' => $recent_votes
        ));
    }

    public function get_admin_stats($request) {
        global $wpdb;

        // Total counts
        $total_polls = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_polls");
        $active_polls = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_polls WHERE is_active = 1");
        $pending_polls = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_polls WHERE is_active = 0");
        $total_votes = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_poll_votes");
        $total_users = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}users");

        // Popular categories
        $popular_categories = $wpdb->get_results("
            SELECT category, COUNT(*) as count
            FROM {$wpdb->prefix}wpcs_polls
            WHERE is_active = 1
            GROUP BY category
            ORDER BY count DESC
            LIMIT 10
        ");

        // Recent activity
        $recent_polls = $wpdb->get_results("
            SELECT p.*, u.display_name as creator_name,
                   (SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_poll_votes v WHERE v.poll_id = p.id) as vote_count
            FROM {$wpdb->prefix}wpcs_polls p
            LEFT JOIN {$wpdb->prefix}users u ON p.created_by = u.ID
            ORDER BY p.created_at DESC
            LIMIT 10
        ");

        // Most active users
        $active_users = $wpdb->get_results("
            SELECT u.display_name, u.user_email, COUNT(v.id) as vote_count
            FROM {$wpdb->prefix}users u
            JOIN {$wpdb->prefix}wpcs_poll_votes v ON u.ID = v.user_id
            GROUP BY u.ID
            ORDER BY vote_count DESC
            LIMIT 10
        ");

        return rest_ensure_response(array(
            'totals' => array(
                'polls' => $total_polls,
                'active_polls' => $active_polls,
                'pending_polls' => $pending_polls,
                'votes' => $total_votes,
                'users' => $total_users
            ),
            'popular_categories' => $popular_categories,
            'recent_polls' => $recent_polls,
            'active_users' => $active_users
        ));
    }

    // Permission callbacks
    public function check_create_permission() {
        return is_user_logged_in();
    }

    public function check_edit_permission($request) {
        if (!is_user_logged_in()) {
            return false;
        }

        if (current_user_can('manage_options')) {
            return true;
        }

        // Check if user owns the poll
        global $wpdb;
        $poll = $wpdb->get_row($wpdb->prepare(
            "SELECT created_by FROM {$wpdb->prefix}wpcs_polls WHERE id = %d",
            $request['id']
        ));

        return $poll && $poll->created_by == get_current_user_id();
    }

    public function check_delete_permission($request) {
        return current_user_can('manage_options') || $this->check_edit_permission($request);
    }

    public function check_vote_permission() {
        return is_user_logged_in();
    }

    public function check_user_permission() {
        return is_user_logged_in();
    }

    public function check_admin_permission() {
        return current_user_can('manage_options');
    }

    private function update_poll_vote_counts($poll_id) {
        global $wpdb;
        
        $poll = $wpdb->get_row($wpdb->prepare(
            "SELECT options FROM {$wpdb->prefix}wpcs_polls WHERE id = %d",
            $poll_id
        ));

        if (!$poll) return;

        $options = json_decode($poll->options, true);
        
        foreach ($options as &$option) {
            $vote_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_poll_votes WHERE poll_id = %d AND option_id = %s",
                $poll_id, $option['id']
            ));
            $option['votes'] = intval($vote_count);
        }

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