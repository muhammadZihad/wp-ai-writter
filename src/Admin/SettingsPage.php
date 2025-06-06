<?php

declare(strict_types=1);

namespace AiWriter\Admin;

/**
 * Settings Page Class
 *
 * Handles the settings page functionality for the AI Writer plugin.
 *
 * @package AiWriter\Admin
 * @since 1.0.0
 */
final class SettingsPage
{
    /**
     * Default settings values
     */
    private const DEFAULT_SETTINGS = [
        'api_key' => '',
        'model' => 'gpt-3.5-turbo',
        'max_tokens' => 1000,
        'temperature' => 0.7,
        'default_tone' => 'professional',
        'auto_save' => false,
    ];

    /**
     * Settings option name in WordPress
     */
    private const OPTION_NAME = 'ai_writer_settings';

    /**
     * Nonce action for settings form
     */
    private const NONCE_ACTION = 'ai_writer_settings';

    /**
     * Nonce field name
     */
    private const NONCE_FIELD = 'ai_writer_settings_nonce';

    /**
     * Render the settings page
     *
     * @return void
     */
    public function render(): void
    {
        // Prevent direct access
        if (!defined('ABSPATH')) {
            exit;
        }

        $this->handleFormSubmission();
        $settings = $this->getSettings();
        $this->renderPage($settings);
    }

    /**
     * Handle form submission
     *
     * @return void
     */
    private function handleFormSubmission(): void
    {
        if (!isset($_POST['submit']) || !$this->verifyNonce()) {
            return;
        }

        $settings = $this->sanitizeSettings($_POST);
        $this->saveSettings($settings);
        $this->displaySuccessMessage();
    }

    /**
     * Verify the nonce for security
     *
     * @return bool
     */
    private function verifyNonce(): bool
    {
        return isset($_POST[self::NONCE_FIELD]) &&
               wp_verify_nonce($_POST[self::NONCE_FIELD], self::NONCE_ACTION);
    }

    /**
     * Sanitize settings from form submission
     *
     * @param array<string, mixed> $postData POST data from form
     * @return array<string, mixed>
     */
    private function sanitizeSettings(array $postData): array
    {
        return [
            'api_key' => sanitize_text_field($postData['api_key'] ?? ''),
            'model' => sanitize_text_field($postData['model'] ?? self::DEFAULT_SETTINGS['model']),
            'max_tokens' => $this->sanitizeMaxTokens($postData['max_tokens'] ?? self::DEFAULT_SETTINGS['max_tokens']),
            'temperature' => $this->sanitizeTemperature($postData['temperature'] ?? self::DEFAULT_SETTINGS['temperature']),
            'default_tone' => sanitize_text_field($postData['default_tone'] ?? self::DEFAULT_SETTINGS['default_tone']),
            'auto_save' => !empty($postData['auto_save']),
        ];
    }

    /**
     * Sanitize max tokens value
     *
     * @param mixed $value Raw value
     * @return int
     */
    private function sanitizeMaxTokens($value): int
    {
        $tokens = intval($value);
        return max(100, min(4000, $tokens));
    }

    /**
     * Sanitize temperature value
     *
     * @param mixed $value Raw value
     * @return float
     */
    private function sanitizeTemperature($value): float
    {
        $temperature = floatval($value);
        return max(0.0, min(2.0, $temperature));
    }

    /**
     * Save settings to WordPress options
     *
     * @param array<string, mixed> $settings Settings to save
     * @return void
     */
    private function saveSettings(array $settings): void
    {
        update_option(self::OPTION_NAME, $settings);
    }

    /**
     * Display success message
     *
     * @return void
     */
    private function displaySuccessMessage(): void
    {
        echo '<div class="notice notice-success is-dismissible"><p>' .
             esc_html__('Settings saved successfully!', 'ai-writer') .
             '</p></div>';
    }

    /**
     * Get current settings
     *
     * @return array<string, mixed>
     */
    private function getSettings(): array
    {
        return get_option(self::OPTION_NAME, self::DEFAULT_SETTINGS);
    }

    /**
     * Render the complete settings page
     *
     * @param array<string, mixed> $settings Current settings
     * @return void
     */
    private function renderPage(array $settings): void
    {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD); ?>
                
                <?php $this->renderApiSettings($settings); ?>
                <?php $this->renderContentSettings($settings); ?>
                <?php $this->renderConnectionTest(); ?>
                
                <?php submit_button(); ?>
            </form>
            
            <?php $this->renderInformationSection(); ?>
        </div>
        <?php
    }

    /**
     * Render API settings section
     *
     * @param array<string, mixed> $settings Current settings
     * @return void
     */
    private function renderApiSettings(array $settings): void
    {
        ?>
        <table class="form-table">
            <?php $this->renderApiKeyField($settings['api_key']); ?>
            <?php $this->renderModelField($settings['model']); ?>
            <?php $this->renderMaxTokensField($settings['max_tokens']); ?>
            <?php $this->renderTemperatureField($settings['temperature']); ?>
        </table>
        <?php
    }

    /**
     * Render API key field
     *
     * @param string $apiKey Current API key
     * @return void
     */
    private function renderApiKeyField(string $apiKey): void
    {
        ?>
        <tr>
            <th scope="row">
                <label for="api_key"><?php esc_html_e('OpenAI API Key', 'ai-writer'); ?></label>
            </th>
            <td>
                <input type="password" id="api_key" name="api_key" 
                       value="<?php echo esc_attr($apiKey); ?>" class="regular-text" />
                <p class="description">
                    <?php esc_html_e('Enter your OpenAI API key. You can get one from', 'ai-writer'); ?> 
                    <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>.
                </p>
            </td>
        </tr>
        <?php
    }

    /**
     * Render model selection field
     *
     * @param string $currentModel Current selected model
     * @return void
     */
    private function renderModelField(string $currentModel): void
    {
        $models = $this->getAvailableModels();

        ?>
        <tr>
            <th scope="row">
                <label for="model"><?php esc_html_e('AI Model', 'ai-writer'); ?></label>
            </th>
            <td>
                <select id="model" name="model">
                    <?php foreach ($models as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($currentModel, $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">
                    <?php esc_html_e('Choose the AI model to use for content generation.', 'ai-writer'); ?>
                </p>
            </td>
        </tr>
        <?php
    }

    /**
     * Render max tokens field
     *
     * @param int $maxTokens Current max tokens
     * @return void
     */
    private function renderMaxTokensField(int $maxTokens): void
    {
        ?>
        <tr>
            <th scope="row">
                <label for="max_tokens"><?php esc_html_e('Max Tokens', 'ai-writer'); ?></label>
            </th>
            <td>
                <input type="number" id="max_tokens" name="max_tokens" 
                       value="<?php echo esc_attr((string) $maxTokens); ?>" 
                       min="100" max="4000" class="small-text" />
                <p class="description">
                    <?php esc_html_e('Maximum number of tokens to generate (100-4000). Higher values allow for longer content.', 'ai-writer'); ?>
                </p>
            </td>
        </tr>
        <?php
    }

    /**
     * Render temperature field
     *
     * @param float $temperature Current temperature
     * @return void
     */
    private function renderTemperatureField(float $temperature): void
    {
        ?>
        <tr>
            <th scope="row">
                <label for="temperature"><?php esc_html_e('Temperature', 'ai-writer'); ?></label>
            </th>
            <td>
                <input type="number" id="temperature" name="temperature" 
                       value="<?php echo esc_attr((string) $temperature); ?>" 
                       min="0" max="2" step="0.1" class="small-text" />
                <p class="description">
                    <?php esc_html_e('Controls randomness in the output (0.0-2.0). Lower values make output more focused and deterministic.', 'ai-writer'); ?>
                </p>
            </td>
        </tr>
        <?php
    }

    /**
     * Render content generation settings section
     *
     * @param array<string, mixed> $settings Current settings
     * @return void
     */
    private function renderContentSettings(array $settings): void
    {
        ?>
        <h2><?php esc_html_e('Content Generation Settings', 'ai-writer'); ?></h2>
        
        <table class="form-table">
            <?php $this->renderDefaultToneField($settings['default_tone']); ?>
            <?php $this->renderAutoSaveField($settings['auto_save']); ?>
        </table>
        <?php
    }

    /**
     * Render default tone field
     *
     * @param string $defaultTone Current default tone
     * @return void
     */
    private function renderDefaultToneField(string $defaultTone): void
    {
        $toneOptions = $this->getToneOptions();

        ?>
        <tr>
            <th scope="row">
                <label for="default_tone"><?php esc_html_e('Default Tone', 'ai-writer'); ?></label>
            </th>
            <td>
                <select id="default_tone" name="default_tone">
                    <?php foreach ($toneOptions as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($defaultTone, $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">
                    <?php esc_html_e('Default tone for content generation.', 'ai-writer'); ?>
                </p>
            </td>
        </tr>
        <?php
    }

    /**
     * Render auto save field
     *
     * @param bool $autoSave Current auto save setting
     * @return void
     */
    private function renderAutoSaveField(bool $autoSave): void
    {
        ?>
        <tr>
            <th scope="row">
                <label for="auto_save"><?php esc_html_e('Auto Save Generated Content', 'ai-writer'); ?></label>
            </th>
            <td>
                <input type="checkbox" id="auto_save" name="auto_save" value="1" <?php checked($autoSave); ?> />
                <label for="auto_save"><?php esc_html_e('Automatically save generated content to database', 'ai-writer'); ?></label>
            </td>
        </tr>
        <?php
    }

    /**
     * Render connection test section
     *
     * @return void
     */
    private function renderConnectionTest(): void
    {
        ?>
        <h2><?php esc_html_e('API Connection Test', 'ai-writer'); ?></h2>
        
        <table class="form-table">
            <tr>
                <td>
                    <button type="button" class="button" id="test-connection">
                        <span class="dashicons dashicons-admin-plugins"></span>
                        <?php esc_html_e('Test API Connection', 'ai-writer'); ?>
                    </button>
                    <div id="connection-status" class="ai-writer-connection-status"></div>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render information and help section
     *
     * @return void
     */
    private function renderInformationSection(): void
    {
        ?>
        <div class="ai-writer-settings-info">
            <h2><?php esc_html_e('Getting Started', 'ai-writer'); ?></h2>
            <ol>
                <li>
                    <?php esc_html_e('Sign up for an OpenAI account at', 'ai-writer'); ?> 
                    <a href="https://platform.openai.com" target="_blank">platform.openai.com</a>
                </li>
                <li>
                    <?php esc_html_e('Generate an API key from your', 'ai-writer'); ?> 
                    <a href="https://platform.openai.com/api-keys" target="_blank">
                        <?php esc_html_e('API keys page', 'ai-writer'); ?>
                    </a>
                </li>
                <li><?php esc_html_e('Enter your API key above and save the settings', 'ai-writer'); ?></li>
                <li><?php esc_html_e('Test the connection to ensure everything is working', 'ai-writer'); ?></li>
                <li>
                    <?php esc_html_e('Start generating content from the', 'ai-writer'); ?> 
                    <a href="<?php echo esc_url(admin_url('admin.php?page=ai-writer')); ?>">
                        <?php esc_html_e('Dashboard', 'ai-writer'); ?>
                    </a>
                </li>
            </ol>
            
            <h3><?php esc_html_e('Support', 'ai-writer'); ?></h3>
            <p>
                <?php esc_html_e('If you need help or have questions, please visit our', 'ai-writer'); ?> 
                <a href="#" target="_blank"><?php esc_html_e('documentation', 'ai-writer'); ?></a> 
                <?php esc_html_e('or', 'ai-writer'); ?> 
                <a href="#" target="_blank"><?php esc_html_e('contact support', 'ai-writer'); ?></a>.
            </p>
        </div>
        <?php
    }

    /**
     * Get available AI models
     *
     * @return array<string, string>
     */
    private function getAvailableModels(): array
    {
        return [
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
            'gpt-4' => 'GPT-4',
            'gpt-4-turbo' => 'GPT-4 Turbo',
        ];
    }

    /**
     * Get available tone options
     *
     * @return array<string, string>
     */
    private function getToneOptions(): array
    {
        return [
            'professional' => __('Professional', 'ai-writer'),
            'casual' => __('Casual', 'ai-writer'),
            'friendly' => __('Friendly', 'ai-writer'),
            'formal' => __('Formal', 'ai-writer'),
            'creative' => __('Creative', 'ai-writer'),
        ];
    }
}
