<?php

declare(strict_types=1);

namespace AiWriter\Ajax;

/**
 * Connection Test AJAX Handler
 *
 * Handles AJAX requests for testing OpenAI API connectivity.
 *
 * @package AiWriter\Ajax
 * @since 1.0.0
 */
final class ConnectionTest
{
    /**
     * Settings option name
     */
    private const OPTION_NAME = 'ai_writer_settings';

    /**
     * OpenAI API base URL
     */
    private const OPENAI_API_URL = 'https://api.openai.com/v1/models';

    /**
     * Handle the connection test AJAX request
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            error_log('AI Writer: ConnectionTest::handle() started');
            
            // Verify nonce
            if (!$this->verifyNonce()) {
                error_log('AI Writer: Nonce verification failed');
                $this->sendJsonError(['message' => __('Security check failed.', 'ai-writer')]);
                return;
            }

            // Check user capabilities
            if (!current_user_can('manage_options')) {
                error_log('AI Writer: User capability check failed');
                $this->sendJsonError(['message' => __('Insufficient permissions.', 'ai-writer')]);
                return;
            }

            // Get API key from request or settings
            $apiKey = $this->getApiKey();
            error_log('AI Writer: Retrieved API key length: ' . strlen($apiKey));

            if (empty($apiKey)) {
                error_log('AI Writer: No API key found');
                $this->sendJsonError(['message' => __('No API key found. Please enter your OpenAI API key first.', 'ai-writer')]);
                return;
            }

            // Test the connection
            error_log('AI Writer: Starting API connection test');
            $result = $this->testApiConnection($apiKey);
            error_log('AI Writer: API test result: ' . json_encode($result));

            if ($result['success']) {
                $this->sendJsonSuccess([
                    'message' => $result['message'],
                    'model_info' => $result['data'] ?? null,
                ]);
            } else {
                $this->sendJsonError(['message' => $result['message']]);
            }
        } catch (\Throwable $e) {
            error_log('AI Writer: ConnectionTest exception - ' . $e->getMessage());
            error_log('AI Writer: Stack trace - ' . $e->getTraceAsString());
            
            $this->sendJsonError([
                'message' => 'Connection test failed: ' . $e->getMessage()
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
            error_log('AI Writer: No nonce provided in request');
            return false;
        }
        
        if (!function_exists('wp_verify_nonce')) {
            error_log('AI Writer: wp_verify_nonce function not available');
            return false;
        }
        
        $result = wp_verify_nonce($nonce, 'ai_writer_nonce');
        error_log('AI Writer: Nonce verification result: ' . ($result ? 'true' : 'false'));
        
        return (bool) $result;
    }

    /**
     * Get API key from request or settings
     *
     * @return string
     */
    private function getApiKey(): string
    {
        // First, try to get from the current request (if testing from form)
        $apiKey = sanitize_text_field($_POST['api_key'] ?? '');

        if (!empty($apiKey)) {
            return $apiKey;
        }

        // Fall back to saved settings
        $settings = get_option(self::OPTION_NAME, []);
        return $settings['api_key'] ?? '';
    }

    /**
     * Test the OpenAI API connection
     *
     * @param string $apiKey The API key to test
     * @return array<string, mixed> Test result
     */
    private function testApiConnection(string $apiKey): array
    {
        // Prepare the request
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
                'User-Agent' => 'AI-Writer-Plugin/1.0.0',
            ],
            'timeout' => 30,
            'sslverify' => true,
        ];

        // Make the request to OpenAI API
        $response = wp_remote_get(self::OPENAI_API_URL, $args);

        // Check for WordPress HTTP errors
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => sprintf(
                    __('Network error: %s', 'ai-writer'),
                    $response->get_error_message()
                ),
            ];
        }

        // Get response code and body
        $responseCode = wp_remote_retrieve_response_code($response);
        $responseBody = wp_remote_retrieve_body($response);

        // Parse response
        $data = json_decode($responseBody, true);

        // Handle different response codes
        switch ($responseCode) {
            case 200:
                return $this->handleSuccessResponse($data);

            case 401:
                return [
                    'success' => false,
                    'message' => __('Invalid API key. Please check your OpenAI API key.', 'ai-writer'),
                ];

            case 403:
                return [
                    'success' => false,
                    'message' => __('Access forbidden. Your API key may not have the required permissions.', 'ai-writer'),
                ];

            case 429:
                return [
                    'success' => false,
                    'message' => __('Rate limit exceeded. Please try again later.', 'ai-writer'),
                ];

            case 500:
            case 502:
            case 503:
                return [
                    'success' => false,
                    'message' => __('OpenAI service is temporarily unavailable. Please try again later.', 'ai-writer'),
                ];

            default:
                $errorMessage = $this->extractErrorMessage($data);
                return [
                    'success' => false,
                    'message' => sprintf(
                        __('API request failed with status %d: %s', 'ai-writer'),
                        $responseCode,
                        $errorMessage
                    ),
                ];
        }
    }

    /**
     * Handle successful API response
     *
     * @param array<string, mixed>|null $data Response data
     * @return array<string, mixed>
     */
    private function handleSuccessResponse(?array $data): array
    {
        if (!$data || !isset($data['data']) || !is_array($data['data'])) {
            return [
                'success' => false,
                'message' => __('Invalid response format from OpenAI API.', 'ai-writer'),
            ];
        }

        $models = $data['data'];
        $availableModels = $this->filterAvailableModels($models);

        $message = sprintf(
            __('Connection successful! Found %d available models.', 'ai-writer'),
            count($availableModels)
        );

        return [
            'success' => true,
            'message' => $message,
            'data' => [
                'total_models' => count($models),
                'available_models' => $availableModels,
            ],
        ];
    }

    /**
     * Filter models to show only relevant ones
     *
     * @param array<mixed> $models All models from API
     * @return array<string>
     */
    private function filterAvailableModels(array $models): array
    {
        $relevantModels = ['gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo', 'gpt-4o'];
        $availableModels = [];

        foreach ($models as $model) {
            if (isset($model['id']) && is_string($model['id'])) {
                foreach ($relevantModels as $relevantModel) {
                    if (str_contains($model['id'], $relevantModel)) {
                        $availableModels[] = $model['id'];
                        break;
                    }
                }
            }
        }

        return array_unique($availableModels);
    }

    /**
     * Extract error message from API response
     *
     * @param array<string, mixed>|null $data Response data
     * @return string
     */
    private function extractErrorMessage(?array $data): string
    {
        if (!$data) {
            return __('Unknown error occurred.', 'ai-writer');
        }

        // Check for standard OpenAI error format
        if (isset($data['error']['message'])) {
            return sanitize_text_field($data['error']['message']);
        }

        // Check for alternative error formats
        if (isset($data['message'])) {
            return sanitize_text_field($data['message']);
        }

        if (isset($data['detail'])) {
            return sanitize_text_field($data['detail']);
        }

        return __('Unknown error occurred.', 'ai-writer');
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
}
