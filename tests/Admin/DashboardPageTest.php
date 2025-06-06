<?php

declare(strict_types=1);

namespace AiWriter\Tests\Admin;

use AiWriter\Admin\DashboardPage;
use PHPUnit\Framework\TestCase;

/**
 * Dashboard Page Test Class
 *
 * @package AiWriter\Tests\Admin
 */
final class DashboardPageTest extends TestCase
{
    private DashboardPage $dashboardPage;

    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->dashboardPage = new DashboardPage();
    }

    /**
     * Test dashboard page instantiation
     *
     * @return void
     */
    public function testDashboardPageInstantiation(): void
    {
        $this->assertInstanceOf(DashboardPage::class, $this->dashboardPage);
    }

    /**
     * Test dashboard page renders without errors
     *
     * @return void
     */
    public function testDashboardPageRender(): void
    {
        ob_start();
        $this->dashboardPage->render();
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('ai-writer-dashboard', $output);
        $this->assertStringContainsString('Welcome to AI Writer', $output);
    }

    /**
     * Test dashboard page contains expected elements
     *
     * @return void
     */
    public function testDashboardPageContainsExpectedElements(): void
    {
        ob_start();
        $this->dashboardPage->render();
        $output = ob_get_clean();

        // Check for main sections
        $this->assertStringContainsString('ai-writer-welcome-panel', $output);
        $this->assertStringContainsString('ai-writer-stats-grid', $output);
        $this->assertStringContainsString('ai-writer-quick-actions', $output);
        $this->assertStringContainsString('ai-writer-content-generator', $output);

        // Check for form elements
        $this->assertStringContainsString('content-topic', $output);
        $this->assertStringContainsString('content-type', $output);
        $this->assertStringContainsString('content-length', $output);
        $this->assertStringContainsString('content-tone', $output);
    }
} 