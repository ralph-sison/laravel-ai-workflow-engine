# ADR-004: AI Provider Abstraction via Strategy Pattern

**Date:** 2026-07-14
**Status:** Accepted

## Context

FlowForge supports multiple AI providers (OpenAI, Anthropic Claude, Google Gemini, Ollama, DeepSeek). Users can select a provider per AI step. We need to swap providers without touching workflow execution logic.

## Decision

Define an `AiProviderContract` interface with a single `complete(array $messages, array $options): string` method. Each provider is a concrete driver implementing this contract. A `AiProviderFactory` resolves the correct driver based on the step config.

```php
interface AiProviderContract {
    public function complete(array $messages, array $options): AiResponse;
}

// Drivers: OpenAiProvider, ClaudeProvider, GeminiProvider, OllamaProvider
```

Provider credentials come from the tenant's `connectors` table (encrypted). Model, temperature, max tokens, and system prompt are configured per workflow step.

## Alternatives Considered

| Approach | Pros | Cons |
|---|---|---|
| Strategy pattern / interface (chosen) | Clean separation, easy to add providers, testable with fakes | Slightly more boilerplate upfront |
| Direct API calls per step type | Simple to start | Couples workflow engine to provider SDKs — hard to swap or mock in tests |
| LangChain / LlamaIndex (Python) | Rich abstractions | Adds a Python microservice dependency; overkill for this use case |

## Consequences

**Positive:**
- Adding a new AI provider = one new class implementing `AiProviderContract`, registered in the factory
- Workflow execution code never imports a provider SDK directly — only the contract
- Easy to test: swap real providers with a `FakeAiProvider` in feature tests
- Ollama (local, Docker) can be used as the default in development — zero API cost

**Negative / Mitigations:**
- Each provider has different capability sets (function calling, vision, streaming) — `AiResponse` object normalizes the output; provider-specific capabilities are opt-in via `$options`
