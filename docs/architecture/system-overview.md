# System Overview

FlowForge is an AI Workflow Automation Platform that allows SMEs to build, run, and monitor automated workflows — connecting apps, APIs, and AI models without writing code.

## High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        CLIENTS                                  │
│   Web App (React/Vue)    Mobile App (Flutter)    3rd-Party      │
│         │                       │                Webhooks       │
└─────────┼───────────────────────┼────────────────────┼──────────┘
          │                       │                    │
          ▼                       ▼                    ▼
┌─────────────────────────────────────────────────────────────────┐
│                     API GATEWAY / NGINX                         │
│           Rate limiting · TLS · Request routing                 │
└────────────────────────────┬────────────────────────────────────┘
                             │
          ┌──────────────────┼──────────────────┐
          ▼                  ▼                  ▼
┌──────────────────┐ ┌──────────────┐ ┌────────────────────┐
│  Laravel API     │ │  Laravel     │ │  Webhook Receiver  │
│  (REST v1)       │ │  Horizon     │ │  (inbound events)  │
│  Sanctum / OAuth │ │  (workers)   │ │                    │
└────────┬─────────┘ └──────┬───────┘ └──────────┬─────────┘
         │                  │                     │
         ▼                  ▼                     ▼
┌─────────────────────────────────────────────────────────────────┐
│                        CORE SERVICES                            │
│                                                                 │
│  ┌─────────────┐  ┌──────────────┐  ┌─────────────────────┐    │
│  │  Workflow   │  │   Trigger    │  │   Action Executor   │    │
│  │  Engine     │  │   Manager    │  │   (steps runner)    │    │
│  └─────────────┘  └──────────────┘  └─────────────────────┘    │
│                                                                 │
│  ┌─────────────┐  ┌──────────────┐  ┌─────────────────────┐    │
│  │  AI Router  │  │  Connector   │  │   Notification      │    │
│  │  (multi-LLM)│  │  Registry    │  │   Service           │    │
│  └─────────────┘  └──────────────┘  └─────────────────────┘    │
└────────────────────────────┬────────────────────────────────────┘
                             │
          ┌──────────────────┼──────────────────┐
          ▼                  ▼                  ▼
┌──────────────┐   ┌──────────────────┐  ┌──────────────┐
│  PostgreSQL  │   │  Redis           │  │  S3 / R2     │
│  (primary DB)│   │  Cache · Queues  │  │  File Store  │
│              │   │  Sessions · Pub  │  │              │
└──────────────┘   └──────────────────┘  └──────────────┘
```

## Core Domain Concepts

```
TENANT (Organization)
  └── has many USERS (with roles via RBAC)
  └── has many WORKFLOWS
        └── has one TRIGGER (webhook / schedule / email / manual)
        └── has many STEPS (ordered actions)
              └── each STEP has a TYPE:
                    - AI Action     (OpenAI, Claude, Gemini, Ollama)
                    - HTTP Request  (call external API)
                    - Transform     (map/filter/format data)
                    - Notification  (email, Slack, SMS)
                    - Database      (save to CRM, create record)
                    - Condition     (if/else branching)
                    - Delay         (wait N minutes)
        └── has many EXECUTIONS (run history + per-step logs)
```

## Infrastructure

| Component | Local Dev | Production (free tier) |
|---|---|---|
| Web server | NGINX (Docker) | Railway / Render / Fly.io |
| App server | PHP-FPM (Docker) | Same container |
| Database | PostgreSQL (Docker) | Railway PostgreSQL |
| Cache / Queue | Redis (Docker) | Railway Redis |
| File storage | Local disk | Cloudflare R2 (free 10GB) |
| Queue monitor | Laravel Horizon | Same |
| CI/CD | — | GitHub Actions (free) |

## Technology Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 11, PHP 8.3 |
| Frontend | React (Vite) or Vue 3 (Inertia.js) |
| Mobile | Flutter |
| Queue | Laravel Horizon + Redis |
| Auth | Laravel Sanctum (API tokens), OAuth2 (Socialite) |
| AI | OpenAI, Anthropic Claude, Google Gemini, Ollama |
| Billing | Stripe (Cashier) — test mode |
| Notifications | Mail, Slack, Vonage SMS |
| Testing | PHPUnit, Pest |
| Containers | Docker, Docker Compose |
| CI/CD | GitHub Actions |
