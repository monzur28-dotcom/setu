<?php

namespace App\Http\Middleware;

use App\Models\GuardianLink;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGuardian
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user, 403);

        $link = GuardianLink::where('guardian_user_id', $user->id)
            ->where('link_status', 'ACTIVE')
            ->whereNull('revoked_at')
            ->first();

        // A revoked link is indistinguishable from one that never existed.
        // The guardian is told only "access ended". Spec 12.2 G7/G8.
        abort_unless($link, 403, __('family.access_ended'));

        $request->attributes->set('guardian_link', $link);

        return $next($request);
    }
}
