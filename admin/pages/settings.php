<?php
/**
 * AI Writer - Settings Page
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['submit']) && check_admin_referer('ai_writer_settings', 'ai_writer_settings_nonce')) {
    $settings = array(
        'api_key' => sanitize_text_field($_POST['api_key']),
        'model' => sanitize_text_field($_POST['model']),
        'max_tokens' => intval($_POST['max_tokens']),
        'temperature' => floatval($_POST['temperature']),
    );
    
    update_option('ai_writer_settings', $settings);
    
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully!', 'ai-writer') . '</p></div>';
}

// Get current settings
$settings = get_option('ai_writer_settings', array(
    'api_key' => '',
    'model' => 'gpt-3.5-turbo',
    'max_tokens' => 1000,
    'temperature' => 0.7,
));
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('ai_writer_settings', 'ai_writer_settings_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="api_key"><?php _e('OpenAI API Key', 'ai-writer'); ?></label>
                </th>
                <td>
                    <input type="password" id="api_key" name="api_key" value="<?php echo esc_attr($settings['api_key']); ?>" class="regular-text" />
                    <p class="description">
                        <?php _e('Enter your OpenAI API key. You can get one from', 'ai-writer'); ?> 
                        <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>.
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="model"><?php _e('AI Model', 'ai-writer'); ?></label>
                </th>
                <td>
                    <select id="model" name="model">
                        <option value="gpt-3.5-turbo" <?php selected($settings['model'], 'gpt-3.5-turbo'); ?>>
                            GPT-3.5 Turbo
                        </option>
                        <option value="gpt-4" <?php selected($settings['model'], 'gpt-4'); ?>>
                            GPT-4
                        </option>
                        <option value="gpt-4-turbo" <?php selected($settings['model'], 'gpt-4-turbo'); ?>>
                            GPT-4 Turbo
                        </option>
                    </select>
                    <p class="description">
                        <?php _e('Choose the AI model to use for content generation.', 'ai-writer'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="max_tokens"><?php _e('Max Tokens', 'ai-writer'); ?></label>
                </th>
                <td>
                    <input type="number" id="max_tokens" name="max_tokens" value="<?php echo esc_attr($settings['max_tokens']); ?>" min="100" max="4000" class="small-text" />
                    <p class="description">
                        <?php _e('Maximum number of tokens to generate (100-4000). Higher values allow for longer content.', 'ai-writer'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="temperature"><?php _e('Temperature', 'ai-writer'); ?></label>
                </th>
                <td>
                    <input type="number" id="temperature" name="temperature" value="<?php echo esc_attr($settings['temperature']); ?>" min="0" max="2" step="0.1" class="small-text" />
                    <p class="description">
                        <?php _e('Controls randomness in the output (0.0-2.0). Lower values make output more focused and deterministic.', 'ai-writer'); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <h2><?php _e('Content Generation Settings', 'ai-writer'); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="default_tone"><?php _e('Default Tone', 'ai-writer'); ?></label>
                </th>
                <td>
                    <select id="default_tone" name="default_tone">
                        <option value="professional"><?php _e('Professional', 'ai-writer'); ?></option>
                        <option value="casual"><?php _e('Casual', 'ai-writer'); ?></option>
                        <option value="friendly"><?php _e('Friendly', 'ai-writer'); ?></option>
                        <option value="formal"><?php _e('Formal', 'ai-writer'); ?></option>
                        <option value="creative"><?php _e('Creative', 'ai-writer'); ?></option>
                    </select>
                    <p class="description">
                        <?php _e('Default tone for content generation.', 'ai-writer'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="auto_save"><?php _e('Auto Save Generated Content', 'ai-writer'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="auto_save" name="auto_save" value="1" />
                    <label for="auto_save"><?php _e('Automatically save generated content to database', 'ai-writer'); ?></label>
                </td>
            </tr>
        </table>
        
        <h2><?php _e('API Connection Test', 'ai-writer'); ?></h2>
        
        <table class="form-table">
            <tr>
                <td>
                    <button type="button" class="button" id="test-connection">
                        <span class="dashicons dashicons-admin-plugins"></span>
                        <?php _e('Test API Connection', 'ai-writer'); ?>
                    </button>
                    <div id="connection-status" class="ai-writer-connection-status"></div>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
    
    <div class="ai-writer-settings-info">
        <h2><?php _e('Getting Started', 'ai-writer'); ?></h2>
        <ol>
            <li><?php _e('Sign up for an OpenAI account at', 'ai-writer'); ?> <a href="https://platform.openai.com" target="_blank">platform.openai.com</a></li>
            <li><?php _e('Generate an API key from your', 'ai-writer'); ?> <a href="https://platform.openai.com/api-keys" target="_blank"><?php _e('API keys page', 'ai-writer'); ?></a></li>
            <li><?php _e('Enter your API key above and save the settings', 'ai-writer'); ?></li>
            <li><?php _e('Test the connection to ensure everything is working', 'ai-writer'); ?></li>
            <li><?php _e('Start generating content from the', 'ai-writer'); ?> <a href="<?php echo admin_url('admin.php?page=ai-writer'); ?>"><?php _e('Dashboard', 'ai-writer'); ?></a></li>
        </ol>
        
        <h3><?php _e('Support', 'ai-writer'); ?></h3>
        <p><?php _e('If you need help or have questions, please visit our', 'ai-writer'); ?> <a href="#" target="_blank"><?php _e('documentation', 'ai-writer'); ?></a> <?php _e('or', 'ai-writer'); ?> <a href="#" target="_blank"><?php _e('contact support', 'ai-writer'); ?></a>.</p>
    </div>
</div> 