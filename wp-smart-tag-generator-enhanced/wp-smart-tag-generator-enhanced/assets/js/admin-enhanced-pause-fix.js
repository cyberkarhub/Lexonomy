// Add this to your admin-enhanced.js file:

// Enhanced form submission with proper status updates
$('#bulk-analysis-form').on('submit', function(e) {
    e.preventDefault();
    
    var formData = $(this).serialize();
    var $submitBtn = $('#start-bulk-btn');
    
    // Validate selections
    if (!$('input[name="include_posts"]').is(':checked') && 
        !$('input[name="include_pages"]').is(':checked') && 
        !$('input[name="include_custom_posts"]').is(':checked')) {
        showNotice('Please select at least one content type to process.', 'error');
        return;
    }
    
    if (!$('input[name="analyze_tags"]').is(':checked') && 
        !$('input[name="analyze_entities"]').is(':checked') && 
        !$('input[name="analyze_topics"]').is(':checked')) {
        showNotice('Please select at least one analysis feature.', 'error');
        return;
    }
    
    // Confirm before starting
    var itemsCount = $('#items-count').text();
    var estimatedCost = $('#estimated-cost').text();
    var confirmMessage = 'Start bulk analysis for ' + itemsCount + ' items?\n' +
                       'Estimated cost: ' + estimatedCost + '\n' +
                       'This process can be paused but not easily stopped once started.';
    
    if (!confirm(confirmMessage)) {
        return;
    }
    
    // Start processing
    $submitBtn.prop('disabled', true).text('⏳ Starting...');
    showNotice('Initializing bulk analysis...', 'info');
    
    $.post(ajaxurl, {
        action: 'wstg_start_bulk_analysis',
        nonce: '<?php echo wp_create_nonce('wstg_bulk_analysis'); ?>',
        form_data: formData
    }, function(response) {
        if (response.success) {
            showNotice('Bulk analysis started successfully! The page will reload to show progress.', 'success');
            // Force reload to show the processing interface
            setTimeout(function() { 
                location.reload(); 
            }, 2000);
        } else {
            showNotice('Failed to start bulk analysis: ' + response.data, 'error');
            $submitBtn.prop('disabled', false).text('🚀 Start Enhanced Bulk Analysis');
        }
    }).fail(function() {
        showNotice('Request failed. Please check your connection and try again.', 'error');
        $submitBtn.prop('disabled', false).text('🚀 Start Enhanced Bulk Analysis');
    });
});

// Enhanced pause functionality with immediate feedback
$('#pause-processing').on('click', function() {
    var $btn = $(this);
    $btn.prop('disabled', true).text('⏸️ Pausing...');
    
    // Show immediate feedback
    showNotice('Pausing after current batch completes...', 'info');
    
    $.post(ajaxurl, {
        action: 'wstg_pause_bulk_analysis',
        nonce: '<?php echo wp_create_nonce('wstg_bulk_control'); ?>'
    }, function(response) {
        if (response.success) {
            showNotice('✅ Processing paused successfully!', 'success');
            // Update UI immediately
            $btn.closest('.bulk-controls').html(
                '<button type="button" id="resume-processing" class="button button-primary">' +
                '▶️ Resume Processing</button>' +
                '<span class="paused-indicator">⏸️ Processing Paused</span>'
            );
            
            // Reload after short delay to refresh status
            setTimeout(function() { location.reload(); }, 3000);
        } else {
            showNotice('Failed to pause processing: ' + response.data, 'error');
            $btn.prop('disabled', false).text('⏸️ Pause Processing');
        }
    }).fail(function() {
        showNotice('Pause request failed. Please try again.', 'error');
        $btn.prop('disabled', false).text('⏸️ Pause Processing');
    });
});

// Resume functionality
$(document).on('click', '#resume-processing', function() {
    var $btn = $(this);
    $btn.prop('disabled', true).text('▶️ Resuming...');
    
    $.post(ajaxurl, {
        action: 'wstg_resume_bulk_analysis',
        nonce: '<?php echo wp_create_nonce('wstg_bulk_control'); ?>'
    }, function(response) {
        if (response.success) {
            showNotice('✅ Processing resumed successfully!', 'success');
            setTimeout(function() { location.reload(); }, 2000);
        } else {
            showNotice('Failed to resume processing: ' + response.data, 'error');
            $btn.prop('disabled', false).text('▶️ Resume Processing');
        }
    }).fail(function() {
        showNotice('Resume request failed. Please try again.', 'error');
        $btn.prop('disabled', false).text('▶️ Resume Processing');
    });
});

// Stop processing functionality
$('#stop-processing').on('click', function() {
    if (!confirm('Are you sure you want to stop processing? All progress will be lost and cannot be recovered.')) {
        return;
    }
    
    var $btn = $(this);
    $btn.prop('disabled', true).text('🛑 Stopping...');
    
    $.post(ajaxurl, {
        action: 'wstg_stop_bulk_analysis',
        nonce: '<?php echo wp_create_nonce('wstg_bulk_control'); ?>'
    }, function(response) {
        if (response.success) {
            showNotice('✅ Processing stopped successfully!', 'success');
            setTimeout(function() { location.reload(); }, 2000);
        } else {
            showNotice('Failed to stop processing: ' + response.data, 'error');
            $btn.prop('disabled', false).text('🛑 Stop Processing');
        }
    }).fail(function() {
        showNotice('Stop request failed. Please try again.', 'error');
        $btn.prop('disabled', false).text('🛑 Stop Processing');
    });
});

// Auto-refresh status during processing
<?php if ($queue_status['is_processing']): ?>
setInterval(function() {
    $.post(ajaxurl, {
        action: 'wstg_get_bulk_status',
        nonce: '<?php echo wp_create_nonce('wstg_bulk_status'); ?>'
    }, function(response) {
        if (response.success && response.data) {
            var status = response.data;
            
            // Update progress bar
            if (status.progress_percentage !== undefined) {
                $('.progress-bar').css('width', status.progress_percentage + '%');
                $('.progress-text').text(status.progress_percentage + '% Complete');
            }
            
            // Update progress details
            if (status.progress_details) {
                $('.progress-details').text(status.completed_items + ' / ' + status.total_items + ' items processed | ' + status.remaining_time);
            }
            
            // Update current item
            if (status.current_item) {
                $('.current-item').text(status.current_item);
            }
            
            // Check if processing completed
            if (!status.is_processing) {
                showNotice('✅ Bulk analysis completed!', 'success');
                setTimeout(function() { location.reload(); }, 2000);
            }
        }
    });
}, 5000); // Check every 5 seconds
<?php endif; ?>

function showNotice(message, type) {
    var className = type === 'error' ? 'notice-error' : (type === 'success' ? 'notice-success' : 'notice-info');
    var notice = '<div class="notice ' + className + ' is-dismissible"><p>' + message + '</p></div>';
    
    // Remove existing notices
    $('.notice').remove();
    
    // Add new notice
    $('.wrap h1').after(notice);
    
    // Auto-dismiss success and info notices
    if (type !== 'error') {
        setTimeout(function() {
            $('.notice').fadeOut();
        }, 5000);
    }
}