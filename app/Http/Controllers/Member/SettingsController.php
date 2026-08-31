<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        return view('member.settings', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'locale'   => ['nullable', 'in:bn,en'],
            'currency' => ['nullable', 'in:BDT,USD,GBP,CAD,AED'],
            'timezone' => ['nullable', 'string', 'max:64'],
        ]);

        $request->user()->update(array_filter($data));

        if (! empty($data['locale'])) {
            session(['locale' => $data['locale']]);
        }

        return back()->with('status', __('settings.saved'));
    }

    /** Pause is a first-class feature, not an off switch. Spec 6.13. */
    public function pause(Request $request)
    {
        $request->user()->update(['status' => 'PAUSED']);
        AuditLog::write($request->user(), 'profile_paused');

        return redirect()->route('member.dashboard')->with('status', __('settings.paused'));
    }

    public function resume(Request $request)
    {
        $request->user()->update(['status' => 'ACTIVE']);

        return back()->with('status', __('settings.resumed'));
    }

    public function destroy(Request $request)
    {
        $user = $request->user();
        $user->update(['status' => 'CLOSED', 'public_indexing' => 'NOINDEX']);
        $user->profile?->delete();

        AuditLog::write($user, 'account_deletion_requested');

        // A 30-day grace period, then an irreversible PII purge, plus an
        // active de-index request rather than waiting for a recrawl.
        auth()->logout();

        return redirect()->route('home')->with('status', __('settings.deleted'));
    }
}
