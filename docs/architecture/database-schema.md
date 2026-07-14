# Database Schema

FlowForge uses PostgreSQL with row-level multi-tenancy. Every tenant-scoped table carries a `tenant_id` foreign key enforced via a global Eloquent scope on a base `TenantAwareModel`.

## Entity Relationship Diagram

```
┌──────────────────┐       ┌─────────────────────┐
│   tenants        │       │   users              │
│──────────────────│       │─────────────────────│
│ id (uuid)        │◄──┐   │ id (uuid)           │
│ name             │   │   │ tenant_id (FK)       │
│ slug             │   │   │ name                │
│ plan             │   └───│ email               │
│ stripe_customer  │       │ password (hashed)   │
│ trial_ends_at    │       │ email_verified_at   │
│ settings (json)  │       │ two_factor_secret   │
│ timestamps       │       │ timestamps          │
└──────────────────┘       └──────────┬──────────┘
         │                            │
         │                  ┌─────────▼──────────┐
         │                  │   model_has_roles   │
         │                  │────────────────────│
         │                  │ role_id (FK)        │
         │                  │ model_id            │
         │                  │ model_type          │
         │                  └─────────┬──────────┘
         │                            │
         │                  ┌─────────▼──────────┐
         │                  │   roles             │
         │                  │────────────────────│
         │                  │ id                  │
         │                  │ tenant_id (FK)      │
         │                  │ name                │
         │                  │ guard_name          │
         │                  └────────────────────┘
         │
         ▼
┌──────────────────────┐
│   workflows          │
│──────────────────────│
│ id (uuid)            │
│ tenant_id (FK)       │
│ name                 │
│ description          │
│ status               │  ← draft | active | paused | archived
│ trigger_type         │  ← webhook | schedule | manual | email
│ trigger_config (json)│  ← cron expr, webhook secret, etc.
│ version (int)        │
│ last_run_at          │
│ timestamps           │
└──────────┬───────────┘
           │
           ├──────────────────────────────────┐
           ▼                                  ▼
┌──────────────────────┐          ┌───────────────────────┐
│   workflow_steps     │          │   executions          │
│──────────────────────│          │───────────────────────│
│ id (uuid)            │          │ id (uuid)             │
│ workflow_id (FK)     │          │ workflow_id (FK)      │
│ order (int)          │          │ triggered_by          │  ← manual|webhook|schedule
│ name                 │          │ status                │  ← pending|running|success|failed
│ type                 │          │ payload (json)        │
│ config (json)        │          │ context (json)        │  ← data envelope passed between steps
│ on_error             │          │ started_at            │
│ retry_limit (int)    │          │ finished_at           │
│ timeout_seconds      │          │ duration_ms           │
│ timestamps           │          │ timestamps            │
└──────────────────────┘          └──────────┬────────────┘
                                             │
                                  ┌──────────▼────────────┐
                                  │   execution_logs      │
                                  │───────────────────────│
                                  │ id (uuid)             │
                                  │ execution_id (FK)     │
                                  │ step_id (FK)          │
                                  │ status                │
                                  │ input (json)          │
                                  │ output (json)         │
                                  │ error (text)          │
                                  │ attempt (int)         │
                                  │ duration_ms           │
                                  │ timestamps            │
                                  └───────────────────────┘

┌──────────────────────┐
│   connectors         │
│──────────────────────│
│ id (uuid)            │
│ tenant_id (FK)       │
│ name                 │
│ type                 │  ← slack | gmail | openai | stripe | http
│ credentials (json)   │  ← AES-256 encrypted via Laravel encrypt()
│ meta (json)          │  ← scopes, account info, etc.
│ expires_at           │
│ timestamps           │
└──────────────────────┘

┌──────────────────────┐
│   audit_logs         │
│──────────────────────│
│ id (uuid)            │
│ tenant_id (FK)       │
│ user_id (FK, null)   │  ← null for system-triggered events
│ event                │  ← workflow.created, execution.failed, etc.
│ auditable_type       │
│ auditable_id         │
│ old_values (json)    │
│ new_values (json)    │
│ ip_address           │
│ user_agent           │
│ timestamps           │
└──────────────────────┘

┌──────────────────────┐
│   invitations        │
│──────────────────────│
│ id (uuid)            │
│ tenant_id (FK)       │
│ email                │
│ role                 │
│ token (hashed)       │
│ accepted_at          │
│ expires_at           │
│ timestamps           │
└──────────────────────┘

┌──────────────────────┐
│   subscriptions      │  ← managed by Laravel Cashier
│──────────────────────│
│ id                   │
│ tenant_id (FK)       │
│ type                 │
│ stripe_id            │
│ stripe_status        │
│ stripe_price         │
│ quantity             │
│ trial_ends_at        │
│ ends_at              │
│ timestamps           │
└──────────────────────┘
```

## Key Design Decisions

- **UUIDs** for all primary keys — safe for public URLs, no sequential enumeration attacks
- **JSON columns** for flexible config (step config, trigger config, connector credentials) — avoids EAV anti-pattern while remaining queryable in PostgreSQL
- **Soft deletes** on `workflows`, `workflow_steps`, `connectors` — audit-friendly and recoverable
- **All credentials encrypted** at application level before storage (see ADR-006)
- **`context` JSON on executions** carries the data envelope passed between steps at runtime
- **`order` int on `workflow_steps`** — simple integer ordering, reordering done in a transaction
