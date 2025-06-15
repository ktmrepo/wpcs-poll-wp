<?php

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WPCS_Poll_List_Table extends WP_List_Table {

    private $db;

    public function __construct($db_instance) {
        $this->db = $db_instance;
        parent::__construct(array(
            'singular' => __('Poll', 'wpcs-poll'),   // Singular name of the listed records
            'plural'   => __('Polls', 'wpcs-poll'),  // Plural name of the listed records
            'ajax'     => false                     // Does this table support ajax?
        ));
    }

    public function get_columns() {
        $columns = array(
            // 'cb'       => '<input type="checkbox" />', // For bulk actions
            'title'    => __('Title', 'wpcs-poll'),
            'category' => __('Category', 'wpcs-poll'),
            'tags'     => __('Tags', 'wpcs-poll'),
            'is_active'=> __('Status', 'wpcs-poll'),
            'votes'    => __('Votes', 'wpcs-poll'), // Placeholder for vote count
            'created_at' => __('Created At', 'wpcs-poll')
        );
        return $columns;
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'category':
            case 'tags':
            case 'created_at':
                return esc_html($item->$column_name);
            case 'is_active':
                return $item->is_active ? __('Active', 'wpcs-poll') : __('Inactive', 'wpcs-poll');
            case 'votes':
                return '0'; // Placeholder, will need a way to count votes
            default:
                return print_r($item, true); // Show the whole item for unmatched columns
        }
    }

    public function column_title($item) {
        $delete_nonce = wp_create_nonce('wpcs_delete_poll_' . $item->id);
        $actions = array(
            'edit'   => sprintf(
                '<a href="?page=%s&action=%s&poll_id=%s">' . __('Edit', 'wpcs-poll') . '</a>',
                esc_attr($_REQUEST['page']),
                'edit',
                absint($item->id)
            ),
            'delete' => sprintf(
                '<a href="?page=%s&action=%s&poll_id=%s&_wpnonce=%s" onclick="return confirm(\'' . esc_js(__('Are you sure you want to delete this poll?', 'wpcs-poll')) . '\');">' . __('Delete', 'wpcs-poll') . '</a>',
                esc_attr($_REQUEST['page']),
                'wpcs_delete_poll_action', // This will be our custom action hook name
                absint($item->id),
                esc_attr($delete_nonce)
            ),
        );
        // TODO: Add delete action with nonce and handler // This TODO can be removed or updated

        return sprintf('%1$s %2$s',
            esc_html($item->title),
            $this->row_actions($actions)
        );
    }

    // Optional: For checkboxes, if bulk actions are needed
    // public function column_cb($item) {
    //     return sprintf(
    //         '<input type="checkbox" name="poll[]" value="%s" />', $item->id
    //     );
    // }

    protected function get_sortable_columns() {
        $sortable_columns = array(
            'title'    => array('title', false), // True if a default sorted column
            'category' => array('category', false),
            'is_active'=> array('is_active', false),
            'created_at' => array('created_at', true) // Default sort by created_at
        );
        return $sortable_columns;
    }

    public function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        // Sorting parameters
        $orderby = 'created_at'; // Default orderby
        if (!empty($_REQUEST['orderby']) && array_key_exists($_REQUEST['orderby'], $sortable)) {
            // Ensure the orderby value is a key in $sortable to prevent SQL injection
            $orderby = sanitize_key($_REQUEST['orderby']);
        }

        $order = 'DESC'; // Default order
        if (!empty($_REQUEST['order']) && in_array(strtoupper($_REQUEST['order']), array('ASC', 'DESC'))) {
            $order = strtoupper($_REQUEST['order']);
        }

        // Pagination parameters
        $per_page = $this->get_items_per_page('polls_per_page', 20);
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        // Get total items count
        $total_items_result = $this->db->get_polls_count(); // TODO: Pass filter args if any

        if (is_wp_error($total_items_result)) {
            $total_items = 0;
            // Optionally add an admin notice here for the error
            // error_log("Error fetching polls count: " . $total_items_result->get_error_message());
        } else {
            $total_items = $total_items_result;
        }

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));

        // Fetch the data for the current page
        $db_args = array(
            'orderby' => $orderby,
            'order' => $order,
            'posts_per_page' => $per_page,
            'offset' => $offset,
            // TODO: Pass filter args if any
        );

        $this->items = $this->db->get_polls($db_args);

        if (is_wp_error($this->items)) {
            // Optionally add an admin notice here for the error
            // error_log("Error fetching polls for list table: " . $this->items->get_error_message());
            $this->items = array(); // Ensure items is an array if error
        }
    }
}
