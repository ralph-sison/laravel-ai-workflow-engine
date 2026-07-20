<?php

namespace App\Billing;

final class Plans
{
    // Stripe Price IDs — set these in .env
    const FREE       = 'free';
    const PRO        = 'pro';
    const ENTERPRISE = 'enterprise';

    /**
     * Plan limits — what each plan allows per billing period.
     */
    public static function limits(string $plan): array
    {
        return match ($plan) {
            self::PRO        => [
                'workflows'            => 25,
                'executions_per_month' => 5000,
                'ai_steps_per_month'   => 1000,
            ],
            self::ENTERPRISE => [
                'workflows'            => -1,   // unlimited
                'executions_per_month' => -1,
                'ai_steps_per_month'   => -1,
            ],
            default => [ // free
                'workflows'            => 3,
                'executions_per_month' => 100,
                'ai_steps_per_month'   => 20,
            ],
        };
    }

    public static function stripePriceId(string $plan): ?string
    {
        return match ($plan) {
            self::PRO        => env('STRIPE_PRICE_PRO'),
            self::ENTERPRISE => env('STRIPE_PRICE_ENTERPRISE'),
            default          => null,
        };
    }
}
