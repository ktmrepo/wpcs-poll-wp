<?php

class WPCS_Poll_Database {

    private $wpdb;
    private $table_polls;
    private $table_votes;
    private $table_bookmarks;
    private $table_bulk_uploads;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_polls = $this->wpdb->prefix . 'wpcs_polls';
        $this->table_votes = $this->wpdb->prefix . 'wpcs_poll_votes';
        $this->table_bookmarks = $this->wpdb->prefix . 'wpcs_poll_bookmarks';
        $this->table_bulk_uploads = $this->wpdb->prefix . 'wpcs_poll_bulk_uploads';
    }

    // Poll CRUD operations
    public function create_poll($data) {
        if (empty($data['title']) || !isset($data['options']) || empty($data['created_by'])) {
            return new WP_Error('missing_data', __('Missing required data: title, options, or created_by.', 'wpcs-poll'));
        }

        $defaults = array(
            'description' => null,
            'category' => 'General',
            'tags' => null,
            'is_active' => 0,
            'created_at' => current_time('mysql', 1), // GMT
            'updated_at' => current_time('mysql', 1), // GMT
        );
        $data = wp_parse_args($data, $defaults);

        $formats = array(
            '%s', // title
            '%s', // description
            '%s', // category
            '%s', // options (JSON string)
            '%s', // tags
            '%d', // is_active
            '%d', // created_by
            '%s', // created_at
            '%s', // updated_at
        );

        // Ensure options are JSON encoded if they are not already a string
        if (is_array($data['options']) || is_object($data['options'])) {
            $data['options'] = wp_json_encode($data['options']);
            if ($data['options'] === false) {
                 return new WP_Error('json_encode_failed', __('Failed to encode options to JSON.', 'wpcs-poll'));
            }
        } elseif (!is_string($data['options']) || !json_decode($data['options'])) {
             return new WP_Error('invalid_options_format', __('Options must be a valid JSON string or an array/object.', 'wpcs-poll'));
        }


        $insert_data = array(
            'title' => $data['title'],
            'description' => $data['description'],
            'category' => $data['category'],
            'options' => $data['options'],
            'tags' => $data['tags'],
            'is_active' => $data['is_active'],
            'created_by' => $data['created_by'],
            'created_at' => $data['created_at'],
            'updated_at' => $data['updated_at'],
        );

        $result = $this->wpdb->insert($this->table_polls, $insert_data, $formats);

        if ($result === false) {
            return new WP_Error('db_insert_error', __('Failed to insert poll into the database.', 'wpcs-poll'), $this->wpdb->last_error);
        }

        return $this->wpdb->insert_id;
    }

    public function get_poll($poll_id) {
        if (empty($poll_id)) {
            return null;
        }

        $poll_id = absint($poll_id);
        if ($poll_id <= 0) {
            return null;
        }

        $sql = $this->wpdb->prepare("SELECT * FROM {$this->table_polls} WHERE id = %d", $poll_id);
        $poll = $this->wpdb->get_row($sql, OBJECT);

        if (empty($poll)) {
            return null;
        }

        // Decode JSON options
        if (isset($poll->options)) {
            $decoded_options = json_decode($poll->options, true);
            // If json_decode fails, it returns null.
            // It might be better to return the raw string or handle error.
            // For consistency with get_polls, we'll assign it.
            $poll->options = $decoded_options === null ? $poll->options : $decoded_options;
        }

        return $poll; // Returns poll object (stdClass) or null
    }

    public function get_polls($args = array()) {
        $defaults = array(
            'orderby' => 'created_at',
            'order'   => 'DESC',
            'posts_per_page' => -1, // -1 for all, or a number for pagination
            'offset' => 0,
            // Future filtering args:
            // 'is_active' => null,
            // 'category' => '',
            // 'search' => '',
        );
        $args = wp_parse_args($args, $defaults);

        // Validate orderby column
        $allowed_orderby_columns = array('id', 'title', 'category', 'is_active', 'created_at', 'updated_at');
        if (!in_array($args['orderby'], $allowed_orderby_columns)) {
            $args['orderby'] = 'created_at';
        }

        // Validate order direction
        if (!in_array(strtoupper($args['order']), array('ASC', 'DESC'))) {
            $args['order'] = 'DESC';
        }

        // Base SQL
        $sql = "SELECT * FROM {$this->table_polls}";

        // TODO: Add WHERE clauses for filtering

        // Add ORDER BY
        $sql .= " ORDER BY {$args['orderby']} {$args['order']}";

        // Add LIMIT and OFFSET for pagination
        if ($args['posts_per_page'] > 0) {
            $sql .= $this->wpdb->prepare(" LIMIT %d OFFSET %d", absint($args['posts_per_page']), absint($args['offset']));
        }

        $results = $this->wpdb->get_results($sql, OBJECT);

        if ($results === null) {
            return new WP_Error('db_query_error', __('Failed to retrieve polls from the database.', 'wpcs-poll'), $this->wpdb->last_error);
        }

        if (!empty($results)) {
            foreach ($results as $key => $poll) {
                if (isset($poll->options)) {
                    $decoded_options = json_decode($poll->options, true);
                    $results[$key]->options = $decoded_options === null ? $poll->options : $decoded_options;
                }
            }
        }

        return $results;
    }

    public function get_polls_count($args = array()) {
        // Args for filtering, similar to get_polls but without pagination/ordering
        // For now, we'll just count all polls.
        // TODO: Add WHERE clauses for filtering (is_active, category, search)
        // to make this count accurate with filters applied in get_polls.

        $sql = "SELECT COUNT(id) FROM {$this->table_polls}";

        // Example for future filtering:
        // $where_clauses = array();
        // if (isset($args['is_active'])) { $where_clauses[] = $this->wpdb->prepare("is_active = %d", $args['is_active']); }
        // if (!empty($where_clauses)) { $sql .= " WHERE " . implode(" AND ", $where_clauses); }

        $count = $this->wpdb->get_var($sql);

        if ($count === null) {
            // This indicates an error in the query.
            return new WP_Error('db_count_error', __('Failed to count polls in the database.', 'wpcs-poll'), $this->wpdb->last_error);
        }

        return absint($count);
    }

    public function update_poll($poll_id, $data) {
        $poll_id = absint($poll_id);
        if ($poll_id <= 0) {
            return new WP_Error('invalid_poll_id', __('Invalid Poll ID provided for update.', 'wpcs-poll'));
        }

        // Ensure there's data to update
        if (empty($data)) {
            return new WP_Error('no_data_to_update', __('No data provided for poll update.', 'wpcs-poll'));
        }

        // Prepare data and formats for $wpdb->update
        $update_data = array();
        $update_formats = array();

        // Title (required for creation, optional for update but usually present)
        if (isset($data['title'])) {
            if (empty($data['title'])) {
                return new WP_Error('empty_title', __('Poll title cannot be empty.', 'wpcs-poll'));
            }
            $update_data['title'] = $data['title'];
            $update_formats[] = '%s';
        }

        if (isset($data['description'])) {
            $update_data['description'] = $data['description'];
            $update_formats[] = '%s';
        }

        if (isset($data['category'])) {
            $update_data['category'] = $data['category'];
            $update_formats[] = '%s';
        }

        if (isset($data['options'])) {
            if (is_array($data['options']) || is_object($data['options'])) {
                $json_options = wp_json_encode($data['options']);
                if ($json_options === false) {
                    return new WP_Error('json_encode_failed', __('Failed to encode options to JSON for update.', 'wpcs-poll'));
                }
                $update_data['options'] = $json_options;
            } elseif (is_string($data['options']) && json_decode($data['options']) !== null) {
                $update_data['options'] = $data['options']; // Assume valid JSON string
            } else {
                return new WP_Error('invalid_options_format', __('Options must be a valid JSON string or an array/object for update.', 'wpcs-poll'));
            }
            $update_formats[] = '%s';
        }

        if (isset($data['tags'])) {
            $update_data['tags'] = $data['tags'];
            $update_formats[] = '%s';
        }

        if (isset($data['is_active'])) {
            $update_data['is_active'] = absint($data['is_active']);
            $update_formats[] = '%d';
        }

        // Always update the 'updated_at' timestamp
        $update_data['updated_at'] = current_time('mysql', 1); // GMT
        $update_formats[] = '%s';


        // If no updatable fields were actually provided (besides updated_at)
        if (count($update_data) <= 1 && isset($update_data['updated_at'])) {
             // Or just return true if only updated_at was set, indicating no other changes.
            // For now, let's consider it an edge case that might not need an error.
            // return new WP_Error('no_fields_to_update', __('No specific fields were provided for update.', 'wpcs-poll'));
        }
        if (empty($update_data)){
            return true; // Nothing to update
        }


        $result = $this->wpdb->update(
            $this->table_polls,
            $update_data,
            array('id' => $poll_id), // WHERE condition
            $update_formats,        // Formats for $update_data
            array('%d')             // Format for WHERE condition
        );

        if ($result === false) {
            // This means the query failed.
            return new WP_Error('db_update_error', __('Failed to update poll in the database.', 'wpcs-poll'), $this->wpdb->last_error);
        }

        // $wpdb->update returns the number of rows affected, or false on error.
        // If 0 rows were affected but no error, it means the data was the same.
        // We can consider this a success.
        return true;
    }

    public function delete_poll($poll_id) {
        $poll_id = absint($poll_id);
        if ($poll_id <= 0) {
            return new WP_Error('invalid_poll_id', __('Invalid Poll ID provided for deletion.', 'wpcs-poll'));
        }

        // TODO: Consider related data deletion (e.g., votes, bookmarks associated with this poll).
        // This depends on desired behavior (cascade delete, keep orphaned data, or configurable).
        // For now, we will only delete the poll itself.

        $result = $this->wpdb->delete(
            $this->table_polls,
            array('id' => $poll_id), // WHERE condition
            array('%d')             // Format for WHERE condition
        );

        if ($result === false) {
            // This means the query failed.
            return new WP_Error('db_delete_error', __('Failed to delete poll from the database.', 'wpcs-poll'), $this->wpdb->last_error);
        }

        if ($result === 0) {
            // This means no rows were affected, which implies the poll_id didn't exist.
            // Depending on desired strictness, this could be an error or just a non-event.
            // For robustness, let's treat it as if the poll was already gone or never there.
            return new WP_Error('poll_not_found_for_delete', __('Poll not found for deletion, or already deleted.', 'wpcs-poll'));
        }

        // $wpdb->delete returns the number of rows affected, or false on error.
        return true; // Successfully deleted one or more rows (should be 1).
    }

    // Vote management
    public function add_vote($user_id, $poll_id, $option_id, $ip_address = '') {
        // TODO: Implement adding a vote
        // Returns new vote ID or WP_Error
    }

    public function get_votes_for_poll($poll_id) {
        // TODO: Implement fetching all votes for a specific poll
        // Returns array of vote objects or empty array
    }

    public function get_vote_counts_for_poll($poll_id) {
        // TODO: Implement fetching vote counts per option for a poll
        // Returns array (option_id => count) or empty array
    }

    public function has_user_voted($user_id, $poll_id) {
        // TODO: Implement checking if a user has already voted on a specific poll
        // Returns true if voted, false otherwise
    }

    public function get_user_vote($user_id, $poll_id) {
        // TODO: Implement fetching the user's specific vote on a poll
        // Returns vote object or null
    }

    // Bookmark management
    public function add_bookmark($user_id, $poll_id) {
        // TODO: Implement adding a bookmark
        // Returns new bookmark ID or WP_Error
    }

    public function remove_bookmark($user_id, $poll_id) {
        // TODO: Implement removing a bookmark
        // Returns true on success, false or WP_Error on failure
    }

    public function get_user_bookmarks($user_id, $args = array()) {
        // TODO: Implement fetching all polls bookmarked by a user
        // $args could include pagination parameters
        // Returns array of poll objects or empty array
    }

    public function is_bookmarked($user_id, $poll_id) {
        // TODO: Implement checking if a user has bookmarked a poll
        // Returns true if bookmarked, false otherwise
    }

    // Bulk upload management
    public function log_bulk_upload($data) {
        if (empty($data['user_id']) || empty($data['filename'])) {
            return new WP_Error('missing_bulk_upload_data', __('Missing required data for logging bulk upload: user_id, filename.', 'wpcs-poll'));
        }

        $defaults = array(
            'total_records' => 0,
            'successful_imports' => 0,
            'failed_imports' => 0,
            'status' => 'pending', // e.g., pending, processing, completed, failed
            'error_log' => null,
            'created_at' => current_time('mysql', 1), // GMT
        );
        $data = wp_parse_args($data, $defaults);

        $insert_data = array(
            'user_id' => absint($data['user_id']),
            'filename' => sanitize_file_name($data['filename']),
            'total_records' => absint($data['total_records']),
            'successful_imports' => absint($data['successful_imports']),
            'failed_imports' => absint($data['failed_imports']),
            'status' => sanitize_text_field($data['status']),
            'error_log' => is_string($data['error_log']) ? $data['error_log'] : null,
            'created_at' => $data['created_at'],
        );

        $formats = array('%d', '%s', '%d', '%d', '%d', '%s', '%s', '%s');

        $result = $this->wpdb->insert($this->table_bulk_uploads, $insert_data, $formats);

        if ($result === false) {
            return new WP_Error('db_insert_error_bulk_log', __('Failed to log bulk upload task.', 'wpcs-poll'), $this->wpdb->last_error);
        }
        return $this->wpdb->insert_id;
    }

    public function update_bulk_upload_status($upload_id, $status, $successful_imports = null, $failed_imports = null, $error_log = null) {
        $upload_id = absint($upload_id);
        if ($upload_id <= 0) {
            return new WP_Error('invalid_upload_id', __('Invalid Upload ID provided for status update.', 'wpcs-poll'));
        }

        $update_data = array('status' => sanitize_text_field($status));
        $update_formats = array('%s');

        if ($successful_imports !== null) {
            $update_data['successful_imports'] = absint($successful_imports);
            $update_formats[] = '%d';
        }
        if ($failed_imports !== null) {
            $update_data['failed_imports'] = absint($failed_imports);
            $update_formats[] = '%d';
        }
        if ($error_log !== null) {
            $update_data['error_log'] = is_string($error_log) ? $error_log : wp_json_encode($error_log); // Store complex errors as JSON
            $update_formats[] = '%s';
        }

        // Prevent updating other fields like total_records or filename here.

        $result = $this->wpdb->update(
            $this->table_bulk_uploads,
            $update_data,
            array('id' => $upload_id),
            $update_formats,
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_update_error_bulk_log', __('Failed to update bulk upload task status.', 'wpcs-poll'), $this->wpdb->last_error);
        }
        return true;
    }

    public function get_bulk_upload_log($upload_id) {
        // TODO: Implement fetching a specific bulk upload log
        // Returns log object or null
    }

    public function get_bulk_upload_logs($args = array()) {
        // TODO: Implement fetching bulk upload logs with optional filters/pagination
        // Returns array of log objects or empty array
    }
}
