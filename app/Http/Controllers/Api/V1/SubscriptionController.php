<?php

namespace App\Http\Controllers\Api\V1;

use App\Billing\Plans;
use App\Http\Controllers\Controller;
use App\Services\UsageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(private readonly UsageService $usage) {}

    /**
     * Current plan, subscription status, and usage summary.
     */
    public function index(Request $request): JsonResponse
    {
        $tenant       = $request->user()->tenant;
        $subscription = $tenant->subscription('default');

        return response()->json([
            'data' => [
                'plan'         => $tenant->plan ?? Plans::FREE,
                'subscription' => $subscription ? [
                    'stripe_status' => $subscription->stripe_status,
                    'ends_at'       => $subscription->ends_at?->toISOString(),
                    'on_trial'      => $subscription->onTrial(),
                    'cancelled'     => $subscription->cancelled(),
                ] : null,
                'usage'        => $this->usage->summary($tenant),
                'plans'        => [
                    Plans::FREE       => Plans::limits(Plans::FREE),
                    Plans::PRO        => Plans::limits(Plans::PRO),
                    Plans::ENTERPRISE => Plans::limits(Plans::ENTERPRISE),
                ],
            ],
        ]);
    }

    /**
     * Create a Stripe Checkout session for upgrading to a paid plan.
     */
    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'plan' => ['required', 'string', 'in:pro,enterprise'],
        ]);

        $tenant  = $request->user()->tenant;
        $priceId = Plans::stripePriceId($request->plan);

        if (! $priceId) {
            return response()->json(['message' => 'Stripe price ID not configured for this plan.'], 422);
        }

        $session = $tenant->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => config('app.url') . '/billing/success',
                'cancel_url'  => config('app.url') . '/billing/cancel',
            ]);

        return response()->json(['data' => ['checkout_url' => $session->url]]);
    }

    /**
     * Create a Stripe Billing Portal session for managing an existing subscription.
     */
    public function portal(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $portalSession = $tenant->billingPortalSession([
            'return_url' => config('app.url') . '/billing',
        ]);

        return response()->json(['data' => ['portal_url' => $portalSession->url]]);
    }

    /**
     * Cancel the current subscription at period end.
     */
    public function cancel(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $tenant->subscription('default')?->cancel();

        return response()->json(['message' => 'Subscription will be cancelled at the end of the billing period.']);
    }
}
