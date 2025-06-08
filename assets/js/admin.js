jQuery(document).ready(function($) {
    'use strict';

    // Content generation form handler
    $('#ai-content-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $generateBtn = $('#generate-btn');
        const $contentResult = $('#content-result');
        const $generatedContent = $('#generated-content');
        
        // Debug: Check if elements are found
        console.log('AI Writer: Form elements found:');
        console.log('Form:', $form.length);
        console.log('Generate button:', $generateBtn.length);
        console.log('Content result:', $contentResult.length);
        console.log('Generated content:', $generatedContent.length);
        
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
        
        if (formData.topic.trim().length < 3) {
            alert('Topic must be at least 3 characters long.');
            return;
        }
        
        // Show loading state
        $generateBtn.prop('disabled', true).addClass('ai-writer-loading');
        $generateBtn.find('span').text('Generating...');
        $generateBtn.find('.dashicons').removeClass('dashicons-edit').addClass('dashicons-update spin');
        
        // Hide any previous content
        $generatedContent.hide();
        $contentResult.val('');
        
        // Show progress indicator
        if ($('#generation-progress').length === 0) {
            $form.after('<div id="generation-progress" class="ai-writer-progress">' +
                       '<div class="progress-bar"><div class="progress-fill"></div></div>' +
                       '<p class="progress-text">Generating your content...</p></div>');
        } else {
            $('#generation-progress').show();
        }
        
        // Make AJAX request
        $.ajax({
            url: aiWriter.ajaxUrl,
            type: 'POST',
            data: formData,
            timeout: 120000, // 2 minutes timeout for content generation
            success: function(response) {
                console.log('AI Writer: AJAX Response received:', response);
                
                if (response.success && response.data.content) {
                    console.log('AI Writer: Content generated successfully');
                    console.log('AI Writer: Response data:', response.data);
                    console.log('AI Writer: Generated title:', response.data.title);
                    
                    // Ensure the generated content section exists
                    if ($generatedContent.length === 0) {
                        console.error('AI Writer: Generated content section not found');
                        showNotification('Error: Content display area not found', 'error');
                        return;
                    }
                    
                    // Display the generated content with proper formatting
                    $contentResult.val(response.data.content);
                    
                    // Store the generated title for later use
                    $contentResult.data('generated-title', response.data.title || '');
                    
                    // Ensure textarea is visible and properly styled
                    $contentResult.show().removeClass('hidden');
                    
                    // Show word count, title, and success message
                    const wordCount = response.data.word_count || 0;
                    const generatedTitle = response.data.title || '';
                    const successMessage = response.data.message || 'Content generated successfully!';
                    
                    // Remove any existing content metadata
                    $generatedContent.find('.content-meta').remove();
                    
                    // Update content area with metadata including title
                    let contentHeader = '<div class="content-meta">';
                    if (generatedTitle) {
                        contentHeader += '<div class="generated-title"><strong>Generated Title:</strong> ' + generatedTitle + '</div>';
                    }
                    contentHeader += '<span class="word-count">Words: ' + wordCount + '</span>';
                    contentHeader += '<span class="generation-time">Generated: ' + new Date().toLocaleTimeString() + '</span>';
                    contentHeader += '</div>';
                    
                    // Insert metadata after the h3 heading if it exists, otherwise at the beginning
                    if ($generatedContent.find('h3').length > 0) {
                        $generatedContent.find('h3').after(contentHeader);
                    } else {
                        $generatedContent.prepend(contentHeader);
                    }
                    
                    // Show the generated content section
                    $generatedContent.show();
                    
                    // Force show after a brief delay to prevent interference
                    setTimeout(function() {
                        if ($generatedContent.is(':hidden')) {
                            console.log('AI Writer: Content section was hidden, forcing show');
                            $generatedContent.show();
                        }
                        
                        // Verify content is still there
                        if ($contentResult.val() === '') {
                            console.error('AI Writer: Content was cleared unexpectedly');
                            $contentResult.val(response.data.content);
                        }
                    }, 500);
                    
                    // Auto-resize textarea to fit content
                    setTimeout(function() {
                        autoResizeTextarea($contentResult[0]);
                    }, 100);
                    
                    // Scroll to generated content
                    $('html, body').animate({
                        scrollTop: $generatedContent.offset().top - 100
                    }, 500);
                    
                    // Show success notification
                    showNotification(successMessage, 'success');
                    
                    // Enable content action buttons
                    $('#generated-content .content-actions .button').prop('disabled', false);
                    
                } else {
                    console.log('AI Writer: Content generation failed:', response);
                    const errorMessage = response.data?.message || response.message || aiWriter.strings.error;
                    showNotification(errorMessage, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AI Writer: AJAX Error Details:');
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('Response Status:', xhr.status);
                console.error('Response Text:', xhr.responseText);
                
                let errorMessage = aiWriter.strings.error;
                
                if (status === 'timeout') {
                    errorMessage = 'Content generation timed out. Please try again with a shorter length.';
                } else if (xhr.status === 429) {
                    errorMessage = 'Rate limit exceeded. Please wait a moment before trying again.';
                } else if (xhr.status === 401) {
                    errorMessage = 'Invalid API key. Please check your settings.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error occurred. Please check your error logs.';
                } else if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.data && response.data.message) {
                            errorMessage = response.data.message;
                        }
                    } catch (e) {
                        console.error('Failed to parse error response:', e);
                    }
                }
                
                showNotification(errorMessage, 'error');
            },
            complete: function() {
                // Remove loading state
                $generateBtn.prop('disabled', false).removeClass('ai-writer-loading');
                $generateBtn.find('span').text('Generate Content');
                $generateBtn.find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-edit');
                
                // Hide progress indicator
                $('#generation-progress').hide();
            }
        });
    });
    
    // Copy to clipboard functionality
    $('#copy-content').on('click', function() {
        const $contentResult = $('#content-result');
        const content = $contentResult.val();
        
        if (!content) {
            showNotification('No content to copy.', 'error');
            return;
        }
        
        const $btn = $(this);
        const originalText = $btn.text();
        
        $btn.prop('disabled', true).text('Copying...');
        
        copyToClipboard(content).then(function(success) {
            if (success) {
                $btn.text('Copied!').addClass('button-primary');
                showNotification('Content copied to clipboard successfully!', 'success');
                
                setTimeout(function() {
                    $btn.text(originalText).removeClass('button-primary').prop('disabled', false);
                }, 2000);
            } else {
                $btn.text('Failed').addClass('button-secondary');
                showNotification('Failed to copy to clipboard. Please copy manually.', 'error');
                
                setTimeout(function() {
                    $btn.text(originalText).removeClass('button-secondary').prop('disabled', false);
                }, 2000);
            }
        }).catch(function() {
            $btn.text('Failed').addClass('button-secondary');
            showNotification('Failed to copy to clipboard. Please copy manually.', 'error');
            
            setTimeout(function() {
                $btn.text(originalText).removeClass('button-secondary').prop('disabled', false);
            }, 2000);
        });
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
        const generatedTitle = $contentResult.data('generated-title') || $('#content-topic').val() || 'Generated Content';
        
        console.log('AI Writer: Create post - stored title:', $contentResult.data('generated-title'));
        console.log('AI Writer: Create post - topic fallback:', $('#content-topic').val());
        console.log('AI Writer: Create post - final title:', generatedTitle);
        
        if (!content) {
            showNotification('No content to create post with.', 'error');
            return;
        }
        
        const $btn = $(this);
        const originalText = $btn.text();
        
        // Show loading state
        $btn.prop('disabled', true).text('Creating Post...');
        
        // Show confirmation dialog
        const selectedStatus = $('#content-post-status').val() || 'draft';
        const statusText = selectedStatus.charAt(0).toUpperCase() + selectedStatus.slice(1);
        const confirmed = confirm('Create a new post with the title "' + generatedTitle + '"?\n\nThe post will be created as: ' + statusText);
        
        if (!confirmed) {
            $btn.prop('disabled', false).text(originalText);
            return;
        }
        
        // Create the post via AJAX
        $.ajax({
            url: aiWriter.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ai_writer_create_post',
                title: generatedTitle,
                content: content,
                status: selectedStatus,
                nonce: aiWriter.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    
                    // Ask if user wants to edit the post now
                    const editNow = confirm('Post created successfully! Do you want to edit it now?');
                    
                    if (editNow && response.data.edit_url) {
                        window.open(response.data.edit_url, '_blank');
                    }
                } else {
                    showNotification(response.data.message || 'Failed to create post.', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AI Writer: Post creation error:', error);
                let errorMessage = 'Failed to create post. Please try again.';
                
                if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.data && response.data.message) {
                            errorMessage = response.data.message;
                        }
                    } catch (e) {
                        console.error('Failed to parse error response:', e);
                    }
                }
                
                showNotification(errorMessage, 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).text(originalText);
            }
        });
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
    
    // Helper function to show notifications
    function showNotification(message, type = 'info') {
        // Remove existing notifications
        $('.ai-writer-notification').remove();
        
        const notificationClass = 'ai-writer-notification notification-' + type;
        const icon = type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ';
        
        const $notification = $('<div class="' + notificationClass + '">' +
                               '<span class="notification-icon">' + icon + '</span>' +
                               '<span class="notification-message">' + message + '</span>' +
                               '<button class="notification-close">×</button>' +
                               '</div>');
        
        // Insert notification at the top of the dashboard
        $('.ai-writer-dashboard').prepend($notification);
        
        // Auto-hide after 5 seconds for success messages
        if (type === 'success') {
            setTimeout(function() {
                $notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
        
        // Manual close button
        $notification.find('.notification-close').on('click', function() {
            $notification.fadeOut(function() {
                $(this).remove();
            });
        });
    }
    
    // Helper function to auto-resize textarea
    function autoResizeTextarea(textarea) {
        if (!textarea) return;
        
        // Reset height to auto to get the correct scrollHeight
        textarea.style.height = 'auto';
        
        // Set height based on content, with min/max limits
        const newHeight = Math.max(Math.min(textarea.scrollHeight, 600), 200);
        textarea.style.height = newHeight + 'px';
    }
    
    // Enhanced content generation progress
    function updateProgress(percentage, message) {
        const $progress = $('#generation-progress');
        if ($progress.length) {
            $progress.find('.progress-fill').css('width', percentage + '%');
            $progress.find('.progress-text').text(message);
        }
    }
    
    // Copy content with enhanced feedback
    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            // Use modern clipboard API
            return navigator.clipboard.writeText(text).then(function() {
                return true;
            }).catch(function() {
                return fallbackCopy(text);
            });
        } else {
            // Fallback for older browsers
            return fallbackCopy(text);
        }
    }
    
    function fallbackCopy(text) {
        const $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(text).select();
        
        try {
            const successful = document.execCommand('copy');
            $temp.remove();
            return successful;
        } catch (err) {
            $temp.remove();
            return false;
        }
    }
    
    // Initialize content actions state
    function initializeContentActions() {
        $('#generated-content .content-actions .button').prop('disabled', true);
    }
    
    // Call initialization functions
    initializeContentActions();
    
    // Also initialize when document is ready
    $(document).ready(function() {
        initializeContentActions();
    });
}); 