jQuery(document).ready(function($) {
    'use strict';

    // Content generation form handler
    $('#ai-content-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $generateBtn = $('#generate-btn');
        const $contentResult = $('#content-result');
        const $generatedContent = $('#generated-content');
        
        // Get form data
        const formData = {
            topic: $('#content-topic').val(),
            content_type: $('#content-type').val(),
            length: $('#content-length').val(),
            tone: $('#content-tone').val(),
            action: 'ai_writer_generate_content',
            nonce: aiWriter.nonce
        };
        
        // Validate required fields
        if (!formData.topic.trim()) {
            alert(aiWriter.strings.error + ' Please enter a topic.');
            return;
        }
        
        // Show loading state
        $generateBtn.prop('disabled', true).addClass('ai-writer-loading');
        $generateBtn.find('.dashicons').removeClass('dashicons-update').addClass('dashicons-update');
        
        // Make AJAX request
        $.ajax({
            url: aiWriter.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $contentResult.val(response.data.content);
                    $generatedContent.show();
                    
                    // Scroll to generated content
                    $('html, body').animate({
                        scrollTop: $generatedContent.offset().top - 100
                    }, 500);
                } else {
                    alert(response.data.message || aiWriter.strings.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert(aiWriter.strings.error);
            },
            complete: function() {
                // Remove loading state
                $generateBtn.prop('disabled', false).removeClass('ai-writer-loading');
                $generateBtn.find('.dashicons').removeClass('dashicons-update').addClass('dashicons-update');
            }
        });
    });
    
    // Copy to clipboard functionality
    $('#copy-content').on('click', function() {
        const $contentResult = $('#content-result');
        const content = $contentResult.val();
        
        if (!content) {
            alert('No content to copy.');
            return;
        }
        
        // Create temporary textarea for copying
        const $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(content).select();
        
        try {
            document.execCommand('copy');
            
            // Show success feedback
            const $btn = $(this);
            const originalText = $btn.text();
            $btn.text('Copied!').addClass('button-primary');
            
            setTimeout(function() {
                $btn.text(originalText).removeClass('button-primary');
            }, 2000);
            
        } catch (err) {
            console.error('Copy failed:', err);
            alert('Failed to copy to clipboard. Please copy manually.');
        }
        
        $temp.remove();
    });
    
    // Save content functionality
    $('#save-content').on('click', function() {
        const $contentResult = $('#content-result');
        const content = $contentResult.val();
        
        if (!content) {
            alert('No content to save.');
            return;
        }
        
        const $btn = $(this);
        const originalText = $btn.text();
        
        $btn.prop('disabled', true).text('Saving...');
        
        $.ajax({
            url: aiWriter.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ai_writer_save_content',
                content: content,
                title: $('#content-topic').val() || 'Generated Content',
                nonce: aiWriter.nonce
            },
            success: function(response) {
                if (response.success) {
                    $btn.text('Saved!').addClass('button-primary');
                    setTimeout(function() {
                        $btn.text(originalText).removeClass('button-primary');
                    }, 2000);
                } else {
                    alert(response.data.message || 'Failed to save content.');
                }
            },
            error: function() {
                alert('Failed to save content. Please try again.');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
    
    // Create new post functionality
    $('#create-post').on('click', function() {
        const $contentResult = $('#content-result');
        const content = $contentResult.val();
        const title = $('#content-topic').val() || 'Generated Content';
        
        if (!content) {
            alert('No content to create post with.');
            return;
        }
        
        // Create form and submit to wp-admin/post-new.php
        const $form = $('<form>', {
            method: 'POST',
            action: 'post-new.php'
        });
        
        $form.append($('<input>', {
            type: 'hidden',
            name: 'post_title',
            value: title
        }));
        
        $form.append($('<input>', {
            type: 'hidden',
            name: 'content',
            value: content
        }));
        
        $form.append($('<input>', {
            type: 'hidden',
            name: 'ai_writer_generated',
            value: '1'
        }));
        
        $('body').append($form);
        $form.submit();
    });
    
    // API connection test
    $('#test-connection').on('click', function() {
        const $btn = $(this);
        const $status = $('#connection-status');
        const $apiKeyField = $('#api_key');
        const originalText = $btn.text();
        
        // Get the current API key from the form
        const apiKey = $apiKeyField.val().trim();
        
        if (!apiKey) {
            $status.removeClass('loading success').addClass('error')
                .text('✗ Please enter an API key first.')
                .show();
            return;
        }
        
        $btn.prop('disabled', true).text('Testing...');
        $status.removeClass('success error').addClass('loading')
            .text('Testing API connection...').show();
        
        $.ajax({
            url: aiWriter.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ai_writer_test_connection',
                api_key: apiKey,
                nonce: aiWriter.nonce
            },
            success: function(response) {
                if (response.success) {
                    let message = '✓ ' + response.data.message;
                    if (response.data.model_info && response.data.model_info.available_models) {
                        const models = response.data.model_info.available_models;
                        if (models.length > 0) {
                            message += ' Available models: ' + models.slice(0, 3).join(', ');
                            if (models.length > 3) {
                                message += ' and ' + (models.length - 3) + ' more.';
                            }
                        }
                    }
                    $status.removeClass('loading error').addClass('success').text(message);
                } else {
                    $status.removeClass('loading success').addClass('error')
                        .text('✗ ' + (response.data.message || 'Connection test failed.'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                $status.removeClass('loading success').addClass('error')
                    .text('✗ Connection test failed. Please check your internet connection.');
            },
            complete: function() {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Quick action buttons
    $('#generate-content').on('click', function() {
        $('html, body').animate({
            scrollTop: $('.ai-writer-content-generator').offset().top - 100
        }, 500);
        $('#content-topic').focus();
    });
    
    $('#view-history').on('click', function() {
        // This would navigate to a history page if implemented
        alert('History feature coming soon!');
    });
    
    // Form validation
    $('#content-topic').on('input', function() {
        const $generateBtn = $('#generate-btn');
        const hasValue = $(this).val().trim().length > 0;
        $generateBtn.prop('disabled', !hasValue);
    });
    
    // Initialize form state
    $('#generate-btn').prop('disabled', $('#content-topic').val().trim().length === 0);
    
    // Auto-resize textarea
    $('#content-result').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
    
    // Settings page enhancements
    if ($('#api_key').length) {
        // Toggle API key visibility
        $('<button type="button" class="button" style="margin-left: 10px;">Show</button>')
            .insertAfter('#api_key')
            .on('click', function() {
                const $apiKey = $('#api_key');
                const $btn = $(this);
                
                if ($apiKey.attr('type') === 'password') {
                    $apiKey.attr('type', 'text');
                    $btn.text('Hide');
                } else {
                    $apiKey.attr('type', 'password');
                    $btn.text('Show');
                }
            });
    }
    
    // Auto-save settings (optional)
    $('.form-table input, .form-table select').on('change', function() {
        // Could implement auto-save functionality here
    });
    
    // Tooltips for form elements
    $('[data-tooltip]').each(function() {
        $(this).attr('title', $(this).data('tooltip'));
    });
    
    // Confirmation for potentially destructive actions
    $('form').on('submit', function(e) {
        const $form = $(this);
        
        if ($form.hasClass('requires-confirmation')) {
            if (!confirm('Are you sure you want to proceed?')) {
                e.preventDefault();
                return false;
            }
        }
    });
}); 