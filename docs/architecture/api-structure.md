# API Structure

FlowForge exposes a versioned REST API under `/api/v1/`. All endpoints except auth and inbound webhooks require a Bearer token (Laravel Sanctum).

## Versioning Strategy

URL prefix versioning: `/api/v1/`, `/api/v2/` — unambiguous, cache-friendly, easy to route in NGINX.
See ADR-005 for the full rationale.

## Endpoints

### Authentication
```
POST   /api/v1/auth/register
POST   /api/v1/auth/login
POST   /api/v1/auth/logout
POST   /api/v1/auth/refresh
POST   /api/v1/auth/forgot-password
POST   /api/v1/auth/reset-password
POST   /api/v1/auth/verify-email
```

### Current User
```
GET    /api/v1/me
PUT    /api/v1/me
POST   /api/v1/me/two-factor
DELETE /api/v1/me/two-factor
```

### Workflows
```
GET    /api/v1/workflows                        ← list (paginated, filterable)
POST   /api/v1/workflows                        ← create
GET    /api/v1/workflows/{id}                   ← show
PUT    /api/v1/workflows/{id}                   ← update
DELETE /api/v1/workflows/{id}                   ← soft delete
POST   /api/v1/workflows/{id}/activate          ← set status = active
POST   /api/v1/workflows/{id}/pause             ← set status = paused
POST   /api/v1/workflows/{id}/execute           ← manual trigger
```

### Workflow Steps
```
GET    /api/v1/workflows/{id}/steps
POST   /api/v1/workflows/{id}/steps
PUT    /api/v1/workflows/{id}/steps/{stepId}
DELETE /api/v1/workflows/{id}/steps/{stepId}
POST   /api/v1/workflows/{id}/steps/reorder     ← bulk reorder
```

### Executions
```
GET    /api/v1/executions                       ← list (filterable by workflow, status, date)
GET    /api/v1/executions/{id}                  ← show with step summary
GET    /api/v1/executions/{id}/logs             ← per-step logs
POST   /api/v1/executions/{id}/retry            ← retry failed execution
DELETE /api/v1/executions/{id}                  ← delete run history
```

### Connectors
```
GET    /api/v1/connectors
POST   /api/v1/connectors
GET    /api/v1/connectors/{id}
PUT    /api/v1/connectors/{id}
DELETE /api/v1/connectors/{id}
POST   /api/v1/connectors/{id}/test             ← verify credentials
```

### Team
```
GET    /api/v1/team/members
POST   /api/v1/team/invitations                 ← send invite by email
DELETE /api/v1/team/invitations/{id}
PUT    /api/v1/team/members/{id}/role
DELETE /api/v1/team/members/{id}               ← remove from tenant
```

### Billing (Stripe)
```
GET    /api/v1/billing/plans
POST   /api/v1/billing/subscribe
POST   /api/v1/billing/cancel
GET    /api/v1/billing/invoices
POST   /api/v1/billing/portal                  ← redirect to Stripe Customer Portal
```

### Audit Logs
```
GET    /api/v1/audit-logs                      ← paginated, filterable
```

### Inbound Webhooks (public — no auth)
```
POST   /webhook/{workflowId}/{secret}          ← triggers a workflow execution
```

## Request / Response Conventions

- All responses return JSON
- Success: `{ "data": {...}, "meta": {...} }` (Laravel API Resources)
- Errors: `{ "message": "...", "errors": { "field": ["..."] } }`
- Pagination: `{ "data": [...], "links": {...}, "meta": { "current_page": 1, "total": 100 } }`
- Dates: ISO 8601 UTC (`2025-01-15T10:30:00Z`)
- IDs: UUID strings

## Rate Limiting

- Auth endpoints: 10 req/min per IP
- API endpoints: 120 req/min per tenant (configurable per plan)
- Webhook endpoints: 60 req/min per workflow

## Authentication

- **Web/SPA**: Laravel Sanctum SPA cookies
- **API/Mobile**: Sanctum personal access tokens (Bearer)
- **OAuth**: Laravel Socialite for connector OAuth flows (Google, Slack, etc.)
