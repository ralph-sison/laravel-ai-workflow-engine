<?php

namespace App\AI\Providers;

use App\AI\Contracts\AiProviderContract;
use App\AI\DTOs\AiResponse;
use OpenAI\Client;

class OpenAiProvider implements AiProviderContract
{
    public function __construct(private readonly Client $client) {}

    public function complete(array $messages, array $options = []): AiResponse
    {
        $model  = $options['model'] ?? 'gpt-4o-mini';
        $params = [
            'model'       => $model,
            'messages'    => $messages,
            'max_tokens'  => $options['max_tokens'] ?? 1024,
            'temperature' => $options['temperature'] ?? 0.7,
        ];

        if (isset($options['system'])) {
            array_unshift($params['messages'], [
                'role'    => 'system',
                'content' => $options['system'],
            ]);
        }

        $response = $this->client->chat()->create($params);
        $choice   = $response->choices[0];
        $usage    = $response->usage;

        return new AiResponse(
            content:          $choice->message->content ?? '',
            model:            $response->model,
            promptTokens:     $usage->promptTokens,
            completionTokens: $usage->completionTokens,
            provider:         'openai',
        );
    }
}
