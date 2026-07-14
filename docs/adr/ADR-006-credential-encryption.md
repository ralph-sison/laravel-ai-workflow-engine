# ADR-006: Connector Credentials Encrypted at Application Level

**Date:** 2026-07-14
**Status:** Accepted

## Context

Connector records store OAuth tokens, API keys, and webhook secrets for third-party services (Slack, Gmail, OpenAI, Stripe, etc.). These credentials must never be readable in plain text — not in the database, not in backups, not in logs.

## Decision

All connector credentials are encrypted using **Laravel's built-in `encrypt()` / `decrypt()` helpers** (AES-256-CBC) before being written to the `credentials` JSON column. Decryption happens only in the application layer, never in SQL queries.

The Eloquent model uses a cast:
```php
protected $casts = [
    'credentials' => 'encrypted:array',
];
```

Laravel's `encrypted` cast handles encrypt-on-write and decrypt-on-read transparently.

## Alternatives Considered

| Approach | Pros | Cons |
|---|---|---|
| Laravel encrypted cast (chosen) | Built-in, zero extra dependency, transparent | Tied to `APP_KEY` — key rotation requires re-encryption |
| PostgreSQL column encryption (pgcrypto) | DB-level, independent of app | Requires DB key management, harder to rotate, complex queries |
| External secrets manager (AWS Secrets Manager, Vault) | Best-in-class, auditable | Adds cost and infrastructure dependency — overkill at this scale |
| Plain text (no encryption) | Simple | Unacceptable — credentials in DB backups, logs, or a breach become immediately exploitable |

## Consequences

**Positive:**
- Zero additional infrastructure — uses Laravel's `APP_KEY`
- Transparent to the rest of the application via Eloquent cast
- Credentials never appear in logs, query results, or serialized JSON responses (model hides `credentials` via `$hidden`)

**Negative / Mitigations:**
- `APP_KEY` rotation requires re-encrypting all credential records — mitigated by a `php artisan flowforge:rekey` command that decrypts with the old key and re-encrypts with the new one, run as part of the key rotation runbook
- If `APP_KEY` is lost, all credentials are unrecoverable — mitigated by storing `APP_KEY` in a secrets manager (Railway environment variables, AWS SSM) and never in the repository
