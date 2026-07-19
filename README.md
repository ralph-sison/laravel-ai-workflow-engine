# FlowForge

> AI Workflow Automation Platform for SMEs — build, run, and monitor automated workflows connecting your apps, APIs, and AI models.

[![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=flat&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat&logo=php)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?style=flat&logo=postgresql)](https://postgresql.org)
[![Redis](https://img.shields.io/badge/Redis-7-DC382D?style=flat&logo=redis)](https://redis.io)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=flat&logo=docker)](https://docker.com)
[![Tests](https://img.shields.io/badge/tests-68%20passing-brightgreen)](#testing)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

---

## What is FlowForge?

FlowForge lets you build automated workflows — like Zapier or Make.com — but purpose-built for small and medium businesses. Connect your tools, add AI steps, and let FlowForge handle the rest.

**Example workflow:**

```
New email received
  → AI summarises the email (Claude / OpenAI / Ollama)
  → Extract customer information
  → Save to CRM via HTTP step
  → Send Slack notification
  → Generate and send follow-up email
```

Workflows can be triggered **manually**, by **inbound webhooks** (with HMAC verification), or on a **cron schedule**. Every execution is logged step-by-step with full input/output/error/duration tracking.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 11, PHP 8.3 |
| Frontend | React / Inertia.js (v0.8.0) |
| Mobile | Flutter (v0.9.0) |
| Database | PostgreSQL 16 |
| Cache & Queues | Redis 7 + Laravel Horizon |
| Auth | Laravel Sanctum (API tokens) |
| AI | OpenAI, Anthropic Claude, Ollama (local Docker) |
| Billing | Stripe — Laravel Cashier, test mode (v0.7.0) |
| Notifications | Mail, Slack webhook, Laravel log |
| Testing | PHPUnit — 68 tests, 183 assertions |
| Containers | Docker, Docker Compose |
| CI/CD | GitHub Actions (v1.0.0) |
| Deployment | Railway / AWS (v1.0.0) |

---

## Architecture

- [System Overview](docs/architecture/system-overview.md)

### Architecture Decision Records

| ADR | Decision |
|---|---|
| [ADR-001](docs/adr/ADR-001-multitenancy.md) | Row-level multi-tenancy via global Eloquent scope |
| [ADR-002](docs/adr/ADR-002-redis-queues-cache.md) | Redis for queues, cache, and sessions |
| [ADR-003](docs/adr/ADR-003-workflow-execution-jobs.md) | Workflow steps as chained Laravel jobs via Bus::batch() |
| [ADR-004](docs/adr/ADR-004-ai-provider-strategy.md) | AI provider abstraction via Strategy pattern |
| [ADR-005](docs/adr/ADR-005-api-versioning.md) | API versioning via URL prefix (/api/v1/) |
| [ADR-006](docs/adr/ADR-006-credential-encryption.md) | Connector credentials encrypted at application level |

---

## Local Development

**Requirements:** Docker, Docker Compose, Git

```bash
git clone https://github.com/ralph-sison/laravel-ai-workflow-engine.git flowforge
cd flowforge
cp .env.example .env
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

| Service | URL |
|---|---|
| API | http://localhost:8880/api/v1 |
| Laravel Horizon | http://localhost:8880/horizon |
| Laravel Telescope | http://localhost:8880/telescope |

> **Note:** Host ports are intentionally non-standard (8880/5440/6381) to avoid conflicts with other local projects. See `.env.example` for the full port mapping.

---

## API Reference

All endpoints are prefixed with `/api/v1/`. Authenticated routes require a Sanctum bearer token.

### Auth
| Method | Endpoint | Description |
|---|---|---|
| POST | `/auth/register` | Register tenant + owner user |
| POST | `/auth/login` | Login, returns API token |
| POST | `/auth/logout` | Revoke current token |
| GET | `/me` | Current authenticated user |

### Workflows
| Method | Endpoint | Description |
|---|---|---|
| GET | `/workflows` | List tenant workflows |
| POST | `/workflows` | Create workflow |
| GET | `/workflows/{id}` | Get workflow |
| PUT | `/workflows/{id}` | Update workflow |
| DELETE | `/workflows/{id}` | Soft delete |
| POST | `/workflows/{id}/activate` | Set status → active |
| POST | `/workflows/{id}/pause` | Set status → paused |
| POST | `/workflows/{id}/execute` | Manual trigger |

### Steps & Executions
| Method | Endpoint | Description |
|---|---|---|
| GET/POST | `/workflows/{id}/steps` | List / create steps |
| PUT/DELETE | `/workflows/{id}/steps/{step}` | Update / delete step |
| POST | `/workflows/{id}/steps/reorder` | Reorder steps |
| GET | `/workflows/{id}/executions` | Execution history |
| GET | `/workflows/{id}/executions/{exec}` | Execution detail + step logs |
| POST | `/workflows/{id}/executions/{exec}/retry` | Retry failed execution |

### Connectors (AI providers & integrations)
| Method | Endpoint | Description |
|---|---|---|
| GET/POST | `/connectors` | List / create connectors |
| GET/PUT/DELETE | `/connectors/{id}` | Get / update / delete |
| POST | `/connectors/{id}/test` | Verify credentials live |

### Webhook Endpoints
| Method | Endpoint | Description |
|---|---|---|
| GET/POST | `/webhook-endpoints` | List / create |
| GET/PUT/DELETE | `/webhook-endpoints/{id}` | Get / update / delete |
| POST | `/webhook-endpoints/{id}/regenerate-secret` | Rotate signing secret |
| POST/GET | `/webhooks/{slug}` | **Public** — inbound payload receiver |

### Scheduled Triggers
| Method | Endpoint | Description |
|---|---|---|
| GET/POST | `/scheduled-triggers` | List / create |
| GET/PUT/DELETE | `/scheduled-triggers/{id}` | Get / update / delete |

---

## Workflow Step Types

| Type | Description |
|---|---|
| `ai` | Call an AI provider (OpenAI, Claude, Ollama) with a prompt and context |
| `notification` | Send email, post to Slack, or write to log |
| `http` | Call an external API (coming soon) |
| `transform` | Map, filter, or reformat data (coming soon) |
| `condition` | Branch based on context values (coming soon) |
| `delay` | Wait N minutes before the next step (coming soon) |

---

## Testing

```bash
docker compose exec app php artisan test
```

```
Tests: 68 passed (183 assertions)
```

Test coverage spans authentication, multi-tenant isolation, workflow CRUD, async job execution, AI provider fakes, HMAC webhook verification, scheduled trigger firing, and notification step delivery.

---

## Roadmap

| Version | Scope | Status |
|---|---|---|
| v0.1.0 | Auth, multi-tenancy, RBAC | ✅ Done |
| v0.2.0 | Workflow engine, CRUD, sync execution | ✅ Done |
| v0.3.0 | Async queues, Horizon, retry | ✅ Done |
| v0.4.0 | AI provider abstraction (OpenAI, Claude, Ollama) | ✅ Done |
| v0.5.0 | Inbound webhooks with HMAC verification | ✅ Done |
| v0.6.0 | Scheduled triggers, notification steps | ✅ Done |
| v0.7.0 | Stripe billing, subscription plans, usage metering | 🔨 Next |
| v0.8.0 | React frontend — workflow builder UI | 📋 Planned |
| v0.9.0 | Flutter mobile app | 📋 Planned |
| v1.0.0 | CI/CD, Railway/AWS deployment, OpenAPI docs | 📋 Planned |

---

## Laravel Skills Demonstrated

`Queues` `Jobs` `Bus::batch()` `Events` `Scheduling` `Artisan Commands` `API Resources` `Sanctum` `Policies` `Webhooks` `HMAC Verification` `Horizon` `Redis` `Multi-tenancy` `Eloquent Global Scopes` `Encrypted Casts` `Strategy Pattern` `Service Container` `Mail` `Notifications` `Docker` `Testing`

---

## License

MIT
