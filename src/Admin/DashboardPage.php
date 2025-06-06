<?php

declare(strict_types=1);

namespace AiWriter\Admin;

/**
 * Dashboard Page Class
 *
 * Handles the main dashboard page functionality for the AI Writer plugin.
 *
 * @package AiWriter\Admin
 * @since 1.0.0
 */
final class DashboardPage
{
    /**
     * Render the dashboard page
     *
     * @return void
     */
    public function render(): void
    {
        // Prevent direct access
        if (!defined('ABSPATH')) {
            exit;
        }

        $stats = $this->getStats();
        $this->renderPage($stats);
    }

    /**
     * Get dashboard statistics
     *
     * @return array<string, mixed>
     */
    private function getStats(): array
    {
        // TODO: Implement actual statistics retrieval from database
        return [
            'content_generated' => 0,
            'words_written' => 0,
            'time_saved' => '0h',
        ];
    }

    /**
     * Render the complete dashboard page
     *
     * @param array<string, mixed> $stats Dashboard statistics
     * @return void
     */
    private function renderPage(array $stats): void
    {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="ai-writer-dashboard">
                <?php $this->renderWelcomePanel(); ?>
                <?php $this->renderStatsGrid($stats); ?>
                <?php $this->renderQuickActions(); ?>
                <?php $this->renderContentGenerator(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render the welcome panel
     *
     * @return void
     */
    private function renderWelcomePanel(): void
    {
        ?>
        <div class="ai-writer-welcome-panel">
            <h2><?php esc_html_e('Welcome to AI Writer', 'ai-writer'); ?></h2>
            <p><?php esc_html_e('Your AI-powered writing assistant is ready to help you create amazing content.', 'ai-writer'); ?></p>
        </div>
        <?php
    }

    /**
     * Render the statistics grid
     *
     * @param array<string, mixed> $stats Dashboard statistics
     * @return void
     */
    private function renderStatsGrid(array $stats): void
    {
        ?>
        <div class="ai-writer-stats-grid">
            <div class="ai-writer-stat-box">
                <h3><?php esc_html_e('Content Generated', 'ai-writer'); ?></h3>
                <div class="stat-number"><?php echo esc_html((string) $stats['content_generated']); ?></div>
                <p><?php esc_html_e('Total pieces of content created', 'ai-writer'); ?></p>
            </div>
            
            <div class="ai-writer-stat-box">
                <h3><?php esc_html_e('Words Written', 'ai-writer'); ?></h3>
                <div class="stat-number"><?php echo esc_html((string) $stats['words_written']); ?></div>
                <p><?php esc_html_e('Total words generated', 'ai-writer'); ?></p>
            </div>
            
            <div class="ai-writer-stat-box">
                <h3><?php esc_html_e('Time Saved', 'ai-writer'); ?></h3>
                <div class="stat-number"><?php echo esc_html($stats['time_saved']); ?></div>
                <p><?php esc_html_e('Estimated time saved', 'ai-writer'); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Render the quick actions section
     *
     * @return void
     */
    private function renderQuickActions(): void
    {
        ?>
        <div class="ai-writer-quick-actions">
            <h2><?php esc_html_e('Quick Actions', 'ai-writer'); ?></h2>
            <div class="action-buttons">
                <button class="button button-primary" id="generate-content">
                    <span class="dashicons dashicons-edit"></span>
                    <?php esc_html_e('Generate Content', 'ai-writer'); ?>
                </button>
                <button class="button" id="view-history">
                    <span class="dashicons dashicons-clock"></span>
                    <?php esc_html_e('View History', 'ai-writer'); ?>
                </button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=ai-writer-settings')); ?>" class="button">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php esc_html_e('Settings', 'ai-writer'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Render the content generator form
     *
     * @return void
     */
    private function renderContentGenerator(): void
    {
        $contentTypes = $this->getContentTypes();
        $lengthOptions = $this->getLengthOptions();
        $toneOptions = $this->getToneOptions();

        ?>
        <div class="ai-writer-content-generator">
            <h2><?php esc_html_e('Generate New Content', 'ai-writer'); ?></h2>
            <form id="ai-content-form">
                <?php wp_nonce_field('ai_writer_generate_content', 'ai_writer_generate_nonce'); ?>
                <table class="form-table">
                    <?php $this->renderTopicField(); ?>
                    <?php $this->renderContentTypeField($contentTypes); ?>
                    <?php $this->renderLengthField($lengthOptions); ?>
                    <?php $this->renderToneField($toneOptions); ?>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary" id="generate-btn">
                        <span class="dashicons dashicons-update"></span>
                        <?php esc_html_e('Generate Content', 'ai-writer'); ?>
                    </button>
                </p>
            </form>
            
            <?php $this->renderGeneratedContentArea(); ?>
        </div>
        <?php
    }

    /**
     * Render the topic input field
     *
     * @return void
     */
    private function renderTopicField(): void
    {
        ?>
        <tr>
            <th scope="row">
                <label for="content-topic"><?php esc_html_e('Topic', 'ai-writer'); ?></label>
            </th>
            <td>
                <input type="text" id="content-topic" name="topic" class="regular-text" 
                       placeholder="<?php esc_attr_e('Enter your content topic...', 'ai-writer'); ?>" />
                <p class="description">
                    <?php esc_html_e('Describe what you want to write about.', 'ai-writer'); ?>
                </p>
            </td>
        </tr>
        <?php
    }

    /**
     * Render the content type field
     *
     * @param array<string, string> $contentTypes Available content types
     * @return void
     */
    private function renderContentTypeField(array $contentTypes): void
    {
        ?>
        <tr>
            <th scope="row">
                <label for="content-type"><?php esc_html_e('Content Type', 'ai-writer'); ?></label>
            </th>
            <td>
                <select id="content-type" name="content_type">
                    <?php foreach ($contentTypes as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>">
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Render the length field
     *
     * @param array<string, string> $lengthOptions Available length options
     * @return void
     */
    private function renderLengthField(array $lengthOptions): void
    {
        ?>
        <tr>
            <th scope="row">
                <label for="content-length"><?php esc_html_e('Length', 'ai-writer'); ?></label>
            </th>
            <td>
                <select id="content-length" name="length">
                    <?php foreach ($lengthOptions as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($value, 'medium'); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Render the tone field
     *
     * @param array<string, string> $toneOptions Available tone options
     * @return void
     */
    private function renderToneField(array $toneOptions): void
    {
        ?>
        <tr>
            <th scope="row">
                <label for="content-tone"><?php esc_html_e('Tone', 'ai-writer'); ?></label>
            </th>
            <td>
                <select id="content-tone" name="tone">
                    <?php foreach ($toneOptions as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>">
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Render the generated content display area
     *
     * @return void
     */
    private function renderGeneratedContentArea(): void
    {
        ?>
        <div id="generated-content" class="ai-writer-generated-content" style="display: none;">
            <h3><?php esc_html_e('Generated Content', 'ai-writer'); ?></h3>
            <div class="content-output">
                <textarea id="content-result" rows="15" class="large-text" readonly></textarea>
            </div>
            <div class="content-actions">
                <button type="button" class="button" id="copy-content">
                    <span class="dashicons dashicons-clipboard"></span>
                    <?php esc_html_e('Copy to Clipboard', 'ai-writer'); ?>
                </button>
                <button type="button" class="button" id="save-content">
                    <span class="dashicons dashicons-saved"></span>
                    <?php esc_html_e('Save Content', 'ai-writer'); ?>
                </button>
                <button type="button" class="button button-primary" id="create-post">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e('Create New Post', 'ai-writer'); ?>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Get available content types
     *
     * @return array<string, string>
     */
    private function getContentTypes(): array
    {
        return [
            'blog-post' => __('Blog Post', 'ai-writer'),
            'article' => __('Article', 'ai-writer'),
            'social-media' => __('Social Media Post', 'ai-writer'),
            'email' => __('Email', 'ai-writer'),
            'product-description' => __('Product Description', 'ai-writer'),
        ];
    }

    /**
     * Get available length options
     *
     * @return array<string, string>
     */
    private function getLengthOptions(): array
    {
        return [
            'short' => __('Short (100-300 words)', 'ai-writer'),
            'medium' => __('Medium (300-800 words)', 'ai-writer'),
            'long' => __('Long (800+ words)', 'ai-writer'),
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
