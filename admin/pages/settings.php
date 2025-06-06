<?php
/**
 * AI Writer - Settings Page
 * 
 * This file now uses the OOP SettingsPage class for better organization.
 * This maintains backward compatibility while leveraging the new structure.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use AiWriter\Admin\SettingsPage;

// Create and render the settings page using the OOP class
$settingsPage = new SettingsPage();
$settingsPage->render(); 