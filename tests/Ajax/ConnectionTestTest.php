<?php

declare(strict_types=1);

namespace AiWriter\Tests\Ajax;

use AiWriter\Ajax\ConnectionTest;
use PHPUnit\Framework\TestCase;

/**
 * Connection Test AJAX Handler Test Class
 *
 * @package AiWriter\Tests\Ajax
 */
final class ConnectionTestTest extends TestCase
{
    private ConnectionTest $connectionTest;

    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->connectionTest = new ConnectionTest();
        
        // Mock $_POST data
        $_POST = [
            'nonce' => 'test_nonce',
            'api_key' => 'test-api-key'
        ];
    }

    /**
     * Clean up after tests
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $_POST = [];
        parent::tearDown();
    }

    /**
     * Test connection test instantiation
     *
     * @return void
     */
    public function testConnectionTestInstantiation(): void
    {
        $this->assertInstanceOf(ConnectionTest::class, $this->connectionTest);
    }

    /**
     * Test handle method with valid API key
     *
     * @return void
     */
    public function testHandleWithValidApiKey(): void
    {
        $this->expectOutputString('{"success":true,"data":{"message":"Connection successful! Found 2 available models.","model_info":{"total_models":2,"available_models":["gpt-3.5-turbo","gpt-4"]}}}');
        
        $this->connectionTest->handle();
    }

    /**
     * Test handle method without API key
     *
     * @return void
     */
    public function testHandleWithoutApiKey(): void
    {
        $_POST['api_key'] = '';
        
        $this->expectOutputString('{"success":false,"data":{"message":"No API key found. Please enter your OpenAI API key first."}}');
        
        $this->connectionTest->handle();
    }
} 