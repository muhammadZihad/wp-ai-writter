<?php

declare(strict_types=1);

namespace AiWriter\Tests;

use AiWriter\Plugin;
use PHPUnit\Framework\TestCase;

/**
 * Plugin Test Class
 * 
 * @package AiWriter\Tests
 */
final class PluginTest extends TestCase
{
    /**
     * Test plugin version getter
     * 
     * @return void
     */
    public function testGetVersion(): void
    {
        $plugin = Plugin::getInstance(__FILE__);
        $this->assertEquals('1.0.0', $plugin->getVersion());
    }

    /**
     * Test plugin directory path getter
     * 
     * @return void
     */
    public function testGetPluginDir(): void
    {
        $plugin = Plugin::getInstance(__FILE__);
        $this->assertIsString($plugin->getPluginDir());
        $this->assertStringEndsWith('/', $plugin->getPluginDir());
    }

    /**
     * Test plugin URL getter
     * 
     * @return void
     */
    public function testGetPluginUrl(): void
    {
        $plugin = Plugin::getInstance(__FILE__);
        $this->assertIsString($plugin->getPluginUrl());
        $this->assertStringStartsWith('http', $plugin->getPluginUrl());
    }

    /**
     * Test singleton pattern
     * 
     * @return void
     */
    public function testSingletonPattern(): void
    {
        $plugin1 = Plugin::getInstance(__FILE__);
        $plugin2 = Plugin::getInstance();

        $this->assertSame($plugin1, $plugin2);
    }
} 