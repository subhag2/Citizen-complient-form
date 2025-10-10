jQuery(function($) {
    'use strict';
    
    // Initialize admin functionality
    function initAdmin() {
        bindEvents();
    }
    
    // Bind all event handlers
    function bindEvents() {
        // Status change detection
        $('.sr-status-select').on('change', handleStatusChange);
        
        // Update status button
        $('.sr-update-status').on('click', handleStatusUpdate);
        
        // View details button
        $('.sr-view-details').on('click', handleViewDetails);
                
        // Delete request button
        $('.sr-delete-request').on('click', handleDeleteRequest);
        
        // Toggle description
        $('.sr-toggle-description').on('click', handleToggleDescription);
        
        // Modal close
        $('.sr-modal-close, .sr-modal').on('click', handleModalClose);
        
        // Prevent modal close when clicking inside modal content
        $('.sr-modal-content').on('click', function(e) {
            e.stopPropagation();
        });
        // Bulk select functionality
        $('#sr-select-all').on('change', handleSelectAll);
        $('.sr-row-checkbox').on('change', handleRowSelect);
        
        // Update selected count on page load
        updateSelectedCount();
        
        // Keyboard shortcuts
        $(document).on('keydown', handleKeyboardShortcuts);
    }
    
    // Handle status dropdown change
    function handleStatusChange() {
        const $select = $(this);
        const $updateBtn = $select.siblings('.sr-update-status');
        const currentStatus = $select.data('current-status');
        const newStatus = $select.val();
        
        if (newStatus !== currentStatus) {
            $updateBtn.show().text('Update');
            $select.addClass('sr-status-changed');
        } else {
            $updateBtn.hide();
            $select.removeClass('sr-status-changed');
        }
    }
    
    // Handle status update
    function handleStatusUpdate() {
        const $button = $(this);
        const requestId = $button.data('request-id');
        const $select = $button.siblings('.sr-status-select');
        const newStatus = $select.val();
        const originalText = $button.text();
        
        // Show loading state
        $button.prop('disabled', true).text('Updating...');
        showLoadingOverlay();
        
        // Send AJAX request
        $.ajax({
            url: SR_Admin_Ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sr_update_status',
                request_id: requestId,
                new_status: newStatus,
                security: SR_Admin_Ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update UI
                    updateStatusUI(requestId, newStatus);
                    showNotice('Status updated successfully!', 'success');
                    
                    // Update the current status data attribute
                    $select.data('current-status', newStatus);
                    $button.hide();
                    $select.removeClass('sr-status-changed');
                } else {
                    showNotice(response.data || 'Failed to update status', 'error');
                }
            },
            error: function() {
                showNotice('Network error. Please try again.', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
                hideLoadingOverlay();
            }
        });
    }
    
    // Update status UI elements
    function updateStatusUI(requestId, newStatus) {
        const $row = $('tr[data-request-id="' + requestId + '"]');
        const $statusBadge = $row.find('.sr-status-badge');
        
        // Update status badge
        $statusBadge.removeClass('sr-status-pending sr-status-in-progress sr-status-completed sr-status-rejected');
        $statusBadge.addClass('sr-status-' + newStatus.toLowerCase().replace(' ', '-'));
        $statusBadge.text(newStatus);
        
        // Update stats (simple approach - could be enhanced with real-time counting)
        updateStats();
    }
    
    // Handle view details
    function handleViewDetails() {
        const requestId = $(this).data('request-id');
        const $row = $('tr[data-request-id="' + requestId + '"]');
        
        showRequestDetails(requestId, $row);
    }
        
    // Handle delete request
    function handleDeleteRequest() {
        const requestId = $(this).data('request-id');
        const $button = $(this);
        const originalText = $button.text();
        
        // Show confirmation dialog
        if (!confirm('Are you sure you want to delete this service request? This action cannot be undone.')) {
            return;
        }
        
        // Show loading state
        $button.prop('disabled', true).text('Deleting...');
        showLoadingOverlay();
        
        // Send delete request
        $.ajax({
            url: SR_Admin_Ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sr_delete_request',
                request_id: requestId,
                security: SR_Admin_Ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Remove the row from the table with animation
                    const $row = $('tr[data-request-id="' + requestId + '"]');
                    $row.fadeOut(300, function() {
                        $(this).remove();
                        
                        // Check if table is empty
                        if ($('.sr-admin-table-wrapper tbody tr').length === 0) {
                            // Reload page to show "no requests" message and update pagination
                            updateStats();
                        }
                    });
                    
                    showNotice('Service request deleted successfully!', 'success');
                } else {
                    showNotice(response.data || 'Failed to delete request', 'error');
                    $button.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                showNotice('Network error. Please try again.', 'error');
                $button.prop('disabled', false).text(originalText);
            },
            complete: function() {
                hideLoadingOverlay();
            }
        });
    }
    // Show request details in modal
    function showRequestDetails(requestId, $row) {
        const $modal = $('#sr-details-modal');
        const $modalBody = $modal.find('.sr-modal-body');
        
        // Extract data from the row
        const description = $row.find('.sr-full-description').length ? 
                          $row.find('.sr-full-description').text() : 
                          $row.find('.sr-description').text();
        
        const location = $row.find('.sr-location').text().trim();
        const contact = $row.find('.sr-contact').length ? 
                       $row.find('.sr-contact').html() : 
                       $row.find('.sr-anonymous').text();
        
        const status = $row.find('.sr-status-badge').text();
        const date = $row.find('.sr-date').text().trim();
        
        // Build modal content
        const modalContent = `
            <div class="sr-detail-section">
                <h3>Request #${requestId}</h3>
                <div class="sr-detail-grid">
                    <div class="sr-detail-item">
                        <label>Submitted:</label>
                        <span>${date}</span>
                    </div>
                </div>
            </div>
            
            <div class="sr-detail-section">
                <h4>Description</h4>
                <p>${escapeHtml(description)}</p>
            </div>
            
            <div class="sr-detail-section">
                <h4>Location</h4>
                <p>${escapeHtml(location)}</p>
            </div>
            
            <div class="sr-detail-section">
                <h4>Contact Information</h4>
                <div>${contact.includes('Anonymous') ? 'Anonymous Request' : contact}</div>
            </div>
            
            <div class="sr-detail-actions" style="display: none;">
                <select class="sr-modal-status-select" data-request-id="${requestId}">
                    <option value="Pending" ${status === 'Pending' ? 'selected' : ''}>Pending</option>
                    <option value="In Progress" ${status === 'In Progress' ? 'selected' : ''}>In Progress</option>
                    <option value="Completed" ${status === 'Completed' ? 'selected' : ''}>Completed</option>
                    <option value="Rejected" ${status === 'Rejected' ? 'selected' : ''}>Rejected</option>
                </select>
                <button type="button" class="button button-primary sr-modal-update-status" data-request-id="${requestId}">
                    Update Status
                </button>
            </div>
        `;
        
        $modalBody.html(modalContent);
        
        // Bind modal-specific events
        $modal.find('.sr-modal-update-status').on('click', function() {
            const newStatus = $modal.find('.sr-modal-status-select').val();
            updateStatusFromModal(requestId, newStatus);
        });
        
        // Show modal
        $modal.fadeIn();
        $('body').addClass('sr-modal-open');
    }
    
    // Update status from modal
    function updateStatusFromModal(requestId, newStatus) {
        const $button = $('.sr-modal-update-status');
        const originalText = $button.text();
        
        $button.prop('disabled', true).text('Updating...');
        
        $.ajax({
            url: SR_Admin_Ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sr_update_status',
                request_id: requestId,
                new_status: newStatus,
                security: SR_Admin_Ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateStatusUI(requestId, newStatus);
                    showNotice('Status updated successfully!', 'success');
                    
                    // Update modal content
                    const $statusBadge = $('.sr-modal-body .sr-status-badge');
                    $statusBadge.removeClass('sr-status-pending sr-status-in-progress sr-status-completed sr-status-rejected');
                    $statusBadge.addClass('sr-status-' + newStatus.toLowerCase().replace(' ', '-'));
                    $statusBadge.text(newStatus);
                    
                    // Close modal after successful update
                    setTimeout(function() {
                        $('#sr-details-modal').fadeOut();
                        $('body').removeClass('sr-modal-open');
                    }, 1000);
                } else {
                    showNotice(response.data || 'Failed to update status', 'error');
                }
            },
            error: function() {
                showNotice('Network error. Please try again.', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    }
    
    // Handle toggle description
    function handleToggleDescription() {
        const $button = $(this);
        const $fullDesc = $button.siblings('.sr-full-description');
        const $shortDesc = $button.siblings('.sr-description');
        
        if ($fullDesc.is(':visible')) {
            $fullDesc.hide();
            $shortDesc.show();
            $button.text('Show full description');
        } else {
            $fullDesc.show();
            $shortDesc.hide();
            $button.text('Show less');
        }
    }
    
    // Handle modal close
    function handleModalClose(e) {
        if (e.target === this) {
            $('#sr-details-modal').fadeOut();
            $('body').removeClass('sr-modal-open');
        }
    }
    
    // Handle keyboard shortcuts
    function handleKeyboardShortcuts(e) {
        // ESC key to close modal
        if (e.keyCode === 27) {
            $('#sr-details-modal').fadeOut();
            $('body').removeClass('sr-modal-open');
        }
    }
    
    // Show loading overlay
    function showLoadingOverlay() {
        $('#sr-loading-overlay').show();
    }
    
    // Hide loading overlay
    function hideLoadingOverlay() {
        $('#sr-loading-overlay').hide();
    }
    
    // Show notice
    function showNotice(message, type) {
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        const $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + escapeHtml(message) + '</p></div>');
        
        $('.wrap h1').after($notice);
        
        // Auto dismiss after 3 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
        
        // Manual dismiss
        $notice.on('click', '.notice-dismiss', function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        });
    }
    
    // Simple stats update (could be enhanced with real AJAX call)
    function updateStats() {
        // Preserve current page when refreshing
        const urlParams = new URLSearchParams(window.location.search);
        const currentPage = urlParams.get('paged');
        const baseUrl = window.location.pathname + '?page=sr-requests';
        const refreshUrl = currentPage ? baseUrl + '&paged=' + currentPage : baseUrl;
        window.location.href = refreshUrl;
    }
    
    // Escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Handle select all checkbox
    function handleSelectAll() {
        const $selectAll = $('#sr-select-all');
        const isChecked = $selectAll.is(':checked');
        $('.sr-row-checkbox').prop('checked', isChecked);
        updateSelectedCount();
    }
    
    // Handle individual row checkbox
    function handleRowSelect() {
        const totalRows = $('.sr-row-checkbox').length;
        const checkedRows = $('.sr-row-checkbox:checked').length;
        
        // Update select all checkbox state
        $('#sr-select-all').prop('checked', checkedRows === totalRows);
        $('#sr-select-all').prop('indeterminate', checkedRows > 0 && checkedRows < totalRows);
        
        updateSelectedCount();
    }
    
    // Update selected count display
    function updateSelectedCount() {
        const checkedCount = $('.sr-row-checkbox:checked').length;
        const $countSpan = $('.sr-selected-count');
        
        if (checkedCount === 0) {
            $countSpan.text('0 selected');
        } else if (checkedCount === 1) {
            $countSpan.text('1 selected');
        } else {
            $countSpan.text(checkedCount + ' selected');
        }
        
        // Show/hide bulk delete button based on selection
        const $deleteButton = $('button[name="bulk_delete"]');
        if (checkedCount > 0) {
            $deleteButton.prop('disabled', false);
        } else {
            $deleteButton.prop('disabled', true);
        }
    }
    
    // Initialize when document is ready
    initAdmin();
});