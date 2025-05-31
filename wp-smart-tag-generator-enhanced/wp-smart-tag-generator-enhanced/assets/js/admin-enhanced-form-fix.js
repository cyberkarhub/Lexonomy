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
                       'You can pause this process but not easily stop it once started.';
    
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
            showNotice('✅ Bulk analysis started successfully! Page will reload to show controls.', 'success');
            // Force reload to show the processing interface with pause button
            setTimeout(function() { 
                location.reload(); 
            }, 2000);
        } else {
            showNotice('❌ Failed to start bulk analysis: ' + response.data, 'error');
            $submitBtn.prop('disabled', false).text('🚀 Start Enhanced Bulk Analysis');
        }
    }).fail(function() {
        showNotice('❌ Request failed. Please check your connection and try again.', 'error');
        $submitBtn.prop('disabled', false).text('🚀 Start Enhanced Bulk Analysis');
    });
});