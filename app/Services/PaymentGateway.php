<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * One interface behind every rail: SSLCOMMERZ, direct bKash/Nagad, and Stripe
 * for the diaspora. Swapping a provider must never touch business logic.
 *
 * Entitlements are granted only after a SERVER-SIDE verification. Redirect
 * parameters are advisory, never authoritative. Spec 18.6.
 */
class PaymentGateway
{
    public function initiate(User $user, Plan $plan, string $provider): Transaction
    {
        $vat = (int) round($plan->price * 0.15);

        return Transaction::create([
            'user_id'              => $user->id,
            'plan_id'              => $plan->id,
            'provider'             => $provider,
            'provider_txn_id'      => strtoupper($provider).'-'.Str::random(20),
            'amount'               => $plan->price + $vat,
            'vat_amount'           => $vat,
            'currency'             => $plan->currency,
            'status'               => 'INITIATED',
            // A line item naming a dating product on a shared household
            // statement is a real exposure risk. Spec 18.6.
            'statement_descriptor' => 'SETU MEMBERSHIP',
        ]);
    }

    /**
     * Called from the callback AND from the webhook. Idempotent by design:
     * replaying a webhook five times must create exactly one subscription.
     */
    public function verifyAndActivate(Transaction $txn, array $providerPayload = []): bool
    {
        if ($txn->status === 'SUCCESS') {
            return true;   // already processed — do not double-credit
        }

        if (! $this->verifyWithProvider($txn, $providerPayload)) {
            $txn->update(['status' => 'FAILED', 'raw_payload' => $providerPayload]);

            return false;
        }

        $txn->update([
            'status'      => 'SUCCESS',
            'verified_at' => now(),
            'raw_payload' => $providerPayload,
        ]);

        $plan = $txn->plan_id ? Plan::find($txn->plan_id) : null;

        if ($plan) {
            \App\Models\Subscription::create([
                'user_id'        => $txn->user_id,
                'plan_id'        => $plan->id,
                'product'        => $plan->product,
                'starts_at'      => now(),
                'ends_at'        => now()->addDays($plan->duration_days ?? 30),
                'status'         => 'ACTIVE',
                'transaction_id' => $txn->id,
            ]);
        }

        return true;
    }

    /**
     * Replace with a real server-to-server call per provider. Deliberately
     * a single seam so nobody is tempted to trust the browser.
     */
    private function verifyWithProvider(Transaction $txn, array $payload): bool
    {
        return match ($txn->provider) {
            'sslcommerz' => app()->environment('local') ? true : $this->verifySslcommerz($txn, $payload),
            'bkash'      => app()->environment('local') ? true : $this->verifyBkash($txn, $payload),
            'stripe'     => app()->environment('local') ? true : $this->verifyStripe($txn, $payload),
            default      => app()->environment('local'),
        };
    }

    private function verifySslcommerz(Transaction $t, array $p): bool { /* TODO: validation API */ return false; }
    private function verifyBkash(Transaction $t, array $p): bool      { /* TODO: execute API   */ return false; }
    private function verifyStripe(Transaction $t, array $p): bool     { /* TODO: retrieve PI   */ return false; }
}
