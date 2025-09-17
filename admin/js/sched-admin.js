jQuery(document).ready(function($) {
    // Test connection button
    $('#test-connection').click(function() {
        var button = $(this);
        var status = $('#sync-status');
        
        button.prop('disabled', true);
        status.removeClass('success error').addClass('loading').text('Testing connection...').show();
        
        $.ajax({
            url: sched_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sched_test_connection',
                nonce: sched_ajax.nonce
            },
            success: function(response) {
                button.prop('disabled', false);
                status.removeClass('loading');
                
                if (response.success) {
                    status.addClass('success').text(response.data);
                } else {
                    status.addClass('error').text('Connection failed: ' + response.data);
                }
            },
            error: function() {
                button.prop('disabled', false);
                status.removeClass('loading').addClass('error').text('Connection test failed due to network error.');
            }
        });
    });
    
    // Sync data button
    $('#sync-data').click(function() {
        var button = $(this);
        var status = $('#sync-status');
        
        button.prop('disabled', true);
        status.removeClass('success error').addClass('loading').text('Syncing data...').show();
        
        $.ajax({
            url: sched_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sched_sync_data',
                nonce: sched_ajax.nonce
            },
            success: function(response) {
                button.prop('disabled', false);
                status.removeClass('loading');
                
                if (response.success) {
                    status.addClass('success').text(response.data);
                    // Reload page to update last sync time
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    status.addClass('error').text('Sync failed: ' + response.data);
                }
            },
            error: function() {
                button.prop('disabled', false);
                status.removeClass('loading').addClass('error').text('Sync failed due to network error.');
            }
        });
    });

    // Color picker functionality
    $('.color-picker').on('change', function() {
        var color = $(this).val();
        var textInput = $(this).siblings('.color-text');
        var preview = $(this).siblings('.color-preview');
        
        textInput.val(color.toUpperCase());
        preview.css('background-color', color);
    });
    
    // Text input functionality
    $('.color-text').on('change keyup', function() {
        var color = $(this).val();
        if (/^#[0-9A-F]{6}$/i.test(color)) {
            var colorPicker = $(this).siblings('.color-picker');
            var preview = $(this).siblings('.color-preview');
            
            colorPicker.val(color);
            preview.css('background-color', color);
        }
    });
});
