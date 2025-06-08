<?php

declare(strict_types=1);

namespace AiWriter\Tests\Ajax;

use AiWriter\Ajax\ContentGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Content Generator AJAX Handler Test Class
 *
 * @package AiWriter\Tests\Ajax
 */
final class ContentGeneratorTest extends TestCase
{
    private ContentGenerator $contentGenerator;

    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->contentGenerator = new ContentGenerator();
        
        // Mock $_POST data
        $_POST = [
            'nonce' => 'test_nonce',
            'topic' => 'WordPress Development',
            'content_type' => 'blog-post',
            'length' => 'medium',
            'tone' => 'professional'
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
     * Test content generator instantiation
     *
     * @return void
     */
    public function testContentGeneratorInstantiation(): void
    {
        $this->assertInstanceOf(ContentGenerator::class, $this->contentGenerator);
    }

    /**
     * Test handle method with valid data
     *
     * @return void
     */
    public function testHandleWithValidData(): void
    {
        $this->expectOutputRegex('/{"success":true,"data":{"content":".*","message":".*","word_count":\d+}}/');
        
        $this->contentGenerator->handle();
    }

    /**
     * Test handle method without topic
     *
     * @return void
     */
    public function testHandleWithoutTopic(): void
    {
        $_POST['topic'] = '';
        
        $this->expectOutputString('{"success":false,"data":{"message":"Please enter a topic for content generation."}}');
        
        $this->contentGenerator->handle();
    }

    /**
     * Test handle method with short topic
     *
     * @return void
     */
    public function testHandleWithShortTopic(): void
    {
        $_POST['topic'] = 'AI';
        
        $this->expectOutputString('{"success":false,"data":{"message":"Topic must be at least 3 characters long."}}');
        
        $this->contentGenerator->handle();
    }
} 