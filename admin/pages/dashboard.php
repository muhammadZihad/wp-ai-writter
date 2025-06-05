<?php
/**
 * AI Writer - Dashboard Page
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="ai-writer-dashboard">
        <div class="ai-writer-welcome-panel">
            <h2><?php _e('Welcome to AI Writer', 'ai-writer'); ?></h2>
            <p><?php _e('Your AI-powered writing assistant is ready to help you create amazing content.', 'ai-writer'); ?></p>
        </div>
        
        <div class="ai-writer-stats-grid">
            <div class="ai-writer-stat-box">
                <h3><?php _e('Content Generated', 'ai-writer'); ?></h3>
                <div class="stat-number">0</div>
                <p><?php _e('Total pieces of content created', 'ai-writer'); ?></p>
            </div>
            
            <div class="ai-writer-stat-box">
                <h3><?php _e('Words Written', 'ai-writer'); ?></h3>
                <div class="stat-number">0</div>
                <p><?php _e('Total words generated', 'ai-writer'); ?></p>
            </div>
            
            <div class="ai-writer-stat-box">
                <h3><?php _e('Time Saved', 'ai-writer'); ?></h3>
                <div class="stat-number">0h</div>
                <p><?php _e('Estimated time saved', 'ai-writer'); ?></p>
            </div>
        </div>
        
        <div class="ai-writer-quick-actions">
            <h2><?php _e('Quick Actions', 'ai-writer'); ?></h2>
            <div class="action-buttons">
                <button class="button button-primary" id="generate-content">
                    <span class="dashicons dashicons-edit"></span>
                    <?php _e('Generate Content', 'ai-writer'); ?>
                </button>
                <button class="button" id="view-history">
                    <span class="dashicons dashicons-clock"></span>
                    <?php _e('View History', 'ai-writer'); ?>
                </button>
                <a href="<?php echo admin_url('admin.php?page=ai-writer-settings'); ?>" class="button">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php _e('Settings', 'ai-writer'); ?>
                </a>
            </div>
        </div>
        
        <div class="ai-writer-content-generator">
            <h2><?php _e('Generate New Content', 'ai-writer'); ?></h2>
            <form id="ai-content-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="content-topic"><?php _e('Topic', 'ai-writer'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="content-topic" name="topic" class="regular-text" 
                                   placeholder="<?php _e('Enter your content topic...', 'ai-writer'); ?>" />
                            <p class="description">
                                <?php _e('Describe what you want to write about.', 'ai-writer'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="content-type"><?php _e('Content Type', 'ai-writer'); ?></label>
                        </th>
                        <td>
                            <select id="content-type" name="content_type">
                                <option value="blog-post"><?php _e('Blog Post', 'ai-writer'); ?></option>
                                <option value="article"><?php _e('Article', 'ai-writer'); ?></option>
                                <option value="social-media"><?php _e('Social Media Post', 'ai-writer'); ?></option>
                                <option value="email"><?php _e('Email', 'ai-writer'); ?></option>
                                <option value="product-description"><?php _e('Product Description', 'ai-writer'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="content-length"><?php _e('Length', 'ai-writer'); ?></label>
                        </th>
                        <td>
                            <select id="content-length" name="length">
                                <option value="short"><?php _e('Short (100-300 words)', 'ai-writer'); ?></option>
                                <option value="medium" selected><?php _e('Medium (300-800 words)', 'ai-writer'); ?></option>
                                <option value="long"><?php _e('Long (800+ words)', 'ai-writer'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="content-tone"><?php _e('Tone', 'ai-writer'); ?></label>
                        </th>
                        <td>
                            <select id="content-tone" name="tone">
                                <option value="professional"><?php _e('Professional', 'ai-writer'); ?></option>
                                <option value="casual"><?php _e('Casual', 'ai-writer'); ?></option>
                                <option value="friendly"><?php _e('Friendly', 'ai-writer'); ?></option>
                                <option value="formal"><?php _e('Formal', 'ai-writer'); ?></option>
                                <option value="creative"><?php _e('Creative', 'ai-writer'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary" id="generate-btn">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Generate Content', 'ai-writer'); ?>
                    </button>
                </p>
            </form>
            
            <div id="generated-content" class="ai-writer-generated-content" style="display: none;">
                <h3><?php _e('Generated Content', 'ai-writer'); ?></h3>
                <div class="content-output">
                    <textarea id="content-result" rows="15" class="large-text"></textarea>
                </div>
                <div class="content-actions">
                    <button type="button" class="button" id="copy-content">
                        <span class="dashicons dashicons-clipboard"></span>
                        <?php _e('Copy to Clipboard', 'ai-writer'); ?>
                    </button>
                    <button type="button" class="button" id="save-content">
                        <span class="dashicons dashicons-saved"></span>
                        <?php _e('Save Content', 'ai-writer'); ?>
                    </button>
                    <button type="button" class="button button-primary" id="create-post">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Create New Post', 'ai-writer'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div> 