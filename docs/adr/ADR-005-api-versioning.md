# ADR-005: API Versioning via URL Prefix

**Date:** 2026-07-14
**Status:** Accepted

## Context

FlowForge exposes a public REST API consumed by the web frontend, Flutter mobile app, and third-party integrations. We need a versioning strategy that allows breaking changes without breaking existing clients.

## Decision

Use **URL prefix versioning**: `/api/v1/`, `/api/v2/`, etc.

All routes are registered under a versioned prefix in Laravel's route files:
```
routes/api/v1.php  →  prefix: /api/v1
routes/api/v2.php  →  prefix: /api/v2  (when needed)
```

## Alternatives Considered

| Approach | Pros | Cons |
|---|---|---|
| URL prefix (chosen) | Unambiguous, cache-friendly, easy to route in NGINX, visible in logs | URL "impurity" — version in URL is debated in REST purist circles |
| Accept header versioning | "Correct" REST | Invisible in browser, harder to cache, more complex middleware |
| Query parameter (`?v=1`) | Simple | Easily forgotten, pollutes query strings, poor cache behavior |
| No versioning | Simple to start | Breaking changes break all clients simultaneously |

## Consequences

**Positive:**
- Clients (web, mobile, third-party) can pin to `/api/v1/` and remain unaffected when v2 ships
- NGINX routing is trivial — prefix-match routes to the correct service/controller group
- Easy to deprecate: add a response header `Deprecation: true` on v1 endpoints when v2 is stable
- Swagger/OpenAPI docs can be generated per version

**Negative / Mitigations:**
- Code duplication risk when v2 is similar to v1 — mitigated by sharing service/domain layer; only controllers and request validation differ between versions
