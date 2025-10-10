<?php
if (!defined('ABSPATH')) exit;

class SR_Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_ajax_sr_update_status', [$this, 'update_status']);
        add_action('wp_ajax_sr_delete_request', [$this, 'delete_request']);
    }

    public function menu() {
        add_menu_page('Service Requests', 'Service Requests', 'manage_options', 'sr-requests', [$this, 'render_admin']);
    }

    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_sr-requests') {
            return;
        }
        
        wp_enqueue_style('sr-admin-css', SR_PLUGIN_URL . 'assets/css/sr-admin.css');
        wp_enqueue_script('sr-admin-js', SR_PLUGIN_URL . 'assets/js/sr-admin.js', ['jquery'], null, true);
        wp_localize_script('sr-admin-js', 'SR_Admin_Ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sr_admin_nonce')
        ]);
    }

    public function render_admin() {
        // Handle bulk delete submission
        if (isset($_POST['bulk_delete']) && isset($_POST['selected_requests']) && is_array($_POST['selected_requests'])) {
            // Verify nonce
            if (!wp_verify_nonce($_POST['sr_bulk_delete_nonce'], 'sr_bulk_delete')) {
                wp_die('Security check failed');
            }
            
            global $wpdb;
            $selected_ids = array_map('intval', $_POST['selected_requests']);
            
            if (!empty($selected_ids)) {
                $ids_placeholder = implode(',', array_fill(0, count($selected_ids), '%d'));
                $query = "UPDATE " . SR_DB::table_name() . " SET status = 'Deleted' WHERE id IN ($ids_placeholder)";
                $wpdb->query($wpdb->prepare($query, ...$selected_ids));
                
                $deleted_count = count($selected_ids);
                echo '<div class="notice notice-success is-dismissible"><p>' . 
                     sprintf(_n('%d service request deleted.', '%d service requests deleted.', $deleted_count), $deleted_count) . 
                     '</p></div>';
            }
        }
        
        // Check if we should render full details page
        if (isset($_GET['action']) && $_GET['action'] === 'view-full' && isset($_GET['request_id'])) {
            $this->render_full_details();
            return;
        }
        
        // Regular admin list page
        global $wpdb;
        
        // Pagination settings
        $per_page = 10;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;
        
        // Get total count for pagination (excluding deleted records)
        $total_requests = $wpdb->get_var("SELECT COUNT(*) FROM " . SR_DB::table_name() . " WHERE status != 'Deleted'");
        $total_pages = ceil($total_requests / $per_page);
        
        // Get paginated results (excluding deleted records)
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . SR_DB::table_name() . " WHERE status != 'Deleted' ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));
        
        // Get all requests for stats (excluding deleted records)
        $all_requests = $wpdb->get_results("SELECT status FROM " . SR_DB::table_name() . " WHERE status != 'Deleted'");
        ?>
        <div class="wrap">
            <h1>Service Requests Management</h1>
            <div class="sr-admin-stats">
                <?php $this->render_stats($all_requests); ?>
            </div>
            
            <?php if (empty($rows) && $current_page == 1): ?>
                <div class="sr-no-requests">
                    <h3>No service requests found</h3>
                    <p>When users submit service requests, they will appear here for management.</p>
                </div>
            <?php elseif (empty($rows) && $current_page > 1): ?>
                <div class="sr-no-requests">
                    <h3>No requests found on this page</h3>
                    <p><a href="<?php echo admin_url('admin.php?page=sr-requests'); ?>">Go back to page 1</a></p>
                </div>
            <?php else: ?>
                <!-- Page info -->
                <div class="sr-page-info">
                    <p>
                        Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $per_page, $total_requests); ?> 
                        of <?php echo $total_requests; ?> requests
                        <?php if ($total_pages > 1): ?>
                            (Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>)
                        <?php endif; ?>
                    </p>
                </div>
                
                <!-- Bulk Delete Form -->
                <form method="post" action="" id="sr-bulk-delete-form">
                    <?php wp_nonce_field('sr_bulk_delete', 'sr_bulk_delete_nonce'); ?>
                    <div class="sr-bulk-actions">
                        <button type="submit" name="bulk_delete" class="button button-secondary" onclick="return confirm('Are you sure you want to delete the selected service requests? This action cannot be undone.');">Delete Selected</button>
                        <span class="sr-selected-count">0 selected</span>
                    </div>
                
                <div class="sr-admin-table-wrapper">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 40px;"><input type="checkbox" id="sr-select-all"></th>
                                <th style="width: 60px;">ID</th>
                                <th>Description</th>
                                <th style="width: 150px;">Location</th>
                                <th style="width: 120px;">Contact</th>
                                <!-- <th style="width: 130px;">Status</th> -->
                                <th style="width: 120px;">Created</th>
                                <th style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr data-request-id="<?php echo $r->id; ?>">
                                <td><input type="checkbox" name="selected_requests[]" value="<?php echo $r->id; ?>" class="sr-row-checkbox"></td>
                                <td><strong>#<?php echo $r->id; ?></strong></td>
                                <td>
                                    <div class="sr-description">
                                        <?php echo esc_html(wp_trim_words($r->description, 15)); ?>
                                    </div>
                                    <?php if (strlen($r->description) > 100): ?>
                                        <button type="button" class="button-link sr-toggle-description">Show full description</button>
                                        <div class="sr-full-description" style="display: none;">
                                            <?php echo esc_html($r->description); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="sr-location">
                                        <strong><?php echo esc_html($r->location_general); ?></strong>
                                        <?php if ($r->location_intersection	): ?>
                                            <br><small><?php echo esc_html($r->location_intersection	); ?></small>
                                        <?php endif; ?>
                                        <?php if ($r->address): ?>
                                            <br><small><?php echo esc_html($r->address); ?></small>
                                        <?php endif; ?>
                                        <?php if ($r->city || $r->state): ?>
                                            <br><small><?php echo esc_html(trim($r->city . ', ' . $r->state, ', ')); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($r->anonymous): ?>
                                        <span class="sr-anonymous">Anonymous</span>
                                    <?php else: ?>
                                        <div class="sr-contact">
                                            <?php if ($r->name): ?>
                                                <strong><?php echo esc_html($r->name); ?></strong><br>
                                            <?php endif; ?>
                                            <?php if ($r->phone): ?>
                                                <small><?php echo esc_html($r->phone); ?></small><br>
                                            <?php endif; ?>
                                            <?php if ($r->email_contact): ?>
                                                <small><?php echo esc_html($r->email_contact); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <!-- <td>
                                    <div class="sr-status-wrapper">
                                        <span class="sr-status-badge sr-status-<?php echo strtolower(str_replace(' ', '-', $r->status)); ?>">
                                            <?php echo esc_html($r->status); ?>
                                        </span>
                                    </div>
                                </td> -->
                                <td>
                                    <div class="sr-date">
                                        <?php 
                                        $date = new DateTime($r->created_at);
                                        echo $date->format('M j, Y'); 
                                        ?>
                                        <br><small><?php echo $date->format('g:i A'); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="sr-actions">
                                        <!-- <select class="sr-status-select" data-request-id="<?php echo $r->id; ?>" data-current-status="<?php echo esc_attr($r->status); ?>">
                                            <option value="Pending" <?php selected($r->status, 'Pending'); ?>>Pending</option>
                                            <option value="In Progress" <?php selected($r->status, 'In Progress'); ?>>In Progress</option>
                                            <option value="Completed" <?php selected($r->status, 'Completed'); ?>>Completed</option>
                                            <option value="Rejected" <?php selected($r->status, 'Rejected'); ?>>Rejected</option>
                                        </select> -->
                                        <button type="button" class="button button-primary sr-update-status" data-request-id="<?php echo $r->id; ?>" style="display: none;">
                                            Update
                                        </button>
                                        <button type="button" class="button sr-view-details" data-request-id="<?php echo $r->id; ?>">
                                            View Details
                                        </button>
                                        <a href="<?php echo admin_url('admin.php?page=sr-requests&action=view-full&request_id=' . $r->id); ?>" class="button button-secondary sr-view-full">
                                            View Full Details
                                        </a>
                                        <button type="button" class="button button-link-delete sr-delete-request" data-request-id="<?php echo $r->id; ?>">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                </form>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="sr-pagination-wrapper">
                        <?php $this->render_pagination($current_page, $total_pages, $total_requests); ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- Details Modal -->
            <div id="sr-details-modal" class="sr-modal" style="display: none;">
                <div class="sr-modal-content">
                    <div class="sr-modal-header">
                        <h2>Service Request Details</h2>
                        <span class="sr-modal-close">&times;</span>
                    </div>
                    <div class="sr-modal-body">
                        <!-- Content will be loaded via JavaScript -->
                    </div>
                </div>
            </div>
            
            <!-- Loading overlay -->
            <div id="sr-loading-overlay" style="display: none;">
                <div class="sr-spinner"></div>
            </div>
        </div>
        <?php
    }

    private function render_full_details() {
        $request_id = intval($_GET['request_id'] ?? 0);
        
        if (!$request_id) {
            wp_die('Invalid request ID');
        }
        
        $request = SR_DB::get_request($request_id);
        
        if (!$request) {
            wp_die('Service request not found');
        }
        
        ?>
        <div class="wrap">
            <h1>Service Request #<?php echo $request->id; ?> - Full Details</h1>
            
            <div class="sr-full-details-header">
                <a href="<?php echo admin_url('admin.php?page=sr-requests'); ?>" class="button button-secondary">
                    &larr; Back to All Requests
                </a>
            </div>
            
            <div class="sr-full-details-container">
                <div class="sr-full-details-card">
                    <div class="sr-detail-header">
                        <div class="sr-detail-title">
                            <h2>Service Request #<?php echo $request->id; ?></h2>
                            <span class="sr-status-badge sr-status-<?php echo strtolower(str_replace(' ', '-', $request->status)); ?>">
                                <?php echo esc_html($request->status); ?>
                            </span>
                        </div>
                        <div class="sr-detail-meta">
                            <p><strong>Submitted:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($request->created_at)); ?></p>
                        </div>
                    </div>
                    
                    <div class="sr-detail-sections">
                        <!-- Description Section -->
                        <div class="sr-detail-section">
                            <h3>Description</h3>
                            <div class="sr-detail-content">
                                <p><?php echo esc_html($request->description); ?></p>
                            </div>
                        </div>
                        
                        <!-- Location Section -->
                        <div class="sr-detail-section">
                            <h3>Location Information</h3>
                            <div class="sr-detail-content">
                                <div class="sr-detail-grid">
                                    <div class="sr-detail-item">
                                        <label>General Location:</label>
                                        <span><?php echo esc_html($request->location_general ?: 'Not provided'); ?></span>
                                    </div>
                                    <div class="sr-detail-item">
                                        <label>Nearest Intersection:</label>
                                        <span><?php echo esc_html($request->location_intersection ?: 'Not provided'); ?></span>
                                    </div>
                                    <div class="sr-detail-item">
                                        <label>Street Address:</label>
                                        <span><?php echo esc_html($request->address ?: 'Not provided'); ?></span>
                                    </div>
                                    <div class="sr-detail-item">
                                        <label>City:</label>
                                        <span><?php echo esc_html($request->city ?: 'Not provided'); ?></span>
                                    </div>
                                    <div class="sr-detail-item">
                                        <label>State:</label>
                                        <span><?php echo esc_html($request->state ?: 'Not provided'); ?></span>
                                    </div>
                                    <div class="sr-detail-item">
                                        <label>ZIP Code:</label>
                                        <span><?php echo esc_html($request->zipcode ?: 'Not provided'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Contact Section -->
                        <div class="sr-detail-section">
                            <h3>Contact Information</h3>
                            <div class="sr-detail-content">
                                <?php if ($request->anonymous): ?>
                                    <p class="sr-anonymous-note">This request was submitted anonymously.</p>
                                <?php else: ?>
                                    <div class="sr-detail-grid">
                                        <div class="sr-detail-item">
                                            <label>Full Name:</label>
                                            <span><?php echo esc_html($request->name ?: 'Not provided'); ?></span>
                                        </div>
                                        <div class="sr-detail-item">
                                            <label>Street Address:</label>
                                            <span><?php echo esc_html($request->street ?: 'Not provided'); ?></span>
                                        </div>
                                        <div class="sr-detail-item">
                                            <label>City:</label>
                                            <span><?php echo esc_html($request->city_contact ?: 'Not provided'); ?></span>
                                        </div>
                                        <div class="sr-detail-item">
                                            <label>State:</label>
                                            <span><?php echo esc_html($request->state_contact ?: 'Not provided'); ?></span>
                                        </div>
                                        <div class="sr-detail-item">
                                            <label>ZIP Code:</label>
                                            <span><?php echo esc_html($request->zip_contact ?: 'Not provided'); ?></span>
                                        </div>
                                        <div class="sr-detail-item">
                                            <label>Phone Number:</label>
                                            <span><?php echo esc_html($request->phone ?: 'Not provided'); ?></span>
                                        </div>
                                        <div class="sr-detail-item">
                                            <label>Email Address:</label>
                                            <span><?php echo esc_html($request->email_contact ?: 'Not provided'); ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Status Management Section -->
                        <div class="sr-detail-section">
                            <h3>Status Management</h3>
                            <div class="sr-detail-content">
                                <div class="sr-status-management">
                                    <div class="sr-current-status">
                                        <label>Current Status:</label>
                                        <span class="sr-status-badge sr-status-<?php echo strtolower(str_replace(' ', '-', $request->status)); ?>">
                                            <?php echo esc_html($request->status); ?>
                                        </span>
                                    </div>
                                    <div class="sr-status-change">
                                        <label for="full-status-select">Change Status:</label>
                                        <select id="full-status-select" class="sr-status-select" data-request-id="<?php echo $request->id; ?>" data-current-status="<?php echo esc_attr($request->status); ?>">
                                            <option value="Pending" <?php selected($request->status, 'Pending'); ?>>Pending</option>
                                            <option value="In Progress" <?php selected($request->status, 'In Progress'); ?>>In Progress</option>
                                            <option value="Completed" <?php selected($request->status, 'Completed'); ?>>Completed</option>
                                            <option value="Rejected" <?php selected($request->status, 'Rejected'); ?>>Rejected</option>
                                        </select>
                                        <button type="button" class="button button-primary sr-update-status" data-request-id="<?php echo $request->id; ?>" style="display: none;">
                                            Update Status
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Technical Details Section -->
                        <div class="sr-detail-section">
                            <h3>Technical Details</h3>
                            <div class="sr-detail-content">
                                <div class="sr-detail-grid">
                                    <div class="sr-detail-item">
                                        <label>Request ID:</label>
                                        <span><?php echo $request->id; ?></span>
                                    </div>
                                    <div class="sr-detail-item">
                                        <label>Created Date:</label>
                                        <span><?php echo date('F j, Y \a\t g:i:s A', strtotime($request->created_at)); ?></span>
                                    </div>
                                    <div class="sr-detail-item">
                                        <label>Anonymous Request:</label>
                                        <span><?php echo $request->anonymous ? 'Yes' : 'No'; ?></span>
                                    </div>
                                    <div class="sr-detail-item">
                                        <label>PIN Status:</label>
                                        <span><?php echo !empty($request->pin_hash) ? 'PIN Set' : 'No PIN'; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="sr-detail-actions">
                        <a href="<?php echo admin_url('admin.php?page=sr-requests'); ?>" class="button button-secondary">
                            &larr; Back to All Requests
                        </a>
                        <button type="button" class="button sr-view-details" data-request-id="<?php echo $request->id; ?>">
                            Quick View Modal
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Include the same modal from the main page for consistency -->
            <div id="sr-details-modal" class="sr-modal" style="display: none;">
                <div class="sr-modal-content">
                    <div class="sr-modal-header">
                        <h2>Service Request Details</h2>
                        <span class="sr-modal-close">&times;</span>
                    </div>
                    <div class="sr-modal-body">
                        <!-- Content will be loaded via JavaScript -->
                    </div>
                </div>
            </div>
            
            <!-- Loading overlay -->
            <div id="sr-loading-overlay" style="display: none;">
                <div class="sr-spinner"></div>
            </div>
        </div>
        <?php
    }

    private function render_stats($requests) {
        $stats = [
            'total' => count($requests),
            'pending' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'rejected' => 0
        ];
        
        foreach ($requests as $request) {
            $status = isset($request->status) ? $request->status : $request;
            switch (strtolower($status)) {
                case 'pending':
                    $stats['pending']++;
                    break;
                case 'in progress':
                    $stats['in_progress']++;
                    break;
                case 'completed':
                    $stats['completed']++;
                    break;
                case 'rejected':
                    $stats['rejected']++;
                    break;
            }
        }
        ?>
        <!-- <div class="sr-stats-grid">
            <div class="sr-stat-card sr-stat-total">
                <div class="sr-stat-number"><?php echo $stats['total']; ?></div>
                <div class="sr-stat-label">Total Requests</div>
            </div>
            <div class="sr-stat-card sr-stat-pending">
                <div class="sr-stat-number"><?php echo $stats['pending']; ?></div>
                <div class="sr-stat-label">Pending</div>
            </div>
            <div class="sr-stat-card sr-stat-progress">
                <div class="sr-stat-number"><?php echo $stats['in_progress']; ?></div>
                <div class="sr-stat-label">In Progress</div>
            </div>
            <div class="sr-stat-card sr-stat-completed">
                <div class="sr-stat-number"><?php echo $stats['completed']; ?></div>
                <div class="sr-stat-label">Completed</div>
            </div>
            <div class="sr-stat-card sr-stat-rejected">
                <div class="sr-stat-number"><?php echo $stats['rejected']; ?></div>
                <div class="sr-stat-label">Rejected</div>
            </div>
        </div> -->
        <?php
    }

    private function render_pagination($current_page, $total_pages, $total_requests) {
        $base_url = admin_url('admin.php?page=sr-requests');
        
        ?>
        <div class="sr-pagination">
            <div class="sr-pagination-info">
                <span class="displaying-num"><?php echo $total_requests; ?> items</span>
            </div>
            
            <div class="sr-pagination-links">
                <?php if ($current_page > 1): ?>
                    <a class="first-page button" href="<?php echo $base_url; ?>">
                        <span class="screen-reader-text">First page</span>
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                    <a class="prev-page button" href="<?php echo add_query_arg('paged', $current_page - 1, $base_url); ?>">
                        <span class="screen-reader-text">Previous page</span>
                        <span aria-hidden="true">&lsaquo;</span>
                    </a>
                <?php else: ?>
                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>
                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>
                <?php endif; ?>
                
                <span class="paging-input">
                    <label for="current-page-selector" class="screen-reader-text">Current Page</label>
                    <input class="current-page" id="current-page-selector" type="text" name="paged" value="<?php echo $current_page; ?>" size="<?php echo strlen($total_pages); ?>" aria-describedby="table-paging" readonly>
                    <span class="tablenav-paging-text"> of <span class="total-pages"><?php echo $total_pages; ?></span></span>
                </span>
                
                <?php if ($current_page < $total_pages): ?>
                    <a class="next-page button" href="<?php echo add_query_arg('paged', $current_page + 1, $base_url); ?>">
                        <span class="screen-reader-text">Next page</span>
                        <span aria-hidden="true">&rsaquo;</span>
                    </a>
                    <a class="last-page button" href="<?php echo add_query_arg('paged', $total_pages, $base_url); ?>">
                        <span class="screen-reader-text">Last page</span>
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                <?php else: ?>
                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>
                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>
                <?php endif; ?>
                
                <!-- Page number links for easier navigation -->
                <!-- <div class="sr-page-numbers">
                    <?php
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <?php if ($i == $current_page): ?>
                            <span class="sr-page-number current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a class="sr-page-number" href="<?php echo add_query_arg('paged', $i, $base_url); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div> -->
            </div>
        </div>
        <?php
    }

    public function update_status() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['security'], 'sr_admin_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $request_id = intval($_POST['request_id'] ?? 0);
        $new_status = sanitize_text_field($_POST['new_status'] ?? '');
        
        if (!$request_id || !$new_status) {
            wp_send_json_error('Invalid request ID or status');
            return;
        }
        
        // Validate status
        $allowed_statuses = ['Pending', 'In Progress', 'Completed', 'Rejected'];
        if (!in_array($new_status, $allowed_statuses)) {
            wp_send_json_error('Invalid status value');
            return;
        }
        
        // Update status
        $result = SR_DB::update_status($request_id, $new_status);
        
        if ($result === false) {
            wp_send_json_error('Failed to update status');
            return;
        }
        
        wp_send_json_success([
            'message' => 'Status updated successfully',
            'request_id' => $request_id,
            'new_status' => $new_status
        ]);
    }

    public function delete_request() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['security'], 'sr_admin_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $request_id = intval($_POST['request_id'] ?? 0);
        
        if (!$request_id) {
            wp_send_json_error('Invalid request ID');
            return;
        }
        
        // Check if request exists and is not already deleted
        $request = SR_DB::get_request($request_id);
        if (!$request) {
            wp_send_json_error('Service request not found');
            return;
        }
        
        if ($request->status === 'Deleted') {
            wp_send_json_error('Service request is already deleted');
            return;
        }
        
        // Update status to "Deleted"
        $result = SR_DB::update_status($request_id, 'Deleted');
        
        if ($result === false) {
            wp_send_json_error('Failed to delete request');
            return;
        }
        
        wp_send_json_success([
            'message' => 'Service request deleted successfully',
            'request_id' => $request_id
        ]);
    }
}
