<?php

declare(strict_types=1);

namespace AiWriter;

/**
 * Main AI Writer Plugin Class
 *
 * This class handles the core functionality of the AI Writer plugin,
 * including initialization, admin menu setup, and asset management.
 *
 * @package AiWriter
 * @since 1.0.0
 */
final class Plugin
{
    /**
     * Plugin version
     */
    private const VERSION = '1.0.0';

    /**
     * Plugin instance
     */
    private static ?Plugin $instance = null;

    /**
     * Plugin directory path
     */
    private readonly string $pluginDir;

    /**
     * Plugin URL
     */
    private readonly string $pluginUrl;

    /**
     * Plugin file path
     */
    private readonly string $pluginFile;

    /**
     * Constructor
     *
     * @param string $pluginFile The main plugin file path
     */
    private function __construct(string $pluginFile)
    {
        $this->pluginFile = $pluginFile;
        $this->pluginDir = plugin_dir_path($pluginFile);
        $this->pluginUrl = plugin_dir_url($pluginFile);

        $this->initHooks();
    }

    /**
     * Get plugin instance (Singleton pattern)
     *
     * @param string|null $pluginFile The main plugin file path
     * @return Plugin
     */
    public static function getInstance(?string $pluginFile = null): Plugin
    {
        if (self::$instance === null && $pluginFile !== null) {
            self::$instance = new self($pluginFile);
        }

        return self::$instance ?? throw new \RuntimeException('Plugin not initialized');
    }

    /**
     * Initialize WordPress hooks
     *
     * @return void
     */
    private function initHooks(): void
    {
        add_action('init', [$this, 'init']);
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);

        // Activation and deactivation hooks
        register_activation_hook($this->pluginFile, [$this, 'activate']);
        register_deactivation_hook($this->pluginFile, [$this, 'deactivate']);
    }

    /**
     * Initialize the plugin
     *
     * @return void
     */
    public function init(): void
    {
        // Load text domain for translations
        load_plugin_textdomain(
            'ai-writer',
            false,
            dirname(plugin_basename($this->pluginFile)) . '/languages'
        );
    }

    /**
     * Add admin menu pages
     *
     * @return void
     */
    public function addAdminMenu(): void
    {
        // Add main menu page
        add_menu_page(
            __('AI Writer', 'ai-writer'),           // Page title
            __('AI Writer', 'ai-writer'),           // Menu title
            'manage_options',                        // Capability
            'ai-writer',                            // Menu slug
            [$this, 'renderAdminPage'],             // Callback function
            'dashicons-edit-large',                 // Icon
            30                                      // Position
        );

        // Add submenu pages
        add_submenu_page(
            'ai-writer',                            // Parent slug
            __('Dashboard', 'ai-writer'),           // Page title
            __('Dashboard', 'ai-writer'),           // Menu title
            'manage_options',                       // Capability
            'ai-writer',                            // Menu slug (same as parent for main page)
            [$this, 'renderAdminPage']              // Callback function
        );

        add_submenu_page(
            'ai-writer',                            // Parent slug
            __('Settings', 'ai-writer'),            // Page title
            __('Settings', 'ai-writer'),            // Menu title
            'manage_options',                       // Capability
            'ai-writer-settings',                   // Menu slug
            [$this, 'renderSettingsPage']           // Callback function
        );
    }

    /**
     * Render main admin page
     *
     * @return void
     */
    public function renderAdminPage(): void
    {
        $dashboardFile = $this->pluginDir . 'admin/pages/dashboard.php';

        if (file_exists($dashboardFile)) {
            include_once $dashboardFile;
        }
    }

    /**
     * Render settings page
     *
     * @return void
     */
    public function renderSettingsPage(): void
    {
        $settingsFile = $this->pluginDir . 'admin/pages/settings.php';

        if (file_exists($settingsFile)) {
            include_once $settingsFile;
        }
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook The current admin page hook
     * @return void
     */
    public function enqueueAdminScripts(string $hook): void
    {
        // Only load on our plugin pages
        if (!str_contains($hook, 'ai-writer')) {
            return;
        }

        $this->enqueueAdminStyles();
        $this->enqueueAdminJavaScript();
    }

    /**
     * Enqueue admin styles
     *
     * @return void
     */
    private function enqueueAdminStyles(): void
    {
        wp_enqueue_style(
            'ai-writer-admin',
            $this->pluginUrl . 'assets/css/admin.css',
            [],
            self::VERSION
        );
    }

    /**
     * Enqueue admin JavaScript
     *
     * @return void
     */
    private function enqueueAdminJavaScript(): void
    {
        wp_enqueue_script(
            'ai-writer-admin',
            $this->pluginUrl . 'assets/js/admin.js',
            ['jquery'],
            self::VERSION,
            true
        );

        // Localize script for AJAX
        wp_localize_script('ai-writer-admin', 'aiWriter', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_writer_nonce'),
            'strings' => [
                'error' => __('An error occurred. Please try again.', 'ai-writer'),
                'success' => __('Success!', 'ai-writer'),
            ]
        ]);
    }

    /**
     * Plugin activation callback
     *
     * @return void
     */
    public function activate(): void
    {
        $this->createDatabaseTables();
        $this->setDefaultOptions();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation callback
     *
     * @return void
     */
    public function deactivate(): void
    {
        // Clean up temporary data, flush rewrite rules, etc.
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     *
     * @return void
     */
    private function createDatabaseTables(): void
    {
        global $wpdb;

        $tableName = $wpdb->prefix . 'ai_writer_content';
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$tableName} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title text NOT NULL,
            content longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charsetCollate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Set default plugin options
     *
     * @return void
     */
    private function setDefaultOptions(): void
    {
        $defaultOptions = [
            'api_key' => '',
            'model' => 'gpt-3.5-turbo',
            'max_tokens' => 1000,
            'temperature' => 0.7,
        ];

        add_option('ai_writer_settings', $defaultOptions);
    }

    /**
     * Get plugin version
     *
     * @return string
     */
    public function getVersion(): string
    {
        return self::VERSION;
    }

    /**
     * Get plugin directory path
     *
     * @return string
     */
    public function getPluginDir(): string
    {
        return $this->pluginDir;
    }

    /**
     * Get plugin URL
     *
     * @return string
     */
    public function getPluginUrl(): string
    {
        return $this->pluginUrl;
    }
}
