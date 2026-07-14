# ADR-002: Redis for Queues, Cache, and Sessions

**Date:** 2026-07-14
**Status:** Accepted

## Context

FlowForge needs a fast cache layer, a reliable async queue backend, and session storage. We need to decide whether to use one broker or multiple.

## Decision

Use a **single Redis instance** with separate logical databases:
- DB 0 — Cache
- DB 1 — Queues (Laravel Horizon)
- DB 2 — Sessions

## Alternatives Considered

| Approach | Pros | Cons |
|---|---|---|
| Redis for everything (chosen) | Single dependency, Horizon gives full queue visibility | Logical DBs share memory — need to monitor |
| Database (MySQL/PostgreSQL) queues | No extra dependency | Slow, polling-based, not suitable for high throughput |
| Amazon SQS | Managed, scalable, cheap | Adds AWS dependency, harder local dev, no Horizon UI |
| Separate Redis instances per concern | True isolation | Overkill at this scale, more ops |

## Consequences

**Positive:**
- Laravel Horizon provides a real-time dashboard for queue workers — a visible portfolio artifact
- Single service to manage in Docker Compose
- Redis cache gives sub-millisecond response on hot data (workflow configs, user sessions)
- Easy to swap queues to SQS later by changing `QUEUE_CONNECTION` env var — no code changes

**Negative / Mitigations:**
- Shared memory across logical DBs — mitigated by setting `maxmemory-policy allkeys-lru` and monitoring
- Redis is ephemeral by default — mitigated by enabling AOF persistence in production config
