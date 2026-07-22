export interface User {
    id: string;
    name: string;
    email: string;
    tenant_id: string;
    roles: string[];
    created_at: string;
}

export interface Tenant {
    id: string;
    name: string;
    slug: string;
    plan: 'free' | 'pro' | 'enterprise';
}

export interface Workflow {
    id: string;
    name: string;
    description: string | null;
    status: 'draft' | 'active' | 'paused' | 'archived';
    trigger_type: 'manual' | 'webhook' | 'schedule';
    version: number;
    last_run_at: string | null;
    created_at: string;
    steps?: WorkflowStep[];
    executions?: Execution[];
}

export interface WorkflowStep {
    id: string;
    name: string;
    type: 'ai' | 'http' | 'transform' | 'notification' | 'condition' | 'delay';
    order: number;
    on_error: 'stop' | 'continue';
    config: Record<string, unknown>;
}

export interface Execution {
    id: string;
    workflow_id: string;
    status: 'running' | 'success' | 'failed' | 'cancelled';
    trigger_type: 'manual' | 'webhook' | 'schedule';
    started_at: string;
    finished_at: string | null;
    logs?: ExecutionLog[];
}

export interface ExecutionLog {
    id: string;
    step_id: string;
    status: 'running' | 'success' | 'failed';
    input: Record<string, unknown>;
    output: Record<string, unknown> | null;
    error: string | null;
    duration_ms: number | null;
    attempt: number;
}

export interface PlanUsage {
    plan: string;
    workflows: { used: number; limit: number | 'unlimited' };
    executions_per_month: { used: number; limit: number | 'unlimited' };
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    auth: {
        user: User;
        tenant: Tenant;
    };
    flash: {
        success?: string;
        error?: string;
    };
};
