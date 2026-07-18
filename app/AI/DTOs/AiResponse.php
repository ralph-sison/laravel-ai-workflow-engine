<?php

namespace App\AI\DTOs;

final class AiResponse
{
    public function __construct(
        public readonly string $content,
        public readonly string $model,
        public readonly int    $promptTokens,
        public readonly int    $completionTokens,
        public readonly string $provider,
    ) {}

    public function totalTokens(): int
    {
        return $this->promptTokens + $this->completionTokens;
    }

    public function toArray(): array
    {
        return [
            'content'           => $this->content,
            'model'             => $this->model,
            'prompt_tokens'     => $this->promptTokens,
            'completion_tokens' => $this->completionTokens,
            'total_tokens'      => $this->totalTokens(),
            'provider'          => $this->provider,
        ];
    }
}
