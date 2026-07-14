# ADR-003: Workflow Steps Executed as Chained Laravel Jobs

**Date:** 2026-07-14
**Status:** Accepted

## Context

When a workflow is triggered, its steps must execute in order, with support for retries, timeouts, error handling, and per-step logging. We need an execution model that is observable, resumable, and fits the Laravel ecosystem.

## Decision

Each workflow execution dispatches an `ExecuteWorkflowStep` job per step. Steps are chained using Laravel's `Bus::chain()`. On success, the chain proceeds to the next step. On failure, the `on_error` strategy for that step is applied (`stop`, `continue`, or `retry`).

```
Trigger → ExecuteWorkflowStep(step 1) → ExecuteWorkflowStep(step 2) → ... → MarkExecutionComplete
```

Each job writes an `ExecutionLog` record (input, output, status, duration) before completing.

## Alternatives Considered

| Approach | Pros | Cons |
|---|---|---|
| Chained Laravel Jobs (chosen) | Native Laravel, Horizon visibility, retry/backoff per step, no external dependency | Long chains have limited branching — conditional logic requires job fan-out |
| Temporal / Conductor | True durable workflow orchestration, resumable mid-chain | Heavy dependency, overkill at this scale, no free managed tier |
| Synchronous execution (in-process) | Simple | Blocks HTTP request, no retry, no visibility, not scalable |
| Custom state machine | Full control | Reinventing the wheel — Laravel Jobs already handle this well |

## Consequences

**Positive:**
- Native Laravel Queues + Horizon — zero additional infrastructure
- Per-step retry, timeout, and backoff configured via job properties
- Full execution history visible in Horizon dashboard
- Easy to test — jobs are plain PHP classes

**Negative / Mitigations:**
- Conditional branching (if/else steps) requires dispatching the correct next job rather than using a linear chain — handled by a `WorkflowStepDispatcher` service that reads the step config and resolves the next step at runtime
- Long-running AI steps may occupy a queue worker for extended time — mitigated by a dedicated `ai` queue with separate worker pool
