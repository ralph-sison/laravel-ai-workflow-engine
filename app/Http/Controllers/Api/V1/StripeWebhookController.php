<?php

namespace App\Http\Controllers\Api\V1;

use App\Billing\Plans;
use App\Models\Tenant;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;

class StripeWebhookController extends CashierWebhookController
{
    /**
     * Handle customer.subscription.created — set tenant plan to pro/enterprise.
     */
    public function handleCustomerSubscriptionCreated(array $payload): void
    {
        $this->syncTenantPlan($payload['data']['object']);
    }

    /**
     * Handle customer.subscription.updated — sync plan on upgrade/downgrade.
     */
    public function handleCustomerSubscriptionUpdated(array $payload): void
    {
        $this->syncTenantPlan($payload['data']['object']);
    }

    /**
     * Handle customer.subscription.deleted — revert tenant to free plan.
     */
    public function handleCustomerSubscriptionDeleted(array $payload): void
    {
        $stripeCustomerId = $payload['data']['object']['customer'];

        $tenant = Tenant::where('stripe_customer_id', $stripeCustomerId)->first();

        $tenant?->update(['plan' => Plans::FREE]);
    }

    private function syncTenantPlan(array $subscription): void
    {
        $stripeCustomerId = $subscription['customer'];
        $stripeStatus     = $subscription['status'];

        $tenant = Tenant::where('stripe_customer_id', $stripeCustomerId)->first();

        if (! $tenant) {
            return;
        }

        // Only set paid plan when subscription is active or trialing
        if (in_array($stripeStatus, ['active', 'trialing'])) {
            $priceId = $subscription['items']['data'][0]['price']['id'] ?? null;

            $plan = match ($priceId) {
                env('STRIPE_PRICE_ENTERPRISE') => Plans::ENTERPRISE,
                env('STRIPE_PRICE_PRO')        => Plans::PRO,
                default                        => Plans::FREE,
            };

            $tenant->update(['plan' => $plan]);
        } else {
            $tenant->update(['plan' => Plans::FREE]);
        }
    }
}
