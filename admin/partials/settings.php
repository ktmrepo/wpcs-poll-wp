<?php
/**
 * Settings Admin Page
 *
 * @package WPCS_Poll
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['submit'])) {
    // Verify nonce
    if (!wp_verify_nonce($_POST['wpcs_poll_settings_nonce'], 'wpcs_poll_settings')) {
        wp_die(__('Security check failed', 'wpcs-poll'));
    }
    
    // Save settings
    $options = array(
        'guest_voting' => isset($_POST['guest_voting']) ? 1 : 0,
        'auto_approve_polls' => isset($_POST['auto_approve_polls']) ? 1 : 0,
        'require_login_to_create' => isset($_POST['require_login_to_create']) ? 1 : 0,
        'max_options_per_poll' => intval($_POST['max_options_per_poll']),
        'default_category' => sanitize_text_field($_POST['default_category']),
        'enable_poll_comments' => isset($_POST['enable_poll_comments']) ? 1 : 0,
        'enable_poll_sharing' => isset($_POST['enable_poll_sharing']) ? 1 : 0,
        'polls_per_page' => intval($_POST['polls_per_page']),
        'enable_analytics' => isset($_POST['enable_analytics']) ? 1 : 0,
        'delete_data_on_uninstall' => isset($_POST['delete_data_on_uninstall']) ? 1 : 0
    );
    
    update_option('wpcs_poll_options', $options);
    echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'wpcs-poll') . '</p></div>';
}

// Get current settings
$options = get_option('wpcs_poll_options', array(
    'guest_voting' => 0,
    'auto_approve_polls' => 0,
    'require_login_to_create' => 1,
    'max_options_per_poll' => 10,
    'default_category' => 'General',
    'enable_poll_comments' => 0,
    'enable_poll_sharing' => 1,
    'polls_per_page' => 10,
    'enable_analytics' => 1,
    'delete_data_on_uninstall' => 0
));

// Get version information
$plugin_version = WPCS_POLL_VERSION;
$build_date = get_option('wpcs_poll_build_date', WPCS_POLL_BUILD_DATE);
$build_number = get_option('wpcs_poll_build_number', WPCS_POLL_BUILD_NUMBER);
$install_date = get_option('wpcs_poll_install_date', 'Unknown');
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- Version Information Banner -->
    <div class="version-info-banner">
        <div class="version-header">
            <h2><?php _e('Plugin Version Information', 'wpcs-poll'); ?></h2>
            <div class="version-badge">v<?php echo esc_html($plugin_version); ?></div>
        </div>
        <div class="version-details">
            <div class="version-item">
                <strong><?php _e('Current Version:', 'wpcs-poll'); ?></strong>
                <span class="version-value"><?php echo esc_html($plugin_version); ?></span>
            </div>
            <div class="version-item">
                <strong><?php _e('Build Date:', 'wpcs-poll'); ?></strong>
                <span class="version-value"><?php echo esc_html(date('F j, Y g:i A', strtotime($build_date))); ?></span>
            </div>
            <div class="version-item">
                <strong><?php _e('Build Number:', 'wpcs-poll'); ?></strong>
                <span class="version-value"><?php echo esc_html($build_number); ?></span>
            </div>
            <div class="version-item">
                <strong><?php _e('Installation Date:', 'wpcs-poll'); ?></strong>
                <span class="version-value"><?php echo esc_html($install_date); ?></span>
            </div>
        </div>
        <div class="version-features">
            <h4><?php _e('Latest Features (v1.2.0):', 'wpcs-poll'); ?></h4>
            <ul>
                <li>✅ Horizontal TikTok-style swiping (left/right navigation)</li>
                <li>✅ Auto-advance timer with 5-second countdown after voting</li>
                <li>✅ Enhanced vote indicators with green checkmarks</li>
                <li>✅ Improved results display for voted polls</li>
                <li>✅ Fixed navigation button positioning and styling</li>
                <li>✅ Enhanced debugging and error handling</li>
                <li>✅ Version tracking system with build information</li>
            </ul>
        </div>
    </div>
    
    <form method="post" action="">
        <?php wp_nonce_field('wpcs_poll_settings', 'wpcs_poll_settings_nonce'); ?>
        
        <!-- General Settings -->
        <div class="settings-section">
            <h2><?php _e('General Settings', 'wpcs-poll'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="guest_voting"><?php _e('Guest Voting', 'wpcs-poll'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="guest_voting" name="guest_voting" value="1" <?php checked($options['guest_voting'], 1); ?>>
                        <label for="guest_voting"><?php _e('Allow non-logged-in users to vote on polls', 'wpcs-poll'); ?></label>
                        <p class="description"><?php _e('When enabled, guests can vote without creating an account.', 'wpcs-poll'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="require_login_to_create"><?php _e('Require Login to Create Polls', 'wpcs-poll'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="require_login_to_create" name="require_login_to_create" value="1" <?php checked($options['require_login_to_create'], 1); ?>>
                        <label for="require_login_to_create"><?php _e('Users must be logged in to create polls', 'wpcs-poll'); ?></label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="auto_approve_polls"><?php _e('Auto-approve Polls', 'wpcs-poll'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="auto_approve_polls" name="auto_approve_polls" value="1" <?php checked($options['auto_approve_polls'], 1); ?>>
                        <label for="auto_approve_polls"><?php _e('Automatically approve user-submitted polls', 'wpcs-poll'); ?></label>
                        <p class="description"><?php _e('When disabled, polls require admin approval before becoming active.', 'wpcs-poll'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="max_options_per_poll"><?php _e('Maximum Options per Poll', 'wpcs-poll'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="max_options_per_poll" name="max_options_per_poll" value="<?php echo esc_attr($options['max_options_per_poll']); ?>" min="2" max="20" class="small-text">
                        <p class="description"><?php _e('Maximum number of options allowed per poll (2-20).', 'wpcs-poll'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="default_category"><?php _e('Default Category', 'wpcs-poll'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="default_category" name="default_category" value="<?php echo esc_attr($options['default_category']); ?>" class="regular-text">
                        <p class="description"><?php _e('Default category for new polls.', 'wpcs-poll'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="polls_per_page"><?php _e('Polls per Page', 'wpcs-poll'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="polls_per_page" name="polls_per_page" value="<?php echo esc_attr($options['polls_per_page']); ?>" min="1" max="50" class="small-text">
                        <p class="description"><?php _e('Number of polls to display per page in listings.', 'wpcs-poll'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Feature Settings -->
        <div class="settings-section">
            <h2><?php _e('Feature Settings', 'wpcs-poll'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="enable_poll_comments"><?php _e('Poll Comments', 'wpcs-poll'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="enable_poll_comments" name="enable_poll_comments" value="1" <?php checked($options['enable_poll_comments'], 1); ?>>
                        <label for="enable_poll_comments"><?php _e('Enable comments on polls', 'wpcs-poll'); ?></label>
                        <p class="description"><?php _e('Allow users to comment on polls (requires additional development).', 'wpcs-poll'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="enable_poll_sharing"><?php _e('Poll Sharing', 'wpcs-poll'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="enable_poll_sharing" name="enable_poll_sharing" value="1" <?php checked($options['enable_poll_sharing'], 1); ?>>
                        <label for="enable_poll_sharing"><?php _e('Enable social sharing buttons on polls', 'wpcs-poll'); ?></label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="enable_analytics"><?php _e('Analytics Tracking', 'wpcs-poll'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="enable_analytics" name="enable_analytics" value="1" <?php checked($options['enable_analytics'], 1); ?>>
                        <label for="enable_analytics"><?php _e('Enable detailed analytics tracking', 'wpcs-poll'); ?></label>
                        <p class="description"><?php _e('Track detailed user interactions and poll performance.', 'wpcs-poll'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Data Management -->
        <div class="settings-section">
            <h2><?php _e('Data Management', 'wpcs-poll'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="delete_data_on_uninstall"><?php _e('Delete Data on Uninstall', 'wpcs-poll'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="delete_data_on_uninstall" name="delete_data_on_uninstall" value="1" <?php checked($options['delete_data_on_uninstall'], 1); ?>>
                        <label for="delete_data_on_uninstall"><?php _e('Delete all plugin data when uninstalling', 'wpcs-poll'); ?></label>
                        <p class="description" style="color: #d63638;">
                            <strong><?php _e('Warning:', 'wpcs-poll'); ?></strong> 
                            <?php _e('This will permanently delete all polls, votes, and related data when the plugin is uninstalled.', 'wpcs-poll'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- System Information -->
        <div class="settings-section">
            <h2><?php _e('System Information', 'wpcs-poll'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Plugin Version', 'wpcs-poll'); ?></th>
                    <td>
                        <strong style="color: #0073aa;"><?php echo WPCS_POLL_VERSION; ?></strong>
                        <span class="version-build">(Build: <?php echo esc_html($build_number); ?>)</span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('WordPress Version', 'wpcs-poll'); ?></th>
                    <td><?php echo get_bloginfo('version'); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('PHP Version', 'wpcs-poll'); ?></th>
                    <td><?php echo PHP_VERSION; ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Database Tables', 'wpcs-poll'); ?></th>
                    <td>
                        <?php
                        global $wpdb;
                        $tables = array(
                            $wpdb->prefix . 'wpcs_polls',
                            $wpdb->prefix . 'wpcs_poll_votes',
                            $wpdb->prefix . 'wpcs_poll_bookmarks'
                        );
                        
                        foreach ($tables as $table) {
                            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
                            echo '<span style="color: ' . ($exists ? 'green' : 'red') . ';">' . $table . ' (' . ($exists ? 'exists' : 'missing') . ')</span><br>';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Debug Mode', 'wpcs-poll'); ?></th>
                    <td>
                        <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
                            <span style="color: orange;">Enabled</span>
                            <?php if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG): ?>
                                <span style="color: green;">(Logging Enabled)</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color: #666;">Disabled</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Export/Import -->
        <div class="settings-section">
            <h2><?php _e('Export/Import', 'wpcs-poll'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Export Settings', 'wpcs-poll'); ?></th>
                    <td>
                        <button type="button" class="button" onclick="exportSettings()">
                            <?php _e('Export Settings', 'wpcs-poll'); ?>
                        </button>
                        <p class="description"><?php _e('Download current plugin settings as a JSON file.', 'wpcs-poll'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Import Settings', 'wpcs-poll'); ?></th>
                    <td>
                        <input type="file" id="import_settings" accept=".json">
                        <button type="button" class="button" onclick="importSettings()">
                            <?php _e('Import Settings', 'wpcs-poll'); ?>
                        </button>
                        <p class="description"><?php _e('Import settings from a previously exported JSON file.', 'wpcs-poll'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save Settings', 'wpcs-poll'); ?>">
        </p>
    </form>
</div>

<script>
function exportSettings() {
    var settings = <?php echo json_encode($options); ?>;
    var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(settings, null, 2));
    var downloadAnchorNode = document.createElement('a');
    downloadAnchorNode.setAttribute("href", dataStr);
    downloadAnchorNode.setAttribute("download", "wpcs-poll-settings.json");
    document.body.appendChild(downloadAnchorNode);
    downloadAnchorNode.click();
    downloadAnchorNode.remove();
}

function importSettings() {
    var fileInput = document.getElementById('import_settings');
    var file = fileInput.files[0];
    
    if (!file) {
        alert('<?php _e('Please select a file to import.', 'wpcs-poll'); ?>');
        return;
    }
    
    var reader = new FileReader();
    reader.onload = function(e) {
        try {
            var settings = JSON.parse(e.target.result);
            
            // Populate form fields with imported settings
            for (var key in settings) {
                var element = document.querySelector('[name="' + key + '"]');
                if (element) {
                    if (element.type === 'checkbox') {
                        element.checked = settings[key] == 1;
                    } else {
                        element.value = settings[key];
                    }
                }
            }
            
            alert('<?php _e('Settings imported successfully! Please save to apply changes.', 'wpcs-poll'); ?>');
        } catch (error) {
            alert('<?php _e('Error importing settings. Please check the file format.', 'wpcs-poll'); ?>');
        }
    };
    reader.readAsText(file);
}
</script>

<style>
.version-info-banner {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.version-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.version-header h2 {
    margin: 0;
    color: white;
    font-size: 24px;
}

.version-badge {
    background: rgba(255, 255, 255, 0.2);
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 16px;
    font-weight: bold;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.version-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.version-item {
    background: rgba(255, 255, 255, 0.1);
    padding: 12px 15px;
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.version-item strong {
    color: rgba(255, 255, 255, 0.9);
}

.version-value {
    color: white;
    font-weight: 600;
}

.version-features {
    background: rgba(255, 255, 255, 0.1);
    padding: 20px;
    border-radius: 8px;
}

.version-features h4 {
    margin: 0 0 15px 0;
    color: white;
    font-size: 18px;
}

.version-features ul {
    margin: 0;
    padding-left: 20px;
}

.version-features li {
    margin-bottom: 8px;
    color: rgba(255, 255, 255, 0.9);
    line-height: 1.4;
}

.settings-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.settings-section h2 {
    margin-top: 0;
    color: #0073aa;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 10px;
}

.form-table th {
    width: 200px;
    padding: 15px 10px 15px 0;
}

.form-table td {
    padding: 15px 10px;
}

.description {
    margin-top: 5px !important;
    font-style: italic;
}

.small-text {
    width: 80px;
}

.regular-text {
    width: 300px;
}

.version-build {
    color: #666;
    font-size: 12px;
    margin-left: 10px;
}

@media (max-width: 768px) {
    .version-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .version-details {
        grid-template-columns: 1fr;
    }
    
    .version-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
}
</style>