<?php

declare(strict_types=1);

namespace AiWriter\Ajax;

/**
 * Post Creator Class
 *
 * Handles creating WordPress posts with AI-generated content,
 * properly formatted for Gutenberg editor.
 *
 * @package AiWriter\Ajax
 * @since 1.0.0
 */
final class PostCreator
{
    /**
     * Handle AJAX request for creating post
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            // Verify nonce for security
            if (!$this->verifyNonce()) {
                $this->sendJsonError([
                    'message' => __('Security verification failed.', 'ai-writer'),
                ]);
                return;
            }

            // Check user permissions
            if (!$this->checkPermissions()) {
                $this->sendJsonError([
                    'message' => __('You do not have permission to create posts.', 'ai-writer'),
                ]);
                return;
            }

            // Validate and sanitize form data
            $validation = $this->validateFormData();
            if (!$validation['valid']) {
                $this->sendJsonError([
                    'message' => $validation['message'],
                ]);
                return;
            }

            // Create the post
            $result = $this->createPost($validation['data']);

            if ($result['success']) {
                $this->sendJsonSuccess([
                    'message' => $result['message'],
                    'post_id' => $result['post_id'],
                    'edit_url' => $result['edit_url'],
                ]);
            } else {
                $this->sendJsonError([
                    'message' => $result['message'],
                ]);
            }
        } catch (\Exception $e) {
            error_log('AI Writer Post Creator Error: ' . $e->getMessage());
            $this->sendJsonError([
                'message' => __('An unexpected error occurred while creating the post.', 'ai-writer'),
            ]);
        }
    }

    /**
     * Verify WordPress nonce for security
     *
     * @return bool
     */
    private function verifyNonce(): bool
    {
        $nonce = $_POST['nonce'] ?? '';
        return (bool) wp_verify_nonce($nonce, 'ai_writer_nonce');
    }

    /**
     * Check if user has permission to create posts
     *
     * @return bool
     */
    private function checkPermissions(): bool
    {
        return current_user_can('edit_posts');
    }

    /**
     * Validate and sanitize form data
     *
     * @return array<string, mixed>
     */
    private function validateFormData(): array
    {
        $title = sanitize_text_field($_POST['title'] ?? '');
        $content = wp_kses_post($_POST['content'] ?? '');
        $status = sanitize_text_field($_POST['status'] ?? 'draft');

        if (empty($title)) {
            return [
                'valid' => false,
                'message' => __('Post title is required.', 'ai-writer')
            ];
        }

        if (empty($content)) {
            return [
                'valid' => false,
                'message' => __('Post content is required.', 'ai-writer')
            ];
        }

        // Validate post status
        $allowedStatuses = ['draft', 'publish', 'private'];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'draft';
        }

        return [
            'valid' => true,
            'data' => [
                'title' => $title,
                'content' => $content,
                'status' => $status,
            ]
        ];
    }

    /**
     * Create WordPress post with Gutenberg formatting
     *
     * @param array<string, string> $data Post data
     * @return array<string, mixed>
     */
    private function createPost(array $data): array
    {
        // Convert HTML content to Gutenberg blocks
        $gutenbergContent = $this->convertToGutenbergBlocks($data['content']);

        $postData = [
            'post_title' => $data['title'],
            'post_content' => $gutenbergContent,
            'post_status' => $data['status'],
            'post_type' => 'post',
            'post_author' => get_current_user_id(),
            'meta_input' => [
                '_ai_writer_generated' => true,
                '_ai_writer_generated_at' => current_time('mysql'),
            ]
        ];

        $postId = wp_insert_post($postData, true);

        if (is_wp_error($postId)) {
            return [
                'success' => false,
                'message' => sprintf(
                    __('Failed to create post: %s', 'ai-writer'),
                    $postId->get_error_message()
                )
            ];
        }

        // Generate edit URL
        $editUrl = admin_url('post.php?post=' . $postId . '&action=edit');

        return [
            'success' => true,
            'message' => sprintf(
                __('Post "%s" created successfully!', 'ai-writer'),
                $data['title']
            ),
            'post_id' => $postId,
            'edit_url' => $editUrl,
        ];
    }

    /**
     * Convert HTML content to Gutenberg blocks
     *
     * @param string $htmlContent HTML content
     * @return string Gutenberg block content
     */
    private function convertToGutenbergBlocks(string $htmlContent): string
    {
        // Clean up the HTML content
        $content = trim($htmlContent);
        
        // Split content into elements
        $elements = $this->parseHtmlElements($content);
        
        $gutenbergBlocks = [];
        
        foreach ($elements as $element) {
            $block = $this->convertElementToBlock($element);
            if (!empty($block)) {
                $gutenbergBlocks[] = $block;
            }
        }
        
        return implode("\n\n", $gutenbergBlocks);
    }

    /**
     * Parse HTML content into individual elements
     *
     * @param string $content HTML content
     * @return array<array<string, string>>
     */
    private function parseHtmlElements(string $content): array
    {
        $elements = [];
        
        // Use DOMDocument to parse HTML properly
        $dom = new \DOMDocument('1.0', 'UTF-8');
        
        // Suppress warnings for malformed HTML
        libxml_use_internal_errors(true);
        
        // Load HTML with UTF-8 encoding
        $dom->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        // Clear libxml errors
        libxml_clear_errors();
        
        $body = $dom->getElementsByTagName('body')->item(0);
        if (!$body) {
            // If no body tag, use the document element
            $body = $dom->documentElement;
        }
        
        if ($body) {
            foreach ($body->childNodes as $node) {
                if ($node->nodeType === XML_ELEMENT_NODE) {
                    $elements[] = [
                        'tag' => strtolower($node->nodeName),
                        'content' => $dom->saveHTML($node),
                        'text' => trim($node->textContent),
                    ];
                }
            }
        }
        
        // Fallback: if DOM parsing fails, use regex
        if (empty($elements)) {
            $elements = $this->parseHtmlWithRegex($content);
        }
        
        return $elements;
    }

    /**
     * Fallback method to parse HTML with regex
     *
     * @param string $content HTML content
     * @return array<array<string, string>>
     */
    private function parseHtmlWithRegex(string $content): array
    {
        $elements = [];
        
        // Match HTML elements
        preg_match_all('/<(h[1-6]|p|ul|ol|blockquote)[^>]*>(.*?)<\/\1>/is', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $elements[] = [
                'tag' => strtolower($match[1]),
                'content' => $match[0],
                'text' => strip_tags($match[2]),
            ];
        }
        
        return $elements;
    }

    /**
     * Convert HTML element to Gutenberg block
     *
     * @param array<string, string> $element Element data
     * @return string Gutenberg block
     */
    private function convertElementToBlock(array $element): string
    {
        $tag = $element['tag'];
        $content = $element['content'];
        $text = $element['text'];
        
        return match ($tag) {
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6' => $this->createHeadingBlock($tag, $text),
            'p' => $this->createParagraphBlock($content),
            'ul', 'ol' => $this->createListBlock($content, $tag),
            'blockquote' => $this->createQuoteBlock($text),
            default => $this->createParagraphBlock($content)
        };
    }

    /**
     * Create heading block
     *
     * @param string $tag Heading tag (h1, h2, etc.)
     * @param string $text Heading text
     * @return string
     */
    private function createHeadingBlock(string $tag, string $text): string
    {
        $level = (int) substr($tag, 1);
        
        return sprintf(
            '<!-- wp:heading {"level":%d} -->' . "\n" .
            '<%s class="wp-block-heading">%s</%s>' . "\n" .
            '<!-- /wp:heading -->',
            $level,
            $tag,
            esc_html($text),
            $tag
        );
    }

    /**
     * Create paragraph block
     *
     * @param string $content Paragraph content (may contain HTML)
     * @return string
     */
    private function createParagraphBlock(string $content): string
    {
        // Clean and preserve basic formatting
        $cleanContent = wp_kses($content, [
            'strong' => [],
            'b' => [],
            'em' => [],
            'i' => [],
            'a' => ['href' => [], 'title' => []],
            'br' => [],
        ]);
        
        // Remove paragraph tags if present (we'll add the block wrapper)
        $cleanContent = preg_replace('/<\/?p[^>]*>/', '', $cleanContent);
        $cleanContent = trim($cleanContent);
        
        if (empty($cleanContent)) {
            return '';
        }
        
        return sprintf(
            '<!-- wp:paragraph -->' . "\n" .
            '<p>%s</p>' . "\n" .
            '<!-- /wp:paragraph -->',
            $cleanContent
        );
    }

    /**
     * Create list block
     *
     * @param string $content List HTML content
     * @param string $type List type (ul or ol)
     * @return string
     */
    private function createListBlock(string $content, string $type): string
    {
        $blockType = $type === 'ol' ? 'list' : 'list';
        $ordered = $type === 'ol' ? 'true' : 'false';
        
        return sprintf(
            '<!-- wp:list {"ordered":%s} -->' . "\n" .
            '%s' . "\n" .
            '<!-- /wp:list -->',
            $ordered,
            $content
        );
    }

    /**
     * Create quote block
     *
     * @param string $text Quote text
     * @return string
     */
    private function createQuoteBlock(string $text): string
    {
        return sprintf(
            '<!-- wp:quote -->' . "\n" .
            '<blockquote class="wp-block-quote"><p>%s</p></blockquote>' . "\n" .
            '<!-- /wp:quote -->',
            esc_html($text)
        );
    }

    /**
     * Send JSON error response
     *
     * @param array<string, mixed> $data Error data
     * @return void
     */
    private function sendJsonError(array $data): void
    {
        if (function_exists('wp_send_json_error')) {
            wp_send_json_error($data);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'data' => $data]);
            exit;
        }
    }

    /**
     * Send JSON success response
     *
     * @param array<string, mixed> $data Success data
     * @return void
     */
    private function sendJsonSuccess(array $data): void
    {
        if (function_exists('wp_send_json_success')) {
            wp_send_json_success($data);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $data]);
            exit;
        }
    }
} 