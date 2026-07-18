<?php

namespace App\AI\Providers;

use App\AI\Contracts\AiProviderContract;
use App\AI\DTOs\AiResponse;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class ClaudeProvider implements AiProviderContract
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const VERSION = '2023-06-01';

    public function __construct(private readonly string $apiKey) {}

    public function complete(array $messages, array $options = []): AiResponse
    {
        $model  = $options['model'] ?? 'claude-haiku-4-5-20251001';
        $params = [
            'model'      => $model,
            'messages'   => $messages,
            'max_tokens' => $options['max_tokens'] ?? 1024,
        ];

        if (isset($options['system'])) {
            $params['system'] = $options['system'];
        }

        $response = $this->client()->post(self::API_URL, $params);
        $response->throw();

        $body  = $response->json();
        $usage = $body['usage'] ?? [];

        return new AiResponse(
            content:          $body['content'][0]['text'] ?? '',
            model:            $body['model'],
            promptTokens:     $usage['input_tokens'] ?? 0,
            completionTokens: $usage['output_tokens'] ?? 0,
            provider:         'claude',
        );
    }

    private function client(): PendingRequest
    {
        return Http::withHeaders([
            'x-api-key'         => $this->apiKey,
            'anthropic-version' => self::VERSION,
            'content-type'      => 'application/json',
        ])->timeout(60);
    }
}
