# ADR-001: Row-Level Multi-Tenancy via tenant_id Scoping

**Date:** 2026-07-14
**Status:** Accepted

## Context

FlowForge serves multiple organizations (tenants) from a single deployment. We need to isolate tenant data reliably without running separate infrastructure per tenant.

## Decision

Use **row-level multi-tenancy**: a `tenant_id` UUID foreign key on every tenant-scoped table, enforced via a global Eloquent scope on a base `TenantAwareModel` that all domain models extend.

## Alternatives Considered

| Approach | Pros | Cons |
|---|---|---|
| Separate database per tenant | Strong isolation | Complex migrations, high ops overhead, costly at small scale |
| Separate schema per tenant (PostgreSQL) | Good isolation, single DB | Complex connection management, harder to query across tenants |
| Row-level (chosen) | Simple ops, single migration path, easy cross-tenant queries for admin | Requires discipline — scope must never be bypassed |

## Consequences

**Positive:**
- Single migration path — `php artisan migrate` applies to all tenants
- Simple deployment — one database connection string
- Works on free-tier databases (Railway, Render)

**Negative / Mitigations:**
- Risk of tenant data leakage if global scope is accidentally bypassed — mitigated by:
  - All domain models extend `TenantAwareModel` (enforces scope)
  - `TenantAwareModel` throws an exception if `tenant_id` is not set on the model
  - Feature tests assert cross-tenant isolation on every domain model
- Harder to give a tenant their own database later — acceptable tradeoff at this scale
