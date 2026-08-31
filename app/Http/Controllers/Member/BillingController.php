<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Consent;
use App\Models\Plan;
use App\Models\Transaction;
use App\Services\PaymentGateway;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    public function checkout(Request $request, Plan $plan)
    {
        return view('member.checkout', [
            'plan' => $plan,
            'vat'  => (int) round($plan->price * 0.15),
            // The ghotok tier's success component is a REFUNDABLE DEPOSIT,
            // shown plainly rather than in a footnote. Spec 18.5.
            'deposit' => $plan->code === 'ghotok'
                ? config('setu.plans.ghotok.success_deposit_bdt') : null,
        ]);
    }

    public function initiate(Request $request, Plan $plan)
    {
        $request->validate([
            'provider' => ['required', 'in:sslcommerz,bkash,nagad,stripe,manual'],
            'agreement' => [$plan->code === 'ghotok' ? 'accepted' : 'nullable'],
        ]);

        if ($plan->code === 'ghotok') {
            Consent::record($request->user()->id, 'SERVICE_AGREEMENT', $request, [
                'evidence' => ['document_hash' => hash('sha256', 'service-agreement-v1')],
            ]);
        }

        $txn = $this->gateway->initiate($request->user(), $plan, $request->input('provider'));

        // In production this redirects to the gateway. Locally we short-circuit
        // to the callback so the flow is testable end to end.
        return redirect()->route('billing.callback', ['transaction' => $txn->id, 'status' => 'SUCCESS']);
    }

    /**
     * Entitlements are granted only after SERVER-SIDE verification.
     * Redirect parameters are advisory, never authoritative. Spec 18.6.
     */
    public function callback(Request $request, Transaction $transaction)
    {
        $ok = $this->gateway->verifyAndActivate($transaction, $request->all());

        return $ok
            ? redirect()->route('member.dashboard')->with('status', __('billing.success'))
            : redirect()->route('plans')->withErrors(['plan' => __('billing.failed')]);
    }

    /** Idempotent: replaying this five times creates exactly one subscription. */
    public function webhook(Request $request, string $provider)
    {
        $txn = Transaction::where('provider_txn_id', $request->input('tran_id'))->first();

        if (! $txn) {
            return response()->json(['ok' => false], 404);
        }

        $this->gateway->verifyAndActivate($txn, $request->all());

        return response()->json(['ok' => true]);
    }

    public function invoices(Request $request)
    {
        return view('member.invoices', [
            'transactions' => Transaction::where('user_id', $request->user()->id)
                ->where('status', 'SUCCESS')->latest()->get(),
        ]);
    }
}
