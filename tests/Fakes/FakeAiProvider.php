<?php

namespace Tests\Fakes;

use App\AI\Contracts\AiProviderContract;
use App\AI\DTOs\AiResponse;

class FakeAiProvider implements AiProviderContract
{
    public array $calls = [];

    public function __construct(
        private readonly string $content = 'Hello from OpenAI',
        private readonly string $provider = 'openai',
        private readonly string $model = 'gpt-4o-mini',
    ) {}

    public function complete(array $messages, array $options = []): AiResponse
    {
        $this->calls[] = ['messages' => $messages, 'options' => $options];

        return new AiResponse(
            content:          $this->content,
            model:            $this->model,
            promptTokens:     10,
            completionTokens: 5,
            provider:         $this->provider,
        );
    }
}
