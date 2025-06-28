<?php
/**
 * Poll Management Admin Page
 *
 * @package WPCS_Poll
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Handle bulk actions
if (isset($_POST['action']) && $_POST['action'] !== '-1') {
    $action = sanitize_text_field($_POST['action']);
    $poll_ids = array_map('intval', $_POST['poll_ids'] ?? []);
    
    if (!empty($poll_ids)) {
        switch ($action) {
            case 'activate':
                $wpdb->query("UPDATE {$wpdb->prefix}wpcs_polls SET is_active = 1 WHERE id IN (" . implode(',', $poll_ids) . ")");
                echo '<div class="notice notice-success"><p>' . __('Selected polls have been activated.', 'wpcs-poll') . '</p></div>';
                break;
            case 'deactivate':
                $wpdb->query("UPDATE {$wpdb->prefix}wpcs_polls SET is_active = 0 WHERE id IN (" . implode(',', $poll_ids) . ")");
                echo '<div class="notice notice-success"><p>' . __('Selected polls have been deactivated.', 'wpcs-poll') . '</p></div>';
                break;
            case 'delete':
                $wpdb->query("DELETE FROM {$wpdb->prefix}wpcs_polls WHERE id IN (" . implode(',', $poll_ids) . ")");
                echo '<div class="notice notice-success"><p>' . __('Selected polls have been deleted.', 'wpcs-poll') . '</p></div>';
                break;
        }
    }
}

// Get polls with pagination
$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;

$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

$where_conditions = ['1=1'];
$where_values = [];

if ($search) {
    $where_conditions[] = "(title LIKE %s OR description LIKE %s)";
    $where_values[] = '%' . $search . '%';
    $where_values[] = '%' . $search . '%';
}

if ($status_filter === 'active') {
    $where_conditions[] = "is_active = 1";
} elseif ($status_filter === 'inactive') {
    $where_conditions[] = "is_active = 0";
}

$where_clause = implode(' AND ', $where_conditions);

$total_polls = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_polls WHERE {$where_clause}",
    $where_values
));

$polls = $wpdb->get_results($wpdb->prepare(
    "SELECT p.*, u.display_name as creator_name,
            (SELECT COUNT(*) FROM {$wpdb->prefix}wpcs_poll_votes v WHERE v.poll_id = p.id) as vote_count
     FROM {$wpdb->prefix}wpcs_polls p
     LEFT JOIN {$wpdb->prefix}users u ON p.created_by = u.ID
     WHERE {$where_clause}
     ORDER BY p.created_at DESC
     LIMIT %d OFFSET %d",
    array_merge($where_values, [$per_page, $offset])
));

$total_pages = ceil($total_polls / $per_page);
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- Search and Filters -->
    <form method="get" class="search-form">
        <input type="hidden" name="page" value="wpcs-poll-manage">
        <p class="search-box">
            <label class="screen-reader-text" for="poll-search-input"><?php _e('Search Polls:', 'wpcs-poll'); ?></label>
            <input type="search" id="poll-search-input" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Search polls...', 'wpcs-poll'); ?>">
            <input type="submit" id="search-submit" class="button" value="<?php _e('Search Polls', 'wpcs-poll'); ?>">
        </p>
        
        <div class="tablenav top">
            <div class="alignleft actions">
                <select name="status">
                    <option value=""><?php _e('All Statuses', 'wpcs-poll'); ?></option>
                    <option value="active" <?php selected($status_filter, 'active'); ?>><?php _e('Active', 'wpcs-poll'); ?></option>
                    <option value="inactive" <?php selected($status_filter, 'inactive'); ?>><?php _e('Inactive', 'wpcs-poll'); ?></option>
                </select>
                <input type="submit" class="button" value="<?php _e('Filter', 'wpcs-poll'); ?>">
            </div>
        </div>
    </form>

    <!-- Bulk Actions Form -->
    <form method="post" id="polls-filter">
        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <select name="action" id="bulk-action-selector-top">
                    <option value="-1"><?php _e('Bulk Actions', 'wpcs-poll'); ?></option>
                    <option value="activate"><?php _e('Activate', 'wpcs-poll'); ?></option>
                    <option value="deactivate"><?php _e('Deactivate', 'wpcs-poll'); ?></option>
                    <option value="delete"><?php _e('Delete', 'wpcs-poll'); ?></option>
                </select>
                <input type="submit" class="button action" value="<?php _e('Apply', 'wpcs-poll'); ?>">
            </div>
        </div>

        <!-- Polls Table -->
        <table class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input id="cb-select-all-1" type="checkbox">
                    </td>
                    <th class="manage-column column-title"><?php _e('Title', 'wpcs-poll'); ?></th>
                    <th class="manage-column column-creator"><?php _e('Creator', 'wpcs-poll'); ?></th>
                    <th class="manage-column column-category"><?php _e('Category', 'wpcs-poll'); ?></th>
                    <th class="manage-column column-votes"><?php _e('Votes', 'wpcs-poll'); ?></th>
                    <th class="manage-column column-status"><?php _e('Status', 'wpcs-poll'); ?></th>
                    <th class="manage-column column-date"><?php _e('Date', 'wpcs-poll'); ?></th>
                    <th class="manage-column column-actions"><?php _e('Actions', 'wpcs-poll'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($polls): ?>
                    <?php foreach ($polls as $poll): ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="poll_ids[]" value="<?php echo $poll->id; ?>">
                            </th>
                            <td class="column-title">
                                <strong><?php echo esc_html($poll->title); ?></strong>
                                <?php if ($poll->description): ?>
                                    <p class="description"><?php echo esc_html(wp_trim_words($poll->description, 15)); ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="column-creator">
                                <?php echo esc_html($poll->creator_name ?: 'Unknown'); ?>
                            </td>
                            <td class="column-category">
                                <?php echo esc_html($poll->category); ?>
                            </td>
                            <td class="column-votes">
                                <?php echo number_format($poll->vote_count); ?>
                            </td>
                            <td class="column-status">
                                <span class="status-badge <?php echo $poll->is_active ? 'active' : 'inactive'; ?>">
                                    <?php echo $poll->is_active ? __('Active', 'wpcs-poll') : __('Inactive', 'wpcs-poll'); ?>
                                </span>
                            </td>
                            <td class="column-date">
                                <?php echo date('Y/m/d', strtotime($poll->created_at)); ?>
                            </td>
                            <td class="column-actions">
                                <a href="#" class="button button-small" onclick="editPoll(<?php echo $poll->id; ?>)">
                                    <?php _e('Edit', 'wpcs-poll'); ?>
                                </a>
                                <a href="#" class="button button-small button-link-delete" onclick="deletePoll(<?php echo $poll->id; ?>)">
                                    <?php _e('Delete', 'wpcs-poll'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="no-items"><?php _e('No polls found.', 'wpcs-poll'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    $page_links = paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $total_pages,
                        'current' => $current_page
                    ]);
                    echo $page_links;
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </form>
</div>

<script>
function editPoll(pollId) {
    // Implement edit functionality
    alert('Edit poll functionality to be implemented');
}

function deletePoll(pollId) {
    if (confirm('<?php _e('Are you sure you want to delete this poll?', 'wpcs-poll'); ?>')) {
        // Implement delete functionality
        alert('Delete poll functionality to be implemented');
    }
}

// Select all checkbox functionality
jQuery(document).ready(function($) {
    $('#cb-select-all-1').on('change', function() {
        $('input[name="poll_ids[]"]').prop('checked', this.checked);
    });
});
</script>

<style>
.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.status-badge.active {
    background: #d4edda;
    color: #155724;
}

.status-badge.inactive {
    background: #f8d7da;
    color: #721c24;
}

.column-title .description {
    color: #666;
    font-style: italic;
    margin: 5px 0 0 0;
}
</style>