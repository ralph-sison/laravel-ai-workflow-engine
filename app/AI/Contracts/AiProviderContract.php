<?php

namespace App\AI\Contracts;

use App\AI\DTOs\AiResponse;

interface AiProviderContract
{
    /**
     * Send a chat completion request and return a normalised response.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $options  model, temperature, max_tokens, system …
     */
    public function complete(array $messages, array $options = []): AiResponse;
}
