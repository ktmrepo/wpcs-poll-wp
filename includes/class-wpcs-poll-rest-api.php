<?php
/**
 * REST API for WPCS Poll
 *
 * @package WPCS_Poll
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WPCS_Poll_REST_API {
    
    private $db;

    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
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
    }

    public function get_polls($request) {
        $db = $this->get_db();
        if (!$db) {
            return new WP_Error('database_error', 'Database service not available', array('status' => 500));
        }

        $category = $request->get_param('category');
        $limit = $request->get_param('limit') ?: 10;
        $offset = $request->get_param('offset') ?: 0;
        $search = $request->get_param('search');
        $user_id = get_current_user_id();

        // Build query arguments
        $args = array(
            'limit' => intval($limit),
            'offset' => intval($offset),
            'is_active' => 1,
            'order_by' => 'created_at',
            'order' => 'DESC'
        );

        if ($category && $category !== 'all') {
            $args['category'] = sanitize_text_field($category);
        }

        if ($search) {
            $args['search'] = sanitize_text_field($search);
        }

        try {
            $polls = $db->get_polls($args);
            
            // If no polls found, create sample polls
            if (empty($polls)) {
                $this->create_sample_polls();
                $polls = $db->get_polls($args);
            }

            // Process polls data for frontend
            foreach ($polls as &$poll) {
                // Ensure options is an array
                if (is_string($poll->options)) {
                    $poll->options = json_decode($poll->options, true);
                }
                if (!is_array($poll->options)) {
                    $poll->options = array();
                }

                // Add user vote information
                $poll->user_vote = null;
                if ($user_id) {
                    global $wpdb;
                    $poll->user_vote = $wpdb->get_var($wpdb->prepare(
                        "SELECT option_id FROM {$wpdb->prefix}wpcs_poll_votes WHERE user_id = %d AND poll_id = %d",
                        $user_id, $poll->id
                    ));
                }

                // Calculate total votes
                $poll->total_votes = 0;
                foreach ($poll->options as $option) {
                    $poll->total_votes += isset($option['votes']) ? intval($option['votes']) : 0;
                }

                // Add creator name
                if ($poll->created_by) {
                    $creator = get_userdata($poll->created_by);
                    $poll->creator_name = $creator ? $creator->display_name : 'Unknown';
                } else {
                    $poll->creator_name = 'System';
                }

                // Process tags
                if ($poll->tags) {
                    $poll->tags = is_array($poll->tags) ? $poll->tags : explode(',', $poll->tags);
                } else {
                    $poll->tags = array();
                }
            }

            return rest_ensure_response($polls);

        } catch (Exception $e) {
            return new WP_Error('fetch_error', 'Failed to fetch polls: ' . $e->getMessage(), array('status' => 500));
        }
    }

    public function get_poll($request) {
        $db = $this->get_db();
        if (!$db) {
            return new WP_Error('database_error', 'Database service not available', array('status' => 500));
        }

        $poll_id = intval($request['id']);
        $user_id = get_current_user_id();

        try {
            $poll = $db->get_poll($poll_id);

            if (!$poll) {
                return new WP_Error('poll_not_found', 'Poll not found', array('status' => 404));
            }

            // Add user vote information
            $poll->user_vote = null;
            if ($user_id) {
                global $wpdb;
                $poll->user_vote = $wpdb->get_var($wpdb->prepare(
                    "SELECT option_id FROM {$wpdb->prefix}wpcs_poll_votes WHERE user_id = %d AND poll_id = %d",
                    $user_id, $poll->id
                ));
            }

            // Calculate total votes
            $poll->total_votes = 0;
            if (is_array($poll->options)) {
                foreach ($poll->options as $option) {
                    $poll->total_votes += isset($option['votes']) ? intval($option['votes']) : 0;
                }
            }

            // Add creator name
            if ($poll->created_by) {
                $creator = get_userdata($poll->created_by);
                $poll->creator_name = $creator ? $creator->display_name : 'Unknown';
            } else {
                $poll->creator_name = 'System';
            }

            return rest_ensure_response($poll);

        } catch (Exception $e) {
            return new WP_Error('fetch_error', 'Failed to fetch poll: ' . $e->getMessage(), array('status' => 500));
        }
    }

    public function create_poll($request) {
        $db = $this->get_db();
        if (!$db) {
            return new WP_Error('database_error', 'Database service not available', array('status' => 500));
        }

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

        // Process tags
        $processed_tags = '';
        if ($tags && is_array($tags)) {
            $processed_tags = implode(',', array_map('sanitize_text_field', $tags));
        } elseif ($tags) {
            $processed_tags = sanitize_text_field($tags);
        }

        $poll_data = array(
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'options' => $options,
            'tags' => $processed_tags,
            'is_active' => current_user_can('manage_options') ? 1 : 0,
            'created_by' => $user_id
        );

        $result = $db->create_poll($poll_data);

        if (is_wp_error($result)) {
            return $result;
        } else {
            return rest_ensure_response(array(
                'id' => $result,
                'message' => current_user_can('manage_options') ? 'Poll created and published' : 'Poll created and pending approval'
            ));
        }
    }

    public function vote_on_poll($request) {
        $db = $this->get_db();
        if (!$db) {
            return new WP_Error('database_error', 'Database service not available', array('status' => 500));
        }

        $poll_id = intval($request['id']);
        $option_id = sanitize_text_field($request->get_param('option_id'));
        $user_id = get_current_user_id();

        if (!$user_id) {
            return new WP_Error('login_required', 'Please log in to vote', array('status' => 401));
        }

        $result = $db->add_vote($user_id, $poll_id, $option_id);

        if (is_wp_error($result)) {
            return $result;
        } else {
            $vote_counts = $db->get_vote_counts_for_poll($poll_id);
            return rest_ensure_response(array(
                'message' => 'Vote recorded successfully',
                'poll_id' => $poll_id,
                'option_id' => $option_id,
                'vote_counts' => $vote_counts
            ));
        }
    }

    /**
     * Create sample polls if none exist
     */
    private function create_sample_polls() {
        $db = $this->get_db();
        if (!$db) {
            return;
        }

        $sample_polls = array(
            array(
                'title' => 'What\'s your favorite programming language?',
                'description' => 'Choose the programming language you enjoy working with the most.',
                'category' => 'Technology',
                'options' => array('JavaScript', 'Python', 'PHP', 'Java'),
                'tags' => 'programming,technology,coding',
                'is_active' => 1,
                'created_by' => 0
            ),
            array(
                'title' => 'Best time to exercise?',
                'description' => 'When do you prefer to work out during the day?',
                'category' => 'Health',
                'options' => array('Early Morning', 'Afternoon', 'Evening', 'Night'),
                'tags' => 'health,fitness,exercise',
                'is_active' => 1,
                'created_by' => 0
            ),
            array(
                'title' => 'Favorite social media platform?',
                'description' => 'Which social media platform do you use the most?',
                'category' => 'Technology',
                'options' => array('Instagram', 'Twitter', 'Facebook', 'TikTok', 'LinkedIn'),
                'tags' => 'social media,technology',
                'is_active' => 1,
                'created_by' => 0
            ),
            array(
                'title' => 'Preferred work environment?',
                'description' => 'Where do you work best?',
                'category' => 'Business',
                'options' => array('Home Office', 'Coffee Shop', 'Traditional Office', 'Co-working Space'),
                'tags' => 'work,productivity,business',
                'is_active' => 1,
                'created_by' => 0
            ),
            array(
                'title' => 'Favorite movie genre?',
                'description' => 'What type of movies do you enjoy watching?',
                'category' => 'Entertainment',
                'options' => array('Action', 'Comedy', 'Drama', 'Horror', 'Sci-Fi'),
                'tags' => 'movies,entertainment',
                'is_active' => 1,
                'created_by' => 0
            )
        );

        foreach ($sample_polls as $poll_data) {
            $db->create_poll($poll_data);
        }
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
}