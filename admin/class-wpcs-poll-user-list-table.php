<?php

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WPCS_Poll_User_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct(array(
            'singular' => __('User', 'wpcs-poll'),
            'plural'   => __('Users', 'wpcs-poll'),
            'ajax'     => false // True if we want to handle actions via AJAX later
        ));
    }

    public function get_columns() {
        $columns = array(
            // 'cb'            => '<input type="checkbox" />', // For bulk actions
            'username'      => __('Username', 'wpcs-poll'),
            'name'          => __('Name', 'wpcs-poll'),
            'email'         => __('Email', 'wpcs-poll'),
            'wp_role'       => __('WordPress Role', 'wpcs-poll'),
            'wpcs_poll_role'=> __('WPCS Poll Role', 'wpcs-poll'),
            // 'polls_created' => __('Polls Created', 'wpcs-poll'), // Placeholder
            // 'votes_cast'    => __('Votes Cast', 'wpcs-poll'),    // Placeholder
        );
        return $columns;
    }

    protected function column_username($item) {
        $actions = array(
            // 'edit_wpcs_role' => sprintf('<a href="#">%s</a>', __('Edit WPCS Role', 'wpcs-poll')),
        );
        // Link to user's profile page
        $user_edit_link = get_edit_user_link($item['ID']);
        return sprintf('<a href="%s">%s</a> %s',
            esc_url($user_edit_link),
            esc_html($item['username']),
            $this->row_actions($actions)
        );
    }

    protected function column_wpcs_poll_role($item) {
            $current_role = get_user_meta($item['ID'], 'wpcs_poll_role', true);
            if (empty($current_role)) {
                // Default to 'user' if not set, or if user is not admin, otherwise 'admin'
                $user_info = get_userdata($item['ID']);
                if ($user_info && in_array('administrator', (array) $user_info->roles)) {
                    $current_role = 'admin';
                } else {
                    $current_role = 'user';
                }
            }

            $user_id = $item['ID'];
            $nonce = wp_create_nonce('wpcs_update_user_poll_role_' . $user_id);

            $output = sprintf(
                '<select class="wpcs-poll-user-role-select" name="wpcs_poll_role[%d]" data-user-id="%d" data-nonce="%s">',
                $user_id,
                $user_id,
                esc_attr($nonce)
            );
            $output .= sprintf(
                '<option value="user" %s>%s</option>',
                selected($current_role, 'user', false),
                __('User', 'wpcs-poll')
            );
            $output .= sprintf(
                '<option value="admin" %s>%s</option>',
                selected($current_role, 'admin', false),
                __('Admin (Poll)', 'wpcs-poll')
            );
            $output .= '</select>';
            // Add a spinner for visual feedback during AJAX, initially hidden
            $output .= '<span class="spinner" style="float: none; vertical-align: middle; margin-left: 5px;"></span>';

            return $output;
    }

    protected function column_wp_role($item) {
        $user_info = get_userdata($item['ID']);
        return $user_info ? implode(', ', array_map('ucfirst', $user_info->roles)) : 'N/A';
    }


    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'name':
                return esc_html($item['display_name']);
            case 'email':
                return esc_html($item['user_email']);
            // case 'polls_created':
            //     return 0; // Placeholder
            // case 'votes_cast':
            //     return 0; // Placeholder
            default:
                return isset($item[$column_name]) ? esc_html($item[$column_name]) : print_r($item, true);
        }
    }

    protected function get_sortable_columns() {
        $sortable_columns = array(
            'username' => array('login', false), // 'login' is the field name in wp_users
            'name'     => array('display_name', false),
            'email'    => array('email', false),
            // 'wpcs_poll_role' is tricky to sort server-side as it's meta. Defer for now or do client-side if small set.
        );
        return $sortable_columns;
    }

    public function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        // Sorting parameters
        $orderby = 'login'; // Default orderby
        if (!empty($_REQUEST['orderby']) && array_key_exists($_REQUEST['orderby'], $sortable)) {
            $orderby = sanitize_key($_REQUEST['orderby']);
        }

        $order = 'ASC'; // Default order
        if (!empty($_REQUEST['order']) && in_array(strtoupper($_REQUEST['order']), array('ASC', 'DESC'))) {
            $order = strtoupper($_REQUEST['order']);
        }

        // Pagination parameters
        $per_page = $this->get_items_per_page('users_per_page', 20); // Default 20 users per page
        $current_page = $this->get_pagenum();

        // Arguments for get_users()
        $user_args = array(
            'orderby' => $orderby,
            'order'   => $order,
            'number'  => $per_page,
            'paged'   => $current_page, // get_users uses 'paged' for page number
            // 'count_total' => true, // Not needed if we do a separate count query
        );

        // TODO: Add search functionality by modifying $user_args['search'] = "*{$search_term}*";

        // Get total users count
        // For a more precise count if there are filters applied by other plugins or for specific roles,
        // a custom count query might be needed or careful use of get_users with 'fields' => 'ID' and then count.
        // For now, use WordPress's count_users().
        $total_users_data = count_users();
        $total_items = isset($total_users_data['total_users']) ? $total_users_data['total_users'] : 0;
        // If you implement role filtering for example, you'd adjust $total_items based on that specific role count.

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));

        // Fetch the user data
        $users_data = get_users($user_args);

        $data = array();
        foreach ($users_data as $user) {
            $data[] = array(
                'ID'             => $user->ID,
                'username'       => $user->user_login,
                'display_name'   => $user->display_name,
                'user_email'     => $user->user_email,
                // wpcs_poll_role will be fetched in the column rendering method
            );
        }
        $this->items = $data;
    }
}
