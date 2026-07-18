<?php

namespace App\AI;

use App\AI\Contracts\AiProviderContract;
use App\AI\Providers\ClaudeProvider;
use App\AI\Providers\OllamaProvider;
use App\AI\Providers\OpenAiProvider;
use App\Models\Connector;
use InvalidArgumentException;
use OpenAI;

class AiProviderFactory
{
    /**
     * Resolve the correct AI provider driver from a step's config.
     *
     * Step config shape:
     *   { "connector_id": "<uuid>", "model": "gpt-4o-mini", "system": "...", ... }
     */
    public function make(array $stepConfig): AiProviderContract
    {
        $connectorId = $stepConfig['connector_id'] ?? null;

        if (! $connectorId) {
            throw new InvalidArgumentException('AI step config must include a connector_id.');
        }

        $connector = Connector::findOrFail($connectorId);

        return match ($connector->type) {
            'openai' => $this->makeOpenAi($connector),
            'claude' => $this->makeClaude($connector),
            'ollama' => $this->makeOllama($connector),
            default  => throw new InvalidArgumentException("Unsupported AI connector type: [{$connector->type}]"),
        };
    }

    private function makeOpenAi(Connector $connector): OpenAiProvider
    {
        $credentials = $connector->credentials;
        $apiKey      = $credentials['api_key'] ?? throw new InvalidArgumentException('OpenAI connector missing api_key.');

        // Use OpenAI's factory so tests can swap the underlying HTTP client via OpenAI::fake()
        $client = OpenAI::factory()
            ->withApiKey($apiKey)
            ->withHttpClient(new \GuzzleHttp\Client())
            ->make();

        return new OpenAiProvider($client);
    }

    private function makeClaude(Connector $connector): ClaudeProvider
    {
        $credentials = $connector->credentials;
        $apiKey      = $credentials['api_key'] ?? throw new InvalidArgumentException('Claude connector missing api_key.');

        return new ClaudeProvider($apiKey);
    }

    private function makeOllama(Connector $connector): OllamaProvider
    {
        $meta    = $connector->meta ?? [];
        $baseUrl = $meta['base_url'] ?? 'http://ollama:11434';

        return new OllamaProvider($baseUrl);
    }
}
