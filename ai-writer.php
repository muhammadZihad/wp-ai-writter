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
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AI_WRITER_VERSION', '1.0.0');
define('AI_WRITER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AI_WRITER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AI_WRITER_PLUGIN_FILE', __FILE__);

/**
 * Main AI Writer Plugin Class
 */
class AI_Writer_Plugin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('ai-writer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Add main menu page
        add_menu_page(
            __('AI Writer', 'ai-writer'),           // Page title
            __('AI Writer', 'ai-writer'),           // Menu title
            'manage_options',                        // Capability
            'ai-writer',                            // Menu slug
            array($this, 'admin_page'),             // Callback function
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
            array($this, 'admin_page')              // Callback function
        );
        
        add_submenu_page(
            'ai-writer',                            // Parent slug
            __('Settings', 'ai-writer'),            // Page title
            __('Settings', 'ai-writer'),            // Menu title
            'manage_options',                       // Capability
            'ai-writer-settings',                   // Menu slug
            array($this, 'settings_page')           // Callback function
        );
    }
    
    /**
     * Main admin page callback
     */
    public function admin_page() {
        include_once AI_WRITER_PLUGIN_DIR . 'admin/pages/dashboard.php';
    }
    
    /**
     * Settings page callback
     */
    public function settings_page() {
        include_once AI_WRITER_PLUGIN_DIR . 'admin/pages/settings.php';
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'ai-writer') === false) {
            return;
        }
        
        wp_enqueue_style(
            'ai-writer-admin',
            AI_WRITER_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            AI_WRITER_VERSION
        );
        
        wp_enqueue_script(
            'ai-writer-admin',
            AI_WRITER_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            AI_WRITER_VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('ai-writer-admin', 'aiWriter', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_writer_nonce'),
            'strings' => array(
                'error' => __('An error occurred. Please try again.', 'ai-writer'),
                'success' => __('Success!', 'ai-writer'),
            )
        ));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables, set default options, etc.
        $this->create_database_tables();
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up temporary data, flush rewrite rules, etc.
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private function create_database_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ai_writer_content';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title text NOT NULL,
            content longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        $default_options = array(
            'api_key' => '',
            'model' => 'gpt-3.5-turbo',
            'max_tokens' => 1000,
            'temperature' => 0.7,
        );
        
        add_option('ai_writer_settings', $default_options);
    }
}

// Initialize the plugin
new AI_Writer_Plugin(); 