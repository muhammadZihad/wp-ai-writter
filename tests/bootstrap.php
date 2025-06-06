<?php

declare(strict_types=1);

// Bootstrap file for PHPUnit tests
// This file mocks WordPress functions for testing

// Mock WordPress functions if they don't exist
if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return dirname($file) . '/';
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return 'http://example.com/' . basename(dirname($file)) . '/';
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        // Mock implementation
        return true;
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $callback) {
        // Mock implementation
        return true;
    }
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook($file, $callback) {
        // Mock implementation
        return true;
    }
}

if (!function_exists('load_plugin_textdomain')) {
    function load_plugin_textdomain($domain, $deprecated = false, $plugin_rel_path = false) {
        // Mock implementation
        return true;
    }
}

if (!function_exists('plugin_basename')) {
    function plugin_basename($file) {
        return basename(dirname($file)) . '/' . basename($file);
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url) {
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('get_admin_page_title')) {
    function get_admin_page_title() {
        return 'AI Writer Dashboard';
    }
}

if (!function_exists('esc_html_e')) {
    function esc_html_e($text, $domain = 'default') {
        echo esc_html(__($text, $domain));
    }
}

if (!function_exists('esc_attr_e')) {
    function esc_attr_e($text, $domain = 'default') {
        echo esc_attr(__($text, $domain));
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '') {
        return 'http://example.com/wp-admin/' . $path;
    }
}

if (!function_exists('wp_nonce_field')) {
    function wp_nonce_field($action, $name, $referer = true, $echo = true) {
        $nonce_field = '<input type="hidden" name="' . $name . '" value="test_nonce" />';
        if ($echo) {
            echo $nonce_field;
        }
        return $nonce_field;
    }
}

if (!function_exists('selected')) {
    function selected($selected, $current = true, $echo = true) {
        $result = selected_helper($selected, $current);
        if ($echo) {
            echo $result;
        }
        return $result;
    }
}

if (!function_exists('selected_helper')) {
    function selected_helper($selected, $current) {
        return (string) $selected === (string) $current ? ' selected="selected"' : '';
    }
}

if (!defined('ABSPATH')) {
    define('ABSPATH', '/var/www/html/');
}

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php'; 