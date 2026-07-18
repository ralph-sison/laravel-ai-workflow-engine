<?php

namespace App\AI\Providers;

use App\AI\Contracts\AiProviderContract;
use App\AI\DTOs\AiResponse;
use Illuminate\Support\Facades\Http;

class OllamaProvider implements AiProviderContract
{
    public function __construct(private readonly string $baseUrl) {}

    public function complete(array $messages, array $options = []): AiResponse
    {
        $model  = $options['model'] ?? 'llama3.2';
        $params = [
            'model'    => $model,
            'messages' => $messages,
            'stream'   => false,
            'options'  => [
                'temperature' => $options['temperature'] ?? 0.7,
                'num_predict' => $options['max_tokens'] ?? 1024,
            ],
        ];

        if (isset($options['system'])) {
            array_unshift($params['messages'], [
                'role'    => 'system',
                'content' => $options['system'],
            ]);
        }

        $response = Http::timeout(120)->post("{$this->baseUrl}/api/chat", $params);
        $response->throw();

        $body  = $response->json();
        $usage = $body['prompt_eval_count'] ?? 0;

        return new AiResponse(
            content:          $body['message']['content'] ?? '',
            model:            $body['model'],
            promptTokens:     $usage,
            completionTokens: $body['eval_count'] ?? 0,
            provider:         'ollama',
        );
    }
}
