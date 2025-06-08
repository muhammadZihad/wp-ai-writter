<?php

declare(strict_types=1);

namespace AiWriter\Ajax;

/**
 * Content Generator AJAX Handler
 *
 * Handles AJAX requests for generating content using OpenAI API.
 *
 * @package AiWriter\Ajax
 * @since 1.0.0
 */
final class ContentGenerator
{
    /**
     * Settings option name
     */
    private const OPTION_NAME = 'ai_writer_settings';

    /**
     * OpenAI API base URL for chat completions
     */
    private const OPENAI_API_URL = 'https://api.openai.com/v1/chat/completions';

    /**
     * Handle the content generation AJAX request
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            error_log('AI Writer: ContentGenerator::handle() started');

            // Verify nonce
            if (!$this->verifyNonce()) {
                error_log('AI Writer: Content generation - Nonce verification failed');
                $this->sendJsonError(['message' => __('Security check failed.', 'ai-writer')]);
                return;
            }

            // Check user capabilities
            if (!current_user_can('edit_posts')) {
                error_log('AI Writer: Content generation - User capability check failed');
                $this->sendJsonError(['message' => __('Insufficient permissions.', 'ai-writer')]);
                return;
            }

            // Get and validate form data
            $formData = $this->validateFormData();
            if (!$formData['valid']) {
                $this->sendJsonError(['message' => $formData['message']]);
                return;
            }

            // Get API settings
            $settings = $this->getApiSettings();
            if (!$settings['valid']) {
                $this->sendJsonError(['message' => $settings['message']]);
                return;
            }

            // Generate content
            error_log('AI Writer: Starting content generation');
            $result = $this->generateContent($formData['data'], $settings['data']);
            error_log('AI Writer: Content generation result: ' . ($result['success'] ? 'success' : 'failed'));

            if ($result['success']) {
                $this->sendJsonSuccess([
                    'content' => $result['content'],
                    'title' => $result['title'] ?? '',
                    'message' => __('Content generated successfully!', 'ai-writer'),
                    'word_count' => str_word_count(strip_tags($result['content'])),
                ]);
            } else {
                $this->sendJsonError(['message' => $result['message']]);
            }
        } catch (\Throwable $e) {
            error_log('AI Writer: ContentGenerator exception - ' . $e->getMessage());
            error_log('AI Writer: Stack trace - ' . $e->getTraceAsString());

            $this->sendJsonError([
                'message' => __('Content generation failed: ', 'ai-writer') . $e->getMessage()
            ]);
        }
    }

    /**
     * Verify the nonce for security
     *
     * @return bool
     */
    private function verifyNonce(): bool
    {
        $nonce = $_POST['nonce'] ?? '';

        if (empty($nonce)) {
            error_log('AI Writer: No nonce provided in content generation request');
            return false;
        }

        if (!function_exists('wp_verify_nonce')) {
            error_log('AI Writer: wp_verify_nonce function not available');
            return false;
        }

        $result = wp_verify_nonce($nonce, 'ai_writer_nonce');
        error_log('AI Writer: Content generation nonce verification result: ' . ($result ? 'true' : 'false'));

        return (bool) $result;
    }

    /**
     * Validate form data
     *
     * @return array<string, mixed>
     */
    private function validateFormData(): array
    {
        $topic = sanitize_text_field($_POST['topic'] ?? '');
        $contentType = sanitize_text_field($_POST['content_type'] ?? 'blog-post');
        $length = sanitize_text_field($_POST['length'] ?? 'medium');
        $tone = sanitize_text_field($_POST['tone'] ?? 'professional');

        if (empty($topic)) {
            return [
                'valid' => false,
                'message' => __('Please enter a topic for content generation.', 'ai-writer')
            ];
        }

        if (strlen($topic) < 3) {
            return [
                'valid' => false,
                'message' => __('Topic must be at least 3 characters long.', 'ai-writer')
            ];
        }

        return [
            'valid' => true,
            'data' => [
                'topic' => $topic,
                'content_type' => $contentType,
                'length' => $length,
                'tone' => $tone,
            ]
        ];
    }

    /**
     * Get API settings
     *
     * @return array<string, mixed>
     */
    private function getApiSettings(): array
    {
        $settings = get_option(self::OPTION_NAME, []);
        $apiKey = $settings['api_key'] ?? '';

        if (empty($apiKey)) {
            return [
                'valid' => false,
                'message' => __('OpenAI API key not configured. Please check your settings.', 'ai-writer')
            ];
        }

        return [
            'valid' => true,
            'data' => [
                'api_key' => $apiKey,
                'model' => $settings['model'] ?? 'gpt-3.5-turbo',
                'max_tokens' => intval($settings['max_tokens'] ?? 1500),
                'temperature' => floatval($settings['temperature'] ?? 0.7),
            ]
        ];
    }

    /**
     * Generate content using OpenAI API
     *
     * @param array<string, string> $formData Form data
     * @param array<string, mixed> $settings API settings
     * @return array<string, mixed>
     */
    private function generateContent(array $formData, array $settings): array
    {
        $prompt = $this->buildPrompt($formData);

        $requestBody = [
            'model' => $settings['model'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->getSystemPrompt()
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => $settings['max_tokens'],
            'temperature' => $settings['temperature'],
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0
        ];

        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $settings['api_key'],
                'Content-Type' => 'application/json',
                'User-Agent' => 'AI-Writer-Plugin/1.0.0',
            ],
            'body' => json_encode($requestBody),
            'timeout' => 60,
            'sslverify' => true,
            'method' => 'POST'
        ];

        $response = wp_remote_post(self::OPENAI_API_URL, $args);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => sprintf(
                    __('Network error: %s', 'ai-writer'),
                    $response->get_error_message()
                )
            ];
        }

        $responseCode = wp_remote_retrieve_response_code($response);
        $responseBody = wp_remote_retrieve_body($response);

        error_log('AI Writer: OpenAI API response code: ' . $responseCode);

        return $this->parseApiResponse($responseCode, $responseBody);
    }

    /**
     * Build prompt for content generation
     *
     * @param array<string, string> $formData Form data
     * @return string
     */
    private function buildPrompt(array $formData): string
    {
        $lengthGuide = $this->getLengthGuide($formData['length']);
        $contentTypeGuide = $this->getContentTypeGuide($formData['content_type']);

        return sprintf(
            "Create a %s about '%s' with the following specifications:\n\n" .
            "Content Type: %s\n" .
            "Length: %s\n" .
            "Tone: %s\n" .
            "Topic: %s\n\n" .
            "%s\n\n" .
            "%s\n\n" .
            "IMPORTANT: Please provide your response in the following JSON format:\n" .
            "{\n" .
            '  "title": "SEO-optimized title for the content (60 characters or less)",' . "\n" .
            '  "content": "Full HTML content with proper formatting"' . "\n" .
            "}\n\n" .
            "CRITICAL: Respond ONLY with the JSON object above. Do not include any markdown formatting, " .
            "code blocks, or additional text. Start your response with { and end with }.\n\n" .
            "Requirements for the title:\n" .
            "- SEO-friendly and attention-grabbing\n" .
            "- Include relevant keywords naturally\n" .
            "- Keep under 60 characters for optimal SEO\n" .
            "- Match the %s tone\n\n" .
            "Requirements for the content:\n" .
            "- Use proper HTML formatting with headings (h2, h3), paragraphs, and lists\n" .
            "- Make it SEO-friendly with natural keyword usage\n" .
            "- Include a compelling introduction and conclusion\n" .
            "- Use subheadings to break up the content\n" .
            "- Write in %s tone\n" .
            "- Ensure the content is original, engaging, and valuable to readers\n" .
            "- Include actionable insights where appropriate\n" .
            "- Never include h1 tags (title will be separate)",
            $formData['content_type'],
            $formData['topic'],
            ucwords(str_replace('-', ' ', $formData['content_type'])),
            $lengthGuide,
            ucfirst($formData['tone']),
            $formData['topic'],
            $contentTypeGuide,
            $lengthGuide,
            $formData['tone'],
            $formData['tone']
        );
    }

    /**
     * Get system prompt for OpenAI
     *
     * @return string
     */
    private function getSystemPrompt(): string
    {
        return "You are an expert content writer and SEO specialist. You create high-quality, " .
               "engaging, and SEO-optimized content. IMPORTANT: You MUST respond with valid JSON " .
               "format exactly as specified in the user prompt. The JSON must contain both 'title' " .
               "and 'content' fields. Format content with proper HTML tags including headings (h2, h3), " .
               "paragraphs (p), lists (ul, ol, li), and emphasis (strong, em) where appropriate. " .
               "Never include h1 tags as that will be the title. Focus on creating valuable, " .
               "actionable content that provides real insights to readers.";
    }

    /**
     * Get length guide for prompt
     *
     * @param string $length Length setting
     * @return string
     */
    private function getLengthGuide(string $length): string
    {
        return match ($length) {
            'short' => 'Approximately 300-500 words (brief but comprehensive)',
            'medium' => 'Approximately 800-1200 words (detailed and thorough)',
            'long' => 'Approximately 1500-2500 words (comprehensive and in-depth)',
            default => 'Approximately 800-1200 words (detailed and thorough)'
        };
    }

    /**
     * Get content type specific guide
     *
     * @param string $contentType Content type
     * @return string
     */
    private function getContentTypeGuide(string $contentType): string
    {
        return match ($contentType) {
            'blog-post' => 'Structure as a blog post with introduction, main sections with subheadings, and conclusion. Include practical tips and examples.',
            'article' => 'Write as an informative article with clear sections, data-driven insights, and authoritative tone.',
            'social-media' => 'Create engaging social media content that is concise, attention-grabbing, and shareable.',
            'email' => 'Format as an email with compelling subject line suggestions, clear call-to-action, and personal tone.',
            'product-description' => 'Focus on benefits, features, and compelling reasons to choose this product. Include technical details where relevant.',
            default => 'Structure with clear sections, practical insights, and engaging narrative flow.'
        };
    }

    /**
     * Parse OpenAI API response
     *
     * @param int $responseCode HTTP response code
     * @param string $responseBody Response body
     * @return array<string, mixed>
     */
    private function parseApiResponse(int $responseCode, string $responseBody): array
    {
        if ($responseCode !== 200) {
            return $this->handleApiError($responseCode, $responseBody);
        }

        $data = json_decode($responseBody, true);

        if (!$data || !isset($data['choices'][0]['message']['content'])) {
            return [
                'success' => false,
                'message' => __('Invalid response format from OpenAI API.', 'ai-writer')
            ];
        }

        $content = trim($data['choices'][0]['message']['content']);

        if (empty($content)) {
            return [
                'success' => false,
                'message' => __('No content was generated. Please try again.', 'ai-writer')
            ];
        }

        // Try to parse JSON response first
        $cleanContent = $content;
        
        // Remove potential markdown code blocks
        $cleanContent = preg_replace('/```json\s*(.*?)\s*```/s', '$1', $cleanContent);
        $cleanContent = preg_replace('/```\s*(.*?)\s*```/s', '$1', $cleanContent);
        
        // Remove any leading/trailing whitespace and newlines
        $cleanContent = trim($cleanContent);
        
        $jsonData = json_decode($cleanContent, true);
        error_log('AI Writer: Raw content from API: ' . substr($content, 0, 500) . '...');
        error_log('AI Writer: Cleaned content for JSON: ' . substr($cleanContent, 0, 500) . '...');
        error_log('AI Writer: JSON decode error: ' . json_last_error_msg());
        
        if (json_last_error() === JSON_ERROR_NONE && isset($jsonData['content'])) {
            // JSON format response with title and content
            $formattedContent = $this->formatContent($jsonData['content']);
            $title = $jsonData['title'] ?? $this->generateFallbackTitle($formattedContent);
            
            error_log('AI Writer: Using JSON response - Title: ' . $title);
            
            return [
                'success' => true,
                'content' => $formattedContent,
                'title' => $title,
                'usage' => $data['usage'] ?? null
            ];
        }
        
        // Fallback: treat as plain content and generate a simple title
        $formattedContent = $this->formatContent($content);
        $title = $this->generateFallbackTitle($formattedContent);
        
        error_log('AI Writer: Using fallback title generation - Title: ' . $title);

        return [
            'success' => true,
            'content' => $formattedContent,
            'title' => $title,
            'usage' => $data['usage'] ?? null
        ];
    }

    /**
     * Handle API errors
     *
     * @param int $responseCode HTTP response code
     * @param string $responseBody Response body
     * @return array<string, mixed>
     */
    private function handleApiError(int $responseCode, string $responseBody): array
    {
        $data = json_decode($responseBody, true);
        $errorMessage = $data['error']['message'] ?? __('Unknown API error', 'ai-writer');

        return match ($responseCode) {
            401 => [
                'success' => false,
                'message' => __('Invalid API key. Please check your OpenAI API key in settings.', 'ai-writer')
            ],
            403 => [
                'success' => false,
                'message' => __('Access forbidden. Your API key may not have the required permissions.', 'ai-writer')
            ],
            429 => [
                'success' => false,
                'message' => __('Rate limit exceeded. Please try again in a few moments.', 'ai-writer')
            ],
            500, 502, 503 => [
                'success' => false,
                'message' => __('OpenAI service is temporarily unavailable. Please try again later.', 'ai-writer')
            ],
            default => [
                'success' => false,
                'message' => sprintf(
                    __('API request failed (%d): %s', 'ai-writer'),
                    $responseCode,
                    sanitize_text_field($errorMessage)
                )
            ]
        };
    }

    /**
     * Format content for display
     *
     * @param string $content Raw content from API
     * @return string
     */
    private function formatContent(string $content): string
    {
        // Ensure proper paragraph formatting
        $content = $this->ensureProperFormatting($content);

        // Add WordPress-friendly formatting
        $content = $this->addWordPressFormatting($content);

        return $content;
    }

    /**
     * Ensure proper HTML formatting
     *
     * @param string $content Content to format
     * @return string
     */
    private function ensureProperFormatting(string $content): string
    {
        // Remove any h1 tags and replace with h2
        $content = preg_replace('/<h1[^>]*>(.*?)<\/h1>/i', '<h2>$1</h2>', $content);

        // Ensure paragraphs are properly wrapped
        if (strpos($content, '<p>') === false) {
            $paragraphs = explode("\n\n", $content);
            $content = '';
            foreach ($paragraphs as $paragraph) {
                $paragraph = trim($paragraph);
                if (!empty($paragraph) && !preg_match('/^<[h2-6]/', $paragraph)) {
                    $content .= '<p>' . $paragraph . '</p>' . "\n\n";
                } else {
                    $content .= $paragraph . "\n\n";
                }
            }
        }

        return trim($content);
    }

    /**
     * Add WordPress-friendly formatting
     *
     * @param string $content Content to format
     * @return string
     */
    private function addWordPressFormatting(string $content): string
    {
        // Add line breaks for readability
        $content = str_replace('></h', ">\n\n</h", $content);
        $content = str_replace('></p', ">\n\n</p", $content);
        $content = str_replace('<h', "\n\n<h", $content);
        $content = str_replace('<p', "\n\n<p", $content);

        // Clean up extra whitespace
        $content = preg_replace('/\n{3,}/', "\n\n", $content);

        return trim($content);
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
            $this->sendJson(['success' => false, 'data' => $data]);
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
            $this->sendJson(['success' => true, 'data' => $data]);
        }
    }

    /**
     * Send JSON response
     *
     * @param array<string, mixed> $data Response data
     * @return void
     */
    private function sendJson(array $data): void
    {
        if (function_exists('wp_send_json')) {
            wp_send_json($data);
        } else {
            header('Content-Type: application/json');
            echo json_encode($data);
            exit;
        }
    }

    /**
     * Generate a fallback title from content
     *
     * @param string $content
     * @return string
     */
    private function generateFallbackTitle(string $content): string
    {
        // Extract first heading if available
        if (preg_match('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/i', $content, $matches)) {
            $title = strip_tags($matches[1]);
            if (strlen($title) <= 60) {
                return $title;
            }
        }
        
        // Extract first sentence and truncate if needed
        $plainText = strip_tags($content);
        $sentences = preg_split('/[.!?]+/', $plainText, 2, PREG_SPLIT_NO_EMPTY);
        
        if (!empty($sentences[0])) {
            $title = trim($sentences[0]);
            if (strlen($title) > 60) {
                $title = substr($title, 0, 57) . '...';
            }
            return $title;
        }
        
        return __('Generated Content', 'ai-writer');
    }
}
