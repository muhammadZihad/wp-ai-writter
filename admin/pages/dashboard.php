<?php
/**
 * AI Writer - Dashboard Page
 * 
 * This file now uses the OOP DashboardPage class for better organization.
 * This maintains backward compatibility while leveraging the new structure.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use AiWriter\Admin\DashboardPage;

// Create and render the dashboard page using the OOP class
$dashboardPage = new DashboardPage();
$dashboardPage->render(); 