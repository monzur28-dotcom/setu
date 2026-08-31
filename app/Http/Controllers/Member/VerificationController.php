<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\ModerationItem;
use App\Models\Verification;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function index(Request $request)
    {
        return view('member.verification', [
            'verifications' => $request->user()->verifications()->latest()->get(),
        ]);
    }

    /**
     * Verification is FREE. Only speed is an ancillary product — never
     * charge for trust itself. Spec 17.1.
     */
    public function store(Request $request)
    {
        $request->validate([
            'type'     => ['required', 'in:NID,PASSPORT,SELFIE'],
            'document' => ['required', 'file', 'max:8192'],
            'last4'    => ['nullable', 'string', 'max:8'],
        ]);

        $path = $request->file('document')->store('', 'kyc');

        $v = Verification::create([
            'user_id'        => $request->user()->id,
            'type'           => $request->input('type'),
            'document_path'  => $path,
            'document_last4' => $request->input('last4'),
            'document_hash'  => hash_file('sha256', $request->file('document')->getRealPath()),
            'status'         => 'PENDING',
            // Deleted 30 days after the decision. Only the decision, the last
            // four characters and the hash survive. Spec 17.1.
            'purge_after'    => now()->addDays(config('setu.retention.kyc_document_days')),
        ]);

        ModerationItem::create([
            'entity_type' => 'PROFILE', 'entity_id' => $v->id,
            'mode' => 'MATRIMONIAL', 'priority' => 2,
        ]);

        return back()->with('status', __('verify.submitted'));
    }
}
