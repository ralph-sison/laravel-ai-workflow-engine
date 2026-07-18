# FlowForge

> AI Workflow Automation Platform for SMEs — build, run, and monitor automated workflows connecting your apps, APIs, and AI models.

[![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=flat&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat&logo=php)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?style=flat&logo=postgresql)](https://postgresql.org)
[![Redis](https://img.shields.io/badge/Redis-7-DC382D?style=flat&logo=redis)](https://redis.io)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=flat&logo=docker)](https://docker.com)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

---

## What is FlowForge?

FlowForge lets you build automated workflows — like Zapier or Make.com — but purpose-built for small and medium businesses. Connect your tools, add AI steps, and let FlowForge handle the rest.

**Example workflow:**

```
New email received
  → AI summarizes the email (Claude / OpenAI)
  → Extract customer info
  → Save to CRM
  → Send Slack notification
  → Create invoice
  → Generate follow-up email
```

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 11, PHP 8.3 |
| Frontend | React / Vue 3 (Inertia.js) |
| Mobile | Flutter |
| Database | PostgreSQL 16 |
| Cache & Queues | Redis 7 + Laravel Horizon |
| Auth | Laravel Sanctum, OAuth2 (Socialite) |
| AI | OpenAI, Anthropic Claude, Google Gemini, Ollama |
| Billing | Stripe (Laravel Cashier) |
| Notifications | Mail, Slack, SMS (Vonage) |
| Testing | Pest, PHPUnit |
| Containers | Docker, Docker Compose |
| CI/CD | GitHub Actions |
| Deployment | Railway / AWS |

---

## Architecture

- [System Overview](docs/architecture/system-overview.md)
- [Database Schema](docs/architecture/database-schema.md)
- [API Structure](docs/architecture/api-structure.md)

### Architecture Decision Records

- [ADR-001: Row-Level Multi-Tenancy](docs/adr/ADR-001-multitenancy.md)
- [ADR-002: Redis for Queues, Cache & Sessions](docs/adr/ADR-002-redis-queues-cache.md)
- [ADR-003: Workflow Steps as Chained Laravel Jobs](docs/adr/ADR-003-workflow-execution-jobs.md)
- [ADR-004: AI Provider Strategy Pattern](docs/adr/ADR-004-ai-provider-strategy.md)
- [ADR-005: API Versioning via URL Prefix](docs/adr/ADR-005-api-versioning.md)
- [ADR-006: Connector Credential Encryption](docs/adr/ADR-006-credential-encryption.md)

---

## Roadmap

See [docs/roadmap.md](docs/roadmap.md) for the full milestone plan.

Current milestone: **v0.1.0** — Auth, multi-tenancy, RBAC, tenant onboarding.

---

## Local Development

> Requires: Docker, Docker Compose, Git

```bash
git clone https://github.com/ralph-sison/laravel-ai-workflow-engine.git
cd laravel-ai-workflow-engine
cp .env.example .env
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

App: http://localhost:8000
Horizon: http://localhost:8000/horizon

---

## Laravel Skills Demonstrated

`Queues` `Jobs` `Events` `Scheduling` `APIs` `OAuth` `Webhooks` `Permissions` `Billing` `Notifications` `Docker` `Redis` `Horizon` `Broadcasting` `Testing` `Multi-tenancy` `RBAC` `Audit Logs` `API Versioning` `WebSockets`

---

## License

MIT
