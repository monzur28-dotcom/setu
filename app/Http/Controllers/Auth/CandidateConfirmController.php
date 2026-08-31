<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Consent;
use App\Models\User;
use Illuminate\Http\Request;

class CandidateConfirmController extends Controller
{
    public function show(string $token)
    {
        $userId = cache('candidate_confirm:'.$token);
        abort_unless($userId, 404);

        return view('auth.candidate-confirm', ['user' => User::findOrFail($userId), 'token' => $token]);
    }

    /**
     * ONLY this endpoint may set candidate_confirmed_at. Nothing else in the
     * application writes that column. Spec 22.2.
     */
    public function confirm(Request $request, string $token)
    {
        $userId = cache('candidate_confirm:'.$token);
        abort_unless($userId, 404);

        $user = User::findOrFail($userId);
        $user->update(['candidate_confirmed_at' => now()]);

        Consent::record($user->id, 'CANDIDATE_CONFIRMATION', $request);
        AuditLog::write($user, 'candidate_confirmed', ['entity_type' => 'user', 'entity_id' => $user->id]);

        cache()->forget('candidate_confirm:'.$token);

        return view('auth.candidate-confirmed', ['user' => $user]);
    }

    /** The candidate may delete the profile outright. No reason required. */
    public function reject(string $token)
    {
        $userId = cache('candidate_confirm:'.$token);
        abort_unless($userId, 404);

        $user = User::findOrFail($userId);
        $user->update(['status' => 'CLOSED']);
        $user->profile?->delete();

        cache()->forget('candidate_confirm:'.$token);

        return view('auth.candidate-rejected');
    }
}
