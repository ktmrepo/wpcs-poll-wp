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
        // TODO: Implement fetching multiple polls with optional filters/pagination
        // $args could include: category, tags, is_active, created_by, search_term, orderby, order, posts_per_page, offset

        // For now, a simple query to get all polls, ordered by creation date
        $sql = "SELECT * FROM {$this->table_polls} ORDER BY created_at DESC";

        $results = $this->wpdb->get_results($sql, OBJECT);

        if ($results === null) {
            // $wpdb->get_results returns null on error.
            return new WP_Error('db_query_error', __('Failed to retrieve polls from the database.', 'wpcs-poll'), $this->wpdb->last_error);
        }

        // Decode JSON options for each poll
        if (!empty($results)) {
            foreach ($results as $key => $poll) {
                if (isset($poll->options)) {
                    $decoded_options = json_decode($poll->options, true);
                    // If json_decode fails, it returns null. Check for this.
                    // It might be better to leave it as a string if it's invalid JSON,
                    // or handle the error more robustly.
                    $results[$key]->options = $decoded_options === null ? $poll->options : $decoded_options;
                }
            }
        }

        return $results; // Returns array of poll objects (stdClass) or empty array
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
        // TODO: Implement logging a new bulk upload task
        // $data should include: user_id, filename, total_records
        // Returns new bulk upload ID or WP_Error
    }

    public function update_bulk_upload_status($upload_id, $status, $successful_imports = null, $failed_imports = null, $error_log = null) {
        // TODO: Implement updating the status and details of a bulk upload task
        // Returns true on success, false or WP_Error on failure
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
