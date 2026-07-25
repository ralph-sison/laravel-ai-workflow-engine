# Changelog

All notable changes to FlowForge are documented here.

Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/). Versioning follows [Semantic Versioning](https://semver.org/).

---

## [Unreleased]

### Planned
- Production deployment, CI/CD, monitoring, OpenAPI docs (v1.0.0) — [#11](https://github.com/ralph-sison/laravel-ai-workflow-engine/issues/11)

---

## [0.9.0] — 2026-07-24

### Added
- **Flutter 3.44 mobile app** in `mobile/` — lives inside the monorepo alongside the Laravel backend and React frontend, visible on the same GitHub repo
- **Packages**: `flutter_riverpod` (state), `dio` (HTTP), `flutter_secure_storage` (token persistence), `go_router` (navigation), `intl`, Material 3 theme
- **Auth flow**: Sanctum token login → stored in `flutter_secure_storage` → auto-restored on next launch via `authProvider` (AsyncNotifier) validating against `/api/v1/me`; unauthenticated users redirected to login via go_router redirect hook
- **`ApiClient` (Dio)**: typed methods for login, logout, me, workflows list, executions list, execution detail, manual trigger
- **Riverpod providers**: `authProvider`, `workflowsProvider`, `executionsProvider` (family), `executionDetailProvider` (family), `triggerWorkflowProvider` (family)
- **Screens**:
  - `LoginScreen` — email/password form, loading state, error display
  - `DashboardScreen` — stat cards (total/active workflows), recent workflow list, pull-to-refresh
  - `WorkflowsScreen` — full list with `WorkflowCard`, pull-to-refresh
  - `WorkflowDetailScreen` — status/trigger badges, execution history list, **Run Now** button (triggers via API, refreshes list)
  - `ExecutionDetailScreen` — per-step logs with status badge, duration, error display
- **Widgets**: `StatusBadge` (auto colour-maps status strings), `WorkflowCard` (tap → detail)
- **`go_router`**: declarative auth-gated routing — unauthenticated users redirected to `/login`

### Architecture decision
Consumes existing `/api/v1/` endpoints — zero new backend code required. Read-only monitoring + manual trigger only; the full workflow builder is the web UI's responsibility.

### Tests
- `flutter analyze` — 0 issues
- `flutter test` — 1 test passing (app boots, router redirects unauthenticated user to login)

---

## [0.8.0] — 2026-07-22

### Added
- **Inertia.js v3** (`inertiajs/inertia-laravel`) + **React 18** + **TypeScript** frontend stack
- **`vite.config.ts`** — migrated from JS, `@vitejs/plugin-react` v4 for Vite 6 compatibility
- **`tsconfig.json`** — strict mode, `@/*` path alias mapped to `resources/js/`
- **`HandleInertiaRequests` middleware** — shares `auth.user`, `auth.tenant`, and `flash` to every page automatically; registered in web middleware group
- **`GuestLayout`** — centered card layout for auth pages
- **`AppLayout`** — fixed sidebar with nav links, user avatar, tenant name, sign-out; active link highlighting
- **`Badge` component** — auto-maps status strings (`active`, `failed`, `running`, etc.) to colour variants
- **`Button` component** — primary / secondary / danger variants
- **Pages**:
  - `Auth/Login` — email/password, Inertia validation errors, redirect on success
  - `Auth/Register` — organisation name + user details; calls `RegisterTenantAction` atomically
  - `Dashboard` — stat cards (total/active workflows, executions today, failures today), plan usage bars with over-limit warning, recent execution list with status badges
  - `Workflows/Index` — list with status/trigger badges, run button for active workflows, empty state
  - `Workflows/Create` — name, description, trigger type radio selector
  - `Workflows/Show` — step list with order badges, execution history table with retry button, activate/pause/run action buttons
- **Web controllers** (separate from API — API routes untouched):
  - `Web/AuthController` — session-based login/register/logout
  - `Web/DashboardController` — aggregates stats + usage for dashboard props
  - `Web/WorkflowController` — CRUD + activate/pause/execute via Inertia redirects
- **TypeScript types** (`resources/js/types/index.d.ts`) — `User`, `Tenant`, `Workflow`, `WorkflowStep`, `Execution`, `ExecutionLog`, `PlanUsage`, `PageProps<T>`
- **`TestCase::withoutCsrf()`** helper — disables `ValidateCsrfToken` for web POST tests without removing session/auth middleware

### Build output
- 13 chunks, 323 kB JS (102 kB gzipped), 25 kB CSS

### Tests
- 14 new tests — Inertia component assertions, web auth flow, workflow CRUD + execute via web
- **Total: 93 tests, 289 assertions**

---

## [0.7.0] — 2026-07-20

### Added
- **Laravel Cashier v16.6** — Stripe billing integrated; billing scoped to `Tenant` (not `User`) via `Cashier::useCustomerModel(Tenant::class)`
- **`Plans` class** — defines limits per plan tier:
  - `free`: 3 workflows, 100 executions/month, 20 AI steps/month
  - `pro`: 25 workflows, 5,000 executions/month, 1,000 AI steps/month
  - `enterprise`: unlimited across all dimensions
- **`UsageService`** — counts executions and workflows for the current billing period; `canExecute()` and `canCreateWorkflow()` enforce plan limits against live DB counts
- **`EnforcePlanLimits` middleware** — gates `POST /workflows/{id}/execute`; returns `402` with plan context when the monthly execution limit is reached
- **`SubscriptionController`**:
  - `GET /billing` — current plan, subscription status, live usage summary, all plan limit tiers
  - `POST /billing/checkout` — creates a Stripe Checkout session for upgrade
  - `POST /billing/portal` — creates a Stripe Billing Portal session for self-service management
  - `POST /billing/cancel` — cancels subscription at period end
- **`StripeWebhookController`** — extends Cashier's built-in webhook handler; syncs `tenant.plan` on `customer.subscription.created`, `updated`, and `deleted` Stripe events
- **Cashier migrations** adapted: `subscriptions.user_id` → `tenant_id` (UUID FK) since billing is per-organisation
- `STRIPE_PRICE_PRO` and `STRIPE_PRICE_ENTERPRISE` added to `.env.example`

### Design decision
Billing is per-**tenant** (organisation), not per-user. A team shares one subscription. Matches standard B2B SaaS billing and defers per-seat complexity to a future milestone.

### Tests
- 11 new tests — plan limit enforcement, usage counting (current month only), enterprise unlimited bypass, billing index shape
- **Total: 79 tests, 210 assertions**

---

## [0.6.0] — 2026-07-18

### Added
- **Scheduled triggers** — workflows can be triggered on a cron schedule (e.g. `0 9 * * 1-5` for weekdays at 9am), with per-trigger timezone support
- **`flowforge:trigger-scheduled` artisan command** — queries all active due triggers every minute via Laravel Scheduler, fires each as a new execution, recalculates `next_run_at` after each run. Runs with `withoutOverlapping()` to prevent pile-up.
- **`ScheduledTriggerController`** — authenticated CRUD for managing scheduled triggers; `next_run_at` auto-calculated on create and recalculated on cron/timezone changes
- **Notification step type** — workflow steps of type `notification` now execute via `SendNotificationAction`:
  - `email` — sends `WorkflowNotificationMail` via Laravel Mail
  - `slack` — HTTP POST to a configured Slack webhook URL
  - `log` — writes to Laravel logger (dev/staging fallback, no external call)
  - Supports `{{variable}}` interpolation from execution context
- **`WorkflowNotificationMail`** — proper Mailable class (queueable, testable via `Mail::fake()`)

### Tests
- 15 new tests — scheduler command, scheduled trigger CRUD, notification step (email/slack/log), context interpolation
- **Total: 68 tests, 183 assertions**

---

## [0.5.0] — 2026-07-18

### Added
- **Inbound webhook triggers** — workflows can be triggered by external HTTP requests via a unique public URL (`POST /api/v1/webhooks/{slug}`)
- **HMAC-SHA256 signature verification** — all inbound webhook requests verified via `X-FlowForge-Signature: sha256=<hex>` header using `hash_equals()` for constant-time comparison
- **`WebhookEndpoint` model** — tenant-scoped, unique slug, secret hidden after creation, tracks `trigger_count` and `last_triggered_at`, soft deletes
- **`WebhookEndpointController`** — authenticated CRUD + `POST regenerate-secret` endpoint (new secret returned once, never stored in plain text after that)
- **`WebhookSignatureVerifier` service** — standalone, injectable, fully testable
- **`WebhookEndpointPolicy`** — owner/admin manage; member can list/view

### Security
- Signature secret exposed only on endpoint creation and explicit regeneration
- `TenantScope` + policy double-check prevents cross-tenant access
- 401 on bad/missing signature, 422 on inactive endpoint or non-active workflow

### Tests
- 15 new tests — signature verification, payload stored in execution context, trigger count increment, RBAC, cross-tenant isolation
- **Total: 53 tests, 141 assertions**

---

## [0.4.0] — 2026-07-18

### Added
- **AI provider abstraction layer** — Strategy pattern: `AiProviderContract` interface + `AiResponse` DTO normalise output across all providers
- **Provider drivers**: `OpenAiProvider` (openai-php/client), `ClaudeProvider` (Laravel Http), `OllamaProvider` (Laravel Http — zero cost in local Docker)
- **`AiProviderFactory`** — resolves the correct driver from a step's `connector_id` config; adding a new provider = one new class only
- **`Connector` model** — encrypted credentials at rest (`encrypted:array` cast per ADR-006), tenant-scoped, hidden from API responses, soft deletes
- **`ConnectorController`** — full CRUD + `POST /connectors/{id}/test` to verify live credentials before wiring into a workflow
- **`ConnectorPolicy`** — owner/admin manage; member can list and test
- **`FakeAiProvider`** test double — bound via `$this->app->bind()` in tests; avoids fighting Guzzle internals in `openai-php/client`
- `ExecuteWorkflowStepJob` now dispatches real AI calls for `type=ai` steps via `app(AiProviderFactory::class)`

### Tests
- 11 new tests — all three providers (Claude, OpenAI, Ollama), connector CRUD, credential encryption, cross-tenant isolation
- **Total: 38 tests, 100 assertions**

---

## [0.3.0] — 2026-07-18

### Added
- **Laravel Horizon** — queue monitoring dashboard with two named supervisors:
  - `supervisor-workflows` — 3 tries, 120s timeout, processes `workflows` queue
  - `supervisor-ai` — 2 tries, 300s timeout, processes `ai` queue (longer for LLM calls)
- **`ProcessWorkflowExecutionJob`** — orchestrates async execution by dispatching a `Bus::batch()` of per-step jobs
- **`ExecuteWorkflowStepJob`** — runs a single step, writes `ExecutionLog`, handles `on_error` strategy:
  - `on_error=stop` — cancels the batch, marks execution failed
  - `on_error=continue` — logs failure, proceeds to next step
- **Retry endpoint** — `POST /api/v1/workflows/{workflow}/executions/{execution}/retry` (failed executions only, creates a new execution)
- `QUEUE_CONNECTION=sync` in tests → runs inline; Redis in production → async via Horizon

### Tests
- 27 tests, 73 assertions

---

## [0.2.0] — 2026-07-18

### Added
- **Workflow model** — UUID PK, tenant-scoped, status lifecycle (`draft → active → paused → archived`), trigger types, soft deletes
- **WorkflowStep model** — ordered steps, types: `ai / http / transform / notification / condition / delay`
- **Execution model** — UUID, status lifecycle, `payload` + `context` JSON envelopes, timing
- **ExecutionLog model** — per-step `input / output / error / duration_ms / attempt` tracking
- **`WorkflowPolicy`** — RBAC: owner/admin/member CRUD; execute requires `active` status
- **`ExecuteWorkflowAction`** — sync step runner (runs inline when `QUEUE_CONNECTION=sync`)
- **`WorkflowController`** — CRUD + `activate` / `pause` / `execute` actions
- **`WorkflowStepController`** — CRUD + `reorder`
- **`ExecutionController`** — list + show with nested logs

### Fixed
- `AuthorizesRequests` trait added to base `Controller` (removed in Laravel 11 by default)
- `microtime()` used for duration tracking — `Carbon::diffInMilliseconds()` fails inside `RefreshDatabase` transactions with PostgreSQL

### Tests
- 24 tests, 69 assertions

---

## [0.1.0] — 2026-07-18

### Added
- **Laravel 11** application scaffold (PHP 8.3)
- **Docker Compose** — `app` (PHP-FPM Alpine), `nginx`, `postgres` (16), `redis` (7), `horizon`, `scheduler`
- **Multi-tenancy** — `Tenant` model (UUID, slug, soft deletes, settings JSON); `TenantAwareModel` + `TenantScope` global Eloquent scope; tenant leakage requires explicit `withoutGlobalScopes()` bypass
- **Authentication** — Laravel Sanctum (API tokens, UUID morphs); `POST /api/v1/auth/register|login|logout`, `GET /api/v1/me`
- **RBAC** — Spatie Laravel Permission in teams mode (`tenant_id` as `team_foreign_key`); roles: `owner`, `admin`, `member`, `viewer`
- **`RegisterTenantAction`** — atomically creates tenant + owner user in a single DB transaction
- **`Invitation` model** — team invites with expiry, token, and role
- **Laravel Telescope** — local observability only
- **HOST_PORT separation** — `APP_HOST_PORT`, `DB_HOST_PORT`, `REDIS_HOST_PORT` allow running alongside other local projects without port conflicts

### Architecture decisions
- UUIDs on all PKs — no sequential enumeration of resources
- Spatie teams mode scopes roles per tenant
- Host ports separated from container-internal ports in `.env`

### Tests
- 13 tests, 43 assertions
