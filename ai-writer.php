<?php
/**
 * Plugin Name: AI Writer
 * Plugin URI: https://example.com/ai-writer
 * Description: A WordPress plugin that provides AI-powered writing assistance and content generation tools.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: ai-writer
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 8.2
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check PHP version
if (version_compare(PHP_VERSION, '8.2', '<')) {
    add_action('admin_notices', static function (): void {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('AI Writer requires PHP 8.2 or higher. Please upgrade your PHP version.', 'ai-writer');
        echo '</p></div>';
    });
    return;
}

// Load Composer autoloader
$autoloader = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloader)) {
    add_action('admin_notices', static function (): void {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('AI Writer: Composer dependencies not found. Please run "composer install".', 'ai-writer');
        echo '</p></div>';
    });
    return;
}

require_once $autoloader;

// Define plugin constants
define('AI_WRITER_VERSION', '1.0.0');
define('AI_WRITER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AI_WRITER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AI_WRITER_PLUGIN_FILE', __FILE__);

// Initialize the plugin
AiWriter\Plugin::getInstance(__FILE__); 